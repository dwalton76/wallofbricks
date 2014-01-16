<?php
define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - Recent Activity", "");
$dbh = dbConnect();

$pab_store_id = 0;
$pab_country = 'USA';
$limit = 100;

if (array_key_exists('pab_store_id', $_GET)) {
    $pab_store_id = $_GET['pab_store_id'];

    if (!validStoreID($pab_store_id)) {
        print "ERROR: This is not a supported store ID<br>\n";
        printHTMLFooter();
        exit();
    }

    if (array_key_exists('country', $_GET)) {
        $pab_country = $_GET['country'];
    }
}

if (array_key_exists('limit', $_GET)) {
    $limit = $_GET['limit'];

    if ($limit > 1000) {
        $limit = 1000;
    }
}

print "<div id=wall-activity-wrapper'>\n";
print "<table id='wall-activity'>\n";
print "<tbody>\n";

$query = "SELECT brick_id, DATE(updated_on), design_id, city, state, country, lego_store_inventory_activity.id ".
         "FROM `lego_store_inventory_activity` ".
         "INNER JOIN bricks ON brick_id = bricks.id ".
         "INNER JOIN lego_store ON lego_store_inventory_activity.id = lego_store.id ";

if ($pab_store_id) {
    $query .= "WHERE `lego_store_inventory_activity`.id = $pab_store_id ";
}

$query .= "ORDER BY `lego_store_inventory_activity`.`updated_on` DESC LIMIT 0, $limit";

#print "SQL: $query\n";
$sth = $dbh->prepare($query);
$sth->execute();
$i = 0;
while ($row = $sth->fetch()) {
    $brick = array();
    $brick['id'] = $row[0];
    $id = $row[0];
    $brick['updated_on'] = $row[1];
    $design_id = $row[2];
    $img = "/parts/$design_id/$id.jpg";
    $brick['img'] = $img;

    $city = $row[3];
    $state = $row[4];
    $country = $row[5];
    $store_id = $row[6];

    if ($city && $state) {
        $place = "$city, $state, $country";
    } else {
        $place = "$city, $country";
    }

    $brick_td = getBrickTDDisplay($brick);

    if ($i == 0) {
        print "<tr>\n";
    }

    printf("<td align='center'>%s<a href='/pab-display.php?pab_store_id=%s&country=%s'>%s</a></td></td>", $brick_td, $store_id, $country, $place);

    if (++$i == 5) {
        print "</tr>\n";
        $i = 0;
    }
}
print "</tbody>\n";
print "</table>\n";
print "</br>\n";

print "<form method='get' action='/wall-activity.php' autocomplete='off'>\n";
pickAStore($dbh, $pab_country, $pab_store_id, 1, 0);
print "</form>\n";
print "</div>\n";

printHTMLFooter(0, 0, 0, 0, $show_login_panel);
