#!/bin/sh
#
# This script is executed when the client system is in its "warm reboot" phase.
# It unmounts the old root filesystem and afterwards mounts overlay filesystem
# together with the squashfs (the zip file is unzipped over the mounted
# filesystem).
#
# If the /restore file does not exist, it means that we shutdown regularly.
#

BASEPATH="/lib/live/mount"
ACTION="$1"

unmount_loop() {
    mount_path="$1"
    unmounted="y"

    # try until nothing can be unmounted anymore
    while [ "$unmounted" = "y" ] ; do
        unmounted="n"
        for m in $(mount | cut -d ' ' -f 3 | grep "^${mount_path}") ; do
            umount $m 2>/dev/null&& unmounted="y"
        done
    done
}

# move squashfs and persistence filesystems out of /oldroot
for t in rootfs persistence ; do
  for m in $(ls /oldroot${BASEPATH}/$t) ; do
    mount_path="${BASEPATH}/$t/${m}"
    mkdir -p "$mount_path"
    mount -o move "/oldroot${mount_path}" "$mount_path"
  done
done

# move medium out of /oldroot
mkdir -p ${BASEPATH}/medium
mount -o move /oldroot${BASEPATH}/medium ${BASEPATH}/medium

# unmount what's still mounted under /oldroot
unmount_loop /oldroot

# unmount underlying filesystems
unmount_loop $BASEPATH

# if the /restore file exists, we're in exam mode. In this case we don't have to
# shutdown, instead prepare the filesystem and chroot to it. If the file does not
# exist, do a normal shutdown.
if [ -f "/restore" ]; then

  echo "##############################################################" >/dev/kmsg 2>&1
  echo "##################  SWITCHING TO EXAM MODE  ##################" >/dev/kmsg 2>&1
  echo "##############################################################" >/dev/kmsg 2>&1

  # workaround, plymouth removes /dev/null, which then is recreated as regular
  # file with mode 644. This causes lots of processes to fail.
  kill "$(pidof plymouthd)"
  sleep 1
  kill -9 "$(pidof plymouthd)"

  # this could spawn a shell to investigate
  #/bin/sh >/dev/console

  # determine partitionSystem once and for all
  # this one is for debian 10
  partitionSystem="$(awk '$2~/run\/live\/medium/{print $1}' /proc/mounts)"
  if [ -z "${partitionSystem}" ]; then
    # this one is for debian 9
    partitionSystem="$(awk '$2~/live\/mount\/medium/{print $1}' /proc/mounts)"
  fi
  if [ -z "${partitionSystem}" ]; then
    partitionSystem="/dev/sr0"
  fi
  sed -i "s#partitionSystem=.*#partitionSystem=${partitionSystem}#" /info

  # get data from info file
  eval $(cat /info)
  mkdir -p /usb

  oldMnt="$(awk -v m="$partitionSystem" '$1==m{print $2}' /proc/mounts)"
  if [ -z "${oldMnt}" ]; then
    mount ${partitionSystem} /usb
  else
    mount -o move "${oldMnt}" /usb
  fi

  # mount the filesystems
  mount -o loop -t squashfs /usb/live/filesystem.squashfs /base

  if [ -e /squashfs/exam.squashfs ]; then
    mount -o loop -t squashfs /squashfs/exam.squashfs /exam
  fi

  if [ -e /squashfs/exam.zip ]; then
    mount -t tmpfs tmpfs /tmpfs
    unzip -o /squashfs/exam.zip -d /tmpfs
    chown -R 1000:1000 /tmpfs/home/user 2>/dev/null
    chown -R 0:0 /tmpfs/home/user/Screenshots 2>/dev/null
  fi

  [ -e "/mount" ] && sh "/mount" || mount -t aufs -o br=/backup=rw:/exam=ro:/base=ro none "/newroot"
  cp /info /newroot/info
  cp /config.json /newroot/config.json

  # mount the overlay inside the filesystem
  mkdir /newroot/overlay
  mount --bind /backup /newroot/overlay

  touch /init
  touch /newroot/init

  # finally change the root directory and continue bootup
  exec chroot /newroot /sbin/init

fi

message='System shutdown complete. Press any key to $ACTION or wait $i seconds.'
countdown="$(seq 10 -1 1)"
if [ ${ACTION} != "reboot" ] ; then
    if plymouth --ping ; then
        # show message and wait some time
        plymouth pause-progress
        # watch for keystroke in background and execute $ACTION on keystroke
        plymouth watch-keystroke --command="systemctl --force --force $ACTION" &
        for i in $countdown ; do
            plymouth display-message --text="$(eval echo "${message}")"
            sleep 1
        done
    else
        for i in $countdown ; do
            eval echo "${message}"
            if read -n 1 -t 1 ; then
                systemctl --force --force $ACTION
            fi
        done
    fi
fi

# execute system action
systemctl --force --force "$ACTION"