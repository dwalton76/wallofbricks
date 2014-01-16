<?php
define('INCLUDE_CHECK',true);
include "include/connect.php";
include "include/functions.php";

$dbh = dbConnect();
$ua = "--user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3'";


function getBrickCountForNinetyPercentOfSets($dbh, $id) {
   $query = "SELECT sets.id, brick_quantity ".
            "FROM sets ".
            "INNER JOIN sets_inventory ON sets_inventory.id = sets.id ".
            "INNER JOIN bricks ON brick_id = bricks.id ".
            "WHERE brick_id='$id' ".
            "ORDER BY `sets_inventory`.`brick_quantity` DESC ";
   # print "SQL: $query\n";
   $sth = $dbh->prepare($query);
   $sth->execute();
   $set_stats = array();
   while ($row = $sth->fetch()) {
      $set = array();
      $set['id'] = $row[0];
      $set['qty_bricks'] = $row[1];
      array_push($set_stats, $set);
   }

   #
   # Now figure out how many sets used 1 of this brick, 2 of this brick, etc
   #
   $qty_count = array();
   $total_sets_count = 0;
   foreach ($set_stats as $set) {
      $set_qty  = $set['qty_bricks'];
      if (isset($qty_count[$set_qty])) {
         $qty_count[$set_qty]++;
      } else {
         $qty_count[$set_qty] = 1;
      }
      #printf("qty_count[$set_qty] = %s<br>\n", $qty_count[$set_qty]);
      $total_sets_count++;
   }

   ksort($qty_count);
   $max_height = 0;
   $tmp_sets_count = 0;
   $max_qty_by_one_set = 0;
   #print "TOTAL_SETS: $total_sets_count<br>\n";
   foreach ($qty_count as $key => $value) {
      $value = $qty_count[$key];
      if ($max_qty_by_one_set < $key) {
         $max_qty_by_one_set = $key;
      }

      $tmp_sets_count += $value;
      #print "KEY: '$key', VALUE: $value, SETS: $tmp_sets_count<br>\n";
      if ($tmp_sets_count >= 0.95 * $total_sets_count) {
         #print "RETURNING: $key<br>\n";
         return $key;
      }
   }

   #print "RETURNING MAX: $max_qty_by_one_set<br>\n";
   return ($max_qty_by_one_set);
}

//
// Used Once: Combine the id and version fields into just and id VARCHAR
//
if (0) {
   $query = "SELECT sets_inventory.index, id, version FROM sets_inventory";
   $sth = $dbh->prepare($query);
   $sth->execute();
   $IDs = array();
   while ($row = $sth->fetch()) {
      $index = $row[0];
      $id = $row[1];
      $version = $row[2];
      $IDs[$index] = $id . "-" . $version;
   }
   
   $query = "UPDATE sets_inventory SET id=? WHERE sets_inventory.index=?";
   $sth = $dbh->prepare($query);
   foreach ($IDs as $index=>$value) {
      print "INDEX $index, NEW $value\n";
      $sth->bindParam(1, $value);
      $sth->bindParam(2, $index);
      $sth->execute();
   }
}


//
// Download set images and update the url in the database
//

