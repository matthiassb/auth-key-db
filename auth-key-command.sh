#!/bin/bash

if [ $# -eq 0 ]
  then
    echo "No arguments supplied"
fi

HOST=127.0.0.1
USER=$1
INDEX_PATH="/index.php"

HOMEDIR=$(eval echo ~$USER)
if [ -r "$HOMEDIR/.ssh/authorized_keys" ]; then
  cat $HOMEDIR/.ssh/authorized_keys
fi

curl -s "http://$HOST$INDEX_PATH?username=$USER"
