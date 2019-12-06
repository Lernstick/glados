#!/bin/bash
#
# Install the /usr/bin/max_brightness script with the given value from 0 to 100
#

function max_brightness()
{
  value="$1"
  if [ "${value}" != "100" ] && [ "${value}" != "" ]; then

    # create max_brightness systemd daemon
    cat <<EOF >"${initrd}/newroot/etc/systemd/system/max_brightness.service"
[Unit]
Description=max_brightness

[Service]
Environment=max=${value}
ExecStart=/bin/bash -c "/usr/bin/max_brightness \${max}"

[Install]
WantedBy=multi-user.target
EOF

    # create max_brightness script
    cat <<'EOF' >"${initrd}/newroot/usr/bin/max_brightness"
#!/bin/bash

ratio=${1-100}
exist=0
[ -n "$(ls /sys/class/backlight/ 2>/dev/null)" ] && exist=1

if [ "${exist}" = "1" ] && [ "${ratio}" -ne "100" ] ; then

  for backlight in /sys/class/backlight/*; do
    if [ -e "$backlight/brightness" ] && [ -e "$backlight/actual_brightness" ] && [ -e "$backlight/max_brightness" ]; then
      list="${list} ${backlight}/brightness"
      init="${init}${backlight}/brightness MODIFY\n"
    fi
  done

  ( echo -en "$init" && ( which inotifywait && notifywait -m -e MODIFY ${list} || while true; do sleep 1; echo -en "$init"; done ) ) | while read backlight action; do
    backlight=${backlight%/brightness}
    max=$(cat ${backlight}/max_brightness)
    brightness=$(printf "%.0f\n" $(echo "scale=2; (${ratio}/100)*${max}" | bc))
    mom=$(printf "%.0f\n" $(echo "scale=2; $(cat ${backlight}/actual_brightness)/(${max}/100)" | bc))

    if [ "${mom}" -gt "${ratio}" ]; then
      echo ${brightness} >${backlight}/brightness
    fi
  done

else
  echo "No backlight found or ratio=100. Nothing to do, sleeping."
  while true; do sleep 10000; done
fi
EOF

    chmod 755 "${initrd}/newroot/usr/bin/max_brightness"
    chroot ${initrd}/newroot systemctl enable max_brightness.service
  fi
}
