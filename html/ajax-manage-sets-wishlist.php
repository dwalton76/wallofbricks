<?php

define('INCLUDE_CHECK',true);
include 'include/connect.php';

$id = "";
if (array_key_exists("id", $_POST)) {
    $id= $_POST['id'];
}

$username = "";
if (array_key_exists("username", $_POST)) {
    $username = $_POST['username'];
}

$action = "";
if (array_key_exists("action", $_POST)) {
    $action = $_POST['action'];
}

if ($id && $username && $action) {
    $dbh = dbConnect();

    if ($action == "add") {
        $query = "INSERT INTO sets_wishlist (username,id) VALUE (?,?)";
        $sth = $dbh->prepare($query);
        $sth->bindParam(1, $username);
        $sth->bindParam(2, $id);
        $sth->execute();
    } else {
        $query = "DELETE FROM sets_wishlist WHERE username=? AND id=?";
        $sth = $dbh->prepare($query);
        $sth->bindParam(1, $username);
        $sth->bindParam(2, $id);
        $sth->execute();
    }

    $dbh = null;
}

return 1;
