#!/bin/bash
#
# Setup expert settings
#

function expert_settings()
{
  >&2 echo "setting expert_settings"

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
  else
    echo "user ALL=(ALL) NOPASSWD: ALL" > "${initrd}/newroot/etc/sudoers.d/01-lernstick-exam"
  fi

  # config->allow_mount_external
  if [ "$(config_value "allow_mount_external")" = "False" ]; then
    cat << EOF > ${initrd}/newroot/etc/polkit-1/localauthority/90-mandatory.d/10-udisks2-mount.pkla
[allow user mounting and unmounting of non-system devices with self authentication]
Identity=unix-user:user
Action=org.freedesktop.udisks2.filesystem-mount
ResultAny=auth_admin
ResultInactive=auth_admin
ResultActive=auth_admin
EOF
  else
    cat << EOF > ${initrd}/newroot/etc/polkit-1/localauthority/90-mandatory.d/10-udisks2-mount.pkla
[allow user mounting and unmounting of non-system devices with self authentication]
Identity=unix-user:user
Action=org.freedesktop.udisks2.filesystem-mount
ResultAny=yes
ResultInactive=yes
ResultActive=yes
EOF
  fi

  # config->allow_mount_system
  if [ "$(config_value "allow_mount_system")" = "False" ]; then
    cat << EOF > ${initrd}/newroot/etc/polkit-1/localauthority/90-mandatory.d/10-udisks2-mount-system.pkla
[allow user mounting and unmounting of system devices with self authentication]
Identity=unix-user:user
Action=org.freedesktop.udisks2.filesystem-mount-system
ResultAny=auth_admin
ResultInactive=auth_admin
ResultActive=auth_admin
EOF
  else
    cat << EOF > ${initrd}/newroot/etc/polkit-1/localauthority/90-mandatory.d/10-udisks2-mount-system.pkla
[allow user mounting and unmounting of system devices with self authentication]
Identity=unix-user:user
Action=org.freedesktop.udisks2.filesystem-mount-system
ResultAny=yes
ResultInactive=yes
ResultActive=yes
EOF
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
