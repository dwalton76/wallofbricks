<?php
define('INCLUDE_CHECK',true);
include "include/functions.php";
printHTMLHeader("", "");

$id = "2780-11";
if (array_key_exists('id', $_GET)) {
   $id = $_GET['id'];
}

$dbh = dbConnect();
$query = "SELECT description, img, price, type FROM bricks WHERE id=?";
$sth = $dbh->prepare($query);
$sth->bindParam(1, $id);
$sth->execute();
$row   = $sth->fetch();
$desc  = $row[0];
$img   = $row[1];
$price = $row[2];
$type  = $row[3];

$bricks_owned = getBricks("sets_i_own", $dbh, $id, "", "", "");
$brick = $bricks_owned[0];

print "<div class='display-brick'>\n";
print "<div>\n";
print "<h1>$desc</h1>\n";
print "<img src='$img' /><br>\n";
print "<h2>Basics</h2>\n";
print "ID: $id<br>\n";
print "</div>\n";

if (isset($brick['qty']) || isset($brick['extras'])) {
   print "<div>\n";
   print "<h2>Inventory</h2>\n";
   if (isset($brick['qty'])) {
      printf("From Sets: %s<br>\n", $brick['qty']);
   }
   $qty_i_own = $brick['qty'];
   if (isset($brick['extras'])) {
      printf("Extras: %s<br>\n", $brick['extras']);
      printf("Total: %s<br>\n", $brick['qty'] + $brick['extras']);
      $qty_i_own = $brick['qty'] + $brick['extras'];
   }
   print "</div>\n";
}

$columns = array();
array_push($columns, 'img');
array_push($columns, 'id');
array_push($columns, 'name');
array_push($columns, 'qty_bricks');

#
# Find all of the sets you own that have this brick
#
if (isset($brick['qty'])) {
   $query = "SELECT sets_i_own.id, sets.name, sets.img, brick_quantity, `sets`.`img-tn` ".
            "FROM sets_i_own ".
            "INNER JOIN sets_inventory ON sets_inventory.id = sets_i_own.id ".
            "INNER JOIN sets ON sets.id = sets_i_own.id ".
            "INNER JOIN bricks ON brick_id = bricks.id ".
            "WHERE brick_id='$id' ".
            "ORDER BY `sets_inventory`.`brick_quantity` DESC";
   #print "SQL: $query<br>\n";
   $sth = $dbh->prepare($query);
   $sth->execute();
   print "<div>\n";
   print "<h2>Your Sets With This Brick</h2>\n";
   print "<table>\n";
   print "<thead>\n";
   displaySetRow($dbh, 1, $columns);
   print "</thead>\n";
   print "<tbody>\n";
   while ($row = $sth->fetch()) {
      $set = array();
      $set['id']         = $row[0];
      $set['name']       = $row[1];
      $set['img']        = $row[2];
      $set['qty_bricks'] = $row[3];
      $set['img-tn']     = $row[4];
      displaySetRow($dbh, 0, $columns, $set);
   }
   print "</tbody>\n";
   print "</table>\n";
   print "</div>\n";
}

#
# Find the Top 10 wishlist sets that use this brick
#
$query = "SELECT sets.id, sets.name, sets.img, brick_quantity, `sets`.`img-tn` ".
         "FROM sets_wishlist ".
         "INNER JOIN sets ON sets_wishlist.id = sets.id ".
         "INNER JOIN sets_inventory ON sets_inventory.id = sets.id ".
         "INNER JOIN bricks ON brick_id = bricks.id ".
         "WHERE brick_id='$id' ".
         "ORDER BY `sets_inventory`.`brick_quantity` DESC ";
#print "SQL: $query<br>\n";
$sth = $dbh->prepare($query);
$sth->execute();
print "<div>\n";
print "<h2>Top 10 Users - Wishlist Sets</h2>\n";
print "<table>\n";
print "<thead>\n";
displaySetRow($dbh, 1, $columns);
print "</thead>\n";
print "<tbody>\n";
$i = 0;
$set_stats = array();
while ($row = $sth->fetch()) {
   $set = array();
   $set['id'] = $row[0];
   $set['name'] = $row[1];
   $set['img'] = $row[2];
   $set['qty_bricks'] = $row[3];
   $set['img-tn'] = $row[4];
   array_push($set_stats, $set);
   if (++$i <= 10) {
      displaySetRow($dbh, 0, $columns, $set);
   }
}
print "</tbody>\n";
print "</table>\n";
print "</div>\n";


#
# Find the Top 10 sets that use this brick
#
$query = "SELECT sets.id, sets.name, sets.img, brick_quantity, sets.`img-tn` ".
         "FROM sets ".
         "INNER JOIN sets_inventory ON sets_inventory.id = sets.id ".
         "INNER JOIN bricks ON brick_id = bricks.id ".
         "WHERE brick_id='$id' ".
         "AND name NOT LIKE '% set' AND name NOT LIKE '% pack' AND name NOT LIKE '%pack of%'".
         "ORDER BY `sets_inventory`.`brick_quantity` DESC ";
#print "SQL: $query<br>\n";
$sth = $dbh->prepare($query);
$sth->execute();
print "<div>\n";
print "<h2>Top 10 Users - All Lego Sets</h2>\n";
print "<table>\n";
print "<thead>\n";
displaySetRow($dbh, 1, $columns);
print "</thead>\n";
print "<tbody>\n";
$i = 0;
$set_stats = array();
while ($row = $sth->fetch()) {
   $set = array();
   $set['id'] = $row[0];
   $set['name'] = $row[1];
   $set['img'] = $row[2];
   $set['qty_bricks'] = $row[3];
   $set['img-tn'] = $row[4];
   array_push($set_stats, $set);
   if (++$i <= 10) {
      displaySetRow($dbh, 0, $columns, $set);
   }
}
print "</tbody>\n";
print "</table>\n";
print "</div>\n";

#
# How figure out how many sets used 1 of this brick, 2 of this brick, etc
# Draw a graph of this with flot
#
   $qty_count = array();
   foreach ($set_stats as $set) {
      #if ($set['id'] != $id) {
      #   continue;
      #}
      $set_id   = $set['set_id'];
      $set_name = $set['name'];
      $set_qty  = $set['qty_bricks'];
      $qty_count[$set_qty]++;
   }

   ksort($qty_count);
   $qty_data_set_string = "";
   $max_height = 0;
   foreach ($qty_count as $key=>$value) {
      if ($qty_data_set_string != "") {
         $qty_data_set_string .= "::";
         $qty_i_own_string .= "::";
      }

      $qty_data_set_string .= "$key:$value";
      if ($value > $max_height) {
         $max_height = $value;
      }
   }

   print "<div class='flot-wrapper' pid='$id' raw_data='$qty_data_set_string' raw_data2='$qty_i_own:$max_height'>\n".
         "<div id='flot-graph-$id'  style='width:500px;height:300px'></div>".
         "</div>";

print "</div>\n";
printHTMLFooter();
?>
