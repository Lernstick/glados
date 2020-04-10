#!/bin/bash

DEBUG=true
wget="/usr/bin/wget"
wgetOptions="--dns-timeout=30"
timeout=10
zenity="/usr/bin/zenity"
initrd="/run/initramfs"
infoFile="${initrd}/info"
mountFile="${initrd}/mount"
python="/usr/bin/python"
examUser="user"
desktop="$(sudo -u ${examUser} xdg-user-dir DESKTOP)"
home="$(sudo -u ${examUser} xdg-user-dir)"

# source os-release
. /etc/os-release

# determines whether we have debian 9 or newer
function isdeb9ornewer()
{
  if [ "$(echo "${VERSION_ID}"| egrep -q "^[0-9]+$")" != "" ] && [ ${VERSION_ID} -le 8 ]; then
    false
  else
    true
  fi
}

# transmit state to server
function clientState()
{
  $DEBUG && \
    ${wget} ${wgetOptions} -qO- "${urlNotify//\{state\}/$1}" 1>&2 || \
    ${wget} ${wgetOptions} -qO- "${urlNotify//\{state\}/$1}" 2>&1 >/dev/null
  $DEBUG && >&2 echo "New client state: $1"
}

function config_value()
{
  if [ -n "${config}" ]; then
    config="$(${wget} ${wgetOptions} -qO- "${urlConfig}")"
    retval=$?
    if [ ${retval} -ne 0 ]; then
      >&2 echo "wget failed while fetching the system config (return value: ${retval})."
      ${zenity} --error --title "Wget error" --text "wget failed while fetching the system config (return value: ${retval})."
      do_exit
    fi
  fi

  v="$(echo "${config}" | ${python} -c 'import sys, json; print json.load(sys.stdin)["config"]["'${1}'"]')"
  $DEBUG && >&2 echo "${1} is set to ${v}"
  echo "$v"
}

function do_exit()
{
  $DEBUG && >&2 echo "exiting cleanly"

  # revert all changes to iptables
  #iptables-save | grep -v "searchExamServer" | iptables-restore

  # unmount the filesystem
  umount ${initrd}/newroot
  umount -l ${initrd}/{base,exam,tmpfs}
  exit
}
trap do_exit EXIT

# get DISPLAY and XAUTHORITY env vars to display the firefox window
set -o allexport
. <(strings /proc/$(pgrep firefox)/environ | awk -F= '$1=="DISPLAY"||$1=="XAUTHORITY"') 
set +o allexport

echo 0 > ${initrd}/restore

token=$1
[ -r "${infoFile}" ] && . ${infoFile}
echo "token=${token}" >> "${infoFile}"

# replace the placeholder {token} in the URLs
urlDownload="${actionDownload//\{token\}/$token}"
urlFinish="${actionFinish//\{token\}/$token}"
urlNotify="${actionNotify//\{token\}/$token}"
urlMd5="${actionMd5//\{token\}/$token}"
urlConfig="${actionConfig//\{token\}/$token}"

# write the info file
cat <<EOF >>"${infoFile}"
urlDownload="${urlDownload}"
urlFinish="${urlFinish}"
urlNotify="${urlNotify}"
urlMd5="${urlMd5}"
urlConfig="${urlConfig}"
EOF

# create necessary directory structure
mkdir -p "${initrd}/"{backup,base,newroot,squashfs,exam,tmpfs}
mkdir -p "${initrd}/backup/etc/NetworkManager/"{system-connections,dispatcher.d}
mkdir -p "${initrd}/backup/${desktop}/"
mkdir -p "${initrd}/backup/usr/bin/"
mkdir -p "${initrd}/backup/usr/sbin/"
mkdir -p "${initrd}/backup/etc/live/config/"
mkdir -p "${initrd}/backup/etc/lernstick-firewall/"
mkdir -p "${initrd}/backup/etc/avahi/"
mkdir -p "${initrd}/backup/root/.ssh"
mkdir -p "${initrd}/backup/usr/share/applications"

# set proper permissions
chown user:user "${initrd}/backup/${desktop}/"
chown user:user "${initrd}/backup/${home}/"
chmod 755 "${initrd}/backup/root"
chmod 700 "${initrd}/backup/root/.ssh"

