<?php

define('INCLUDE_CHECK',true);
include "include/functions.php";
printHTMLHeader("", "");

$dbh = dbConnect();
$query = "DELETE FROM sets_wishlist WHERE id=?";
$sth = $dbh->prepare($query);
foreach ($_POST as $key=>$value) {
    if (preg_match("/delete-(\w+-\d+)/", $key, $matches)) {
      $id = $matches[1];
      $sth->bindParam(1, $id);
      $sth->execute();
   }
}


$columns = array();
array_push($columns, 'img');
array_push($columns, 'id');
array_push($columns, 'name');
array_push($columns, 'percent_complete');
array_push($columns, 'cost_to_complete');
array_push($columns, 'delete_form');

print "<table>\n";
print "<thead>\n";
displaySetRow($dbh, 1, $columns, $set);
print "</thead>\n";
print "<tbody>\n";

/*
TODO: I need a new page to display these
$query = "SELECT sets_i_can_build.id, img, name, `img-tn` ".
         "FROM sets_i_can_build ".
         "INNER JOIN sets ON sets_i_can_build.id = sets.id";
$sth = $dbh->prepare($query);
$sth->execute();
while ($row = $sth->fetch()) {
   $set = array();
   $set['id']               = $row[0];
   $set['percent_complete'] = 100;
   $set['cost_to_complete'] = 0;
   $set['img']              = $row[1];
   $set['name']             = $row[2];
   $set['img-tn']           = $row[3];
   displaySetRow($dbh, 0, $columns, $set);
}
*/

$query = "SELECT sets_wishlist.id, percent_complete, cost_to_complete, img, name, `img-tn` ".
         "FROM sets_wishlist ".
         "INNER JOIN sets_progress ON sets_wishlist.id = sets_progress.id ".
         "INNER JOIN sets ON sets_wishlist.id = sets.id ".
         "ORDER BY cost_to_complete ASC";
#print "SQL: $query\n";
$sth = $dbh->prepare($query);
$sth->execute();

while ($row = $sth->fetch()) {
   $set = array();
   $set['id']               = $row[0];
   $set['percent_complete'] = $row[1];
   $set['cost_to_complete'] = $row[2];
   $set['img']              = $row[3];
   $set['name']             = $row[4];
   $set['img-tn']           = $row[5];
   displaySetRow($dbh, 0, $columns, $set);
}

print "</tbody>\n";
print "</table>\n";

printHTMLFooter();
?>
