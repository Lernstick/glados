#!/bin/bash
#
# Setup expert settings
#

function expert_settings()
{
  # config->grp_netdev
  if [ "$(config_value "grp_netdev")" = "False" ]; then
    chroot ${initrd}/newroot gpasswd -d user netdev
    sed -i 's/netdev//' ${initrd}/newroot/etc/live/config.conf.d/user-setup.conf
  else
    chroot ${initrd}/newroot gpasswd -a user netdev
  fi

  # config->allow_sudo
  if [ "$(config_value "allow_sudo")" = "False" ]; then
    rm "${initrd}/newroot/etc/sudoers.d/01-lernstick-exam"
  fi

  # config->allow_mount
  if [ "$(config_value "allow_mount")" = "False" ]; then
    chroot ${initrd}/newroot sed -i 's/^ResultAny=.*/ResultAny=auth_admin/;s/^ResultInactive=.*/ResultInactive=auth_admin/;s/^ResultActive=.*/ResultActive=auth_admin/' /etc/polkit-1/localauthority/50-local.d/10-udisks2-mount.pkla /etc/polkit-1/localauthority/50-local.d/10-udisks2-mount-system.pkla
  else
    chroot ${initrd}/newroot sed -i 's/^ResultAny=.*/ResultAny=yes/;s/^ResultInactive=.*/ResultInactive=yes/;s/^ResultActive=.*/ResultActive=yes/' /etc/polkit-1/localauthority/50-local.d/10-udisks2-mount.pkla /etc/polkit-1/localauthority/50-local.d/10-udisks2-mount-system.pkla
  fi

  # config->firewall_off
  if [ "$(config_value "firewall_off")" = "False" ]; then
    chroot ${initrd}/newroot systemctl enable lernstick-firewall.service
  else
    chroot ${initrd}/newroot systemctl disable lernstick-firewall.service
    chroot ${initrd}/newroot /lib/systemd/lernstick-firewall stop
  fi
}

function expert_settings_defaults()
{
  # remove user from the netdev group to prevent him from changing network connections
  chroot ${initrd}/newroot gpasswd -d user netdev
  sed -i 's/netdev//' ${initrd}/newroot/etc/live/config.conf.d/user-setup.conf

  # prevent user from mounting external media
  chroot ${initrd}/newroot sed -i 's/^ResultAny=.*/ResultAny=auth_admin/;s/^ResultInactive=.*/ResultInactive=auth_admin/;s/^ResultActive=.*/ResultActive=auth_admin/' /etc/polkit-1/localauthority/50-local.d/10-udisks2-mount.pkla /etc/polkit-1/localauthority/50-local.d/10-udisks2-mount-system.pkla

  # enable the firewall
  chroot ${initrd}/newroot systemctl enable lernstick-firewall.service
}
