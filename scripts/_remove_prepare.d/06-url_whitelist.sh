#!/bin/bash
#
# Set url_whitelist
#

function url_whitelist()
{
  # config->url_whitelist
  value="$1"
  if [ "${value}" != "" ]; then
    >&2 echo "setting url whitelist"
    mkdir -pv ${initrd}/backup/etc/lernstick-firewall/proxy.d
    if [ "${VERSION_ID}" = "9" ]; then
      echo "${value}" | sed 's/\./\\\./g' | tee -a ${initrd}/newroot/etc/lernstick-firewall/proxy.d/glados.conf
      echo "${value}" | sed 's/\./\\\./g' | tee -a ${initrd}/newroot/etc/lernstick-firewall/url_whitelist # for backward compatibility, todo: remove as soon as possible
    else
      echo "${value}" | tee -a ${initrd}/newroot/etc/lernstick-firewall/proxy.d/glados.conf
      echo "${value}" | tee -a ${initrd}/newroot/etc/lernstick-firewall/url_whitelist # for backward compatibility, todo: remove as soon as possible
    fi
  fi
}
