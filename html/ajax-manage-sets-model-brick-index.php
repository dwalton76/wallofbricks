<?php

define('INCLUDE_CHECK',true);
include 'include/connect.php';

$set_id = "";
if (array_key_exists("set_id", $_POST)) {
    $set_id = $_POST['set_id'];
}

$model = "";
if (array_key_exists("model", $_POST)) {
    $model = $_POST['model'];
}

$filename = "";
if (array_key_exists("filename", $_POST)) {
    $filename = $_POST['filename'];
}

$page = "";
if (array_key_exists("page", $_POST)) {
    $page = $_POST['page'];
}

$brick_id = "";
if (array_key_exists("brick_id", $_POST)) {
    $brick_id= $_POST['brick_id'];
}

$username = "";
if (array_key_exists("username", $_POST)) {
    $username = $_POST['username'];
}

$action = "";
if (array_key_exists("action", $_POST)) {
    $action = $_POST['action'];
}

print "set_id: $set_id<br>\n";
print "model: $model<br>\n";
print "page: $page<br>\n";
print "action: $action<br>\n";
print "filename: $filename<br>\n";
print "brick_id: $brick_id<br>\n";
print "action: $action<br>\n";

if ($set_id && $model && $filename && $page && $brick_id && $username && $action) {
    $dbh = dbConnect();

    if ($action == "add") {
        $query = "INSERT INTO sets_model_brick_index (id, model, filename, page, brick_id, quantity) VALUE (?,?,?,?,?,1) ON DUPLICATE KEY UPDATE quantity = quantity + 1";
        $sth = $dbh->prepare($query);
        $sth->bindParam(1, $set_id);
        $sth->bindParam(2, $model);
        $sth->bindParam(3, $filename);
        $sth->bindParam(4, $page);
        $sth->bindParam(5, $brick_id);
        $sth->execute();

    } else if ($action == "del") {

        $query = "SELECT quantity ".
                 "FROM sets_model_brick_index ".
                 "WHERE id='$set_id' AND model='$model' AND filename='$filename' AND page=$page AND brick_id='$brick_id'";

        $sth = $dbh->prepare($query);
        $sth->execute();
        $row = $sth->fetch();

        if ($row) {
            # The qty should never be 0 but just in case...
            if ($row[0] == 0 || $row[0] == 1) {
                $query = "DELETE FROM sets_model_brick_index ".
                         "WHERE id='$set_id' AND model='$model' AND filename='$filename' AND page=$page AND brick_id='$brick_id'";
            } else {
                $query = "UPDATE sets_model_brick_index SET quantity = quantity - 1 ".
                         "WHERE id='$set_id' AND model='$model' AND filename='$filename' AND page=$page AND brick_id='$brick_id'";
            }

            $sth = $dbh->prepare($query);
            $sth->execute();
        }
    }

    $dbh = null;
}

return 1;
