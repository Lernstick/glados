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

# get DISPLAY and XAUTHORITY env vars to display the zenity window
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
while IFS= read -r c; do echo "${c}"; echo "${c}.nmconnection"; done <<< "${con}" | LC_ALL=C xargs -I{} cp -p "/etc/NetworkManager/system-connections/{}" "${initrd}/backup/etc/NetworkManager/system-connections/"

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
echo "ShowExamInfo=true" >>"${initrd}/backup/etc/lernstickWelcome" #TODO: replace with sed
cp -p "/usr/share/applications/finish_exam.desktop" "${initrd}/backup/usr/share/applications/"
chmod 644 "${initrd}/backup/usr/share/applications/finish_exam.desktop"
chown user:user "${initrd}/backup/${desktop}/finish_exam.desktop"

# This is to fix an issue when the DNS name of the exam server ends in .local (which is the
# case in most Microsoft domain environments). In case of a .local name the mDNS policy in
# /etc/nsswitch.conf will catch. This ends in ssh login delays of up to 20 seconds. Changing it 
# to .alocal is a workaround. Better is not to use mDNS in an exam.
sed 's/#domain-name=local/domain-name=.alocal/' /etc/avahi/avahi-daemon.conf >${initrd}/backup/etc/avahi/avahi-daemon.conf

# mount/prepare the root filesystem
mount /lib/live/mount/medium/live/filesystem.squashfs ${initrd}/base
if [ -e ${initrd}/squashfs/exam.squashfs ]; then
  mount ${initrd}/squashfs/exam.squashfs ${initrd}/exam
  # find out whether the squashfs is an overlayfs
  type=$(unsquashfs -ll ${initrd}/squashfs/exam.squashfs | awk '$1~/^c/&&$3=="0,"&&$4=="0"{print "overlay"; exit}')
  if [ "${type}" != "overlay" ]; then
    # find out whether the squashfs is an aufs
    type=$(unsquashfs -ll ${initrd}/squashfs/exam.squashfs | awk '$0~/\/\.wh\./{print "aufs"; exit}')
  fi
fi

if [ -e ${initrd}/squashfs/exam.zip ]; then
  mount -t tmpfs tmpfs ${initrd}/tmpfs
  unzip -o ${initrd}/squashfs/exam.zip -d ${initrd}/tmpfs
  type="zip"
  # fix permissions of the files in the home dir
  chown -R 1000:1000 ${initrd}/tmpfs/${home} 2>/dev/null
  chown -R 0:0 ${initrd}/tmpfs/${home}/Screenshots 2>/dev/null
fi

# mount the whole filesystem, the result filesystem looks like this
# +---------------+
# | tmpfs (rw)    |
# +---------------+
# | zip (ro)      |
# +---------------+
# | squashfs (ro) |
# +---------------+
# | base (ro)     |
# +---------------+
mkdir ${initrd}/work
if [ "${type}" = "aufs" ]; then
  # in there are whiteouts for aufs (\.wh\.*) and no whiteouts for overlayfs (character devices with 0/0) it is an aufs filesystem
  mount -t aufs -o br=${initrd}/backup=rw:${initrd}/tmpfs=ro:${initrd}/exam=ro:${initrd}/base=ro none "${initrd}/newroot"
  cat <<EOF >"${mountFile}"
mount -t aufs -o br=/backup=rw:/tmpfs=ro:/exam=ro:/base=ro none "/newroot"
EOF
else
  # in all other cases the filesystem in treated as overlay
  mount -t overlay overlay -o lowerdir=${initrd}/tmpfs:${initrd}/exam:${initrd}/base,upperdir=${initrd}/backup,workdir=${initrd}/work ${initrd}/newroot
  cat <<EOF >"${mountFile}"
mount -t overlay overlay -o lowerdir=/tmpfs:/exam:/base,upperdir=/backup,workdir=/work /newroot
EOF
fi

# install the shutdown hook
cp -pf "${initrd}/squashfs/mount.sh" "/lib/systemd/lernstick-shutdown"
chmod 755 "/lib/systemd/lernstick-shutdown"

# remove policykit action for lernstick welcome application
rm -f ${initrd}/newroot/usr/share/polkit-1/actions/ch.lernstick.welcome.policy

# place finish_exam.desktop in "favorite apps" of Gnome3's dash
if [ -e "${initrd}/newroot/etc/dconf/db/local.d/01-gnome-favorite-apps" ]; then
  newvalue=$(awk -F'[\,,\[,\], ]' '{if($0~/^favorite-apps=/){ printf "favorite-apps=["; for(i = 2; i <= NF; i++) { if($i!="") {printf "%s, ", $i;} } printf "'\''finish_exam.desktop'\'']\n"; } else { print $0; } }' ${initrd}/newroot/etc/dconf/db/local.d/01-gnome-favorite-apps)
  echo "${newvalue}" > "${initrd}/newroot/etc/dconf/db/local.d/01-gnome-favorite-apps"
else
  echo "[org/gnome/shell]