if (0) {
   $query = "UPDATE sets SET img=? WHERE id=?";
   $sth2 = $dbh->prepare($query);

   $suffixes = array();
   array_push($suffixes, "jpg");
   array_push($suffixes, "png");
   array_push($suffixes, "gif");

   $query = "SELECT sets.index, id, img FROM sets ";
   $query = "SELECT sets.index, id, img FROM sets WHERE id='9303-1'";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $index = $row[0];
      $id = $row[1];
      $url = $row[2];
      print "CHECKING: $id\n";
 
      foreach ($suffixes as $suffix) {
         $url = "http://www.1000steine.com/brickset/images/$id.$suffix";
         $output_filename = "images/S$id.$suffix";
         if (!file_exists($output_filename)) {
            print("wget $ua -O $output_filename $url<br>\n");
            system("wget $ua -O $output_filename $url");
         }

         if (filesize($output_filename)) {
            $url= "/$output_filename";
            $sth2->bindParam(1, $url);
            $sth2->bindParam(2, $id);
            $sth2->execute();
            exit();
            break;
         } 
      }
   }


   //
   // Set the thumbnail URL
   //
   $query = "UPDATE sets SET `img-tn`=? WHERE id=?";
   $sth2 = $dbh->prepare($query);

   $query = "SELECT sets.index, id, img FROM sets WHERE img IS NOT NULL AND `img-tn` IS NULL";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $index = $row[0];
      $id = $row[1];
      $url = $row[2];
      $tn_url;
      if (preg_match("/(images\/S.*)\.(.*)/", $url, $matches)) {
         $tn_url = $matches[1] . "-tn." . $matches[2];
      }
 
      if ($tn_url) {
         print "UPDATED: $id to $tn_url\n";
         $sth2->bindParam(1, $tn_url);
         $sth2->bindParam(2, $id);
         $sth2->execute();
      }
   }
}


//
// Download bricks images and update the url in the database
//
if (0) {
   $query = "UPDATE bricks SET img=? WHERE bricks.index=?";
   $sth2 = $dbh->prepare($query);

   $query = "SELECT bricks.index, type, id, color, img FROM bricks";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $index = $row[0];
      $type  = $row[1];
      $id    = $row[2];
      $color = $row[3];
      $img   = $row[4];

      $suffix = "gif";
      if (preg_match("/\.(\w+)$/", $img, $matches)) {
         $suffix = $matches[1];
      }
      $output_filename = "images/$type$id-$color.$suffix";
      if (!file_exists($output_filename)) {
         print("wget $ua -O $output_filename $img<br>\n");
         system("wget $ua -O $output_filename $img");
         sleep(1);
      }
      $img = "/$output_filename";
      $sth2->bindParam(1, $img);
      $sth2->bindParam(2, $index);
      $sth2->execute();
      # break;
   }
}

//
// download missing img files
//
if (0) {
   $query = "UPDATE bricks SET img=? WHERE bricks.index=?";
   $sth2 = $dbh->prepare($query);

   $query = "SELECT bricks.index, type, id, color, img FROM bricks";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $index = $row[0];
      $type  = $row[1];
      $id    = $row[2];
      $color = $row[3];
      $img   = $row[4];
      $suffix;
      $base;

      if (preg_match("/^\/(images\/.*)\.(\w+)$/", $img, $matches)) {
         $base   = $matches[1];
         $suffix = $matches[2];
      } else {
         print "ERROR: no suffix for $index ID $id COLOR $color\n";
         continue;
      }

      if (!$type) {
         print "ERROR: no type for $index ID $id COLOR $color\n";
         continue;
      }

      if (preg_match("/images\/(\d.*)/", $base, $matches)) {
         $output_filename = "images/$type" . $matches[1] . "." . $suffix;
         print "ERROR: base is $base, should be $output_filename\n";
         $img = "/$output_filename";
         $sth2->bindParam(1, $img);
         $sth2->bindParam(2, $index);
         $sth2->execute();
      }

      $output_filename = $base . "." . $suffix;
      $output_gif = $base . ".gif";
      $output_jpg = $base . ".jpg";
      $output_png = $base . ".png";

      if ($color) {
         $bricklink_url = sprintf("http://img.bricklink.com/%s/%s/%s.%s", $type, $color, $id, $suffix);
      } else {
         $bricklink_url = sprintf("http://img.bricklink.com/%s/%s.%s", $type, $id, $suffix);
      }

      if (!file_exists($output_filename)) {
         if (file_exists($output_gif)) {
            print "UPDATE: suffix should have been gif...updating database\n";
            $img = "/$output_gif";
            $sth2->bindParam(1, $img);
            $sth2->bindParam(2, $index);
            $sth2->execute();
         } else if (file_exists($output_jpg)) {
            print "UPDATE: suffix should have been jpg...updating database\n";
            $img = "/$output_jpg";
            $sth2->bindParam(1, $img);
            $sth2->bindParam(2, $index);
            $sth2->execute();
         } else if (file_exists($output_png)) {
            print "UPDATE: suffix should have been png...updating database\n";
            $img = "/$output_png";
            $sth2->bindParam(1, $img);
            $sth2->bindParam(2, $index);
            $sth2->execute();
         } else {
            print("wget $ua -O $output_filename $bricklink_url<br>\n");
            system("wget $ua -O $output_filename $bricklink_url");
         }
      }
   }
}

