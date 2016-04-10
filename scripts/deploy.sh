#!/bin/sh

cd ../daemon

env GOOS=linux GOARCH=386 go build -o stockpeer.linux.386 *.go

git add .

git commit -m "Deployment....."

git push origin master

cd ../scripts

ssh -p 9022 web10.cloudmanic.com 'cd /var/www/stockpeer.com && git pull origin master && php artisan migrate --force && composer install'