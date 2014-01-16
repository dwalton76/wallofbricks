<?php
define('INCLUDE_CHECK',true);
include "include/connect.php";
include "include/functions.php";

$dbh = dbConnect();

$id = 0;
if (array_key_exists("id", $_POST)) {
   $id = $_POST['id'];
}

$query = "SELECT id, img, name, `img-tn`, year, min_age, max_age, price, pieces ".
         "FROM sets WHERE id=? LIMIT 1";

$sth = $dbh->prepare($query);
$sth->bindParam(1, $id);
$sth->execute();

while ($row = $sth->fetch()) {
   $set = array();
   $set['id']          = $row[0];
   $set['img']         = $row[1];
   $set['name']        = $row[2];
   $set['img-tn']      = $row[3];
   $set['year']        = $row[4];
   $set['min_age']     = $row[5];
   $set['max_age']     = $row[6];
   $set['price']       = $row[7];
   $set['pieces']      = $row[8];
   displaySetDiv($set);
}

// close the connection
$dbh = null;

return 1;
?>
