#!/bin/bash

raiseEvent=false;
lastkey="none"
retry=1000
DEBUG=true
SEP=","

#
# Searches the array for a given value and returns the first corresponding key if successful.
# Call by:
# ```
# array_search "needle" "${haystack[@]}"
# ```
#
# @param $1 needle
# @param $2 haystack, the array to search
# @return returns 0 if the elements was found or 1 if not
#
function array_search () {
  local needle="$1"
  shift
  local haystack=("$@")
  for i in "${!haystack[@]}"; do
    if [[ "${haystack[$i]}" = "${needle}" ]]; then
      echo "${i}"
      return 0 
    fi
  done
  return 1
}

declare -a events datas commands;

# read the config file into an array
readarray -t config < <(xmlstarlet sel -n -T -t -m "/config/listen" \
  -v "./event" -o "$SEP" \
  -v "./data"  -o "$SEP" \
  -v "./execute" -o $'\n' \
  agent.conf)

# read the config array fields into seprate arrays
for i in "${config[@]}"; do
  IFS="$SEP" read -ra el <<< "$i"
  events+=("${el[0]}");
  datas+=("${el[1]}");
  commands+=("${el[2]}");
done

$DEBUG && for ((i=0; i<${#events[@]}; ++i)); do
  printf "Listening to event %s with data %s\n" "${events[i]}" "${datas[i]}"
done

$DEBUG && echo "Starting event stream..."

while true; do
  while IFS=': ' read -r key value; do
    #$DEBUG && echo "key: $key, value: $value"
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
      data="$(echo "$value" | python3 -c "import sys, json; print(json.load(sys.stdin)['data'])")"
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
        $DEBUG && echo "id: $id, event: $event, retry:$retry, data: $data"
        index="$(array_search "$event" "${events[@]}")"
        retval="$?"
        if [ "$retval" = "0" ] && [ "${datas[$index]}" = "$data" ]; then
          command="${commands[$index]}"
          $DEBUG && printf 'Event %s triggered with data %s. Executing command "%s".\n' "$event" "$data" "$command"
          ENV_data="$data" ENV_event="$event" ENV_id="$id" nohup sh -c "$command" &
        fi
      fi
      id=""
      event=""
      data=""
    fi

    lastkey="$key"
  done < <(curl -s -N "$1" -o -)

  sleep "$(echo "scale=3;$retry/1000" | bc)";
done

