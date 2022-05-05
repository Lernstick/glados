#!/bin/bash
#
# Setup screenshots in the given interval
#

function screenshots()
{
  # config->screenshots
  if [ "$(config_value "screenshots")" = "True" ]; then
    >&2 echo "enabling screenshots"
    chroot ${initrd}/newroot systemctl enable screenshot.service
  fi
}
