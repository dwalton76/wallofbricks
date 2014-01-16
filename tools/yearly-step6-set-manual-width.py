#!/usr/bin/python

import csv
import MySQLdb
import sys
import re
import os
from PIL import Image

def sql_select(sql):
    cur.execute(sql)
    return cur.fetchall()

def sql_do(sql):
    cur.execute(sql)


db = MySQLdb.connect(host="localhost",
                     user="dwalto76_admin",
                     passwd="PASSWORD",
                     db="dwalto76_lego")
db.autocommit(True)
cur = db.cursor()

set_inventory_directory = '/var/www/lego/tools/set-inventories/'

sql = "SELECT id, filename, description FROM sets_manual WHERE width=0 AND height=0"
sql_results = sql_select(sql)
for row in sql_results:
    set_id = row[0]
    description = row[2]
    directory = "/var/www/lego/html/sets/%s/" % set_id

    result = re.search('(.*)\.pdf', row[1])
    if not result:
        continue
    filename_minus_pdf = result.group(1)
    last_page = 0
    width = 0
    height = 0

    for filename in os.listdir(directory):
        if filename.startswith(filename_minus_pdf) and filename.endswith('.jpg'):
            #print filename
            result = re.search('-(\d+).jpg', filename)

            if result:
                current_page = int(result.group(1))

                if current_page > last_page:
                    last_page = current_page
            
                full_filename = "%s%s" % (directory, filename)

                try:
                    im = Image.open(full_filename)
                    (im_width, im_height) = im.size
        
                    if im_width > width:
                        width = im_width
        
                    if im_height > height:
                        height = im_height
                
                except:
                    pass

    print "%s %d pages, %dx%d" % (set_id, last_page, width, height)
    if width or height or last_page:
        sql = "UPDATE sets_manual SET pages=%d, width=%d, height=%d WHERE id='%s' AND description = '%s' " % (last_page, width, height, set_id, description)
        #print sql
        sql_do(sql)
        #sys.exit(0)

