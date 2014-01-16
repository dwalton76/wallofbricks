<?php

define('INCLUDE_CHECK',true);
include "include/functions.php";
printHTMLHeader("", "");
$dbh = dbConnect();

$id;
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

?>
<form method='get' action='/set.php'>
<label for='set_to_build'>ID</label>
<input type='text' size='4' name='set_to_build' id='set_to_build' value='<?php print $id; ?>'></input>
<input type='submit'>
<br>
<?php

if ($id) {
   getSetCompleteness($dbh, $id, 1);

} else {
   $query = "SELECT DISTINCT theme FROM sets WHERE theme IS NOT NULL ";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      print "<a href='/category.php?theme=$row[0]'>$row[0]</a><br>\n";
   }

}
print "</form>\n";

printHTMLFooter();
?>
