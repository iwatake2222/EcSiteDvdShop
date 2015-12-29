# EcSiteDvdShop

A dummy web site for DVD shop.

<http://dvd-iwiw.rhcloud.com>

## Memo

 - Need to set secret information
  - Google oauth2 information: /creds/google.json
  - Contact Email information: /creds/mailer.json


 - In the local environment, start server in  www directory
  - %> cd www
  - %> php -S localhost:8000


 -  How to change remote URL to upload files to OpenShift
  - git remote set-url origin
ssh://5651554c7628e1549700008e@dvd-iwiw.rhcloud.com/~/git/dvd.git/
  - or
  - git remote add openshift
ssh://5651554c7628e1549700008e@dvd-iwiw.rhcloud.com/~/git/dvd.git/


 -  How to keep it alive
  - Add cron using the following command
  - %> rhc app cartridge add -c cron-1.4 -a MyAppName
