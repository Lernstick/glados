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
gop="\$(bc <<< "\${chunk}*\${fps}")"
mkdir -p "${path}"
date="\$(date +%s)"
master="master\${date}.m3u8"
playlist="video\${date}.m3u8"

echo "[screen_capture] [info] Starting screen capturing..."
echo "[screen_capture] [info] calling ${command}"
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
ExecStop=/bin/bash -c 'kill \$1; tail --pid=\$1 -f /dev/null' sh \$MAINPID
ExecStopPost=/usr/bin/launch screen_capture
Restart=always
RestartSec=10
StandardOutput=syslog
StandardError=syslog
SyslogIdentifier=screen_capture

[Install]
WantedBy=graphical.target
EOF2

    chroot ${initrd}/newroot mkdir "${path}"
    chroot ${initrd}/newroot systemctl enable screen_capture.service

    # set up rsyslog 
    cat <<EOF_RSYSLOG >"${initrd}/newroot/etc/rsyslog.d/screen_capture.conf"
:programname, isequal, "screen_capture" /var/log/screen_capture.log
EOF_RSYSLOG

    chroot ${initrd}/newroot ln -s /var/log/screen_capture.log "${path}"/screen_capture.log

    # root:adm
    chown 0:4 "${initrd}/newroot/etc/rsyslog.d/screen_capture.conf"

    # setup the launch timer
    cat <<EOF3 >>"${initrd}/newroot/etc/launch.conf"
# screen_capture
name+=("screen_capture")
threshold+=("${threshold}")
path+=("${path}")
hardlink+=("@(*.m3u8|*.log)")
move+=("*.ts")
remove+=("*.ts")
log+=("screen_capture.log")
chunk+=("${chunk}")

EOF3



    fi
  fi
}
