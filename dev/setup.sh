#!/usr/bin/env bash

DIR=$(pwd)

cd "$DIR/admin" && npm i && gulp prod build
cd "$DIR/client" && npm i && gulp prod build
cd "$DIR" && composer install