# get all active network connections
con=$(LC_ALL=C nmcli -t -f state,connection d status | awk -F: '$1=="connected"{print $2}')
while IFS= read -r c; do
  # set the autoconnect priority to 999
  LC_ALL=C nmcli connection modify "${c}" connection.autoconnect-priority 999
  # use both names, the old one and the new one for backwards compatibility
  echo "${c}"
  echo "${c}.nmconnection"
done <<< "${con}" | LC_ALL=C xargs -I{} cp -p "/etc/NetworkManager/system-connections/{}" "${initrd}/backup/etc/NetworkManager/system-connections/"

# edit copied connections manually, because nmcli will remove the wifi-sec.psk password when edited by nmcli modify
#sed -i '/\[connection\]/a permissions=user:root:;' ${initrd}/backup/etc/NetworkManager/system-connections/*

# copy needed scripts and files
cp -p "/etc/NetworkManager/dispatcher.d/02searchExamServer" "${initrd}/backup/etc/NetworkManager/dispatcher.d/02searchExamServer"
cp -p "/usr/bin/finishExam" "${initrd}/backup/usr/bin/finishExam"

# those should be removed as fast as possible
cp -p "/usr/bin/lernstick_backup" "${initrd}/backup/usr/bin/lernstick_backup" #TODO: remove
cp -p "/usr/bin/lernstick_autostart" "${initrd}/backup/usr/bin/lernstick_autostart" #TODO: remove
cp -p "/usr/sbin/lernstick-firewall" "${initrd}/backup/usr/sbin/lernstick-firewall" #TODO: remove
cp -p "/etc/lernstick-firewall/lernstick-firewall.conf" "${initrd}/backup/etc/lernstick-firewall/lernstick-firewall.conf" #TODO: remove

# config of /etc/lernstickWelcome
cp -p "/etc/lernstickWelcome" "${initrd}/backup/etc/lernstickWelcome"
sed -i 's/ShowNotUsedInfo=.*/ShowNotUsedInfo=false/g' "${initrd}/backup/etc/lernstickWelcome"
sed -i 's/AutoStartInstaller=.*/AutoStartInstaller=false/g' "${initrd}/backup/etc/lernstickWelcome"
#echo "ShowExamInfo=true" >>"${initrd}/backup/etc/lernstickWelcome" #TODO: replace with sed
cp -p "/usr/share/applications/finish_exam.desktop" "${initrd}/backup/usr/share/applications/"
chmod 644 "${initrd}/backup/usr/share/applications/finish_exam.desktop"
chown user:user "${initrd}/backup/${desktop}/finish_exam.desktop" 2>/dev/null

# This is to fix an issue when the DNS name of the exam server ends in .local (which is the
# case in most Microsoft domain environments). In case of a .local name the mDNS policy in
# /etc/nsswitch.conf will catch. This ends in ssh login delays of up to 20 seconds. Changing it 
# to .alocal is a workaround. Better is not to use mDNS in an exam.
sed 's/#domain-name=local/domain-name=.alocal/' /etc/avahi/avahi-daemon.conf >${initrd}/backup/etc/avahi/avahi-daemon.conf

# mount/prepare the root filesystem
mount_rootfs "newroot"

DESTDIR="${initrd}"
verbose="y"

# create directories
for d in etc bin sbin proc ; do
  mkdir "$initrd/$d" || true 2>/dev/null
done

# copy busybox and dependencies to /run/initramfs
# Bug is filed: https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=953563
# see /usr/share/initramfs-tools/hooks/zz-busybox
BB_BIN=/bin/busybox

if [ -r /usr/share/initramfs-tools/hook-functions ]; then
  . /usr/share/initramfs-tools/hook-functions

  if [ -f $DESTDIR/bin/sh ] && cmp -s $DESTDIR/bin/sh $BB_BIN ; then
    # initramfs copies busybox into /bin/sh, undo this
    rm -f $DESTDIR/bin/sh
  fi
  rm -f $DESTDIR/bin/busybox      # for compatibility with old initramfs
  copy_exec $BB_BIN /bin/busybox

  # this line fixes the canonicalization problem: copy_file copies the binary
  # to /usr/bin instead of /bin, causing the rest of the script to fail, specially
  # the line that link the alias to busybox below
  ln "$DESTDIR/usr/bin/busybox" "$DESTDIR/bin/busybox"

  for alias in $($BB_BIN --list-long); do
    alias="${alias#/}"
    case "$alias" in
      # strip leading /usr, we don't use it
      usr/*) alias="${alias#usr/}" ;;
      */*) ;;
      *) alias="bin/$alias" ;;  # make it into /bin
    esac

    [ -e "$DESTDIR/$alias" ] || \
      ln "$DESTDIR/bin/busybox" "$DESTDIR/$alias"
  done

  # copy plymouth
  . /usr/share/initramfs-tools/hooks/plymouth

  # copy systemctl needed for final shutdown
  copy_exec /bin/systemctl

  # disable set -e again (set in /usr/share/initramfs-tools/hooks/plymouth)
  set +e

