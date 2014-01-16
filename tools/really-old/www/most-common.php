<?php
define('INCLUDE_CHECK',true);
include "include/functions.php";
printHTMLHeader("", "");
$debug = 0;

/*
 * NOTE:
 * This is no longer used.  Most of the functionality here has been moved into the bricks.php page
 */

$category = 65;
# 36 = Technic
# 65 = Star Wars
# 771 = Friends


#
# Get a list of bricks that are used the most by sets in $category
#
$query = "SELECT sets.id, sets.name, brick_id, brick_color, COUNT(brick_quantity), MAX(brick_quantity), MIN(brick_quantity), bricks.img, bricks.description, bricks.price, bricks.type ".
         "FROM sets ".
         "INNER JOIN sets_inventory ON sets_inventory.id = sets.id ".
         "INNER JOIN bricks ON brick_id = bricks.id AND (brick_color = bricks.color OR (brick_color IS NULL AND bricks.color IS NULL)) ".
         "WHERE category=$category ".
         "GROUP BY brick_id, brick_color ORDER BY COUNT(brick_quantity) DESC ".
         "LIMIT 0, 10";
print "SQL: $query<br>\n";
$dbh = dbConnect();
$sth = $dbh->prepare($query);
$sth->execute();
$popular_bricks = array();
$IDs_to_search_array= array();
while ($row = $sth->fetch()) {
   $brick          = array();
   $brick['id']    = $row[2];
   $brick['color'] = $row[3];
   $brick['total'] = $row[4];
   $brick['max']   = $row[5];
   $brick['min']   = $row[6];
   $brick['img']   = $row[7];
   $brick['desc']  = $row[8];
   $brick['price'] = $row[9];
   $brick['type']  = $row[10];

   array_push($IDs_to_search_array, $brick['id']);
   array_push($popular_bricks, $brick);
}

#
# Pull our inventory so we can see how many we have of each
#
$brick_index = array();
$IDs_to_search = implode(",", $IDs_to_search_array);
foreach (getMyBricks($dbh, $IDs_to_search, "") as $brick) {
   $id = $brick['id'];
   $color = $brick['color'];
   $brick_index[$id][$color] = $brick;
}

#
# This will show you which set used the Max number of this brick
#
$set_stats = array();
foreach ($popular_bricks as $brick) {
   $id = $brick['id'];
   $color = $brick['color'];
   $query = "SELECT sets.id, sets.name,  brick_quantity ".
             "FROM sets ".
             "INNER JOIN sets_inventory ON sets_inventory.id = sets.id ".
             "INNER JOIN bricks ON brick_id = bricks.id AND (brick_color = bricks.color OR (brick_color IS NULL AND bricks.color IS NULL)) ".
             "WHERE category=$category AND brick_id='$id' AND brick_color='$color' ORDER BY `sets_inventory`.`brick_quantity` DESC";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $set_info = array();
      $set_info['id'] = $id;
      $set_info['set_id'] = $row[0];
      $set_info['name'] = $row[1];
      $set_info['qty']  = $row[2];
      array_push($set_stats, $set_info);
   }
}

$columns = array();
array_push($columns, 'img');
array_push($columns, 'id');
array_push($columns, 'set_total');
array_push($columns, 'desc');
array_push($columns, 'price');
array_push($columns, 'cost_for_max');
array_push($columns, 'set_graph');

print "<table>\n";
print "<thead>\n";
displayBrickRow($dbh, 1, $columns);
print "</thead>\n";
print "<tbody>\n";
foreach ($popular_bricks as $brick) {
   $id = $brick['id'];
   $color = $brick['color'];
   $mybrick = $brick_index[$id][$color];

   $shortage = 0;
   $qty_i_own = $mybrick['qty'] + $mybrick['extras'];
   if ($brick['max'] > $qty_i_own) {
      $shortage = $brick['max'] - $qty_i_own;
   }
   $brick['cost_for_max'] = $brick['price'] * $shortage;

   $qty_count = array();
   foreach ($set_stats as $set_info) {
      if ($set_info['id'] != $id) {
         continue;
      } 
      $set_id   = $set_info['set_id']; 
      $set_name = $set_info['name']; 
      $set_qty  = $set_info['qty']; 
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

   $brick['set_graph'] = "<td class='flot-wrapper' pid='$id-$color' raw_data='$qty_data_set_string' raw_data2='$qty_i_own:$max_height'>\n".
                         "<div id='flot-graph-$id-$color'  style='width:300px;height:150px'></div>".
                         "</td>";
   displayBrickRow($dbh, 0, $columns, $brick);
}

print "</tbody>\n";
print "<tfoot>\n";
print "</tfoot>\n";
print "</table>\n";

printHTMLFooter();
?>
