#!/bin/bash
#
# Setup expert settings
#

function expert_settings()
{
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

  # config->allow_mount
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
}

function expert_settings_defaults()
{
  # remove user from the netdev group to prevent him from changing network connections
  chroot ${initrd}/newroot gpasswd -d user netdev
  sed -i 's/netdev//' ${initrd}/newroot/etc/live/config/user-setup.conf

  # remove sudo privileges
  sed '/user  ALL=(ALL) PASSWD: ALL/ s/^/#/' /etc/sudoers >${initrd}/backup/etc/sudoers

  # prevent user from mounting external media
  chroot ${initrd}/newroot sed -i 's/^ResultAny=.*/ResultAny=auth_admin/;s/^ResultInactive=.*/ResultInactive=auth_admin/;s/^ResultActive=.*/ResultActive=auth_admin/' /etc/polkit-1/localauthority/50-local.d/10-udisks2.pkla

  # enable the firewall
  chroot ${initrd}/newroot systemctl enable lernstick-firewall.service
}
