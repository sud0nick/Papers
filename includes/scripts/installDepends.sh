#!/bin/sh
#  Author: sud0nick
#  Date:   Jan 2016

RES=$(df | grep sd)
opkg update > /dev/null;

if [[ -z "$RES" ]]; then
        opkg install zip > /dev/null;
else
        export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
        export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin
        opkg install zip --dest sd > /dev/null;
fi

echo "Complete"