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
      sc_command="${v}"
    fi

    sc_path="/home/user/Schreibtisch/out"
    output="${sc_path}/video.m3u8"

    if [ "${sc_command}" != "" ]; then

      #RuntimeMaxSec=${sc_chunk}

    # create screen_capture script
    cat <<EOF >"${initrd}/newroot/usr/bin/screen_capture"
#!/bin/bash

# get DISPLAY and XAUTHORITY env vars
set -o allexport
. <(strings /proc/*/environ | awk -F= '\$1=="DISPLAY"||\$1=="XAUTHORITY"' | head -2) 
set +o allexport
resolution="\$(xdpyinfo | awk '\$1=="dimensions:"{print \$2}')"
mkdir -p "${sc_path}"
output="${output}"

${sc_command}
EOF

      # create screen_capture systemd daemon
      cat <<EOF >"${initrd}/newroot/etc/systemd/system/screen_capture.service"
[Unit]
Description=screen_capture

[Service]
Type=simple
ExecStart=/usr/bin/screen_capture
Restart=always
RestartSec=10

[Install]
WantedBy=graphical.target
EOF
    fi

    chmod 755 "${initrd}/newroot/usr/bin/screen_capture"
    chroot ${initrd}/newroot systemctl enable screen_capture.service

  fi

}
