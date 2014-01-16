<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';
include 'include/functions.php';

$pab_store_id = 0;

if (array_key_exists('pab_store_id', $_GET)) {
    $pab_store_id = $_GET['pab_store_id'];

    if (!validStoreID($pab_store_id)) {
        print "ERROR: This is not a supported store ID<br>\n";
        exit();
    }
}

if (!$pab_store_id) {
    print "ERROR: You must specify a store ID via '?pab_store_id=X'\n";
    exit();
}

$dbh = dbConnect();
$query = "SELECT col, row, slot, brick_id FROM lego_store_inventory WHERE store_id='$pab_store_id' ORDER BY col ASC, row DESC, slot ASC";
$sth = $dbh->prepare($query);
$sth->execute();
print "<bins>\n";
while ($row = $sth->fetch()) {
    printf("<bin><col>%d</col><row>%d</row><slot>%d</slot><brick_id>%d</brick_id></bin>\n",
             $row[0], $row[1], $row[2], $row[3]);
}
print "</bins>\n";

activityLog($dbh, "CSV");
