#!/bin/sh

cd ../daemon

env GOOS=linux GOARCH=386 go build -o stockpeer.linux.386 *.go

git add .

git commit -m "Deployment....."

git push origin master

cd ../scripts

ssh -p 9022 web10.cloudmanic.com 'cd /var/www/stockpeer.com && git pull origin master && php artisan migrate --force && composer update'

ACCESS_TOKEN=b6df56521d174d27b37c02b5c3869b17
ENVIRONMENT=production
LOCAL_USERNAME=`whoami`
REVISION=`cd ../ && git log -n 1 --pretty=format:"%H" && cd scripts`

curl https://api.rollbar.com/api/1/deploy/ \
  -F access_token=$ACCESS_TOKEN \
  -F environment=$ENVIRONMENT \
  -F revision=$REVISION \
  -F local_username=$LOCAL_USERNAME