//
// Used Once: combine the ID and VERSION into ID-VERSION
//
if (0) {
   $query = "SELECT sets_inventory.index, brick_id, brick_version FROM sets_inventory";
   $sth = $dbh->prepare($query);
   $sth->execute();
   $IDs = array();
   while ($row = $sth->fetch()) {
      $index = $row[0];
      $id = $row[1];
      $version = $row[2];
      $IDs[$index] = $id . "-" . $version;
   }
   
   $query = "UPDATE sets_inventory SET id=? WHERE sets_inventory.index=?";
   $sth = $dbh->prepare($query);
   foreach ($IDs as $index=>$value) {
      print "INDEX $index, NEW $value\n";
      $sth->bindParam(1, $value);
      $sth->bindParam(2, $index);
      $sth->execute();
   }
}

//
// Update the used_in_sets field for a part
//
if (0) {
   $pop_query = "SELECT brick_id, COUNT( sets_inventory.id ) , MIN( YEAR ) , MAX( YEAR ) ".
                "FROM  `sets_inventory` ".
                "INNER JOIN sets ON sets_inventory.id = sets.id ".
                "GROUP BY brick_id";
   $pop_sth = $dbh->prepare($pop_query);
   $pop_sth->execute();

   $update_query= "UPDATE bricks SET used_in_sets=?, min_year=?, max_year=? WHERE id=?";
   $update_sth = $dbh->prepare($update_query);

   $IDs = array();
   while ($row = $pop_sth->fetch()) {
      $id = $row[0];
      $count = $row[1];
      $min_year = $row[2];
      $max_year = $row[3];
      // $pop_sth->bindParam(1, $id);
      // $pop_sth->execute();
      // $pop_row = $pop_sth->fetch();
      // $count = $pop_row[0];
 
      $update_sth->bindParam(1, $count);
      $update_sth->bindParam(2, $min_year);
      $update_sth->bindParam(3, $max_year);
      $update_sth->bindParam(4, $id);
      $update_sth->execute();
      print "Updated $id used_in_sets to $count, $min_year -> $max_year\n";
      # print ".";
   }
}

//
// Set the reasonable_target_to_own field
//
if (0) {
   $update_query= "UPDATE bricks SET reasonable_target_to_own=? WHERE id=?";
   $update_sth = $dbh->prepare($update_query);

   $query = "SELECT id ".
            "FROM `bricks` ".
            "WHERE id != 'inv-0' ".
            "ORDER BY `bricks`.`used_in_sets` DESC ".
            "LIMIT 0, 500";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $brick_id = $row[0];
      $reasonable_target_to_own = getBrickCountForNinetyPercentOfSets($dbh, $brick_id);
      $update_sth->bindParam(1, $reasonable_target_to_own);
      $update_sth->bindParam(2, $brick_id);
      $update_sth->execute();
      print "Updated $brick_id used_in_sets to $reasonable_target_to_own\n";
   }
}

