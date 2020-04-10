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
. <(strings /proc/*/environ | awk -F= '\$1=="DISPLAY"||\$1=="XAUTHORITY"' | head -2) 
set +o allexport
resolution="\$(xdpyinfo | awk '\$1=="dimensions:"{print \$2}')"
path="${path}"
chunk="${chunk}"
bitrate="${bitrate}"
fps="${fps}"
gop="\$((chunk*fps))"
date="\$(LC_ALL=C date --iso-8601=seconds | sed 's/:/\\\\:/g')" # ex: 2020-04-10T11\:44\:18+02\:00

# setup logfile
export FFREPORT="file=\${path}/ffreport-\${date}.log:level=32"

mkdir -p "${path}"
${command}
EOF1

    chmod 755 "${initrd}/newroot/usr/bin/screen_capture"

    # create screen_capture systemd daemon
    cat <<'EOF2' >"${initrd}/newroot/etc/systemd/system/screen_capture.service"
[Unit]
Description=screen_capture

[Service]
Type=simple
ExecStart=/usr/bin/screen_capture
Restart=always
RestartSec=10

[Install]
WantedBy=graphical.target
EOF2

    chroot ${initrd}/newroot systemctl enable screen_capture.service

    # create screen_capture_launch script
    cat <<EOF3 >"${initrd}/newroot/usr/bin/screen_capture_launch"
#!/bin/bash

path="${path}"
launch="\${path}/launch"
mkdir -p "\${launch}"

# hard link the m3u8 and log files into the launch directory
cp -vl "\${path}/"*.{m3u8,log} "\${launch}/"

# move all but the two newest files matching *.ts to the launch directory
LANG=C stat -c '%Y %N' "\${path}"/*.ts | \\
    sort -nk1 | head -n -2 | cut -d ' ' -f2- | \\
    xargs -I {} sh -c "mv -v {} '\${launch}/';"
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
    #chroot ${initrd}/newroot systemctl start screen_capture_launch.timer

    fi

  fi

}
