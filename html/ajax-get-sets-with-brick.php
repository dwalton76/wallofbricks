<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';
include 'include/functions.php';

$dbh = dbConnect();

$id = $_POST['id'];

$page = 0;
if (array_key_exists("page", $_POST)) {
    $page = $_POST['page'] - 1;
}

$last_page = 99;
if (array_key_exists("last_page", $_POST)) {
    $last_page = $_POST['last_page'];
}

#
# Find sets that use this brick
#
$query = "SELECT sets.id, sets.name, brick_quantity ".
         "FROM sets_inventory ".
         "INNER JOIN sets ON sets_inventory.id = sets.id ".
         "WHERE brick_id='$id' ".
         "ORDER BY brick_quantity DESC, sets.pieces DESC ";
$query .= sprintf("LIMIT %d, 12", $page * 12);
#print "SQL: $query<br>\n";
$sth = $dbh->prepare($query);
$sth->execute();
while ($row = $sth->fetch()) {
    $set = array();
    $set['id']         = $row[0];
    $set['name']       = $row[1];
    $set['qty_bricks'] = $row[2];
    $id = $set['id'];
    $set['img-tn']     = "/sets/$id/lego-$id-small.jpg";
    displaySetDiv($set);
}

if ($page > 0) {
    print "<span class='hidden' id='show-prev-button'>1</span>\n";
}

if ($page < $last_page - 1) {
    print "<span class='hidden' id='show-next-button'>1</span>\n";
}

return 1;