//
// Used Once: print out all of the possible dimensions
//
if (0) {
   $dimensions = array();
   $query = "SELECT description FROM bricks WHERE description LIKE '% x %'";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $desc = $row[0];
      if (preg_match("/(\d+ x \d+ - \d+ x \d+)/", $desc, $matches)) {
         if (isset($dimensions[$matches[1]])) {
            $dimensions[$matches[1]]++;
         } else {
            $dimensions[$matches[1]] = 1;
         }

      } else if (preg_match("/(\d+ x \d+ x \d+)/", $desc, $matches)) {
         if (isset($dimensions[$matches[1]])) {
            $dimensions[$matches[1]]++;
         } else {
            $dimensions[$matches[1]] = 1;
         }

      } else if (preg_match("/(\d+ x \d+)/", $desc, $matches)) {
         if (isset($dimensions[$matches[1]])) {
            $dimensions[$matches[1]]++;
         } else {
            $dimensions[$matches[1]] = 1;
         }
      }
   } 

   ksort($dimensions);
   foreach ($dimensions as $key => $value) {
      $key = str_replace(" ", "", $key);
      #printf("%03d: %s\n", $value, $key);
      printf("'%s',", $key);
   }
}


//
// This sets the color field for a part
//
if (0) {
   $query = "UPDATE bricks SET color=? WHERE id=?";
   $sth_update = $dbh->prepare($query);

   $query = "SELECT id FROM bricks WHERE color IS NULL ";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $id = $row[0];
      if (preg_match("/\-(\d+)$/", $id, $matches)) {
         $color = $matches[1];
         $sth_update->bindParam(1, $color);
         $sth_update->bindParam(2, $id);
         $sth_update->execute();
         print "ID: $id, COLOR: $color\n";
	# exit();
      }
   }

   //
   // This sets the dimension field for a part
   //
   $query = "UPDATE bricks SET dimensions=? WHERE `index`=?";
   $sth_update = $dbh->prepare($query);

   $query = "SELECT `index`, description FROM bricks WHERE `dimensions` IS NULL ";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $index = $row[0];
      $desc = $row[1];
      $dimensions = "";
      if (preg_match("/(\d+ x \d+ - \d+ x \d+)/", $desc, $matches)) {
         $dimensions= $matches[1];

      } else if (preg_match("/(\d+ x \d+ x \d+ x \d+)/", $desc, $matches)) {
         $dimensions= $matches[1];

      } else if (preg_match("/(\d+ x \d+ x \d+)/", $desc, $matches)) {
         $dimensions= $matches[1];

      } else if (preg_match("/(\d+ x \d+)/", $desc, $matches)) {
         $dimensions= $matches[1];
      }

      if ($dimensions) {
         $sth_update->bindParam(1, $dimensions);
         $sth_update->bindParam(2, $index);
         $sth_update->execute();
         print "INDEX: $index, XY: $dimensions\n";
	 # exit();
      }
   }


   //
   // This sets the part type (brick, plate, etc) for everything in the bricks table
   //
   $query = "UPDATE bricks SET part_type=? WHERE `index`=?";
   $sth_update = $dbh->prepare($query);

   $query = "SELECT `index`, description FROM bricks WHERE `part_type` IS NULL AND id != 'Inv-0' ";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $index = $row[0];
      $desc = $row[1];
      $part_type = "";

      if (preg_match("/technic/i", $desc, $matches)) {
         $part_type = "technic";

      } else if (preg_match("/brick/i", $desc, $matches)) {
         $part_type = "brick";

      } else if (preg_match("/plate/i", $desc, $matches)) {
         $part_type = "plate";

      } else if (preg_match("/tile/i", $desc, $matches)) {
         $part_type = "tile";

      } else if (preg_match("/slope/i", $desc, $matches)) {
         $part_type = "slope";

      // } else if (preg_match("/panel/i", $desc, $matches)) {
      //    $part_type = "panel";
      }

      if ($part_type) {
         $sth_update->bindParam(1, $part_type);
         $sth_update->bindParam(2, $index);
         $sth_update->execute();
         print "INDEX: $index, PT: $part_type\n";
         # printf("UPDATE bricks SET part_type='%s' WHERE `index`='%s';\n", $part_type, $index);
	 # exit();
      }
   }
}

