<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';

$dbh = dbConnect();
$query = "SELECT DISTINCT (brick_id) FROM  `lego_store_inventory` WHERE store_id !=133 ORDER BY  `lego_store_inventory`.`brick_id` ASC";
$sth = $dbh->prepare($query);
$sth->execute();
$first = 1;
while ($row = $sth->fetch()) {
    if ($first) {
        $first = 0;
    } else {
        print ",";
    }
    print "$row[0]";
}

activityLog($dbh, "CSV");
