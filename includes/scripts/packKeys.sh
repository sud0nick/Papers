#!/bin/sh

#  Author: sud0nick
#  Date:   Jan 2016

help() {
	echo "Usage: ./packKeys.sh <opts>";
	echo '';
	echo 'Parameters:';
	echo '';
	echo -e '\t-f:\tFile names as string value';
	echo -e '\t-o:\tName of output file';
	echo '';
}

if  [ "$#" -lt 1 ]; then
	help;
	exit;
fi

DL_DIR="/pineapple/modules/Papers/includes/download/";
SSL_STORE="/pineapple/modules/Papers/includes/ssl/";
FILES='';
OUTPUT='';
export IFS=" ";

while [ "$#" -gt 0 ]
do

if [[ "$1" == "-f" ]]; then
	for word in $2; do
		FILES="$FILES $SSL_STORE$word";
	done
fi
if [[ "$1" == "-o" ]]; then
	OUTPUT="$2";
fi

shift
done;

zip -j $DL_DIR$OUTPUT $FILES > /dev/null;