fi

# copy shutdown script, this script will be executed later by systemd-shutdown
cp -pf "${initrd}/squashfs/mount.sh" "/lib/systemd/lernstick-shutdown"
chmod 755 "/lib/systemd/lernstick-shutdown"

# We do this here, anyway the script /lib/systemd/system-shutdown/lernstick might
# also copy it. That's why in the above line the mount.sh script is also copied to
# /lib/systemd/lernstick-shutdown. The above 2 lines are for backward compatibility.
cp -pf "${initrd}/squashfs/mount.sh" "${initrd}/shutdown"
chmod 755 "${initrd}/shutdown"

# remove policykit action for lernstick welcome application
rm -f ${initrd}/newroot/usr/share/polkit-1/actions/ch.lernstick.welcome.policy

# add an entry to finish the exam in the dash
add_dash_entry "finish_exam.desktop"

# Welcome to exam .desktop entry to be executed at autostart
mkdir -p "${initrd}/newroot/etc/xdg/autostart/"
mkdir -p "${initrd}/newroot/usr/share/applications/"
cat <<EOF >"${initrd}/newroot/etc/xdg/autostart/show-info.desktop"
[Desktop Entry]
Type=Application
Encoding=UTF-8
Icon=/usr/share/icons/gnome/256x256/status/dialog-question.png
Version=1.0
Name=Welcome to the exam
Name[de]=Willkommen zur Prüfung
Exec=show_info
X-GNOME-Autostart-enabled=true
EOF

cp "${initrd}/newroot/etc/xdg/autostart/show-info.desktop" "${initrd}/newroot/usr/share/applications/"

url="${gladosProto}://${gladosIp}:${gladosPort}/glados/index.php/howto/welcome-to-exam.md?mode=inline"
cat <<EOF >"${initrd}/newroot/show_info.html"
<!DOCTYPE html>
<html lang='en-US'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <meta http-equiv='refresh' content='0;url=${url}' />
    </head>
    <body>
    Please wait, redirecting...
    </body>
</html>
EOF

cat <<'EOF' >"${initrd}/newroot/usr/bin/show_info"
#!/bin/bash

#/usr/bin/firefox -createprofile "showInfo /tmp/showInfo" -no-remote
mkdir -p /tmp/showInfo/chrome/

# Dirty hacky way to create a new firefox profile (only in firstrun)
if ! [ -e /tmp/showInfo/prefs.js ]; then
  timeout -s INT -k 8 4 \
    /usr/bin/firefox -profile "/tmp/showInfo/" -no-remote --screenshot i-dont-exist
  (
    cat - <<EOFINNER
/*
 * set default namespace to XUL
 */
@namespace url("http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul");
 
/*
 * Hide tab bar, navigation bar and scrollbars
 * !important may be added to force override, but not necessary
 * #content is not necessary to hide scroll bars
 */
#toolbar-context-menu {display: none !important;}
#TabsToolbar {visibility: collapse;}
#navigator-toolbox {visibility: collapse;}
browser {margin-right: -14px; margin-bottom: -14px;}
EOFINNER
  ) | tee /tmp/showInfo/chrome/userChrome.css >/dev/null

  # another hacky way to remove some firefox default settings at the first start
  echo 'user_pref("browser.tabs.warnOnClose", false);' >> /tmp/showInfo/prefs.js

fi

profile="$(mktemp -d)"
cp -a /tmp/showInfo/. "$profile"

/usr/bin/firefox -no-remote -profile "$profile" -width 850 -height 620 "/show_info.html"

# remove the profile - also remove it from the profiles.ini file
rm -r "$profile"
ex -e - /home/user/.mozilla/firefox/profiles.ini <<@@@
g/Name=showInfo/.-2,+2d
wq
@@@

EOF

