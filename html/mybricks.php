<?php
define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - My Bricks", "");
if (!$username) {
    printAccountBenefits();
    printHTMLFooter(0, 0, 0, 0, $show_login_panel);
    exit();
}

$dbh = dbConnect();

/*
#
# Handle any updated numbers the user submitted for the number of extra bricks
#
$query = "INSERT INTO bricks_extra(brick_id, brick_quantity) VALUE (?,?) ON DUPLICATE KEY UPDATE brick_quantity=?";
$sth = $dbh->prepare($query);
foreach ($_GET as $key=>$value) {
    if (preg_match("/qty-(\w+\-\d+)/", $key, $matches)) {
         $id = $matches[1];
         $qty = $value;

        $sth->bindParam(1, $id);
        $sth->bindParam(2, $qty);
        $sth->bindParam(3, $qty);
        $sth->execute();
    }
}

#
# Handle the "Add A New Brick" section
#
if (isset($_GET["new_brick_id"]) && isset($_GET["new_brick_qty"]) &&
             $_GET["new_brick_id"]  &&         $_GET["new_brick_qty"]) {
    $query = "INSERT INTO bricks_extra(brick_id, brick_quantity) VALUE (?,?) ON DUPLICATE KEY UPDATE brick_quantity=?";
    $sth = $dbh->prepare($query);
    $sth->bindParam(1, $_GET["new_brick_id"]);
    $sth->bindParam(2, $_GET["new_brick_qty"]);
    $sth->bindParam(3, $_GET["new_brick_qty"]);
    $sth->execute();
}
*/

#
# Find all of the bricks I own
#
print "<div id='middle-column'>\n";
print "<table>\n";
print "<tbody>\n";
$col = 1;
$row = 1;
$brickset_colors = array();
$bricks = getMyBricks($dbh, $username, "");
foreach ($bricks as $brick) {
    if (!array_key_exists($brick['color'], $brickset_colors)) {
        $brickset_colors[$brick['color']] = $brick['color'];
    }

    if ($col == 1) {
        print "<tr>\n";
    }

    $brick_id = $brick['id'];
    print "<td class='td-link center' url='/brick.php?id=$brick_id'>\n";
    print getBrickTDDisplay($brick);
    print "</td>\n";

    if ($col++ == 10) {
        print "</tr>\n";
        $col = 1;
        $row++;
    }
}

print "</tbody>\n";
print "</table>\n";
print "</div>\n";

print "<div id='parts-filters' class='set-guts'>\n";
print "<h1>Parts Filter</h1>";
print "<input type='hidden' name='wall-filter' value='filter-color'>\n";
printWallFiltersForColorTypeDimensions($dbh, 0, 1, $brickset_colors, 0);
print "</div>\n";

printHTMLFooter();
