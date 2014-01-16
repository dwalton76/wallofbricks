<?php

define('INCLUDE_CHECK',true);
include "include/functions.php";
printHTMLHeader("", "");
$dbh = dbConnect();

$columns = array();
array_push($columns, 'img');
array_push($columns, 'id');
array_push($columns, 'name');
array_push($columns, 'price');
array_push($columns, 'percent_complete');
array_push($columns, 'cost_to_complete');
array_push($columns, 'delete_form');

#print "<table>\n";
#print "<thead>\n";
#displaySetRow($dbh, 1, $columns, $set);
#print "</thead>\n";
#print "<tbody>\n";

$query = "SELECT sets_progress.id,  img, name, `img-tn`, year, min_age, max_age, price, pieces, percent_complete, cost_to_complete, (SELECT COUNT(sets_i_own.id) FROM sets_i_own WHERE sets_i_own.id = sets_progress.id LIMIT 1) as i_own_it ".
         "FROM `sets_progress` ".
         "WHERE `percent_complete`='100'";

$position = "left";
$sth = $dbh->prepare($query);
$sth->execute();
while ($row = $sth->fetch()) {
   $set = array();
   $set['id']      = $row[0];
   $set['img']     = $row[1];
   $set['name']    = $row[2];
   $set['img-tn']  = $row[3];
   $set['year']    = $row[4];
   $set['min_age'] = $row[5];
   $set['max_age'] = $row[6];
   $set['price']   = $row[7];
   $set['pieces']  = $row[8];
   $set['percent_complete']  = $row[9];
   $set['cost_to_complete']  = $row[10];
   displaySetDiv($dbh, 0, $columns, $set, $position);
      if ($position == "left") {
         $position = "middle";
      } else if ($position == "middle") {
         $position = "right";
      } else if ($position == "right") {
         $position = "left";
      }
}

$query = "SELECT sets.id, img, name, `img-tn`, year, min_age, max_age, price, pieces, percent_complete, cost_to_complete, (cost_to_complete/price) as foo ".
         "FROM `sets_progress` ".
         "INNER JOIN sets ON sets_progress.id = sets.id ".
         "WHERE percent_complete < 100 AND cost_to_complete != 0 AND cost_to_complete < price AND sets.id NOT LIKE 'comcon%' ".
         "ORDER BY `foo` ASC";
#print "SQL: $query\n";
$sth = $dbh->prepare($query);
$sth->execute();
while ($row = $sth->fetch()) {
   $set = array();
   $set['id']      = $row[0];
   $set['img']     = $row[1];
   $set['name']    = $row[2];
   $set['img-tn']  = $row[3];
   $set['year']    = $row[4];
   $set['min_age'] = $row[5];
   $set['max_age'] = $row[6];
   $set['price']   = $row[7];
   $set['pieces']  = $row[8];
   $set['percent_complete']  = $row[9];
   $set['cost_to_complete']  = $row[10];
   displaySetDiv($dbh, 0, $columns, $set, $position);
      if ($position == "left") {
         $position = "middle";
      } else if ($position == "middle") {
         $position = "right";
      } else if ($position == "right") {
         $position = "left";
      }
}
#print "</tbody>\n";
#print "</table>\n";

printHTMLFooter();
?>
