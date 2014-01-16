<?php

define('INCLUDE_CHECK',true);
include "include/functions.php";
$dbh = dbConnect();

$id = "0";
if (array_key_exists("id", $_POST)) {
   $id= $_POST['id'];
}

if ($id) {
   $query = "INSERT IGNORE INTO sets_wishlist (id) VALUE (?)";
   $sth = $dbh->prepare($query);
   $sth->bindParam(1, $id);
   $sth->execute();
}

return 1;

?>
