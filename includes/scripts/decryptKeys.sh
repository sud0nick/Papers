#!/bin/sh

#  Author: sud0nick
#  Date:   Dec 2018

# Location of SSL keys
ssl_store="/pineapple/modules/Papers/includes/ssl/";

help() {
	echo "Decryption script for OpenSSL keys";
	echo "Usage: ./decryptKeys.sh <opts>";
	echo "Use './decryptKeys.sh --examples' to see example commands";
	echo '';
	echo 'NOTE:';
	echo "Current SSL store is at $ssl_store";
	echo '';
	echo 'Parameters:';
	echo '';
	echo -e '\t-k:\tName of key to be decrypted';
	echo -e '\t-p:\tPassword to use to unlock the key';
	echo -e '\t--help:\tDisplays this help info';
	echo '';
}

examples() {
	echo '';
        echo 'Examples:';
        echo 'Decrypt private key:';
        echo './decryptKeys.sh -k keyName -p password';
        echo '';
	echo '';
}

if [ "$#" -lt 1 ]; then
        help;
        exit;
fi

while [ "$#" -gt 0 ]
do

if [[ "$1" == "--examples" ]]; then
	examples;
	exit;
fi
if [[ "$1" == "--help" ]]; then
	help;
	exit;
fi
if [[ "$1" == "-k" ]]; then
	KEY="$2";
fi
if [[ "$1" == "-p" ]]; then
	PASS="$2";
fi

shift
done;

# Generate a password on the private key
openssl rsa -in $ssl_store$KEY.key -out $ssl_store$KEY.key -passin pass:"$PASS";

echo "Complete"
