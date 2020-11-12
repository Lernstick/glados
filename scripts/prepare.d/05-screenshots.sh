#!/bin/bash
#
# Setup screenshots in the given interval
#

function screenshots()
{
  # config->screenshots
  if [ "$(config_value "screenshots")" = "True" ]; then
    chroot ${initrd}/newroot systemctl enable screenshot.service
  fi
}
