<?php

define('INCLUDE_CHECK',true);
include "include/functions.php";
printHTMLHeader("", "");
$dbh = dbConnect();

$id = "4475-1";
if (array_key_exists('set_to_build', $_GET)) {
   $id = $_GET['set_to_build'];
   if (!preg_match("/\w+-\d+/", $id, $matches)) {
      $id = $id . "-1";
   }
}

$submit;
if (array_key_exists('submit', $_GET)) {
   $submit = $_GET['submit'];
}

if ($id && $submit == "Add To Wish List") {
   $query = "INSERT IGNORE INTO sets_wishlist (id) VALUE (?)";
   $sth = $dbh->prepare($query);
   $sth->bindParam(1, $id);
   $sth->execute();
}


getSetCompleteness($dbh, $id, 1);
printHTMLFooter();
?>
