#!/bin/bash
#
# Setup for keylogger
#

function keylogger()
{
  # config->keylogger
  if [ "$(config_value "keylogger")" = "True" ]; then

    >&2 echo "enabling keylogger"

    c="$(config_value "keylogger_path")"
    path="${c:-"/home/user/ScreenCapture"}"

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
