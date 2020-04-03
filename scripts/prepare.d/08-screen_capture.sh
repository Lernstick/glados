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
    path="${c:-"/home/user/Schreibtisch/out"}"

    command="$(config_value "screen_capture_command")"
    output="${path}/video.m3u8"

    if [ "${command}" != "" ]; then

      #RuntimeMaxSec=${chunk}

    # create screen_capture script
    cat <<EOF >"${initrd}/newroot/usr/bin/screen_capture"
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

mkdir -p "${path}"
${command}

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
