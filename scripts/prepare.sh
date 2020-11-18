#!/bin/bash

DEBUG=true
wget="/usr/bin/wget"
wgetOptions="--dns-timeout=30"
timeout=10
zenity="/usr/bin/zenity"
initrd="/run/initramfs"
infoFile="${initrd}/info"
configFile="${initrd}/config.json"
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

# retrieves the configuration json and stores it to $configFile
function get_config()
{
  url=${1}
  ${wget} ${wgetOptions} -q -O "${configFile}" "${url}"
  
  retval=$?
  if [ ${retval} -ne 0 ]; then
    >&2 echo "wget failed while fetching the system config (return value: ${retval})."
    ${zenity} --error --width=300 --title "Wget error" --text "wget failed while fetching the system config (return value: ${retval})."
    do_exit 1
  fi
}

# Echo the configuration value from the config json
# @param $1 the config value to return
# @param $2 the default value if the config value is not set
function config_value()
{
  if [ ! -r "${configFile}" ]; then
    get_config "${urlConfig}"
  fi

  v="$(cat "${configFile}" | ${python} -c 'import sys, json; print json.load(sys.stdin)["config"]["'${1}'"]' 2>/dev/null)"
  retval=$?
  if [ ${retval} -ne 0 ]; then
    $DEBUG && >&2 echo "${1} not found in config file"
    echo "${2}" #return default value
  else
    $DEBUG && >&2 echo "${1} is set to ${v}"
    echo "$v"
  fi
}

function do_exit()
{
  $DEBUG && >&2 echo "exiting cleanly"

  # revert all changes to iptables
  #iptables-save | grep -v "searchExamServer" | iptables-restore

  # unmount the filesystem
  umount ${initrd}/newroot
  umount -l ${initrd}/{base,exam,tmpfs}
  # exit with failure (1) if nothing has been given to $1
  exit ${1:-1}
}

# get DISPLAY and XAUTHORITY env vars from the wxbrowser window
set -o allexport
. <(strings /proc/$(pgrep -n -f wxbrowser || pgrep -n -f firefox)/environ | awk -F= '$1=="DISPLAY"||$1=="XAUTHORITY"') 
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

# Get the whole configuration and store it in configFile
get_config "${urlConfig}"

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

# those should be removed as fast as possible
cp -p "/usr/bin/lernstick_backup" "${initrd}/backup/usr/bin/lernstick_backup" #TODO: remove
cp -p "/usr/bin/lernstick_autostart" "${initrd}/backup/usr/bin/lernstick_autostart" #TODO: remove
cp -p "/usr/sbin/lernstick-firewall" "${initrd}/backup/usr/sbin/lernstick-firewall" #TODO: remove
cp -p "/etc/lernstick-firewall/lernstick-firewall.conf" "${initrd}/backup/etc/lernstick-firewall/lernstick-firewall.conf" #TODO: remove

# config of /etc/lernstickWelcome
cp -p "/etc/lernstickWelcome" "${initrd}/backup/etc/lernstickWelcome"
sed -i 's/ShowNotUsedInfo=.*/ShowNotUsedInfo=false/g' "${initrd}/backup/etc/lernstickWelcome"
sed -i 's/AutoStartInstaller=.*/AutoStartInstaller=false/g' "${initrd}/backup/etc/lernstickWelcome"

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

# add an entry to show information about the exam in the dash
add_dash_entry "show-info.desktop"

# Copy all dependencies, TODO remove
[ -d "/var/lib/lernstick-exam-client/persistent/" ] && cp -apv "/var/lib/lernstick-exam-client/persistent/." "${initrd}/newroot/"

###########################################
# apply specific exam config if available #
###########################################

if [ -n "${actionConfig}" ]; then
  # get the config
  config="$(${wget} ${wgetOptions} -qO- "${urlConfig}")"
  retval=$?
  if [ ${retval} -ne 0 ]; then
    >&2 echo "wget failed while fetching the system config (return value: ${retval})."
    ${zenity} --error --width=300 --title "Wget error" --text "wget failed while fetching the system config (return value: ${retval})."
    do_exit 1
  fi

  # perform the version check server side
  check_version

  # setup the expert settings
  expert_settings

  # setup screenshots in the given interval
  screenshots

  url_whitelist "$(config_value "url_whitelist")"

  # config->max_brightness
  max_brightness "$(config_value "max_brightness")"

  # set all libreoffice options
  libreoffice

  # set up the launch timer service if keylogger or screen_capture is active
  rm -f "${initrd}/newroot/etc/launch.conf" 2>/dev/null
  launch

  # set all screen_capture options
  screen_capture

  # set up the keylogger service
  keylogger

  # set up the agent
  agent

  # fix the permissions
  chown -R user:user ${initrd}/newroot/${home}/.config

else

  $DEBUG && >&2 echo "no config available, setting default values"

  # these are the default values, if the exam server does not provide a config file and the
  # exam file has not configured them
  expert_settings_defaults

fi

# Copy all dependencies
[ -d "/var/lib/lernstick-exam-client/persistent/" ] && cp -apv "/var/lib/lernstick-exam-client/persistent/." "${initrd}/newroot/"

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
  . <(strings /proc/*/environ 2>/dev/null | grep -P "^DISPLAY\=*" | head -1) 
  . <(strings /proc/*/environ 2>/dev/null | grep -P "^XAUTHORITY\=*" | head -1) 
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
# exit successfully
do_exit 0
