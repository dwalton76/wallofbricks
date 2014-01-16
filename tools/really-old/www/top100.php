<?php

define('INCLUDE_CHECK',true);
include "include/functions.php";
printHTMLHeader("", "");
$dbh = dbConnect();

$columns = array();
array_push($columns, 'img');
array_push($columns, 'id');
array_push($columns, 'desc');
array_push($columns, 'price');
array_push($columns, 'reasonable_target_to_own');
array_push($columns, 'qty');

print "<table>\n";
print "<thead>\n";
displayBrickRow($dbh, 1, $columns, 0);
print "</thead>\n";
print "<tbody>\n";
$IDs_to_search = array();
$query = "SELECT id, description, img, price, type, used_in_sets, reasonable_target_to_own ".
         "FROM `bricks` ".
         "WHERE id != 'inv-0' AND description NOT LIKE '%duplo%' ".
         "ORDER BY `bricks`.`used_in_sets` DESC ".
         "LIMIT 0, 500"; 
#print "SQL: $query\n";
$total_cost = 0;
$sth = $dbh->prepare($query);
$sth->execute();
$bricks_qty_to_buy = 0;
while ($row = $sth->fetch()) {
   $brick = array();
   $brick['id'] = $row[0];
   $brick['desc'] = $row[1];
   $brick['img'] = $row[2];
   $brick['price'] = $row[3];
   $brick['type'] = $row[4];
   $brick['used_in_sets'] = $row[5];
   $brick['reasonable_target_to_own'] = $row[6];
   $brick['qty'] = getNumberIOwnOfBrick($dbh, $brick['id']);
   displayBrickRow($dbh, 0, $columns, $brick);
   # $brick[''] = $row[];
   #array_push($IDs_to_search, $id);

   if ($brick['qty'] < $brick['reasonable_target_to_own']) {
      $bricks_qty_to_buy += $brick['reasonable_target_to_own'] - $brick['qty'];
      $total_cost += $brick['price'] * ($brick['reasonable_target_to_own'] - $brick['qty']);
   #   $total_cost += $brick['price'] * $brick['reasonable_target_to_own'];
   }

}
print "</tbody>\n";
print "</table>\n";
printf("Total Cost: %s<br>\n", centsToPrice($total_cost));
printf("Bricks To Buy: %s<br>\n", $bricks_qty_to_buy);

printHTMLFooter();
?>
