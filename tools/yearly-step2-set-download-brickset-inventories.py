#!/usr/bin/python

'''
Once a year (after running yearly-parse-brickset-sets-csv.py) run this script and:
- save the output to sets-inventories/wget_list.txt
- cd to sets-inventories
- wget -i wget_list.txt
We only download the ones from sets released >= 2010.
Odds are the inventories for the older ones haven't changed at all.
'''

import csv
import MySQLdb
import sys

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

sql = "SELECT id FROM sets WHERE year >= 2010"
results = sql_select(sql)
for row in results:
    print 'http://brickset.com/exportscripts/inventory/%s' % row[0]
