#!/bin/bash
#
# Setup for screen_capture
#

function screen_capture()
{
  # config->screen_capture
  if [ "$(config_value "screen_capture")" = "True" ]; then

    c="$(config_value "screen_capture_chunk")"
    chunk="${c:-"10"}"

    c="$(config_value "screen_capture_bitrate")"
    bitrate="${c:-"300k"}"

    c="$(config_value "screen_capture_fps")"
    fps="${c:-"10"}"

    c="$(config_value "screen_capture_overflow_threshold")"
    threshold="${c:-"500m"}"

    c="$(config_value "screen_capture_path")"
    path="${c:-"/home/user/ScreenCapture"}"

    command="$(config_value "screen_capture_command")"
    output="${path}/video.m3u8"

    if [ "${command}" != "" ]; then

      #RuntimeMaxSec=${chunk}

    # create screen_capture script
    cat <<EOF1 >"${initrd}/newroot/usr/bin/screen_capture"
#!/bin/bash

# get DISPLAY and XAUTHORITY env vars
set -o allexport
. <(strings /proc/*/environ 2>/dev/null | awk -F= '\$1=="DISPLAY"||\$1=="XAUTHORITY"' | head -2) 
set +o allexport
resolution="\$(xdpyinfo | awk '\$1=="dimensions:"{print \$2}')"
path="${path}"
chunk="${chunk}"
bitrate="${bitrate}"
fps="${fps}"
gop="\$((chunk*fps))"

mkdir -p "${path}"
${command}
EOF1

    chmod 755 "${initrd}/newroot/usr/bin/screen_capture"

    # create screen_capture systemd daemon
    cat <<EOF2 >"${initrd}/newroot/etc/systemd/system/screen_capture.service"
[Unit]
Description=screen_capture

[Service]
Type=simple
WorkingDirectory=${path}
ExecStart=/usr/bin/screen_capture
Restart=always
RestartSec=10
StandardOutput=append:${path}/screen_capture.log
StandardError=append:${path}/screen_capture.log

[Install]
WantedBy=graphical.target
EOF2

    chroot ${initrd}/newroot mkdir "${path}"
    chroot ${initrd}/newroot systemctl enable screen_capture.service

    # create screen_capture_launch script
    cat <<EOF3 >"${initrd}/newroot/usr/bin/screen_capture_launch"
#!/bin/bash

threshold="${threshold}"
path="${path}"
launch="\${path}/launch"
mkdir -p "\${launch}"

# hard link the m3u8 and log files into the launch directory
cp -vl "\${path}/"*.{m3u8,log} "\${launch}/"

# move all but the two newest files matching *.ts to the launch directory
LANG=C stat -c '%Y %N' "\${path}"/*.ts | \\
    sort -nk1 | head -n -2 | cut -d ' ' -f2- | \\
    xargs -I {} sh -c "mv -v '{}' '\${launch}/';"

# get total physical RAM in bytes
ram="\$(awk '\$1~/MemTotal/{printf "%.0f",\$2*1024}' /proc/meminfo)"
if [ "\${threshold}" != "0%" ] || [ "\${threshold}" != "0m" ]; then
    cur="\$(du -cb "\${launch}/"*.ts | tail -1 | cut -f1)"
    [[ "\${threshold}" == *m ]] && threshold=\$((\${threshold%?}*1042*1024))
    [[ "\${threshold}" == *% ]] && threshold=\$((\${threshold%?}*\${ram}/100))

    if [ "\${cur}" -gt "\${threshold}" ]; then
        echo "[screen_capture_launch] [fatal] overflow threshold exceeded: removing files" | tee -a "\${path}/screen_capture.log"
        oldest="\$(ls -t1 "\${launch}"/*.ts | tail -1)"
        i=0
        while [ "\${cur}" -gt "\${threshold}" ] && [ -n "\${oldest}" ] && [ "\$i" -lt 10 ]; do
            rm -vf "\${oldest}" 2>&1 | tee -a "\${path}/screen_capture.log"
            oldest="\$(ls -t1 "\${launch}"/*.ts | tail -1)"
            cur="\$(du -cb "\${launch}/"*.ts | tail -1 | cut -f1)"
            i=\$((\$i + 1))
        done
    fi
fi

EOF3

    chmod 755 "${initrd}/newroot/usr/bin/screen_capture_launch"

    # create screen_capture_launch systemd service file
    cat <<'EOF4' >"${initrd}/newroot/etc/systemd/system/screen_capture_launch.service"
[Unit]
Description=screen_capture_launch

[Service]
Type=oneshot
ExecStart=/usr/bin/screen_capture_launch
EOF4

    # create screen_capture_launch systemd timer file
    cat <<'EOF5' >"${initrd}/newroot/etc/systemd/system/screen_capture_launch.timer"
[Unit]
Description=screen_capture_launch timer

[Timer]
OnUnitInactiveSec=10s
OnBootSec=10s

[Install]
WantedBy=timers.target
EOF5

    # activate the timer unit
    chroot ${initrd}/newroot ln -s /etc/systemd/system/screen_capture_launch.timer /etc/systemd/system/timers.target.wants/screen_capture_launch.timer

    fi

  fi

}