//
// Used Once: This fixed an issue with the img names in the table
//
if (0) {
   $query = "UPDATE sets SET `img-tn`=? WHERE `index`=?";
   $sth_update = $dbh->prepare($query);

   $query = "SELECT `index`, `img-tn` FROM `sets` WHERE `img-tn` LIKE '%var%'";
   // $query = "SELECT `index`, `img-tn` FROM `sets` WHERE `img-tn` LIKE '%var%' AND `index`='8295' ";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $index = $row[0];
      $img = $row[1];

      if (preg_match("/(\/images\/.*)$/", $img, $matches)) {
         #printf("Changing img from %s to %s for index %s\n", $img, $matches[1], $index);
         print ".";
         $img = $matches[1];
         $sth_update->bindParam(1, $img);
         $sth_update->bindParam(2, $index);
         $sth_update->execute();
         # exit();
      }
   }
}

function getBestImage($sth2, $index, $id) {
   global $ua;

   $suffixes = array();
   array_push($suffixes, "jpg");
   array_push($suffixes, "png");
   array_push($suffixes, "gif");

   // Try to download an image from brickset
   foreach ($suffixes as $suffix) {
      $url = "http://www.1000steine.com/brickset/images/$id.$suffix";
      $output_filename = "images/S$id.$suffix";
      print("wget $ua -O $output_filename $url<br>\n");
      system("wget $ua -O $output_filename $url");

      if (filesize($output_filename)) {
         $url= "/$output_filename";
         $sth2->bindParam(1, $url);
         $sth2->bindParam(2, $id);
         $sth2->execute();
         return;
      } 
   }

   // If that fails then download one from bricklink
   foreach ($suffixes as $suffix) {
      $url = "http://www.bricklink.com/SL/$id.$suffix";
      $output_filename = "images/S$id.$suffix";
      print("wget $ua -O $output_filename $url<br>\n");
      system("wget $ua -O $output_filename $url");

      if (filesize($output_filename)) {
         $url= "/$output_filename";
         $sth2->bindParam(1, $url);
         $sth2->bindParam(2, $id);
         $sth2->execute();
         return;
      } 
   }
}

//
// Download a decent image for every lego set
//
if (0) {
   $query = "UPDATE sets SET img=? WHERE id=?";
   $sth2 = $dbh->prepare($query);

   $query = "SELECT `index`, `img`, id FROM `sets` ORDER BY id ";
   # $query = "SELECT `index`, `img`, id FROM `sets` WHERE id='360-2' ";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $index = $row[0];
      $img = $row[1];
      $id = $row[2];
      $full_path_img = "/var/www/lego/html" . $img;

      if (file_exists($full_path_img)) {
         $size = filesize($full_path_img);
      } else {
         $size = 0;
      }

      if ($size) {
         // We have a crappy image from brinklink, try to download a better one from brickset
         if ($size < 10000) {
            print "INDEX: $index, ID $id,  IMG $img is TOO SMALL\n";
            getBestImage($sth2, $index, $id);

         // We have a decent image...do nothing
         } else {
            print "INDEX: $index, ID $id,  IMG $img is GOOD\n";
         }

      } else {
         print "INDEX: $index, ID $id,  IMG $img is missing\n";
         getBestImage($sth2, $index, $id);
      }
   }
}

