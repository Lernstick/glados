#!/bin/bash
#
# Setup for live_overview
#

function live_overview()
{
    # create live_overview script
    cat <<EOF1 >"${initrd}/newroot/usr/bin/live_overview"
#!/bin/bash

# source the info file
. /info

# get DISPLAY and XAUTHORITY env vars
set -o allexport
. <(strings /proc/*/environ 2>/dev/null | awk -F= '\$1=="DISPLAY"||\$1=="XAUTHORITY"' | head -2) 
set +o allexport
resolution="\$(xdpyinfo | awk '\$1=="dimensions:"{print \$2}')"
interval="1"
width="260"
url=${urlLive:-${urlDownload/download/live}}
duration="60" #seconds

ffmpeg -f x11grab -framerate "\${interval}" -video_size "\${resolution}" -i :0.0 -q:v 10 -vf "scale='\${width}':-2" -strftime 1 -loglevel level+verbose -t "\${duration}" -f image2 "\${url}"
EOF1

    chmod 755 "${initrd}/newroot/usr/bin/live_overview"

    # create screen_capture systemd daemon
    cat <<EOF2 >"${initrd}/newroot/etc/systemd/system/live_overview.service"
[Unit]
Description=live_overview

[Service]
Type=simple
ExecStart=/usr/bin/live_overview

[Install]
WantedBy=graphical.target
EOF2

}
