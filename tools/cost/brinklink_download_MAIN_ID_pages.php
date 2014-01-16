<?php
define('INCLUDE_CHECK',true);
include "../html/include/connect.php";
include "../html/include/functions.php";

$dbh = dbConnect();
$ua = "--user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3'";

$username = "dwalton76";
$set_id = "10178-1"; # ATAT
$set_id = "10144-1"; # Sandcrawler
$set_id = "9396-1"; # Helicopter

   $query = "SELECT brick_id, quantity FROM sets_bricks_needed WHERE username=? AND id=? ORDER BY `quantity` DESC ";
   $sth = $dbh->prepare($query);
   $sth->bindParam(1, $username);
   $sth->bindParam(2, $set_id);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $id = $row[0]; 
      $qty = $row[1]; 

      if (preg_match("/(.*)-\d+$/", $id, $matches)) {
         $id = $matches[1];
      }

      $url = sprintf("http://www.bricklink.com/catalogItem.asp?P=%s", $id);
      $output_filename = "files/$id-MAIN_ID.html";      

      if (file_exists($output_filename)) {
         print "DOWNLOADED ID: $id, QTY: $qty\n"; 
      } else {
         print "DOWNLOADING ID: $id, QTY: $qty\n"; 
         # print "wget $ua -O $output_filename $url<br>\n";
         system("wget $ua -O $output_filename $url");
      }
   }

   $i = 0;
   $sth->execute();
   while ($row = $sth->fetch()) {
      print "\n\n\n\nPART: $i\n================================================\n";
      $id = $row[0]; 
      $qty = $row[1]; 

      if (preg_match("/(.*)-(\d+)$/", $id, $matches)) {
         $id = $matches[1];
         $color = $matches[2];
         print "./brinklink_parse_MAID_ID_pages.pl -id $id -c $color -qty $qty\n";
         system("./brinklink_parse_MAID_ID_pages.pl -id $id -c $color -qty $qty");
         $i++;
      }
   }

?>
