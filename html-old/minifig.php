<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';
include 'include/functions.php';

printHTMLHeader("Wall of Bricks - Brick", "");
$dbh = dbConnect();

$id = "2780-11";
if (array_key_exists('id', $_GET)) {
    $id = $_GET['id'];
}

$query = "SELECT description, img, type FROM bricks WHERE id=?";
$sth = $dbh->prepare($query);
$sth->bindParam(1, $id);
$sth->execute();
$row    = $sth->fetch();
$desc  = $row[0];
$img    = $row[1];
$type  = $row[2];

if (!$desc) {
    print "Sorry, we do not have any data on minifig '$id'\n";

    return;
}

$id_minus_color = $id;
if (preg_match("/^(\w+)\-\d+/", $id, $matches)) {
    $id_minus_color = $matches[1];
}

print "<div id='brick_overview' class='set-guts'>\n";
print "<h1>$desc</h1>\n";
print "<div>\n";
print "<img src='$img' width='60px' /><br>\n";
print "BrickLink ID: <a href='http://www.bricklink.com/catalogItem.asp?M=$id_minus_color' target='_blank'>$id_minus_color</a><br>\n";
print "</div>\n";
print "</div>\n";
print "<div class='clear'></div>\n";

$print_header = 1;
$query = "SELECT brick_id, brick_quantity, bricks.img ".
            "FROM sets_inventory ".
            "INNER JOIN bricks ON brick_id = bricks.id ".
            "INNER JOIN lego_colors ON bricks.color = lego_colors.bricklink_color ".
            "WHERE sets_inventory.id='$id'";

$col = 1;
$row = 1;
$sth = $dbh->prepare($query);
$sth->execute();
while ($row = $sth->fetch()) {
    $brick = array();
    $brick['id']     = $row[0];
    $brick['qty']    = $row[1];
    $brick['img']    = $row[2];

    if ($print_header) {
        $print_header = 0;
        print "<div id='parts-list' class='set-guts'>\n";
        print "<h1>Parts</h1>";
        print "<table>\n";
        print "<tbody>\n";
    }

    if ($col == 1) {
        print "<tr>\n";
    }

    $brick_id = $brick['id'];

    print "<td class='td-link center' url='/minifig-brick.php?id=$brick_id'>\n";
    printf("<a href='/minifig-brick.php?id=$brick_id''><img src='%s'/></a><br>", $brick['img']);
    printf("<span class='qty'>QTY: %s</span>", $brick['qty']);

    print "</td>\n";

    if ($col++ == 10) {
        print "</tr>\n";
        $col = 1;
        $row++;
    }
}

if (!$print_header) {
    print "</tbody>\n";
    print "</table>\n";
    print "<br>\n";
    print "</div>\n";
    print "<div class='clear'></div>\n";
}

// We'll load the contents of div#sets-with-this-brick with AJAX via ajax-get-sets-with-brick.php
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
<h1>Sets With This Minifig</h1>
<div id='sets-with-this-brick' class='set-guts'>
</div>

<div class='clear'></div>
<div id='page_x_of_y'>Page <span id='page'>1</span>/<?php print $last_page ?></div>
<div id='sets-browse-controls'>
<a id='prev-set-by-brick'><img src='/images/Arrow-Prev.png' class='clickable' width='128' /></a>
<a id='next-set-by-brick'><img src='/images/Arrow-Next.png' class='clickable' width='128' /></a>
</div>
<div class='clear'></div>
</div>
<?php
$dbh = null; // close the connection
printHTMLFooter(0, 0, 0, 0);
?>
