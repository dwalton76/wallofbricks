<?php
define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - Set Display", "");
if (!$username) {
    printAccountBenefits();
    printHTMLFooter(0, 0, 0, 0, $show_login_panel);
    exit();
}

print "<span class='hide' id='username'>$username</span>\n";
$dbh = dbConnect();
$query = "DELETE FROM sets_wishlist WHERE username=? AND id=?";
$sth = $dbh->prepare($query);
foreach ($_POST as $key=>$value) {
     if (preg_match("/delete-(\w+-\d+)/", $key, $matches)) {
        $id = $matches[1];
        $sth->bindParam(1, $username);
        $sth->bindParam(2, $id);
        $sth->execute();
    }
}

function printSetsIOwn()
{
    global $dbh;
    global $username;
    print "<div id='own-sets'>\n";
    print "<h1>My Sets</h1>\n";
    $columns = array();
    array_push($columns, 'img');
    array_push($columns, 'id');
    array_push($columns, 'name');

    $position = "left";

    $query = "SELECT sets_i_own.id, name, year, min_age, max_age, price, pieces ".
             "FROM sets_i_own ".
             "INNER JOIN sets ON sets_i_own.id = sets.id ".
             "WHERE username='$username' ".
             "ORDER BY sets_i_own.id ";
    # print "SQL: $query\n";
    $sth = $dbh->prepare($query);
    $sth->execute();
    $position = "left";
    $have_sets = False;

    while ($row = $sth->fetch()) {
        $have_sets = True;
        $set = array();
        $set['id']        = $row[0];
        $set['name']     = $row[1];
        $set['year']     = $row[2];
        $set['min_age'] = $row[3];
        $set['max_age'] = $row[4];
        $set['price']    = $row[5];
        $set['pieces']  = $row[6];
        $set['i_own_it'] = 1;
        $id = $row[0];
        $set['img']     = "/sets/$id/lego-$id.jpg";
        $set['img-tn'] = "/sets/$id/lego-$id-small.jpg";

        displaySetDiv($set, $username, 1);

        if ($position == "left") {
            $position = "middle";
        } elseif ($position == "middle") {
            $position = "right";
        } elseif ($position == "right") {
            $position = "left";
        }
    }

    if (!$have_sets) {
        print "Search for sets you own and add them to your inventory. Once you do they will show up here.";
    }

    print "</div>\n";
    print "<div class='clear'></div>\n";
}

// Display sets on wishlist
function printSetsWishlist()
{
    global $dbh;
    global $username;
    print "<div id='wishlist-sets'>\n";
    print "<h1>Wishlist Sets</h1>\n";
    $columns = array();
    array_push($columns, 'img');
    array_push($columns, 'id');
    array_push($columns, 'name');

    $position = "left";

    $query = "SELECT sets_wishlist.id, name, year, min_age, max_age, price, pieces ".
                "FROM sets_wishlist ".
                "INNER JOIN sets ON sets_wishlist.id = sets.id ".
                "WHERE username='$username' ".
                "ORDER BY sets_wishlist.id ";
    #print "SQL: $query\n";
    $sth = $dbh->prepare($query);
    $sth->execute();
    $position = "left";
    $have_sets = False;

    while ($row = $sth->fetch()) {
        $have_sets = True;
        $set = array();
        $set['id']        = $row[0];
        $set['name']     = $row[1];
        $set['year']     = $row[2];
        $set['min_age'] = $row[3];
        $set['max_age'] = $row[4];
        $set['price']    = $row[5];
        $set['pieces']  = $row[6];
        $set['on_wishlist'] = 1;
        $id = $row[0];
        $set['img']     = "/sets/$id/lego-$id.jpg";
        $set['img-tn'] = "/sets/$id/lego-$id-small.jpg";

        displaySetDiv($set, $username, 1);

        if ($position == "left") {
            $position = "middle";
        } elseif ($position == "middle") {
            $position = "right";
        } elseif ($position == "right") {
            $position = "left";
        }
    }

    if (!$have_sets) {
        print "Search for sets you would like and add them to your wishlist. Once you do they will show up here.";
    }
    print "</div>\n";
    print "<div class='clear'></div>\n";
}

printSetsIOwn();
printSetsWishlist();
printHTMLFooter(0, 0, 0, 0, $show_login_panel);
