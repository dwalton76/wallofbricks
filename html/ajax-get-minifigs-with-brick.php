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
# Find minifigs that use this brick
#
$query = "SELECT sets.type, sets_inventory.id, sets.name, ".
            "(SELECT img FROM bricks WHERE bricks.id = sets_inventory.id) AS minifig_img, ".
            "(SELECT sets.img_type FROM sets WHERE sets.id = sets_inventory.id) AS set_img ".
            "FROM bricks ".
            "INNER JOIN sets_inventory ON brick_id = bricks.id AND brick_id='$id' ".
            "INNER JOIN sets ON sets.id = sets_inventory.id  ".
            "ORDER BY `sets`.`type` DESC, sets_inventory.id ".
$query .= sprintf("LIMIT %d, 6", $page * 6);
# print "SQL: $query<br>\n";
$sth = $dbh->prepare($query);
$sth->execute();
while ($row = $sth->fetch()) {
    $type                = $row[0];
    if ($type == "minifig") {
        $minifig = array();
        $minifig['id']     = $row[1];
        $minifig['name']   = $row[2];
        $minifig['img-tn'] = $row[3];
        displayMinifigDiv($minifig);
    } else {
        $set = array();
        $set['id']     = $row[1];
        $set['name']   = $row[2];
        $img_type      = $row[4];
        $id            = $row[1];
        $set['img-tn'] = "/sets/$id/tn.$img_type";
        displaySetDiv($set);
    }

}

if ($page > 0) {
    print "<span class='hidden' id='show-prev-button'>1</span>\n";
}

if ($page < $last_page - 1) {
    print "<span class='hidden' id='show-next-button'>1</span>\n";
}

return 1;
