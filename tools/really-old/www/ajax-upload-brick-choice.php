<?php
define('INCLUDE_CHECK',true);
include "include/functions.php";

$dbh = dbConnect();

$pab_store_id = 0;
if (array_key_exists("pab_store_id", $_POST)) {
   $pab_store_id = $_POST['pab_store_id'];
}

$row = 0;
if (array_key_exists("row", $_POST)) {
   $row = $_POST['row'];
}

$col = 0;
if (array_key_exists("col", $_POST)) {
   $col = $_POST['col'];
}

$brick_id = 0;
if (array_key_exists("brick_id", $_POST)) {
   $brick_id = $_POST['brick_id'];
}

# $f = fopen("/tmp/dwalton.log", "a"); 
# fwrite($f, "pab_store_id: $pab_store_id, row: $row, col: $col, brick: $brick_id\n"); 
# fclose($f); 

if ($pab_store_id && $row && $col && $brick_id) {
   $query = "UPDATE lego_store_inventory SET brick_id=?, updated_on=NOW() WHERE store_id=? AND row=? AND col=? ";

   # print "SQL: $query<br>\n";
   $sth = $dbh->prepare($query);
   $sth->bindParam(1, $brick_id);
   $sth->bindParam(2, $pab_store_id);
   $sth->bindParam(3, $row);
   $sth->bindParam(4, $col);
   $sth->execute();
   return 1;
}

return 0;
?>
