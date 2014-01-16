<?php
define('INCLUDE_CHECK',true);
include "include/functions.php";

$dbh = dbConnect();

# 36 = Technic
# 65 = Star Wars
# 771 = Friends

# TODO: generate a list of all the bricks we own and only look at sets that have those bricks
$query = "SELECT id ".
         "FROM sets ".
         "WHERE pieces IS NOT NULL AND pieces != 1 ".
         "ORDER BY id";
$i = 0;
#print "SQL: $query\n";
$sth = $dbh->prepare($query);
$sth->execute();
while ($row = $sth->fetch()) {
   $id = $row[0];
   getSetCompleteness($dbh, $id, 0);
   print ".";
   $i++;
}
print "\n$i\n";
?>
