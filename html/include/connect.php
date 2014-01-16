<?php

if (!defined('INCLUDE_CHECK')) {
    die('You are not allowed to execute this file directly');
}

function dbConnect() {
    $dbh = new PDO("mysql:host=localhost;dbname=dwalto76_lego", 'dwalto76_admin', "PASSWORD");

    return $dbh;
}

// Returns TRUE if $str is an email address
function checkEmail($str) {
    return preg_match("/^[\.A-z0-9_\-\+]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z]{1,4}$/", $str);
}
