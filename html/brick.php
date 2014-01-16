<?php
define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - Brick", "");
$dbh = dbConnect();

$id = 0;
if (array_key_exists('id', $_GET)) {
    $id = $_GET['id'];
}

if (!$id) {
    print "You must specifiy a part ID in the url\n";
    printHTMLFooter(0, 0, 0, 0, $show_login_panel);
    exit();
}

$query = "SELECT description, design_id, type, color FROM bricks WHERE id=?";
$sth = $dbh->prepare($query);
$sth->bindParam(1, $id);
$sth->execute();
$row         = $sth->fetch();
$desc        = $row[3] . " " . $row[0];
$design_id = $row[1];
$type        = $row[2];
$img = "/parts/$design_id/$id-large.jpg";

if (!$desc) {
    print "Sorry, we do not have any data on part '$id'\n";
    printHTMLFooter(0, 0, 0, 0, $show_login_panel);
    exit();
}

print "<div id='brick_overview'>\n";
print "<table id='colors-of-this-brick'>\n";
print "<tbody>\n";

# Print all of the other parts of this design_id
$query = "SELECT DISTINCT(bricks.id), ".
            "(SELECT color_group FROM lego_colors WHERE bricks.color = lego_colors.brickset_color LIMIT 1) AS color_group ".
         "FROM bricks ".
         "WHERE design_id='$design_id' ".
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
         "END ASC, color ASC";
#print "SQL: $query<br>\n";
$sth = $dbh->prepare($query);
$sth->execute();
$col = 1;
while ($row = $sth->fetch()) {
    $brick_id = $row[0];
    $brick_img = "/parts/$design_id/$brick_id.jpg";

    if ($col == 1) {
        print "<tr>\n";
    }

    $extra_class = "";

    if ($brick_id == $id) {
        $extra_class = " selected";
    }

    print "<td class='$extra_class' url='/brick.php?id=$brick_id'>\n";
    printf("<a href='/brick.php?id=%s'><img src='%s' width='64px'></a>\n", $brick_id, $brick_img);
    print "</td>\n";

    if ($col++ == 12) {
        print "</tr>\n";
        $col = 1;
    }
}

print "</tbody>\n";
print "</table>\n";
print "<br>\n";
print "<h1>$desc</h1>\n";
print "<div id='brick-image'>\n";
print "<img src='$img' width='192px' height='192px' /><br>\n";
print "</div>\n";
print "<div id='external-brick-links'>\n";

if ($username) {
    $mybricks = getMyBricks($dbh, $username, $id);
    foreach ($mybricks as $brick) {
        print "<li>You own ". $brick['qty'] ." of this brick</li>\n";
    }
}

print "<li>LEGO Element ID: $id</li>\n";
print "<li>LEGO Design ID: $design_id</li>\n";
print "<li><a href='http://www.brickset.com/parts/search/?query=$id' target='_blank'>Brickset</a></li>\n";
print "<li><a href='http://www.bricklink.com/catalogItem.asp?P=$design_id' target='_blank'>Bricklink</a></li>\n";

print "</div>\n";
print "</div>\n";
print "<div class='clear'></div>\n";

#
# Current Pick-A-Brick Availability
#
print "<div id='pab_availability'>\n";
print "<h1>Pick-A-Brick Availability</h1>\n";
$query = "SELECT store_id, city, state, country, DATE(updated_on) ".
            "FROM `lego_store_inventory` ".
            "INNER JOIN lego_store ON lego_store.id = store_id ".
            "WHERE `brick_id`='$id' ".
            "GROUP BY store_id ".
            "ORDER BY DATE( updated_on ) DESC";
#print "SQL: $query<br>\n";
$sth = $dbh->prepare($query);
$sth->execute();
print "<div id='current_availability'>\n";
print "<h2>Current Availability</h2>\n";

