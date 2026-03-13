#!/bin/bash
#TODO: ask for user confirmation
$user_agreed="YES, CLEAN NOW"
if [ "$user_agreed" != "YES, CLEAN NOW" ]; then
    echo "User did not agree to clean the environment"
    exit 1
fi
docker compose down --rmi all -v
docker compose up -d --build