//
// If we have multiple images for a set, keep the largest
// and update the database to store the correct image filename
//
if (0) {
   $query = "UPDATE sets SET img=?, `img-tn`=? WHERE id=?";
   $sth2 = $dbh->prepare($query);

   $query = "SELECT `index`, `img`, id, `img-tn` FROM `sets` ORDER BY id ";
   # $query = "SELECT `index`, `img`, id, `img-tn` FROM `sets` WHERE id = '360-2' ";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $index     = $row[0];
      $db_img    = $row[1];
      $id        = $row[2];
      $db_img_tn = $row[3];

      $file_jpg = "";
      $file_png = "";
      $file_gif = "";
      $size_jpg = 0;
      $size_png = 0;
      $size_gif = 0;
      $file_count = 0;
      $img = "";
      $img_tn = "";

      if (file_exists("images/S$id.jpg")) {
         $file_jpg = "images/S$id.jpg";
         $size_jpg = filesize($file_jpg);
         $file_count++;
         $img = "/images/S$id.jpg";
         $img_tn = "/images/S$id-tn.jpg";
      }

      if (file_exists("images/S$id.png")) {
         $file_png = "images/S$id.png";
         $size_png = filesize($file_png);
         $file_count++;
         $img = "/images/S$id.png";
         $img_tn = "/images/S$id-tn.png";
      }

      if (file_exists("images/S$id.gif")) {
         $file_gif = "images/S$id.gif";
         $size_gif = filesize($file_gif);
         $file_count++;
         $img = "/images/S$id.gif";
         $img_tn = "/images/S$id-tn.gif";
      }

      if ($file_count > 1) {
         if ($size_jpg > $size_png && $size_jpg > $size_gif) {
            print "ID: $id, COUNT $file_count,  jpg image is best\n";
            #print "rm -rf images/S$id.png images/S$id.gif\n";
            $img = "/images/S$id.jpg";
            $img_tn = "/images/S$id-tn.jpg";
            system("rm -rf images/S$id.png images/S$id.gif");

         } else if ($size_png > $size_jpg && $size_png > $size_gif) {
            print "ID: $id, COUNT $file_count, png image is best\n";
            #print "rm -rf images/S$id.jpg images/S$id.gif\n";
            $img = "/images/S$id.png";
            $img_tn = "/images/S$id-tn.png";
            system("rm -rf images/S$id.jpg images/S$id.gif");

         } else if ($size_gif > $size_jpg && $size_gif > $size_png) {
            print "ID: $id, COUNT $file_count, gif image is best\n";
            #print "rm -rf images/S$id.jpg images/S$id.png\n";
            $img = "/images/S$id.gif";
            $img_tn = "/images/S$id-tn.gif";
            system("rm -rf images/S$id.jpg images/S$id.png");

         // All three are size 0
         } else if (!$size_jpg && !$size_png && !$size_gif) {
            print "ID: $id, COUNT $file_count, all are 0\n";
            # print "rm -rf images/S$id.jpg images/S$id.png images/S$id.gif\n";
            system("rm -rf images/S$id.jpg images/S$id.png images/S$id.gif");
            $img = "";
            $img_tn = "";

         } else {
            print "ERROR: don't know which image is best for $id\n";
            exit();
         }
      }

      if ($img != $db_img) {
         print "ID: $id...update img in db from $db_img to $img\n";
         $sth2->bindParam(1, $img);
         $sth2->bindParam(2, $img_tn);
         $sth2->bindParam(3, $id);
         $sth2->execute();
         #exit();
      } else if ($img_tn != $db_img_tn) {
         print "ID: $id...update img-tn in db from $db_img_tn to $img_tn\n";
         $sth2->bindParam(1, $img);
         $sth2->bindParam(2, $img_tn);
         $sth2->bindParam(3, $id);
         $sth2->execute();
         #exit();
      }
   }
}

// print a list of themes/subthemes
if (0) {
   $query = "SELECT theme, subtheme FROM  `sets` WHERE theme IS NOT NULL GROUP BY theme, subtheme";
   $sth = $dbh->prepare($query);
   $sth->execute();
   $previous_theme = "";
   while ($row = $sth->fetch()) {
      $theme = $row[0];
      $subtheme = $row[1];

      if ($subtheme) {
         if ($previous_theme != $theme) {
            printf("\"%s\",\n", $theme);
            printf("\"%s: %s\",\n", $theme, $subtheme);
         } else {
            printf("\"%s: %s\",\n", $theme, $subtheme);
         }
      } else {
         printf("\"%s\",\n", $theme);
      }

      $previous_theme = $theme;
   }
}

