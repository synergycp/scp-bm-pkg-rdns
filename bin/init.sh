#!/bin/bash

DIR=$(pwd)
MAIN_DIR=$1
MODE=$2
REL_DIR=${DIR:${#MAIN_DIR}+1}

case $MODE in
1)
    composer install
    ;;
2)
    cd $MAIN_DIR
    php artisan migrate --force --path=$REL_DIR/database/migrations
    ;;
5)
    cd $DIR/admin
    npm install && gulp prod build

    cd $DIR/client
    npm install && gulp prod build
    ;;
esac

cd $DIR
