#!/bin/bash
#
# mount/prepare the root filesystem
#

function mount_rootfs()
{
  newroot="$1"
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
    chown -R 0:0 ${initrd}/tmpfs/${home}/.Screenshots 2>/dev/null
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
    mount -t aufs -o br=${initrd}/backup=rw:${initrd}/tmpfs=ro:${initrd}/exam=ro:${initrd}/base=ro none "${initrd}/${newroot}"
    cat <<EOF >"${mountFile}"
mount -t aufs -o br=/backup=rw:/tmpfs=ro:/exam=ro:/base=ro none "/${newroot}"
EOF
  else
    # in all other cases the filesystem in treated as overlay
    mount -t overlay overlay -o lowerdir=${initrd}/tmpfs:${initrd}/exam:${initrd}/base,upperdir=${initrd}/backup,workdir=${initrd}/work ${initrd}/${newroot}
    cat <<EOF >"${mountFile}"
mount -t overlay overlay -o lowerdir=/tmpfs:/exam:/base,upperdir=/backup,workdir=/work /${newroot}
EOF
  fi
}
