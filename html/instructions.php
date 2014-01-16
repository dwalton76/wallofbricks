<?php
define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

function getSetBricksOnThisPage($dbh, $set_id, $model, $filename, $page) {
    $query = "SELECT sets_model_brick_index.brick_id, sets_model_brick_index.quantity, ".
             "bricks.design_id, bricks.description, bricks.part_type, bricks.dimensions, ".
             "(SELECT color_group FROM lego_colors WHERE bricks.color = lego_colors.brickset_color LIMIT 1) AS color_group ".
             "FROM sets_model_brick_index ".
             "INNER JOIN bricks ON brick_id = bricks.id ".
             "WHERE sets_model_brick_index.id='$set_id' AND sets_model_brick_index.model='$model' AND filename='$filename.pdf' AND page=$page ".
             "ORDER BY CASE ".
             "WHEN color_group='Black' THEN 1 ".
             "WHEN color_group='Red' THEN 2 ".
             "WHEN color_group='Blue' THEN 3 ".
             "WHEN color_group='Grey' THEN 4 ".
             "WHEN color_group='Brown' THEN 5 ".
             "WHEN color_group='Yellow' THEN 6 ".
             "WHEN color_group='Green' THEN 7 ".
             "WHEN color_group='White' THEN 8 ".
             "WHEN color_group='Orange' THEN 9 ".
             "WHEN color_group='Purple' THEN 10 ".
             "ELSE 99 ".
             "END ASC, color ASC , bricks.part_type, bricks.design_id, bricks.dimensions, bricks.id";

    #print $query;
    $sth = $dbh->prepare($query);
    $sth->execute();
    $bricks = array();

    while ($row = $sth->fetch()) {
        $brick = array();
        $lego_id             = $row[0];
        $brick['id']         = $lego_id;
        $brick['qty']        = $row[1];
        $brick['design_id']  = $row[2];
        $brick['desc']       = $row[3];
        $brick['type']       = $row[4];
        $brick['dimensions'] = $row[5];
        $brick['img']        = "/parts/" . $brick['design_id'] . "/" . $brick['id'] . ".jpg";
        $brick['color']      = $row[6];
        $bricks[$lego_id]    = $brick;
    }

    return $bricks;
}

function getSetBricksIndexed($dbh, $set_id, $model) {
    $query = "SELECT brick_id, SUM(quantity) ".
             "FROM sets_model_brick_index ".
             "WHERE id='$set_id' AND model='$model' ".
             "GROUP BY brick_id";
    $sth = $dbh->prepare($query);
    $sth->execute();
    $bricks = array();

    while ($row = $sth->fetch()) {
        $brick = array();
        $lego_id             = $row[0];
        $brick['id']         = $lego_id;
        $brick['qty']        = $row[1];
        $bricks[$lego_id]    = $brick;
    }

    return $bricks;
}

