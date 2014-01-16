<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';

$dbh = dbConnect();

if ($argc < 3) {
   print "ERROR: Not enough args\n";
   exit();
}

$username = $argv[1];
$brickset_username = $argv[2];
$brickset_password = $argv[3];
$filename = "/tmp/brickset-$username.txt";

if (!$username) {
   print "ERROR: You must specify your WoB username\n";
   exit();
}

if (!$brickset_username) {
   print "ERROR: You must specify your brickset username\n";
   exit();
}

if (!$brickset_password) {
   print "ERROR: You must specify your brickset password\n";
   exit();
}

# Brickset API key uJ7w-DP54-pZ5W
$url = "http://brickset.com/api/v2.asmx/login?apiKey=uJ7w-DP54-pZ5W&username=$brickset_username&password=$brickset_password";
system("wget -O $filename '$url'");

$auth_token = '';
# <string xmlns="http://brickset.com/api/">{J&amp;&lt;&amp;O6j&amp;]</string>
$fh = fopen($filename, "r");
if ($fh) {
    while (($line = fgets($fh)) !== false) {
        if (preg_match('/<string.*?>(.*)<\/string/', $line, $matches)) {
            $auth_token = htmlspecialchars_decode($matches[1]);
            #$auth_token = $matches[1];
        }
    }
}
print "auth_token: $auth_token\n";


$filename = "/tmp/brickset2-$username.txt";
$url = "http://brickset.com/api/v2.asmx/getSets?apiKey=uJ7w-DP54-pZ5W&userHash=$auth_token&owned=1&pageSize=100";
system("wget -O $filename '$url'");

exit(0);


$fh = fopen($filename, "r");
if ($fh) {
   $insert_query = "INSERT INTO sets_i_own (username, id, quantity, added_via) VALUE (?,?,?,'brickset') ON DUPLICATE KEY UPDATE quantity=?";
   $insert_sth = $dbh->prepare($insert_query);

   while (($line = fgets($fh)) !== false) {
      list($setid, $qty) = split(",", $line);
      # print "SETID: $setid, QTY: $qty\n";
      $insert_sth->bindParam(1, $username);
      $insert_sth->bindParam(2, $setid);
      $insert_sth->bindParam(3, $qty);
      $insert_sth->bindParam(4, $qty);
      $insert_sth->execute();
    }
}
unlink($filename);

system("wget -O $filename 'http://brickset.com/api/get_user_parts?key=S21AaFDTL7&email=$brickset_username&pass=$brickset_password&format=csv'");

$fh = fopen($filename, "r");
if ($fh) {
   $insert_query = "INSERT INTO bricks_i_own (username, id, quantity) VALUE (?,?,?) ON DUPLICATE KEY UPDATE quantity=?";
   $insert_sth = $dbh->prepare($insert_query);

   while (($line = fgets($fh)) !== false) {
      $line = trim($line);
      list($design_id, $ldraw_color, $qty) = split(",", $line);

      if (preg_match('/^(\d+)[a-zA-Z]$/', $design_id, $matches)) {
         $design_id = $matches[1];
      }

      $lego_id = 0;
      $brickset_color = $ldraw2brickset{$ldraw_color};
      if ($brickset_color && array_key_exists($design_id, $legoID)) {
         if (array_key_exists($brickset_color, $legoID[$design_id])) {
            $lego_id = $legoID[$design_id][$brickset_color];
         }
      }

      if ($lego_id) {
         #print "LEGO_ID: $lego_id, QTY: $qty\n";
         $insert_sth->bindParam(1, $username);
         $insert_sth->bindParam(2, $lego_id);
         $insert_sth->bindParam(3, $qty);
         $insert_sth->bindParam(4, $qty);
         $insert_sth->execute();
      }
   }
}

unlink($filename);
