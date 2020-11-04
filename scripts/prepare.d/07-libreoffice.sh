#!/bin/bash
#
# Setup for libreoffice
#

function libreoffice()
{
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
}
