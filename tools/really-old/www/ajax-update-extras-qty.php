<?php
define('INCLUDE_CHECK',true);
include "include/functions.php";
$dbh = dbConnect();

$id = "0";
if (array_key_exists("id", $_POST)) {
   $id= $_POST['id'];
}

$qty = 0;
if (array_key_exists("qty", $_POST)) {
   $qty= $_POST['qty'];
}

if ($id) {
   $query = "INSERT INTO bricks_extra (brick_id, brick_quantity) VALUE (?,?) ".
            "ON DUPLICATE KEY UPDATE brick_quantity=?";
   $sth = $dbh->prepare($query);
   $sth->bindParam(1, $id);
   $sth->bindParam(2, $qty);
   $sth->bindParam(3, $qty);
   $sth->execute();
}

return 1;
?>
