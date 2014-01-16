#!/usr/bin/python

import json
import os
import MySQLdb
import sys
from subprocess import call

def sql_select(sql):
    cur.execute(sql)
    return cur.fetchall()

def sql_do(sql):
    cur.execute(sql)

store_id = int(sys.argv[1])
if not store_id:
    sys.exit()

filename = 'brickowl-store-%d.json' % store_id
if os.path.exists(filename):
    os.remove(filename)

# Download the store_id's inventory json file
if not os.path.exists(filename):
    call(["wget","--no-check-certificate", "-O", filename, "https://api.brickowl.com/v1/affiliate/lots?key=856c04b26b417a7e2f2155406f26b07c7bcdb069017a22a9b20f56207127fb79&store_id=%d" % store_id])
    print "\n\nwget --no-check-certificate -O %s https://api.brickowl.com/v1/affiliate/lots?key=856c04b26b417a7e2f2155406f26b07c7bcdb069017a22a9b20f56207127fb79&store_id=%d\n\n" % (filename, store_id)


db = MySQLdb.connect(host="localhost",
                     user="dwalto76_admin",
                     passwd="PASSWORD",
                     db="dwalto76_lego")
db.autocommit(True)
cur = db.cursor()

# Read the json data into 'stores'
store_list_filename = 'brickowl-stores.json'
f = open(store_list_filename, 'r')
stores = json.loads(f.read())
f.close()

# Create a www_store row
currency = None
for store in stores:
    if int(store['store_id']) != store_id:
        continue

    logo = store['square_logo_24']
    if (logo == 'http:'):
        logo = 'NULL'
    else:
        logo = "'" + logo + "'"

    currency = store['base_currency']
    sql = "INSERT IGNORE INTO www_stores (site_name, site_id, name, currency, country, logo, url, minimum_order) VALUES ('brickowl', %d, '%s', '%s', '%s', %s, '%s', '%s')" % (store_id, store['name'], store['base_currency'], store['country'], logo, store['url'], store['minimum_order'])
    sql_do(sql)


# Get the sql ID for this store
sql = "SELECT id FROM www_stores WHERE site_name='brickowl' and site_id=%d" % store_id
store_sql_id = None
for row in sql_select(sql):
    store_sql_id = row[0]

# Nuke the www_store_inventory data for this store
sql = "DELETE FROM www_store_inventory WHERE store_id = %d" % store_sql_id
sql_do(sql)


# We already downloaded the store inventory for each store, load that
# data into www_store_inventory
sql = "INSERT INTO www_store_inventory (store_id, lot_id, lego_id, boid, qty, cond, price, url) VALUES "
filename = 'brickowl-store-%d.json' % store_id
f = open(filename, 'r')
items = json.loads(f.read())
f.close()

found_parts = False
for item in items:
    if (item['type'] != 'Part'):
        continue

    # Extract the LEGO IDs
    found_parts = True
    lego_ids = {}
    part_id_type_tuple = item['ids']
    for x in part_id_type_tuple:
        if (x['type'] == 'item_no'):
            lego_ids[int(x['id'])] = True

    # Convert the price to USD
    price = float(item['price'])
    if currency == 'GBP':
        price = price * 1.67
    elif currency == 'CAD':
        price = price * 0.91
    elif currency == 'EUR':
        price = price * 1.38
    elif currency == 'PLN':
        price = price * 0.33

    # Now convert to cents
    price = price * 100

    url = item['url'] + "?utm_source=wallofbricks"

    # We multiply the price by 100 to convert to cents
    for lego_id in lego_ids:
            sql += "(%d,%d,%d,'%s',%d,'%s','%s','%s')," % (store_sql_id, int(item['lot_id']), lego_id, item['boid'], int(item['qty']), item['con'], price, url)

if (found_parts):
    sql = sql[:-1]
    sql_do(sql)

