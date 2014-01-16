#!/usr/bin/python

# Run this script once a day via a cron job to download inventory data for
# brickowl stores that have partnered with us.  Data will be stored in
# www_stores and www_store_inventory

import json
import os
import sys
import time
from subprocess import call, Popen

for f in os.listdir("."):
    if f.endswith(".json"):
        os.remove(f)
        print "rm %s" % f

# Download the list of stores that have partnered with us
filename = 'brickowl-stores.json'
if (not os.path.exists(filename)):
    call(["wget", "--no-check-certificate", "-O", filename, "https://api.brickowl.com/v1/affiliate/stores?key=856c04b26b417a7e2f2155406f26b07c7bcdb069017a22a9b20f56207127fb79"])

# If the download failed then barf
if not os.path.getsize(filename):
    print "\nERROR: File %s is empty\n" % filename
    sys.exit(1)

# Read the json data into 'stores'
f = open('brickowl-stores.json', 'r')
stores = json.loads(f.read())
f.close()

# For each store call cron-update-brickowl-data-guts.py
# - download the stores inventory json file
for store in stores:
    store_id = int(store['store_id'])
    Popen(["nohup", "/home2/dwalto76/tools/cron-update-brickowl-data-guts.py", str(store_id)])
    time.sleep(10)
