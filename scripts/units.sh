#!/bin/bash
set -e

docker compose up -d
docker compose run --rm phpunit
