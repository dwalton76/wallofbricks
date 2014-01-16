<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';

$dbh = dbConnect();
$query = "SELECT color_group, brickset_color, rgb, number_parts, min_year, max_year, color_group_rep FROM `lego_colors` WHERE `brickset_color` IS NOT NULL ORDER BY `lego_colors`.`color_group` ASC, number_parts DESC";
$sth = $dbh->prepare($query);
$sth->execute();
print "<colors>\n";
while ($row = $sth->fetch()) {
    printf("<color><color_group>%s</color_group><brickset_color>%s</brickset_color><rgb>%s</rgb><number_parts>%d</number_parts><min_year>%d</min_year><max_year>%d</max_year><color_group_rep>%d</color_group_rep></color>\n",
             $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]);
}
print "</colors>\n";

activityLog($dbh, "CSV");
