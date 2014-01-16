#!/usr/bin/python

'''
Update the set_inventory table for all sets
'''

import csv
import MySQLdb
import os
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
set_inventory_directory = '/var/www/lego/tools/set-inventories/'

sql = "DELETE FROM bricks"
print sql
sql_do(sql)

for set_id in sorted(os.listdir(set_inventory_directory)):

    if set_id.endswith('.txt'):
        continue

    full_filename = '%s%s' % (set_inventory_directory, set_id)
    with open(full_filename, 'rb') as csvfile:
        set_inventory = csv.reader(csvfile, delimiter=',', quotechar='"')

        # SetNumber,PartID,Quantity,Colour,Category,DesignID,PartName,ImageURL,SetCount
        # "30111-1",4113209,1,"Black","Animal And Accessories For Animals",30238,"Spider","http://cache.lego.com/media/bricks/5/1/4113209.jpg",61
        # "30111-1",4157659,1,"Black","Figure, Hair",40233,"Harry Potters Wig","http://cache.lego.com/media/bricks/5/1/4157659.jpg",46

        lines_to_add = []
        bricks_to_add = []
        for row in set_inventory:

            if not len(row) or row[0] == 'SetNumber':
                continue

            try:
                color = row[3]
            except:
                print row
                print "shit"
                sys.exit(1)

            if color == 'Brick-Yel':
                color = 'Brick Yellow'

            elif color == 'Br. Purple':
                color = 'Bright Purple'

            elif color == 'Br. Red. Lilac':
                color = 'Bright Reddish Lilac'

            elif color == 'Br. Violet':
                color = 'Bright Violet'

            elif color == 'Br.Blue':
                color = 'Bright Blue'

            elif color == 'Br.Bluegreen':
                color = 'Bright Bluish Green'

            elif color == 'Br.Blueviol':
                color = 'Bright Bluish Violet'

            elif color == 'Br.Green':
                color = 'Bright Green'

            elif color == 'Br.Orange':
                color = 'Bright Orange'

            elif color == 'Br.Red':
                color = 'Bright Red'

            elif color == 'Br.Red-Viol.':
                color = 'Bright Reddish Violet'

            elif color == 'Br.Red.Orang':
                color = 'Bright Reddish Orange'

            elif color == 'Br.Yel':
                color = 'Bright Yellow'

            elif color == 'Br.Yel-Green':
                color = 'Bright Yellowish Green'

            elif color == 'Br.Yelora':
                color = 'Bright Yellow'

            elif color == 'Cool Yel.':
                color = 'Cool Yellow'

            elif color == 'Dk. Brown':
                color = 'Dark Brown'

            elif color == 'Dk. R.Blue':
                color = 'Dark Royal Blue'

            elif color == 'Dk. St. Grey':
                color = 'Dark Stone Grey'

            elif color == 'Dk.Green':
                color = 'Dark Green'

            elif color == 'Dk.Grey':
                color = 'Dark Gray'

            elif color == 'Dk.Ora':
                color = 'Dark Orange'

            elif color == 'Do. Blue':
                color = 'Dove Blue'

            elif color == 'Earth-Ora':
                color = 'Earth Orange'

            elif color == 'Fl. Yell-Ora':
                color = 'Flame Yellowish Orange'

            elif color == 'L.Blue':
                color = 'Light Blue'

            elif color == 'L.Blue-Green':
                color = 'Light Bluish Green'

            elif color == 'L.Blueviol':
                color = 'Light Bluish Violet'

            elif color == 'L.Green':
                color = 'Light Green'

            elif color == 'L.Grey':
                color = 'Light Grey'

            elif color == 'L.Nougat':
                color = 'Light Nougat'

            elif color == 'L.Orabrown':
                color = 'Light Orange Brown'

            elif color == 'L.Redviol':
                color = 'Light Reddish Violet'

            elif color == 'L.Yel':
                color = 'Light Yellow'

            elif color == 'L.Yel-Green':
                color = 'Light Yellowish Green'

            elif color == 'Lgh. Lilac':
                color = 'Light Lilac'

            elif color == 'Lgh. Purple':
                color = 'Light Purple'

            elif color == 'Lgh. Roy. Blue':
                color = 'Light Royal Blue'

            elif color == 'Lgh. St. Grey':
                color = 'Light Stone Grey'

            elif color == 'M. Lilac':
                color = 'Medium Lilac'

            elif color == 'M. Nougat':
                color = 'Medium Nougat'

            elif color == 'Md.Bl-Green':
                color = 'Medium Bluish Green'

            elif color == 'Md.Blue':
                color = 'Medium Blue'

            elif color == 'Md.Green':
                color = 'Medium Green'

            elif color == 'Md.Redviol':
                color = 'Medium Reddish Violet'

            elif color == 'Md.Yel-Green':
                color = 'Medium Yellowish Green'

            elif color == 'Med. St-Grey':
                color = 'Medium Stone Grey'

            elif color == 'Met. Br. Blue':
                color = 'Metallic Bright Blue'

            elif color == 'Met. Br. Red':
                color = 'Metallic Bright Red'

            elif color == 'Met. Dk. Green':
                color = 'Metallic Dark Green'

            elif color == 'Met. Ear.Ora':
                color = 'Metallic Earth Orange'

            elif color == 'Met. White':
                color = 'Metallic White'

            elif color == 'Met.Black':
                color = 'Metallic Black'

            elif color == 'Met.Dk.Grey':
                color = 'Metallic Dark Grey'

            elif color == 'Met.Sand.Blu':
                color = 'Metallic Sand Blue'

            elif color == 'Met.Sand.Yel':
                color = 'Metallic Sand Yellow'

            elif color == 'Ml.Blueviol':
                color = 'Medium Bluish Violet'

            elif color == 'Pastblu':
                color = 'Pastel Blue'

            elif color == 'Ph.Green':
                color = 'Phosph.Green'

            elif color == 'Phos.White':
                color = 'Phosphorescent White'

            elif color == 'R.Blue':
                color = 'Royal Blue'

            elif color == 'Red. Brown':
                color = 'Reddish Brown'

            elif color == 'Red. Gold':
                color = 'Reddish Gold'

            elif color == 'Red. Lilac':
                color = 'Reddish Lilac'

            '''
            elif color == '':
                color = ''

            elif color == '':
                color = ''

            elif color == '':
                color = ''
            '''

            lines_to_add.append("('%s', %s, %s)" % (row[0], row[1], row[2]))
            bricks_to_add.append("('%s', '%s', '%s', '%s', '%s')" % (row[1], row[5], row[6], row[4], color))

        # Brickset doesn't have inventory for every thing (most though) so
        # some of the files will only have the SetNumber line
        if lines_to_add:
            sql = "DELETE FROM sets_inventory WHERE id='%s'" % set_id
            print sql
            sql_do(sql)

            sql = "INSERT INTO sets_inventory (id, brick_id, brick_quantity) VALUES %s" % ','.join(lines_to_add)
            sql_do(sql)

            sql = "INSERT IGNORE INTO bricks (`id`, `design_id`, `description`, `type`, `color`) VALUES %s" % ','.join(bricks_to_add)
            #print sql
            sql_do(sql)
            #sys.exit(1)

