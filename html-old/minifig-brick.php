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

$query = "SELECT description, img FROM bricks WHERE id=?";
$sth = $dbh->prepare($query);
$sth->bindParam(1, $id);
$sth->execute();
$row    = $sth->fetch();
$desc  = $row[0];
$img    = $row[1];

if (!$desc) {
    print "Sorry, we do not have any data on part '$id'\n";

    return;
}

$id_minus_color = $id;
if (preg_match("/^(\w+)\-\d+/", $id, $matches)) {
    $id_minus_color = $matches[1];
}

print "<table id='colors-of-this-brick'>\n";
print "<tbody>\n";

$query = "SELECT bricks.id, img ".
            "FROM bricks ".
            "INNER JOIN lego_colors ON color = bricklink_color ".
            "WHERE bricklink_core_id='$id_minus_color' ".
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
    $brick_img = $row[1];

    if ($col == 1) {
        print "<tr>\n";
    }

    $extra_class = "";

    if ($brick_id == $id) {
        $extra_class = " selected";
    }

    #print "<td class='$extra_class' url='/minifig-brick.php?id=$brick_id'>\n";
    print "<td class='$extra_class'>\n";
    printf("<a href='/minifig-brick.php?id=%s'><img src='%s' width='45'></a>\n", $brick_id, $brick_img);
    print "</td>\n";

    if ($col++ == 15) {
        print "</tr>\n";
        $col = 1;
    }
}

print "</tbody>\n";
print "</table>\n";
print "<br>\n";

print "<div id='brick_overview' class='set-guts'>\n";
print "<h1>$desc</h1>\n";
print "<div>\n";
print "<img src='$img' width='80' /><br>\n";
print "BrickLink ID: <a href='http://www.bricklink.com/catalogItem.asp?P=$id_minus_color' target='_blank'>$id_minus_color</a><br>\n";
// print "Peeron ID: <a href='http://www.peeron.com/inv/parts/$id_minus_color' target='_blank'>$id_minus_color</a><br>\n";
print "</div>\n";
print "</div>\n";

print "<div class='clear'></div>\n";

// We'll load the contents of div#sets-with-this-brick with AJAX via ajax-get-sets-with-brick.php
$query = "SELECT COUNT(sets_inventory.id) ".
            "FROM bricks ".
            "INNER JOIN sets_inventory ON brick_id = bricks.id AND brick_id='$id' ".
            "INNER JOIN sets ON sets.id = sets_inventory.id AND sets.type='minifig' ";

$sth = $dbh->prepare($query);
$sth->execute();
$row = $sth->fetch();
$last_page = ceil($row[0]/6);
?>
<span class='hidden' id='brick_id'><?php print $id ?></span>
<span class='hidden' id='last_page'><?php print $last_page ?></span>
<h1>Minifigs With This Brick</h1>
<div id='sets-with-this-brick' class='set-guts'>
</div>

<div class='clear'></div>
<div id='page_x_of_y'>Page <span id='page'>1</span>/<?php print $last_page ?></div>
<div id='sets-browse-controls'>
<a id='prev-set-by-brick'><img src='/images/Arrow-Prev.png' class='clickable' width='128' /></a>
<a id='next-set-by-brick'><img src='/images/Arrow-Next.png' class='clickable' width='128' /></a>
</div>

<div class='clear'></div>
<?php
$dbh = null; // close the connection
printHTMLFooter(0, 0, 0, 0);
?>
