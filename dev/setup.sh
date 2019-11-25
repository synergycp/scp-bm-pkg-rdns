#!/usr/bin/env bash

DIR=$(pwd)

cd "$DIR/admin" && npm i && gulp prod build || exit 1
cd "$DIR/client" && npm i && gulp prod build || exit 1
cd "$DIR" && composer install || exit 1
