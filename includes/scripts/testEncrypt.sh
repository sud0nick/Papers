#!/bin/sh


help() {
        echo "Usage: ./testEncrypt.sh <opts>";
        echo '';
        echo 'Parameters:';
        echo '';
        echo -e '\t-k:\tName of key to test';
        echo '';
}

if  [ "$#" -lt 2 ]; then
        help;
        exit;
fi

SSL_STORE="/pineapple/modules/Papers/includes/ssl/";
KEY="";

while [ "$#" -gt 0 ]
do

if [[ "$1" == "-k" ]]; then
	KEY="$2.pem"
fi

shift
done;

openssl rsa -in $SSL_STORE$KEY -passin pass: | awk 'NR==0;'
