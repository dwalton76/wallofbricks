#!/usr/bin/python

import csv
import MySQLdb
import os
import sys
import shutil

def sql_select(sql):
    cur.execute(sql)
    return cur.fetchall()

db = MySQLdb.connect(host="localhost",
                     user="dwalto76_admin",
                     passwd="PASSWORD",
                     db="dwalto76_lego")
db.autocommit(True)
cur = db.cursor()

set_inventory_directory = '/var/www/lego/tools/set-inventories/'

valid_set_ids = {}
sql = "SELECT id FROM sets"
results = sql_select(sql)
for row in results:
    valid_set_ids[row[0]] = True

for set_id in sorted(os.listdir('/var/www/lego/html/sets/')):
    if set_id in valid_set_ids:
        print "  VALID: %s" % set_id
    else:
        print "INVALID: %s" % set_id
        shutil.rmtree('/var/www/lego/html/sets/%s' % set_id)
