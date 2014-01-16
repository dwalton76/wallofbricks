<?php
define('INCLUDE_CHECK',true);
include "include/functions.php";
printHTMLHeader("", "");
$debug = 0;

$IDs_to_search = "";
if (array_key_exists('IDs_to_search', $_GET)) {
   $IDs_to_search = $_GET['IDs_to_search'];
}


$desc_to_search = "";
if (array_key_exists('desc_to_search', $_GET)) {
   $desc_to_search = $_GET['desc_to_search'];
}

print "<div id='left-column'>\n";
print "<form method='get' action='/mysets.php'>\n";
print "<div id='search-for-foo'>\n";
print "<h2>Search For A Set</h2>\n";
print "<label for='IDs_to_search'>ID</label>\n";
print "<input type='text' id='IDs_to_search' name='IDs_to_search' value='$IDs_to_search'>\n";
print "<br>\n";
print "<label for='desc_to_search'>Description</label>\n";
print "<input type='text' id='desc_to_search' name='desc_to_search' value='$desc_to_search'>\n";
print "<br>\n";
print "<br>\n";
print "</div>\n";

print "<div id='add-a-new-foo'>\n";
print "<h2>Add A New Set</h2>\n";
print "<label for='new_set_id'>New Set ID</label>\n";
print "<input type='text' name='new_set_id' size='4' value=''></input>\n";
print "<br>\n";
print "<input type='submit' value='Submit'>\n";
print "</div>\n";
print "</div>\n";

#
# Handle any updated numbers the user submitted for the number of extra bricks
#
$dbh = dbConnect();
$query = "INSERT INTO sets_i_own(id, quantity) VALUE (?,?) ON DUPLICATE KEY UPDATE quantity=?";
$sth = $dbh->prepare($query);

$delete_query = "DELETE FROM sets_i_own WHERE id=?";
$delete_sth = $dbh->prepare($delete_query);
foreach ($_GET as $key=>$value) {
   if (preg_match("/qty-(\w+-\d+)/", $key, $matches)) {
      $id = $matches[1];
      $qty = $value;
      # TODO: fix this so it doesn't update everytime
      # print "UDPATE $id-$version to $qty<br>\n";

      $sth->bindParam(1, $id);
      $sth->bindParam(2, $qty);
      $sth->bindParam(3, $qty);
      $sth->execute();
   } else if (preg_match("/delete-(\w+-\d+)/", $key, $matches)) {
      $id = $matches[1];
      $delete_sth->bindParam(1, $id);
      $delete_sth->execute();
   }
}

#
# Handle the "Add A New FOO" section 
#
if (isset($_GET["new_set_id"]) && $_GET["new_set_id"]) {
   $query = "INSERT INTO sets_i_own(id, quantity) VALUE (?,1) ON DUPLICATE KEY UPDATE quantity = quantity + 1";
   $sth = $dbh->prepare($query);
   $id =  $_GET["new_set_id"];
   if (!preg_match("/\w+-\d+)/", $id, $matches)) {
      $id = $id . "-1";
   }

   $sth->bindParam(1, $id);
   $sth->execute();
}

$columns = array();
array_push($columns, 'img');
array_push($columns, 'id');
array_push($columns, 'name');
array_push($columns, 'qty_no_edit');
array_push($columns, 'delete');
print "<div id='middle-column'>\n";
#print "<table>\n";
#print "<thead>\n";
#displaySetRow($dbh, 1, $columns);
#print "</thead>\n";
#print "<tbody>\n";
foreach (getMySets($dbh, $IDs_to_search, $desc_to_search) as $set) {
   # displaySetRow($dbh, 0, $columns, $set);
   displaySetDiv($dbh, 0, $columns, $set, "middle");
}

#print "</tbody>\n";
#print "<tfoot>\n";
#print "</tfoot>\n";
#print "</table>\n";
#print "</form>\n";
print "</div>\n";

printHTMLFooter();
?>