$store_where_available = array();
$print_header = 1;
while ($row = $sth->fetch()) {
    $store_id    = $row[0];
    $city         = $row[1];
    $state        = $row[2];
    $country     = $row[3];
    $date         = $row[4];
    array_push($store_where_available, $store_id);

    if ($print_header) {
        print "<table>\n";
        print "<thead>\n";
        print "<tr><th>Store</th><th>Date Added</th></tr>\n";
        print "</thead>\n";
        print "<tbody>\n";
        $print_header = 0;
    }

    if ($state) {
        $store_name = "$city, $state";
    } else {
        $store_name = "$city, $country";
    }
    printf("<tr><td><a href='/pab-display.php?pab_store_id=%s&country=%s'>%s</a></td><td>%s</td></tr>\n", $store_id, $country, $store_name, $date);
}

if ($print_header) {
    print "Sorry, this part isn't listed on the Pick-A-Brick wall of any store\n";
} else {
    print "</tbody>\n";
    print "</table>\n";
}
print "</div>\n";

#
# Show past availability
#
print "<div id='past_availability'>\n";
$print_header = 1;
print "<h2>Past Availability</h2>\n";

$store_where_available_string = "'";
$store_where_available_string .= implode("','", $store_where_available);
$store_where_available_string .= "'";

$query = "SELECT lego_store_inventory_activity.id, city, state, country, `row`, col, DATE( updated_on ), updated_on ".
         "FROM  `lego_store_inventory_activity` ".
         "INNER JOIN lego_store ON lego_store.id = lego_store_inventory_activity.id ".
         "WHERE `brick_id`='$id' AND lego_store_inventory_activity.id NOT IN ($store_where_available_string) ".
         "GROUP BY id, DATE(updated_on) ".
         "ORDER BY DATE( updated_on ) DESC";
#print "SQL: $query<br>\n";
$sth = $dbh->prepare($query);
$sth->execute();
while ($row = $sth->fetch()) {
    $store_id  = $row[0];
    $city      = $row[1];
    $state     = $row[2];
    $country   = $row[3];
    $store_row = $row[4];
    $store_col = $row[5];
    $from_date = $row[6];
    $from_date_full = $row[7];

    $to_date_query = "SELECT MIN( DATE( updated_on ) ) ".
                     "FROM  `lego_store_inventory_activity` ".
                     "WHERE `id` = $store_id ".
                     "AND `row` = $store_row ".
                     "AND `col` = $store_col ".
                     "AND updated_on > '$from_date_full' ".
                     "AND brick_id != '$id'";
    # print "TO_DATE_SQL: $to_date_query<br>\n";
    $to_date_sth = $dbh->prepare($to_date_query);
    $to_date_sth->execute();
    $to_date_row = $to_date_sth->fetch();
    $to_date = $to_date_row[0];

    if ($print_header) {
        print "<table>\n";
        print "<thead>\n";
        print "<tr><th>Store</th><th>Date Added</th><th>Date Removed</th></tr>\n";
        print "</thead>\n";
        print "<tbody>\n";
        $print_header = 0;
    }

    if ($state) {
        $store_name = "$city, $state";
    } else {
        $store_name = "$city, $country";
    }

    printf("<tr><td><a href='/pab-display.php?pab_store_id=%s&country=%s'>%s</a></td><td>%s</td><td>%s</td></tr>\n",
             $store_id, $country, $store_name, $from_date, $to_date);
}

if ($print_header) {
    if (sizeof($store_where_available )) {
        print "We do not have any 'historic' Pick-A-Brick data for this part\n";
    } else {
        print "Sorry, we have no records of this part ever being available on the Pick-A-Brick wall of any store.\n";
    }
} else {
    print "</tbody>\n";
    print "</table>\n";
}

print "</div>\n";
print "</div>\n";
print "<div class='clear'></div>\n";


#
# Current Online Availability
#
$query = "SELECT qty, cond, price, www_store_inventory.url, name, country, lot_id ".
         "FROM www_store_inventory ".
         "INNER JOIN www_stores ON www_store_inventory.store_id = www_stores.id ".
         "WHERE lego_id=$id ".
         "ORDER BY cond, price ASC, qty DESC ";
