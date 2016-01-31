#!/usr/bin/python

#  Author: sud0nick
#  Date:   Jan 2016

from subprocess import call
import os, sys

if not len(sys.argv) > 1:
	print "\n[!] Missing key pair name"
	print "Usage: python cfgNginx.py <certName>"
	print ""
	quit()

ssl_dir = "/etc/nginx/ssl/"
certName = sys.argv[1] + ".cer"
keyName = sys.argv[1] + ".pem"

# First check to see if SSL certs exist before
# configuring the server to use them
flags = [".pem", ".cer"]
if os.path.isdir(ssl_dir):
	for file in os.listdir(ssl_dir):
		for flag in flags:
			if flag in file:
				flags.remove(flag)
if flags:
	print "SSL certs must first be generated"
	quit()

nginx = "/etc/nginx/nginx.conf"
inServerBlock = False
needsCfg = True
index = 0
lines = [f for f in open(nginx)]
for line in lines:
		if "1471 ssl;" in line:
			needsCfg = False
			break
if needsCfg is True:
	with open(nginx, "w") as out:
		for line in lines:
			if "1471" in line:
				line = "\t\tlisten        1471 ssl;\n"
				inServerBlock = True
			if inServerBlock is True:
				if "root   /pineapple/;" in line:
					lines.insert(index + 1, "\t\tssl_certificate /etc/nginx/ssl/" + certName + ";\n"
								"\t\tssl_certificate_key /etc/nginx/ssl/" + keyName + ";\n"
								"\t\tssl_protocols TLSv1 TLSv1.1 TLSv1.2;\n")
			index = index + 1
			out.write(line)
	call(["/etc/init.d/nginx", "reload"])

print "Complete"
