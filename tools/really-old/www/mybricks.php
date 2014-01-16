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

$page = 1;
if (array_key_exists('page', $_GET)) {
   $page = $_GET['page'];
}

$all_or_mine = "My Bricks";
if (array_key_exists('all_or_mine', $_GET)) {
   $all_or_mine = $_GET['all_or_mine'];
}

$target_table = "sets_i_own";
if ($all_or_mine == "All Bricks") {
   $target_table = "sets";
}

# TODO: Print a color pick menu
# TODO: Give a way to see bricsk 11->20, 21->30, etc

print "<div id='left-column'>\n";
print "<form method='get' action='/mybricks.php'>\n";
print "<div id='search-for-foo'>\n";
print "<h2>Search For A Brick</h2>\n";
print "<label for='IDs_to_search'>ID</label>\n";
print "<input type='text' id='IDs_to_search' name='IDs_to_search' value='$IDs_to_search'>\n";
print "<br>\n";
print "<label for='desc_to_search'>Keywords</label>\n";
print "<input type='text' id='desc_to_search' name='desc_to_search' value='$desc_to_search'><br>\n";

print "<label for='all_bricks'>All Bricks</label>\n";
if ($all_or_mine == "All Bricks") {
   print "<input type='radio' id='all_bricks' name='all_or_mine' value='All Bricks' checked='checked'><br>\n";
} else {
   print "<input type='radio' id='all_bricks' name='all_or_mine' value='All Bricks'><br>\n";
}


print "<label for='my_bricks'>My Bricks</label>\n";
if ($all_or_mine == "My Bricks") {
   print "<input type='radio' id='my_bricks' name='all_or_mine' value='My Bricks' checked='checked'>\n";
} else {
   print "<input type='radio' id='my_bricks' name='all_or_mine' value='My Bricks'>\n";
}

print "<br>\n";
print "<input type='submit'>\n";
print "</form>\n";
print "</div>\n";

/*
print "<div id='add-a-new-foo'>\n";
print "<h2>Add A New Brick</h2>\n";
print "<label for='new_brick_id'>New Brick ID</label>\n";
print "<input type='text' name='new_brick_id' size='4' value=''></input>\n";
print "<br>\n";
print "<label for='new_brick_id'>New Brick Color</label>\n";
print "<input type='text' name='new_brick_color' size='4' value=''></input>\n";
print "<br>\n";
print "<label for='new_brick_id'>New Brick Quantity</label>\n";
print "<input type='text' name='new_brick_qty' size='4' value=''></input>\n";
print "<br>\n";
print "<br>\n";
print "</div>\n";
*/
print "</div>\n";

#
# Handle any updated numbers the user submitted for the number of extra bricks
#
$dbh = dbConnect();
$query = "INSERT INTO bricks_extra(brick_id, brick_quantity) VALUE (?,?) ON DUPLICATE KEY UPDATE brick_quantity=?";
$sth = $dbh->prepare($query);
foreach ($_GET as $key=>$value) {
   if (preg_match("/qty-(\w+\-\d+)/", $key, $matches)) {
       $id = $matches[1];
       $qty = $value;

      $sth->bindParam(1, $id);
      $sth->bindParam(2, $qty);
      $sth->bindParam(3, $qty);
      $sth->execute();
   }
}

#
# Handle the "Add A New Brick" section 
#
if (isset($_GET["new_brick_id"]) && isset($_GET["new_brick_qty"]) &&
          $_GET["new_brick_id"]  &&       $_GET["new_brick_qty"]) {
   $query = "INSERT INTO bricks_extra(brick_id, brick_quantity) VALUE (?,?) ON DUPLICATE KEY UPDATE brick_quantity=?";
   $sth = $dbh->prepare($query);
   $sth->bindParam(1, $_GET["new_brick_id"]);
   $sth->bindParam(2, $_GET["new_brick_qty"]);
   $sth->bindParam(3, $_GET["new_brick_qty"]);
   $sth->execute();
}


#
# Find all of the bricks I own...limited to the search terms we pass getBricks
#
$columns = array();
array_push($columns, 'img');
array_push($columns, 'qty');
array_push($columns, 'extras');
array_push($columns, 'id');
array_push($columns, 'desc');

print "<div id='middle-column'>\n";
print "<table>\n";
print "<thead>\n";
displayBrickRow($dbh, 1, $columns);
print "</thead>\n";
print "<tbody>\n";
$bricks_per_page = 10;
$total_bricks = 0;
foreach (getBricks($target_table, $dbh, "", $IDs_to_search, $desc_to_search, $page, $bricks_per_page) as $brick) {
   displayBrickRow($dbh, 0, $columns, $brick);
   $total_bricks = $brick['rows_count'];
}

print "</tbody>\n";
print "<tfoot>\n";
print "</tfoot>\n";
print "</table>\n";

$base_url = "/mybricks.php?all_or_mine=$all_or_mine";
if ($IDs_to_search) {
   $base_url .= "&IDs_to_search=$IDs_to_search";
}

if ($desc_to_search) {
   $base_url .= "&desc_to_search=$desc_to_search";
}
$base_url .= "&page=";

print "Total Bricks: $total_bricks<br>\n";
printPrevNextLinks(10, 0, $total_bricks, $bricks_per_page, $base_url, $page, 1);

print "</div>\n";

printHTMLFooter();
?>
