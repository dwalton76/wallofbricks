<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';

$dbh = dbConnect();
$query = "SELECT id, city, state, country, rows, cols FROM `lego_store`";
$sth = $dbh->prepare($query);
$sth->execute();
print "<stores>\n";
while ($row = $sth->fetch()) {
    printf("<store><id>%d</id><city>%s</city><state>%s</state><country>%s</country><rows>%d</rows><cols>%d</cols></store>\n",
             $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]);
}
print "</stores>\n";

activityLog($dbh, "CSV");
