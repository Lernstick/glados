#!/bin/bash
#
# Setup for the agent
#

function agent()
{
  # config->screen_capture
  if [ "$(config_value "agent")" = "True" ]; then
    >&2 echo "enabling client agent"
    chroot ${initrd}/newroot systemctl enable lernstick-exam-agent.service
    chroot ${initrd}/newroot systemctl enable lernstick-exam-tray.service
  fi
}
