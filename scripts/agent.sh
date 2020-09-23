#!/bin/bash

raiseEvent=false;
lastkey="none"
retry=1000
DEBUG=true

while true; do
  while IFS=': ' read -r key value; do
    [ "DEBUG" ] && echo "key: $key, value: $value"
    if [ "$key" = "id" ]; then
      raiseEvent=false;
      id="$value"
    fi

    if [ "$key" = "event" ]; then
      raiseEvent=false;
      event="$value"
    fi

    if [ "$key" = "data" ]; then
      raiseEvent=false;
      data="$value"
    fi

    if [ "$key" = "retry" ]; then
      raiseEvent=false;
      export retry="$value"
    fi

    if [ "$key" = "" ] && [ "$value" = "" ] && [ "$lastkey" != "0" ] ; then
      raiseEvent=true;
    fi

    if [ "$key" = "0" ]; then
      raiseEvent=false;
    fi

    if $raiseEvent; then
      if [ -n "$id" ] || [ -n "$event" ] || [ -n "$data" ]; then
        echo "id: $id, event: $event, retry:$retry, data: $data"
      fi
      id=""
      event=""
      data=""
    fi

    lastkey="$key"
  done < <(curl -s -N "$1" -o -)

  sleep "$(echo "scale=3;$retry/1000" | bc)";
done

