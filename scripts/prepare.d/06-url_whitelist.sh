#!/bin/bash
#
# Set url_whitelist
#

function url_whitelist()
{
  # config->url_whitelist
  value="$1"
  if [ "${value}" != "" ]; then
    if [ "${VERSION_ID}" = "9" ]; then
      echo "${value}" | sed 's/\./\\\./g' | tee -a ${initrd}/newroot/etc/lernstick-firewall/url_whitelist
    else
      echo "${value}" | tee -a ${initrd}/newroot/etc/lernstick-firewall/url_whitelist
    fi
  fi
}