if (0) {
   // print a list of city/states per country
   $query = "SELECT id, city, state, country ".
            "FROM lego_store ".
            "ORDER BY country, state, city";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $id = $row[0];
      $city = $row[1];
      $state = $row[2];
      $country = $row[3];

      if ($state) {
         $display = "$state - $city";
      } else {
         $display = $city;
      }

      $selected_string = "";
      #print "{\"optionSelected\": \"$selected_string\", \"optionValue\": \"$id\", \"optionDisplay\": \"$display\"}\n";
      print "\"$country:$display:$id\",\n";
   }
}

#
# Download the main bricklink page for a part (ignoring color) so that we can
# get the "alternate" IDs which are the actual Lego IDs
#
if (0) {
   $query = "SELECT DISTINCT (bricklink_core_id) FROM `bricks` ORDER BY `bricks`.`id` ASC";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $id_minus_color = $row[0];

      $output_filename = "/var/www/lego/tools/parts/$id_minus_color.html";
      if (file_exists($output_filename)) {
         print "SKIP: $output_filename\n";
      } else {
         $url = "http://www.bricklink.com/catalogItem.asp?P=$id_minus_color";
         print("wget $ua -O $output_filename '$url'\n");
         system("wget $ua -O $output_filename '$url'");
      }
   }
}

#
# Download the list of parts released each year...from brickset
#
if (0) {
   for ($i = 2013; $i >= 1980; $i--) {
      $output_filename = "/var/www/lego/private/parts/brickset-$i.html";
      if (file_exists($output_filename)) {
         print "SKIP: $output_filename\n";
      } else {
         $url = "http://www.brickset.com/parts/browse/years/?year=$i";
         print("wget $ua -O $output_filename '$url'\n");
         system("wget $ua -O $output_filename '$url'");
      }
   }
}



#
# Used Once: Populate the bricklink_core_id column based on the bricklink id column
#
if (0) {
   $update_query = "UPDATE bricks SET bricklink_core_id=? WHERE id=?";
   $update_sth = $dbh->prepare($update_query);

   $query = "SELECT DISTINCT (id) FROM `bricks` ORDER BY `bricks`.`id` ASC";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $id = $row[0];

      if (preg_match("/^(\w+)\-\d+/", $id, $matches)) {
         $id_minus_color = $matches[1];

         $update_sth->bindParam(1, $id_minus_color);
         $update_sth->bindParam(2, $id);
         $update_sth->execute();
      }
   }
}

# Used once to download json files from lego that tell there the manual PDFs are
if (0) {
   $query = "SELECT id FROM `sets` ";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $id = $row[0];
      $core_id = $id;
      if (preg_match("/(.*)\-\d+$/", $id, $match)) {
         $core_id = $match[1];
      }

      $old_output_filename = "/var/www/lego/html/sets/$id/lego-$core_id.json";
      if (file_exists($old_output_filename)) {
         system("rm -rf $old_output_filename");
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
}

#    array(7) {
#      ["ImageLocation"]=>
#      string(61) "http://cache.lego.com/images/shop/prod/10240-0000-XX-11-1.jpg"
#      ["ProductName"]=>
#      string(32) "Red Five X-wing Starfighter (TM)"
#      ["ProductId"]=>
#      string(5) "10240"
#      ["PdfLocation"]=>
#      string(67) "http://cache.lego.com/bigdownloads/buildinginstructions/6052406.pdf"
#      ["DownloadSize"]=>
#      string(8) "242.1 Mb"
#      ["Description"]=>
#      string(27) "BI 3019/72+4, 10240 1/3 V39"
#      ["IsAlternative"]=>
#      bool(false)

#
# Used once to parse the json files about PDF manuals and put the data from the jsons in the sets_manual table
#
// dwalton
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

#
# Used Once: Download all of the manual PDFs in the sets_manual table
#
if (0) {
   $query = "SELECT url FROM `sets_manual` ";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $url = $row[0];
    
      if (preg_match("/\/(\w+\.pdf)/", $url, $match)) {
         $pdf_filename = $match[1];
         $output_filename = "/var/www/lego/private/manuals/$pdf_filename";

         if (file_exists($output_filename)) {
            print "SKIP: $output_filename\n";
         } else {
            print("wget $ua -O $output_filename '$url'\n");
            system("wget $ua -O $output_filename '$url'");
         }
      }
   }
}