#print "SQL:<br>$query<br>\n";
print "<div id='brickowl_availability'>\n";
print "<h1>Online Availability</h1>\n";
$sth = $dbh->prepare($query);
$sth->execute();
$print_header = 1;
while ($row = $sth->fetch()) {
    if ($print_header) {
        print "<table>\n";
        print "<thead>\n";
        print "<tr>".
              "<th>Price</th><th>Qty</th><th>Condition</th><th>Store</th>".
              "</tr>\n";
        print "</thead>\n";
        print "<tbody>\n";
        $print_header = 0;
    }
    $qty = $row[0];
    $cond = $row[1];
    $price = centsToPrice($row[2]);
    $url = $row[3];
    $name = $row[4];
    $country = $row[5];
    $lot_id = $row[6];
    $lot_id_string = '';
    if ($lot_id) {
        $lot_id_string = "#" . $lot_id;
    }

    printf("<tr><td>%s</td><td>%d</td><td>%s</td><td><a href='%s%s' target='_blank'>%s, %s</a></td></tr>",
           $price, $qty, $cond, $url, $lot_id_string, $name, $country);
}

if ($print_header) {
    print "Sorry, this part is not available in any of our brickowl affiliate stores\n";
} else {
    print "</tbody>\n";
    print "</table>\n";
}
print "</div>\n";
print "<div class='clear'></div>\n";

#
# Show sets with this brick
#
# We'll load the contents of div#sets-with-this-brick with
# AJAX via ajax-get-sets-with-brick.php
#
$query = "SELECT COUNT(sets.id) ".
         "FROM sets ".
         "INNER JOIN sets_inventory ON sets_inventory.id = sets.id ".
         "INNER JOIN bricks ON brick_id = bricks.id ".
         "WHERE brick_id='$id' ";
$sth = $dbh->prepare($query);
$sth->execute();
$row = $sth->fetch();
$last_page = ceil($row[0]/12);
?>
<span class='hidden' id='brick_id'><?php print $id ?></span>
<span class='hidden' id='last_page'><?php print $last_page ?></span>
<h1>Sets With This Brick</h1>
<div id='sets-with-this-brick'>
</div>

<div class='clear'></div>
<div id='page_x_of_y'>Page <span id='page'>1</span>/<?php print $last_page ?></div>
<div id='sets-browse-controls'>
<a id='prev-set-by-brick'><img src='/images/Arrow-Prev.png' class='clickable' width='128' /></a>
<a id='next-set-by-brick'><img src='/images/Arrow-Next.png' class='clickable' width='128' /></a>
</div>
<?php


#
# Find all of the sets you own that have this brick
#
if ($username) {
    $print_header = 1;
    $query = "SELECT sets_i_own.id, sets.name, brick_quantity ".
             "FROM sets_i_own ".
             "INNER JOIN sets_inventory ON sets_inventory.id = sets_i_own.id ".
             "INNER JOIN sets ON sets.id = sets_i_own.id ".
             "INNER JOIN bricks ON brick_id = bricks.id ".
             "WHERE username='$username' AND brick_id='$id' ".
             "ORDER BY `sets_inventory`.`brick_quantity` DESC";
    # print "SQL: $query<br>\n";
    $sth = $dbh->prepare($query);
    $sth->execute();
    while ($row = $sth->fetch()) {
        if ($print_header) {
            print "<div class='clear'></div>\n";
            print "<h1>My Sets With This Brick</h1>\n";
            print "<div><br>\n";
            $print_header = 0;
        }
        $set = array();
        $set['id']         = $row[0];
        $set['name']       = $row[1];
        $set['qty_bricks'] = $row[2];
        $id = $set['id'];
        $set['img-tn']     = "/sets/$id/lego-$id-small.jpg";
        displaySetDiv($set);
    }

    if (!$print_header) {
        print "</div>\n";
    }
}

print "<div class='clear'></div>\n";
print "</div>\n";
$dbh = null; // close the connection
printHTMLFooter(0, 0, 0, 0, $show_login_panel);
?>
