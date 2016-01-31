#!/bin/bash

#  Author: sud0nick
#  Date:   Jan 2016

if [ -z "$1" ]; then
	echo "Usage: ./replaceKeys.sh <certificate name>";
	exit;
fi

sed -e "s/[a-zA-Z0-9]*.pem;$/$1.pem;/g" -e "s/[a-zA-Z0-9]*.cer;$/$1.cer;/g" /etc/nginx/nginx.conf > /etc/nginx/tmp.conf;
mv /etc/nginx/tmp.conf /etc/nginx/nginx.conf;
/etc/init.d/nginx reload;