#
# Used Once: create a directory for each set and move the set images
#
if (0) {
   $query = "SELECT id, img, `img-tn` FROM sets";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $id = $row[0];
      $img = $row[1];
      $img_tn = $row[2];
      system("mkdir sets/$id");
      system("mv /var/www/lego/html$img sets/$id/");
      system("mv /var/www/lego/html$img_tn sets/$id/");
   }
}

if (0) {
   $query = "UPDATE sets SET img=?, `img-tn`=? WHERE id=?";
   $update_sth = $dbh->prepare($query);

   $query = "SELECT id FROM sets";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $id = $row[0];
      $img;
      $img_tn = "";

      $jpg = "sets/$id/S$id.jpg"; 
      $gif = "sets/$id/S$id.gif"; 

      $jpg_tn = "sets/$id/S$id-tn.jpg"; 
      $gif_tn = "sets/$id/S$id-tn.gif"; 

      if (file_exists($jpg)) {
         $img = "/$jpg";
      } else if (file_exists($gif)) {
         $img = "/$gif";
      }

      if (file_exists($jpg_tn)) {
         $img_tn = "/$jpg_tn";
      } else if (file_exists($gif_tn)) {
         $img_tn = "/$gif_tn";
      }

      if ($img != "") {
         $update_sth->bindParam(1, $img);
         $update_sth->bindParam(2, $img_tn);
         $update_sth->bindParam(3, $id);
         $update_sth->execute();
      }
   }
}

# used once
if (0) {
   $query = "UPDATE sets SET img_type=? WHERE id=?";
   $update_sth = $dbh->prepare($query);

   $query = "SELECT id, img, `img-tn` FROM sets";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $id = $row[0];
      $img = $row[1];
      $img_tn = $row[2];

      $extension = "";
      if (preg_match("/\.jpg/", $img, $match)) {
         $extension = "jpg";
      } else if (preg_match("/\.gif/", $img, $match)) {
         $extension = "gif";
      } else if (preg_match("/\.png/", $img, $match)) {
         $extension = "png";
      }

      if ($extension != "") {
         system("mv /var/www/lego/html$img sets/$id/main.$extension");
         system("mv /var/www/lego/html$img_tn sets/$id/tn.$extension");

         $update_sth->bindParam(1, $extension);
         $update_sth->bindParam(2, $id);
         $update_sth->execute();
      }
   }
}

if (0) {
   $query = "UPDATE bricks SET img=? WHERE `index`=?";
   $update_sth = $dbh->prepare($query);

   $query = "SELECT  `index`, bricklink_core_id, id, img FROM  `bricks` WHERE  `img` IS NOT NULL AND bricklink_core_id IS NOT NULL AND img NOT LIKE '/%parts%'";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $index   = $row[0];
      $core_id = $row[1];
      $id      = $row[2];
      $img     = $row[3];
      print "mv /var/www/lego/html$img parts/$core_id/\n"; 
      system("mv /var/www/lego/html$img parts/$core_id/\n"); 

      if (preg_match("/images\/(.*?)$/", $img, $match)) {
      
        #print "UPDATE /parts/$core_id/$match[1]\n\n"; 
        $new_img = "/parts/$core_id/" . $match[1];
        $update_sth->bindParam(1, $new_img);
        $update_sth->bindParam(2, $index);
        $update_sth->execute();
      } 
   }
}

if (0) {
   $query = "SELECT DISTINCT(theme_group) FROM sets WHERE theme_group IS NOT NULL ORDER BY theme_group ASC";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      printf("\"%s\",", $row[0]);
   }
}

?>
