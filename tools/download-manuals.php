<?php
define('INCLUDE_CHECK',true);
include "include/connect.php";
include "include/functions.php";

function dbConnect() {
    $dbh = new PDO("mysql:host=localhost;dbname=dwalto76_lego", 'dwalto76_admin', "PASSWORD");

    return $dbh;
}

$dbh = dbConnect();
$ua = "--user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3'";

# Download json files from lego that tell there the manual PDFs are
$query = "SELECT id FROM `sets` ";
$sth = $dbh->prepare($query);
$sth->execute();
while ($row = $sth->fetch()) {
   $id = $row[0];
   $core_id = $id;
   if (preg_match("/(.*)\-\d+$/", $id, $match)) {
      $core_id = $match[1];
   }

   $output_filename = "/var/www/lego/html/sets/$id/manual.json";

   if (file_exists($output_filename)) {
      print "SKIP: $output_filename\n";
   } else {
      $url = "http://service.lego.com/Views/Service/Pages/BIService.ashx/GetCompletionListHtml?prefixText=$core_id&fromIdx=0";
      print("wget $ua -O $output_filename '$url'\n");
      system("wget $ua -O $output_filename '$url'");
   }
}


#
# Used once to parse the json files about PDF manuals and put the data from the jsons in the sets_manual table
#
$insert_query = "INSERT INTO sets_manual (id, description, url, size, book, book_max, version, filename) VALUE (?,?,?,?,?,?,?,?)";
$insert_sth = $dbh->prepare($insert_query);

# $query = "SELECT id FROM  `sets` WHERE  `theme` LIKE  'star wars' AND subtheme LIKE  'Ultimate Collector Series'";
$query = "SELECT id FROM `sets` ";
$sth = $dbh->prepare($query);
$sth->execute();
while ($row = $sth->fetch()) {
   $id = $row[0];
   $core_id = $id;
   if (preg_match("/(.*)\-\d+$/", $id, $match)) {
      $core_id = $match[1];
   }

   $output_filename = "/var/www/lego/html/sets/$id/manual.json";

   if (file_exists($output_filename)) {
      print "ID: $id - $output_filename\n";

      $string = file_get_contents($output_filename);
      $json = json_decode($string, true);
      foreach ($json['Content'] as $obj) {
         $book = 1;
         $book_max = 1;
         $version = "";

         # When you search for set "123" that returns hits for any sets that contain the string "123" in the ID
         # So make sure this is the one we want
         if ($obj['ProductId'] != $core_id) {
            continue;
         }

         # If there is a set 123-1, 123-2, 123-3, etc then make sure this manual matches
         # up with the release of the set that we are interested int
         if (preg_match("/ (\d+-\d+)/", $obj['Description'], $match)) {
            if ($match[1] != $id) {
               continue;
            }
         }

         if (preg_match("/ (\d)\/(\d)/", $obj['Description'], $match)) {
            $book = $match[1];
            $book_max = $match[2];
         }

         if (preg_match("/ (V\.?\d+\/\d+)/", $obj['Description'], $match)) {
            $version = $match[1];
         } else if (preg_match("/ (V\.?\d+)/", $obj['Description'], $match)) {
            $version = $match[1];
         }

         $url = $obj['PdfLocation'];
         $filename;
         if (preg_match("/^.*\/(.*?\.pdf)/", $url, $match)) {
            $filename = $match[1];
         }

         $insert_sth->bindParam(1, $id);
         $insert_sth->bindParam(2, $obj['Description']);
         $insert_sth->bindParam(3, $url);
         $insert_sth->bindParam(4, $obj['DownloadSize']);
         $insert_sth->bindParam(5, $book);
         $insert_sth->bindParam(6, $book_max);

         if ($version) {
            $insert_sth->bindParam(7, $version);
         } else {
            $insert_sth->bindValue(7, null, PDO::PARAM_INT);
         }

         if ($filename) {
            $insert_sth->bindParam(8, $filename);
         } else {
            $insert_sth->bindValue(8, null, PDO::PARAM_INT);
         }

         $insert_sth->execute();
      }
   }
}

?>
