<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';
include 'include/functions.php';

$dbh = dbConnect();
$theme = "City";
$subtheme = "";
$min_year = 1970;
$max_year = 2015;
$min_age = 0;
$max_age = 16;
$min_price = 0;
$max_price = 500;
$min_pieces = 1;
$max_pieces = 6000;

$username = "";
if (array_key_exists("username", $_POST)) {
    $username = $_POST['username'];
}

if (array_key_exists("theme", $_POST)) {
    $theme = $_POST['theme'];

    if (preg_match("/(.*): (.*)/", $theme, $matches)) {
       $theme = $matches[1];
       $subtheme = $matches[2];
    }
}

if (array_key_exists("min_year", $_POST)) {
    $min_year = $_POST['min_year'];
}

if (array_key_exists("max_year", $_POST)) {
    $max_year = $_POST['max_year'];
}

if (array_key_exists("min_age", $_POST)) {
    $min_age = $_POST['min_age'];
}

if (array_key_exists("max_age", $_POST)) {
    $max_age = $_POST['max_age'];
}

if (array_key_exists("min_price", $_POST)) {
    $min_price = $_POST['min_price'];
}

if (array_key_exists("max_price", $_POST)) {
    $max_price = $_POST['max_price'];
}

if (array_key_exists("min_pieces", $_POST)) {
    $min_pieces = $_POST['min_pieces'];
}

if (array_key_exists("max_pieces", $_POST)) {
    $max_pieces = $_POST['max_pieces'];
}

$page = 0;
if (array_key_exists("page", $_POST)) {
    $page = $_POST['page'] - 1;
}

$last_page = 0;
if (array_key_exists("last_page", $_POST)) {
    $last_page = $_POST['last_page'];
}

$columns = array();
array_push($columns, 'img');
array_push($columns, 'id');
array_push($columns, 'name');

// Convert from dollars to cents
$min_price *= 100;
$max_price *= 100;

$last_page_query = "SELECT COUNT(id) ";
$query = "SELECT id, name, year, min_age, max_age, price, pieces ";
if ($username) {
    $query .= ", (SELECT username FROM sets_wishlist WHERE username='$username' AND sets_wishlist.id = sets.id LIMIT 1) AS on_wishlist, ".
                "(SELECT username FROM sets_i_own WHERE username='$username' AND sets_i_own.id = sets.id LIMIT 1) AS i_own_it ";
}

$options = "FROM sets WHERE theme='$theme' ";

if ($subtheme) {
    $options .= "AND subtheme='$subtheme' ";
}

$options .= "AND (year IS NULL OR (year >= $min_year AND year <= $max_year)) ".
             "AND (min_age IS NULL OR min_age >= $min_age) AND (max_age IS NULL OR max_age <= $max_age) " .
             "AND (price IS NULL OR (price >= $min_price AND price <= $max_price)) ".
             "AND (pieces IS NULL OR (pieces >= $min_pieces AND pieces <= $max_pieces)) ".
             "ORDER BY pieces DESC ";
$limit = sprintf("LIMIT %d, 12", $page * 12);

$last_page_query .= $options;
$query .= $options . $limit;

# print "SQL: $query\n";

if (!$last_page) {
    $sth = $dbh->prepare($last_page_query);
    $sth->execute();
    $row = $sth->fetch();
    $last_page = ceil($row[0]/12);
    print "<span id='new_last_page' class='hidden'>$last_page</span>\n";
}

#print "SQL: $query<br>\n";
$sth = $dbh->prepare($query);
$sth->execute();

while ($row = $sth->fetch()) {
    $set = array();
    $set['id']          = $row[0];
    $set['name']        = $row[1];
    $set['year']        = $row[2];
    $set['min_age']     = $row[3];
    $set['max_age']     = $row[4];
    $set['price']       = $row[5];
    $set['pieces']      = $row[6];

    if ($username) {
       $set['on_wishlist'] = $row[7];
       $set['i_own_it']    = $row[8];
    }

    $id = $row[0];
    $set['img']    = "/sets/$id/lego-$id.jpg";
    $set['img-tn'] = "/sets/$id/lego-$id-small.jpg";
    displaySetDiv($set, $username);
}

if ($page > 0) {
    print "<span class='hidden' id='show-prev-button'>1</span>\n";
}

if ($page < $last_page - 1) {
    print "<span class='hidden' id='show-next-button'>1</span>\n";
}

$options_brief = "THEME:$theme::SUBTHEME:$subtheme::MIN_YEAR:$min_year::MAX_YEAR:$max_year::MIN_AGE:$min_age::MAX_AGE:$max_age::MIN_PRICE:$min_price::MAX_PRICE:$max_price::MIN_PIECES:$min_pieces::MAX_PIECES:$max_pieces";

// close the connection
$dbh = null;

return 1;