chmod 755 "${initrd}/newroot/usr/bin/show_info"

# add an entry to show information about the exam in the dash
add_dash_entry "show-info.desktop"

###########################################
# apply specific exam config if available #
###########################################

if [ -n "${actionConfig}" ]; then
  # get the config
  config="$(${wget} ${wgetOptions} -qO- "${urlConfig}")"
  retval=$?
  if [ ${retval} -ne 0 ]; then
    >&2 echo "wget failed while fetching the system config (return value: ${retval})."
    ${zenity} --error --title "Wget error" --text "wget failed while fetching the system config (return value: ${retval})."
    do_exit
  fi

  # setup the expert settings
  expert_settings

  # setup screenshots in the given interval
  screenshots

  url_whitelist "$(config_value "url_whitelist")"

  # config->max_brightness
  max_brightness "$(config_value "max_brightness")"

  # set all libreoffice options
  libreoffice

  # set all libreoffice options
  screen_capture

  # fix the permissions
  chown -R user:user ${initrd}/newroot/${home}/.config

else

  $DEBUG && >&2 echo "no config available, setting default values"

  # these are the default values, if the exam server does not provide a config file and the
  # exam file has not configured them
  expert_settings_defaults

fi

# hand over the ssh key from the exam server
echo "${sshKey}" >>"${initrd}/backup/root/.ssh/authorized_keys"

# hand over open ports
echo "tcp ${gladosIp} 22" >>${initrd}/backup/etc/lernstick-firewall/net_whitelist_input

# hand over the url whitelist
if isdeb9ornewer; then
  echo "${gladosProto}://${gladosIp}" | sed 's/\./\\\./g' >>${initrd}/backup/etc/lernstick-firewall/url_whitelist
else
  echo "${gladosProto}://${gladosIp}:${gladosPort}" >>${initrd}/backup/etc/lernstick-firewall/url_whitelist
fi

# hand over allowed ip/ports
echo "tcp ${gladosIp} ${gladosPort}" >>${initrd}/backup/etc/lernstick-firewall/net_whitelist
sort -u -o ${initrd}/backup/etc/lernstick-firewall/url_whitelist ${initrd}/backup/etc/lernstick-firewall/url_whitelist

# grab all UUIDs from physical ethernet connections to bring them down before rebooting the
# system. This forces network-manager to reconnect each of them. This solves a problem when
# the system recieves an IP-address at bootup (by ipconfig) and NM handles it as "manual" IP.
# We don't loose DNS servers and the DHCP lease will be renewed properly.
eths=$(LC_ALL=C nmcli -t -f state,type,con-uuid d status | awk -F: '$1=="connected"&&$2=="ethernet"{print $3}')

# export variables needed in the screen underneath
export eths
export zenity
export wget
export wgetOptions
export DEBUG
export DISPLAY
export XAUTHORITY
export urlNotify

screen -d -m bash -c '
  # transmit state to server
  function clientState()
  {
    $DEBUG && \
      ${wget} ${wgetOptions} -qO- "${urlNotify//\{state\}/$1}" 1>&2 || \
      ${wget} ${wgetOptions} -qO- "${urlNotify//\{state\}/$1}" 2>&1 >/dev/null
    $DEBUG && >&2 echo "New client state: $1"
  }

  # export DISPLAY and XAUTHORITY env vars
  set -o allexport
  eval $(strings /proc/$(pgrep -n firefox)/environ | grep "DISPLAY=")
  eval $(strings /proc/$(pgrep -n firefox)/environ | grep "XAUTHORITY=")
  set +o allexport

  if $DEBUG; then
    if ${zenity} --width=300 --question --title="Continue" --text="The system setup is done. Continue?"; then
      clientState "continue bootup"
      echo "${eths}" | LC_ALL=C xargs -t -I{} nmcli connection down uuid "{}"
      halt
    fi
  else
    # timeout for 10 seconds
    for i in {1..10}; do
      echo "${i}0"
      #echo "#The system will continue in $((10 - $i)) seconds"
      sleep 1
    done | ${zenity} --width=300 --progress --no-cancel --title="Continue" --text="The system will continue in 10 seconds" --percentage=0 --auto-close
    clientState "continue bootup"
    echo "${eths}" | LC_ALL=C xargs -t -I{} nmcli connection down uuid "{}"
    halt
  fi
'

>&2 echo "done"
exit
