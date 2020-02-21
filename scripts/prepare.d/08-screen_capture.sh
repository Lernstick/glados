#!/bin/bash
#
# Setup for screen_capture
#

function screen_capture()
{
  # config->screen_capture
  if [ "$(config_value "screen_capture")" = "True" ]; then

    # config->screen_capture_chunk
    if [ "$(config_value "screen_capture_chunk")" = "" ]; then
      sc_chunk="30"
    else
      sc_chunk="$(config_value "screen_capture_chunk")"
    fi

    # config->screen_capture_command
    if [ "$(config_value "screen_capture_command")" = "" ]; then
      sc_command=""
    else
      v="$(config_value "screen_capture_command")"
      sc_command="${v@Q}"
    fi

    sc_path="/home/user/Schreibtisch/"

    if [ "${sc_command}" != "" ]; then

      #RuntimeMaxSec=${sc_chunk}

      # create screen_capture systemd daemon
      cat <<EOF >"${initrd}/newroot/etc/systemd/system/screen_capture.service"
[Unit]
Description=screen_capture

[Service]
Type=simple
Environment=DISPLAY=:0 XAUTHORITY=/run/user/1000/gdm/Xauthority 
ExecStart=/bin/bash -c "${sc_command} ${sc_path}/video.m3u8"
Restart=always

[Install]
WantedBy=graphical.target
EOF
    fi

    chroot ${initrd}/newroot systemctl enable screen_capture.service

  fi

}
