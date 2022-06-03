#!/bin/bash
#
# Update Gnome3 dash by adding an entry to it
# @param string $1 the entry to add as a .desktop file
#

function add_dash_entry()
{
  entry="$1"

  >&2 echo "adding dash entry ${entry}"
  # place ${entry} in "favorite apps" of Gnome3's dash in the system-db
  if [ -e "${initrd}/newroot/etc/dconf/db/local.d/01-gnome-favorite-apps" ]; then
    newvalue=$(mawk -v entry="${entry}" -F'[\,,\[,\], ]' '{if($0~/^favorite-apps=/){ printf "favorite-apps=["; for(i = 2; i <= NF; i++) { if($i!="") {printf "%s, ", $i;} } printf "'\''%s'\'']\n", entry; } else { print $0; } }' ${initrd}/newroot/etc/dconf/db/local.d/01-gnome-favorite-apps)
  else
    newvalue="[org/gnome/shell]
  favorite-apps=['${entry}']"
  fi
  echo "${newvalue}" > "${initrd}/newroot/etc/dconf/db/local.d/01-gnome-favorite-apps"

  # this touch is needed, because dconf update is not rebuilding the database if the
  # directory containing the rules has the same mtime as before
  touch "${initrd}/newroot/etc/dconf/db/local.d"
  chroot ${initrd}/newroot dconf update

  # place ${entry} in "favorite apps" of Gnome3's dash in the user-db
  cp -pv /home/user/.config/dconf/user /home/user/.config/dconf/user.bak
  [ -e "${initrd}/newroot/home/user/.config/dconf/user" ] && cp -p ${initrd}/newroot/home/user/.config/dconf/user /home/user/.config/dconf/
  sync

  oldvalue="$(sudo -u user dconf read /org/gnome/shell/favorite-apps)"
  if [ "${oldvalue}" = "" ]; then
    newvalue="['${entry}']"
  else
    newvalue=$(echo "${oldvalue}" | mawk -v entry="${entry}" -F'[\,,\[,\], ]' '{ printf "["; for(i = 2; i <= NF; i++) { if($i!="") {printf "%s, ", $i;} } printf "'\''%s'\'']\n", entry; }')

  fi
  sudo -u user dconf write "/org/gnome/shell/favorite-apps" "${newvalue}"
  sync

  [ -e "${initrd}/newroot/home/user/.config/dconf/" ] || mkdir -pv "${initrd}/newroot/home/user/.config/dconf/"
  cp -pv /home/user/.config/dconf/user ${initrd}/newroot/home/user/.config/dconf/user
  cp -pv /home/user/.config/dconf/user.bak /home/user/.config/dconf/user

}
