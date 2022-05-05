#!/bin/bash
#
# Server side version check
# @todo maybe the time comes to remove this, because the client already has a version check. This is only necessary when the client has none (for client versions <=1.0.10)
# 
#

function verToNr()
{
  local r="0";
  local n=0
  IFS=$'.'
  for nr in $1; do
    n=$((n+1))
  done
  n=5
  for nr in $1; do
    r="$r + 100^$n*$nr"
    n=$((n-1))
  done
  unset IFS
  echo "$r" | bc
}

function version_compare()
{
  local ver=$(verToNr "$1")
  local wants="$2"
  [[ "$wants" =~ ([\>,\<,\=]+)([0-9,\.]+) ]]
  local op="${BASH_REMATCH[1]}"
  local cver="$(verToNr ${BASH_REMATCH[2]})"
  (($ver $op $cver))
}

function check_version()
{
  actionInfo="${actionConfig/ticket\/config\/\{token\}/config\/info}"
  jsonInfo="$(${wget} ${wgetOptions} -qO- "${actionInfo}")"

  retval=$?
  if [ ${retval} -eq 0 ]; then
    # check version
    >&2 echo "check version"
    client_version="$(dpkg-query --showformat='${Version}' --show lernstick-exam-client)"
    lernstick_version="$(grep -ohP '[0-9,\-]{8,}' /run/live/rootfs/filesystem.squashfs/usr/local/lernstick.html /usr/local/lernstick.html 2>/dev/null | sed 's/-//g' | head -1)"
    rdiff_backup_version="$(rdiff-backup --version | grep -oP '[0-9,\.]+' || echo 'no rdiff-backup found')"
    wants_server_version="$(cat /usr/share/lernstick-exam-client/compatibility.json | ${python} -c 'import sys, json; print json.load(sys.stdin)["wants_server_version"]')"
    # TODO: better check for flavor
    if [ -r "/run/live/medium/boot/grub/themes/lernstick/theme.txt" ]; then
      lernstick_flavor="$(grep -qP "title-text.*Prüfung" /run/live/medium/boot/grub/themes/lernstick/theme.txt 2>/dev/null && echo exam || echo standard)"
    else
      # fallback to exam if the file does not exist
      if [ -e "/usr/bin/lernstick_backup" ]; then
        lernstick_flavor="exam"
      else
        lernstick_flavor="standard"
      fi
    fi
    wants_client_version="$(echo "$jsonInfo" | ${python} -c 'import sys, json; print json.load(sys.stdin)["wants_client_version"]')"
    wants_lernstick_version="$(echo "$jsonInfo" | ${python} -c 'import sys, json; print json.load(sys.stdin)["wants_lernstick_version"]')"
    wants_lernstick_flavor="$(echo "$jsonInfo" | ${python} -c 'import sys, json; print json.load(sys.stdin)["wants_lernstick_flavor"]')"
    wants_rdiff_backup_version="$(echo "$jsonInfo" | ${python} -c 'import sys, json; print json.load(sys.stdin)["wants_rdiff_backup_version"]')"
    server_rdiff_backup_version="$(echo "$jsonInfo" | ${python} -c 'import sys, json; print json.load(sys.stdin)["rdiff_backup_version"]')"
    server_version="$(echo "$jsonInfo" | ${python} -c 'import sys, json; print json.load(sys.stdin)["server_version"]')"
    >&2 echo "Client version information:"
    >&2 echo "    client_version = $client_version"
    >&2 echo "    lernstick_version = $lernstick_version"
    >&2 echo "    lernstick_flavor = $lernstick_flavor"
    >&2 echo "    rdiff_backup_version = $rdiff_backup_version"
    >&2 echo "    wants_server_version = $wants_server_version"
    >&2 echo "Server version information:"
    >&2 echo "    server_version = $server_version"
    >&2 echo "    rdiff_backup_version = $server_rdiff_backup_version"
    >&2 echo "    wants_client_version = $wants_client_version"
    >&2 echo "    wants_lernstick_version = $wants_lernstick_version"
    >&2 echo "    wants_lernstick_flavor = $wants_lernstick_flavor"
    >&2 echo "    wants_rdiff_backup_version = $wants_rdiff_backup_version"

    # check for lernstick flavor
    if ! [ "$lernstick_flavor" = "$wants_lernstick_flavor" ]; then
      >&2 echo "Lernstick version mismatch. Got ${lernstick_flavor}, but server needs ${wants_lernstick_flavor}."

      export zenity
      export lernstick_flavor
      export wants_lernstick_flavor
      screen -d -m bash -c '${zenity} --error --width=300 --title "Version Error" --no-markup --text "Lernstick version mismatch. Got ${lernstick_flavor}, but server needs ${wants_lernstick_flavor}. Please use the Lernstick exam environment instead of the standard environment. You can find the Lernstick exam environment under the following URL: https://www.digitale-nachhaltigkeit.unibe.ch/dienstleistungen/lernstick/downloads"'
      do_exit 1
    fi

    # check for lernstick version
    if ! version_compare "$lernstick_version" "$wants_lernstick_version"; then
      >&2 echo "Lernstick version mismatch. Got ${lernstick_version}, but server needs ${wants_lernstick_version}."

      export zenity
      export lernstick_version
      export wants_lernstick_version
      screen -d -m bash -c '${zenity} --error --width=300 --title "Version Error" --no-markup --text "Lernstick version mismatch. Got ${lernstick_version}, but server needs ${wants_lernstick_version}."'
      do_exit 1
    fi

    # check for lernstick-exam-client version
    if ! version_compare "$client_version" "$wants_client_version"; then
      >&2 echo "Client version mismatch. Got ${client_version}, but server needs ${wants_client_version}."

      export zenity
      export client_version
      export wants_client_version
      screen -d -m bash -c '${zenity} --error --width=300 --title "Version Error" --no-markup --text "Client version mismatch. Got ${client_version}, but server needs ${wants_client_version}."'
      do_exit 1
    fi

    # check for rdiff-backup version
    if ! version_compare "$rdiff_backup_version" "$wants_rdiff_backup_version"; then
      >&2 echo "rdiff-backup version mismatch. Got ${rdiff_backup_version}, but server needs ${wants_rdiff_backup_version}. Server has ${server_rdiff_backup_version}."

      export zenity
      export client_version
      export wants_client_version
      screen -d -m bash -c '${zenity} --error --width=300 --title "Version Error" --no-markup --text "rdiff-backup version mismatch. Got ${rdiff_backup_version}, but server needs ${wants_rdiff_backup_version}. Server has ${server_rdiff_backup_version}. Both, client and server need to have the same rdiff-backup version, either both 1.x or both 2.x."'
      do_exit 1
    fi

  else
    >&2 echo "wget failed while fetching the system info (return value: ${retval})."

    export zenity
    export retval
    screen -d -m bash -c '${zenity} --error --width=300 --title "Wget error" --no-markup --text "wget failed while fetching the system info (return value: ${retval})."'
    do_exit 1
  fi

  >&2 echo "All version check successful!"
}
