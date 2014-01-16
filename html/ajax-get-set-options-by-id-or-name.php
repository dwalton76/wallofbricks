<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';
include 'include/functions.php';

$dbh = dbConnect();
// This used to create the auto-complete for a the "Set Name or ID" textbox on the set-search.php page

// Nice page here on how to do this
// http://www.simonbattersby.com/blog/jquery-ui-autocomplete-with-a-remote-database-and-php/

$term = "";
if (array_key_exists("term", $_GET)) {
    $term = $_GET['term'];
}

// This should never happen
if (!$term) {
    exit();
}

$id = "";
$name = "";
$query = "SELECT id, name FROM sets WHERE ";
if (preg_match("/^\s*(\d+\-\d)\s*(.*)/", $term, $matches)) {
    $id = addslashes($matches[1]);
    $name = addslashes($matches[2]);
    $query .= "id LIKE '$id%' AND name LIKE '%$name%' ";

} elseif (preg_match("/^\s*(\d+)\s*$/", $term, $matches)) {
    $id = addslashes($matches[1]);
    $query .= "id LIKE '$id%' ";

} elseif (preg_match("/^\s*(\d+)\s*(.*)/", $term, $matches)) {
    $id = addslashes($matches[1]);
    $name = addslashes($matches[2]);
    $query .= "id LIKE '$id%' AND name LIKE '%$name%' ";

} else {
    $name = addslashes($term);
    $query .= "name LIKE '%$name%' ";
}

$query .= "ORDER BY id ASC ";

$sth = $dbh->prepare($query);
$sth->execute();

while ($row = $sth->fetch()) {
    $row_set[] = $row[0] . ": " . $row[1];
}

//format the array into json data
echo json_encode($row_set);
return 1;
