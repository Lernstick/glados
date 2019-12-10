#!/bin/bash
#
# Setup screenshots in the given interval
#

function screenshots()
{
  # config->screenshots
  if [ "$(config_value "screenshots")" = "False" ]; then
    chroot ${initrd}/newroot sed -i 's/BackupScreenshot=.*/BackupScreenshot=false/' /etc/lernstickWelcome 
  else
    # write/append config options
    bf=$(config_value "screenshots_interval")
    chroot ${initrd}/newroot sed -i '/^BackupScreenshot=/{h;s/=.*/=true/};${x;/^$/{s//BackupScreenshot=true/;H};x}' /etc/lernstickWelcome
    chroot ${initrd}/newroot sed -i '/^Backup=/{h;s/=.*/=true/};${x;/^$/{s//Backup=true/;H};x}' /etc/lernstickWelcome
    chroot ${initrd}/newroot sed -i '/^BackupFrequency=/{h;s/=.*/='$bf'/};${x;/^$/{s//BackupFrequency='$bf'/;H};x}' /etc/lernstickWelcome
    chroot ${initrd}/newroot sed -i '/^BackupSource=/{h;s/=.*/=\/home\/user\//};${x;/^$/{s//BackupSource=\/home\/user\//;H};x}' /etc/lernstickWelcome
  fi
}
