#!/usr/bin/python

'''
Go to brickset and download the csv file for all of the sets for that year:
http://brickset.com/sets

- Save it as YEAR-brickset-sets.csv
- Add the year to the for loop below
- Run and poof the sets (but not the inventories) have been added
'''

import csv
import MySQLdb
import sys

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

for year in (2013, 2014):
    filename = '%d-brickset-sets.csv' % year
    with open(filename, 'rb') as csvfile:
        legosets = csv.reader(csvfile, delimiter=',', quotechar='"')

        # SetID,Number,Variant,Theme,Subtheme,Year,Name,Minifigs,Pieces,UKPrice,USPrice,CAPrice,EUPrice,ImageURL,Owned,Wanted,QtyOwned
        set = {}
        for row in legosets:
            #print ', '.join(row)
            set_id   = '%s-%d' % (row[1], int(row[2]))
            theme    = row[3]
            subtheme = row[4]
            year     = int(row[5])
            name     = row[6]

            if row[7]:
                minifigs = int(row[7])
            else:
                minifigs = 0

            if row[8]:
                pieces = int(row[8])
            else:
                pieces = 0

            if row[10]:
                price = int(float(row[10]) * 100)
            else:
                price = 0

            sql = "INSERT IGNORE INTO sets (id, name, pieces, minifigs, price, year, theme, subtheme) "\
                  "VALUES ('%s', \"%s\", %d, %d, %d, %d, \"%s\", \"%s\")" % (set_id, name, pieces, minifigs, price, year, theme, subtheme)

            #print sql
            sql_do(sql)
            #sys.exit(0)
