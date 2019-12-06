#!/bin/bash
#
# Update Gnome3 dash by adding an entry to it
#

function add_dash_entry()
{
  entry="$1"
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
}