favorite-apps=['finish_exam.desktop']" > "${initrd}/newroot/etc/dconf/db/local.d/01-gnome-favorite-apps"
fi

# this touch is needed, because dconf update is not rebuilding the database if the
# directory containing the rules has the same mtime as before
touch "${initrd}/newroot/etc/dconf/db/local.d"
chroot ${initrd}/newroot dconf update

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

  # config->grp_netdev
  if [ "$(config_value "grp_netdev")" = "False" ]; then
    chroot ${initrd}/newroot gpasswd -d user netdev
    sed -i 's/netdev//' ${initrd}/newroot/etc/live/config/user-setup.conf
  else
    chroot ${initrd}/newroot gpasswd -a user netdev
  fi

  # config->allow_sudo
  if [ "$(config_value "allow_sudo")" = "False" ]; then
    sed '/user  ALL=(ALL) PASSWD: ALL/ s/^/#/' /etc/sudoers >${initrd}/backup/etc/sudoers
  else
    sed '/^#user  ALL=(ALL) PASSWD: ALL/ s/^#//' /etc/sudoers >${initrd}/backup/etc/sudoers
  fi

  # config->allow_sudo
  if [ "$(config_value "allow_mount")" = "False" ]; then
    chroot ${initrd}/newroot sed -i 's/^ResultAny=.*/ResultAny=auth_admin/;s/^ResultInactive=.*/ResultInactive=auth_admin/;s/^ResultActive=.*/ResultActive=auth_admin/' /etc/polkit-1/localauthority/50-local.d/10-udisks2.pkla
  else
    chroot ${initrd}/newroot sed -i 's/^ResultAny=.*/ResultAny=yes/;s/^ResultInactive=.*/ResultInactive=yes/;s/^ResultActive=.*/ResultActive=yes/' /etc/polkit-1/localauthority/50-local.d/10-udisks2.pkla
  fi

  # config->firewall_off
  if [ "$(config_value "firewall_off")" = "False" ]; then
    chroot ${initrd}/newroot systemctl enable lernstick-firewall.service
  else
    chroot ${initrd}/newroot systemctl disable lernstick-firewall.service
  fi

  # config->screenshots
  if [ "$(config_value "screenshots")" = "False" ]; then
    chroot ${initrd}/newroot sed -i 's/BackupScreenshot=.*/BackupScreenshot=false/' /etc/lernstickWelcome 
  else
    # write/append config options
    bf=$(config_value "screenshots_interval")
    chroot ${initrd}/newroot sed -i '/^BackupScreenshot=/{h;s/=.*/=true/};${x;/^$/{s//BackupScreenshot=true/;H};x}' /etc/lernstickWelcome
    chroot ${initrd}/newroot sed -i '/^Backup=/{h;s/=.*/=true/};${x;/^$/{s//Backup=true/;H};x}' /etc/lernstickWelcome
    chroot ${initrd}/newroot sed -i '/^BackupFrequency=/{h;s/=.*/='$bf'/};${x;/^$/{s//BackupFrequency='$bf'/;H};x}' /etc/lernstickWelcome
  fi

  # config->url_whitelist
  if [ "$(config_value "url_whitelist")" != "" ]; then
    if [ "${VERSION_ID}" = "9" ]; then
      config_value "url_whitelist" | sed 's/\./\\\./g' | tee -a ${initrd}/newroot/etc/lernstick-firewall/url_whitelist
    else
      config_value "url_whitelist" | tee -a ${initrd}/newroot/etc/lernstick-firewall/url_whitelist
    fi
  fi

  # config->max_brightness
  max_brightness=$(config_value "max_brightness")
  if [ "${max_brightness}" != "100" ] && [ "${max_brightness}" != "" ]; then

    # create max_brightness systemd daemon
    cat <<EOF >"${initrd}/newroot/etc/systemd/system/max_brightness.service"
[Unit]
Description=max_brightness

[Service]
Environment=max=${max_brightness}
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

  # config->libre_autosave
  if [ "$(config_value "libre_autosave")" = "True" ]; then
    libre_autosave="true"
  else
    libre_autosave="false"
  fi

  # config->libre_createbackup
  if [ "$(config_value "libre_createbackup")" = "True" ]; then
    libre_createbackup="true"
  else
    libre_createbackup="false"
  fi

  # config->libre_autosave_interval
  if [ "$(config_value "libre_autosave_interval")" = "" ]; then
    libre_autosave_interval="10"
  else
    libre_autosave_interval="$(config_value "libre_autosave_interval")"
  fi

  # config->libre_autosave_path
  if [ "$(config_value "libre_autosave_path")" = "" ]; then
    libre_autosave_path=""
  else
    libre_autosave_path="$(config_value "libre_autosave_path")"
  fi

  # config->libre_createbackup_path
  if [ "$(config_value "libre_createbackup_path")" = "" ]; then
    libre_createbackup_path=""
  else
    libre_createbackup_path="$(config_value "libre_createbackup_path")"
  fi

  registry='<item oor:path="/org.openoffice.Office.Recovery/AutoSave"><prop oor:name="TimeIntervall" oor:op="fuse"><value>'${libre_autosave_interval}'</value></prop></item>
