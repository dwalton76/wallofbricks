<?php

// NOTE: This is no longer used...we store all of the stores in lego.js now so that
// this can run locally instead of hitting the server

define('INCLUDE_CHECK',true);
include "include/connect.php";
$dbh = dbConnect();

#
# I read about this method here:
# http://remysharp.com/2007/01/20/auto-populating-select-boxes-using-jquery-ajax/
#

$pab_store_id = 0;
if (array_key_exists("id", $_GET)) {
   $pab_store_id = $_GET['id'];
}

$country = "USA";
if (array_key_exists("country", $_GET)) {
   $country = $_GET['country'];
}

$query = "SELECT id, city, state ".
         "FROM lego_store ".
         "WHERE country='$country' ".
         "ORDER BY state, city";
// file_put_contents("/tmp/dwalton.log", "$query\n");
$sth = $dbh->prepare($query);
$sth->execute();
echo "[";
$first = 1;
while($row = $sth->fetch()) {
   $id = $row[0];
   $city = $row[1];
   $state = $row[2];

   if ($first) {
      $first = 0;
      if (!$pab_store_id) {
         $selected_string = " selected";
      } else {
         $selected_string = "";
      }
   } else if ($id == $pab_store_id) {
      $selected_string = " selected";
      echo ", ";
   } else {
      $selected_string = "";
      echo ", ";
   }

   if ($state) {
      $display = "$state - $city";
   } else {
      $display = $city;
   }
   echo "{\"optionSelected\": \"$selected_string\", \"optionValue\": \"$id\", \"optionDisplay\": \"$display\"}";
}
echo "]";

// close the connection
$dbh = null;
?>
