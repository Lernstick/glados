#!/bin/bash
#
# Setup for screen_capture
#

function launch()
{
  # config->screen_capture or config->keylogger
  if [ "$(config_value "screen_capture")" = "True" ] || [ "$(config_value "keylogger")" = "True" ]; then

    # create launch script
    cat <<'EOF3' >"${initrd}/newroot/usr/bin/launch"
#!/bin/bash

. /etc/launch.conf

for key in "${!path[@]}"; do
    t="${threshold[$key]}";
    p="${path[$key]}";
    h="${hardlink[$key]}";
    m="${move[$key]}";
    r="${remove[$key]}";
    l="${log[$key]}";

    launch="${p}/launch"
    mkdir -p "${launch}"

    # hard link the hardlink glob files into the launch directory
    if [ -n "$h" ]; then 
        cp -vl $(eval echo "${p}/"$h) "${launch}/"
    fi

    # move all but the two newest files matching the move glob to the launch directory
    if [ -n "$m" ]; then 
        LANG=C stat -c '%Y %N' $(eval echo "${p}"/$m) | \
            sort -nk1 | head -n -2 | cut -d ' ' -f2- | \
            xargs -I {} sh -c "mv -v '{}' '${launch}/';"
    fi


    # get total drive space in bytes
    space="$(df --block-size=1 --output=size "${p}" | tail -1)"
    if [ "${t}" != "0%" ] && [ "${t}" != "0m" ] && [ -n "$r" ]; then
        cur="$(du -cb $(eval echo "${launch}/"$r) | tail -1 | cut -f1)"
        # calculate the threshold in bytes
        [[ "${t}" == *m ]] && t=$((${t%?}*1042*1024))
        [[ "${t}" == *% ]] && t=$((${t%?}*${space}/100))

        # remove files if thresold is exceeded
        if [ "${cur}" -gt "${t}" ]; then
            echo "[launch] [fatal] overflow threshold of ${t} bytes exceeded: removing files"
            if [ -n "$l" ]; then 
                echo "[launch] [fatal] overflow threshold of ${t} bytes exceeded: removing files" >> "$p/$l"
            fi
            oldest="$(ls -t1 $(eval echo "${launch}"/$r) | tail -1)"
            i=0
            while [ "${cur}" -gt "${t}" ] && [ -n "${oldest}" ] && [ "$i" -lt 10 ]; do
                if [ -n "$l" ]; then
                    rm -vf "${oldest}" 2>&1 | tee -a "$p/$l"
                else
                    rm -vf "${oldest}" 2>&1
                fi
                oldest="$(ls -t1 $(eval echo "${launch}"/$r) | tail -1)"
                cur="$(du -cb $(eval echo "${launch}/"$r) | tail -1 | cut -f1)"
                i=$(($i + 1))
            done
        fi
    fi
done

EOF3

    chmod 755 "${initrd}/newroot/usr/bin/launch"

    # create launch systemd service file
    cat <<'EOF4' >"${initrd}/newroot/etc/systemd/system/launch.service"
[Unit]
Description=launch

[Service]
Type=oneshot
ExecStart=/usr/bin/launch
StandardOutput=syslog
StandardError=syslog
SyslogIdentifier=screen_capture
EOF4

    # create launch systemd timer file
    cat <<'EOF5' >"${initrd}/newroot/etc/systemd/system/launch.timer"
[Unit]
Description=launch timer

[Timer]
OnUnitInactiveSec=10s
OnBootSec=10s

[Install]
WantedBy=timers.target
EOF5

    # activate the timer unit
    chroot ${initrd}/newroot ln -s /etc/systemd/system/launch.timer /etc/systemd/system/timers.target.wants/launch.timer

  fi

}