<item oor:path="/org.openoffice.Office.Recovery/AutoSave"><prop oor:name="Enabled" oor:op="fuse"><value>'${libre_autosave}'</value></prop></item>
<item oor:path="/org.openoffice.Office.Common/Save/Document"><prop oor:name="CreateBackup" oor:op="fuse"><value>'${libre_createbackup}'</value></prop></item>'

  if ! [ "${libre_autosave_path}" = "" ]; then
    registry=${registry}'
<item oor:path="/org.openoffice.Office.Common/Path/Current"><prop oor:name="Temp" oor:op="fuse"><value xsi:nil="true"/></prop></item>
<item oor:path="/org.openoffice.Office.Paths/Paths/org.openoffice.Office.Paths:NamedPath['"'"'Temp'"'"']"><prop oor:name="WritePath" oor:op="fuse"><value>file://'${libre_autosave_path}'</value></prop></item>'
  fi

  if ! [ "${libre_createbackup_path}" = "" ]; then
    registry=${registry}'
<item oor:path="/org.openoffice.Office.Common/Path/Current"><prop oor:name="Backup" oor:op="fuse"><value xsi:nil="true"/></prop></item>
<item oor:path="/org.openoffice.Office.Paths/Paths/org.openoffice.Office.Paths:NamedPath['"'"'Backup'"'"']"><prop oor:name="WritePath" oor:op="fuse"><value>file://'${libre_createbackup_path}'</value></prop></item>'
  fi

registry=${registry}'
</oor:items>'

  # if the file exists, remove the xml entries
  if [ -e "${initrd}/newroot/${home}/.config/libreoffice/4/user/registrymodifications.xcu" ]; then
    sed -i -e '\#org.openoffice.Office.Recovery/AutoSave.*TimeIntervall#d' \
      -e '\#org.openoffice.Office.Recovery/AutoSave.*Enabled#d' \
      -e '\#org.openoffice.Office.Common/Save/Document.*CreateBackup#d' \
      -e '\#org.openoffice.Office.Paths/Paths/org.openoffice.Office.Paths.*NamedPath.*Backup.*WritePath#d' \
      -e '\#org.openoffice.Office.Paths/Paths/org.openoffice.Office.Paths.*NamedPath.*Temp.*WritePath#d' \
      -e '\#org.openoffice.Office.Common/Path/Current.*Temp#d' \
      -e '\#org.openoffice.Office.Common/Path/Current.*Backup#d' \
      -e '\#</oor:items>#d' \
      ${initrd}/newroot/${home}/.config/libreoffice/4/user/registrymodifications.xcu
  else
    # else create the needed config directory
    mkdir -p ${initrd}/newroot/${home}/.config/libreoffice/4/user
    registry='<?xml version="1.0" encoding="UTF-8"?>
<oor:items xmlns:oor="http://openoffice.org/2001/registry" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
'${registry}
  fi

  # append the xml entries to the file
  echo "${registry}" >> ${initrd}/newroot/${home}/.config/libreoffice/4/user/registrymodifications.xcu

  # fix the permissions
  chown -R user:user ${initrd}/newroot/${home}/.config

else
  # these are the default values, if the exam server does not provide a config file and the
  # exam file has not configured them
  $DEBUG && >&2 echo "no config available, setting default values"

  # remove user from the netdev group to prevent him from changing network connections
  chroot ${initrd}/newroot gpasswd -d user netdev
  sed -i 's/netdev//' ${initrd}/newroot/etc/live/config/user-setup.conf

  # remove sudo privileges
  sed '/user  ALL=(ALL) PASSWD: ALL/ s/^/#/' /etc/sudoers >${initrd}/backup/etc/sudoers

  # prevent user from mounting external media
  chroot ${initrd}/newroot sed -i 's/^ResultAny=.*/ResultAny=auth_admin/;s/^ResultInactive=.*/ResultInactive=auth_admin/;s/^ResultActive=.*/ResultActive=auth_admin/' /etc/polkit-1/localauthority/50-local.d/10-udisks2.pkla

  # enable the firewall
  chroot ${initrd}/newroot systemctl enable lernstick-firewall.service

fi

# hand over the ssh key from the exam server
echo "${sshKey}" >>"${initrd}/backup/root/.ssh/authorized_keys"

# hand over open ports
echo "tcp ${gladosIp} 22" >>${initrd}/backup/etc/lernstick-firewall/net_whitelist_input

# hand over the url whitelist
if [ "${VERSION_ID}" = "9" ]; then
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
    if ${zenity} --question --title="Continue" --text="The system setup is done. Continue?"; then
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
    done | ${zenity} --progress --no-cancel --title="Continue" --text="The system will continue in 10 seconds" --percentage=0 --auto-close
    clientState "continue bootup"
    echo "${eths}" | LC_ALL=C xargs -t -I{} nmcli connection down uuid "{}"
    halt
  fi
'

>&2 echo "done"
exit