function printSetInventoryToIndex($dbh, $set_id, $model, $username, $page, $filename)
{
    $bricks_on_page = getSetBricksOnThisPage($dbh, $set_id, $model, $filename, $page);
    $bricks_indexed = getSetBricksIndexed($dbh, $set_id, $model);
    $bricks_required = getSetParts($dbh, $set_id, 0);

    $brickset_colors = array();
    foreach ($bricks_required as $brick) {
        if (!array_key_exists($brick['color'], $brickset_colors)) {
            $brickset_colors[$brick['color']] = $brick['color'];
        }
    }

    print "<input type='hidden' id='set_id' name='set_id' value='$set_id'>\n";
    print "<input type='hidden' id='model' name='model' value='$model'>\n";
    print "<input type='hidden' id='page' name='page' value='$page'>\n";
    print "<input type='hidden' id='filename' name='filename' value='$filename.pdf'>\n";

    print "<div id='parts-list'>\n";
    print "<h1>Parts On This Page</h1>";
    print "<table>\n";
    print "<tbody>\n";
    $col = 1;
    $row = 1;

    $brick_qtys_needed = array();

    foreach ($bricks_required as $brick) {
        $brick_id = $brick['id'];
        $brick_on_page_class = "";

        if (array_key_exists($brick_id, $bricks_on_page)) {
            $brick['on-page'] = $bricks_on_page[$brick_id]['qty'];
            $brick_on_page_class = "brick-on-page";
        }

        if ($col == 1) {
            print "<tr>\n";
        }

        # subtrack the qty of what we've already indexed
        if (array_key_exists($brick_id, $bricks_indexed)) {
            $brick['qty'] -= $bricks_indexed[$brick_id]['qty'];
        }

        # Do not print this part if there are none left to be indexed
        # AND it hasn't been indexed on this page
        if ($brick_on_page_class == "" && !$brick['qty']) {
            continue;
        }

        $brick_id = $brick['id'];
        print "<td brick_id='$brick_id' class='td-link center clickable $brick_on_page_class'>\n";
        print getBrickTDDisplayForInstructions($brick);
        print "</td>\n";

        if ($col++ == 10) {
            print "</tr>\n";
            $col = 1;
            $row++;
        }
    }

    if ($col != 1) {
        print "</tr>\n";
    }

    print "</tbody>\n";
    print "</table>\n";
    print "</div>\n";

    print "<div id='parts-filters' class='set-guts'>\n";
    print "<h1>Parts Filter</h1>";
    print "<input type='hidden' name='wall-filter' value='filter-color'>\n";
    printWallFiltersForColorTypeDimensions($dbh, 0, 1, $brickset_colors, 0);
    print "</div>\n";
    print "<div class='clear'></div>\n";
}

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - Set Display", "");
print "<span class='hide' id='username'>$username</span>\n";
$dbh = dbConnect();

$id = "4475-1";
if (array_key_exists('set_id', $_GET)) {
    $id = $_GET['set_id'];
    if (!preg_match("/\w+-\d+/", $id, $matches)) {
        $id = $id . "-1";
    }
}

$page = 0;
if (array_key_exists('page', $_GET)) {
    $page = $_GET['page'];
}

$page_minus_one = $page - 1;
$model = $_GET['model'];
$filename = $_GET['filename'];
$dir = "/var/www/lego/html/sets/$id/";

print "<div id='instructions'>\n";
print "<img id='zoom-set' src='/sets/$id/$filename-$page_minus_one-medium.jpg' data-zoom-image='/sets/$id/$filename-$page_minus_one.jpg'  />\n";

$query = "SELECT pages FROM sets_manual WHERE id='$id' AND filename='$filename.pdf'";
#print "SQL: $query\n";
$sth = $dbh->prepare($query);
$sth->execute();
$row = $sth->fetch();
$last_page = $row[0] + 1;

print "<div id='controls'>\n";
print "<div id='control-row'>\n";

if ($page > 1) {
    $prev_page = $page - 1;
} else {
    $prev_page = 1;
}

if ($page < $last_page-1) {
    $next_page = $page + 1;
} else {
    $next_page = $last_page;
}

if ($page > 1) {
    print "<a href='/instructions.php?set_id=$id&model=$model&filename=$filename&page=$prev_page' >&lt;</a>";
}

if ($page != $last_page) {
    print "<a href='/instructions.php?set_id=$id&model=$model&filename=$filename&page=$next_page' >&gt;</a>";
}

print "</div>\n";
print "<div id='control-row'>\n";
for ($i = 1; $i <= $last_page; $i++) {

    if ($i == $page) {
        print "<a href='/instructions.php?set_id=$id&model=$model&filename=$filename&page=$i' class='active'><span class='pagenumber'>$i</span></a>";
    } else {
        print "<a href='/instructions.php?set_id=$id&model=$model&filename=$filename&page=$i'><span class='pagenumber'>$i</span></a>";
    }

    if ($i && $i % 20 == 0) {
        print "</div>\n";
        print "<div id='control-row'>\n";
    }
}
print "</div>\n"; # End of control-row
print "</div>\n"; # End of controls
print "<div id='instructions'>\n"; # End of instructions
print "<div class='clear'></div>\n";

if ($username) {
    printSetInventoryToIndex($dbh, $id, $model, $username, $page, $filename);
}

printHTMLFooter(0, 0, 0, 0, $show_login_panel);

