<?php
define('INCLUDE_CHECK',true);
include "include/connect.php";
include "include/functions.php";

function getSetInventory($dbh, $id) {
   $bricks_for_set = array();
   $query = "SELECT brick_id, brick_quantity, bricks.img, bricks.description, bricks.price, bricks.part_type, bricks.dimensions, ".
            "(SELECT store_id FROM lego_store_inventory WHERE lego_store_inventory.brick_id= sets_inventory.brick_id LIMIT 1) AS pab_available ".
            "FROM sets_inventory ".
            "INNER JOIN bricks ON brick_id = bricks.id ".
            "INNER JOIN lego_colors ON bricks.color = lego_colors.bricklink_color ".
            "WHERE sets_inventory.id='$id' ".
         "ORDER BY CASE ".
         "WHEN color_group='Black' THEN 1 ".
         "WHEN color_group='Red' THEN 2 ".
         "WHEN color_group='Blue' THEN 3 ".
         "WHEN color_group='Gray' THEN 4 ".
         "WHEN color_group='Brown' THEN 5 ".
         "WHEN color_group='Yellow' THEN 6 ".
         "WHEN color_group='Green' THEN 7 ".
         "WHEN color_group='White' THEN 8 ".
         "WHEN color_group='Orange' THEN 9 ".
         "WHEN color_group='Purple' THEN 10 ".
         "ELSE 99 ".
         "END ASC, color ASC , bricks.part_type, bricks.dimensions, bricks.id";
   #print "SQL: $query<br>\n";

   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $brick = array();
      $brick['id']    = $row[0];
      $brick['qty']   = $row[1];
      $brick['img']   = $row[2];
      $brick['desc']  = $row[3];
      $brick['price'] = $row[4];
      $brick['type']  = $row[5];
      $brick['dimensions'] = $row[6];

      if ($row[7]) {
         $brick['pab'] = 1;
      }

      array_push($bricks_for_set, $brick);
   }

   return $bricks_for_set;
}

function printSetInventory($dbh, $set_to_build) {
   #
   # Find all of the bricks needed for the lego set we want to build
   #
   $bricks_required = array();
   $bricks_required = getSetInventory($dbh, $set_to_build);

   $bricklink_colors= array();
   $IDs_to_search_array = array();
   foreach ($bricks_required as $brick) {
      array_push($IDs_to_search_array, $brick['id']);

      if (preg_match("/\-(\d+)$/", $brick['id'], $matches)) {
         if (!array_key_exists($matches[1], $bricklink_colors)) {
            $bricklink_colors[$matches[1]] = $matches[1];
         }
      }
   }
   $IDs_to_search = implode(",", $IDs_to_search_array);


   $query = "SELECT name, img_type, pieces, price, year, min_age, max_age, theme, subtheme ".
            "FROM sets ".
            "WHERE id='$set_to_build'";
   $sth = $dbh->prepare($query);
   $sth->execute();
   $row = $sth->fetch();
   $set_name     = $row[0];
   $img_type     = $row[1];
   $set_pieces   = $row[2];
   $set_price    = $row[3];
   $set_year     = $row[4];
   $set_min_age  = $row[5];
   $set_max_age  = $row[6];
   $set_theme    = $row[7];
   $set_subtheme = $row[8];

   print "<div id='set_display_big' class='rounded_corners shadow'>\n";
   print "<div id='img_wrapper'>\n";
   print "<img src='/sets/$set_to_build/main.$img_type' width='600' /><br>";
   print "<h2>#$set_to_build - $set_name</h2>\n";
   print "</div>\n";

   print "<div id='set_info'>\n";

   if ($set_pieces) {
      print "<li>Pieces: $set_pieces</li>\n";
   }

   if ($set_price) {
      printf("<li>Price: %s</li>\n", centsToPrice($set_price));
   }

   if ($set_year) {
      print "<li>Year: $set_year</li>\n";
   }

   if ($set_min_age && $set_max_age) {
      print "<li>Age: $set_min_age - $set_max_age</li>\n";
   }

   if ($set_theme) {
      print "<li>Theme: $set_theme</li>\n";
   }

   if ($set_subtheme) {
      print "<li>Sub Theme: $set_subtheme</li>\n";
   }

   printf("<li>BrickLink: <a href='http://www.bricklink.com/catalogItem.asp?S=%s'>%s</a></li>", $set_to_build, $set_to_build);
   printf("<li>BrickSet: <a href='http://www.brickset.com/detail/?set=%s'>%s</a></li>", $set_to_build, $set_to_build);
   print "</div>\n";
   print "</div>\n";

   print "<div id='parts-list'>\n";
   print "<h1>Parts List</h1>";
   print "<table>\n";
   print "<tbody>\n";
   $col = 1;
   foreach ($bricks_required as $brick) {
      if ($col == 1) {
         print "<tr>\n";
      }

      $brick_id = $brick['id'];
      unset($brick['desc']);

      print "<td class='td-link center' url='/brick.php?id=$brick_id'>\n";
      print getBrickTDDisplay($brick);
      print "</td>\n";

      if ($col++ == 10) {
         print "</tr>\n";
         $col = 1;
      }
   }

   print "</tbody>\n";
   print "</table>\n";
   print "</div>\n";

   print "<div id='parts-filters'>\n";
   print "<h1>Parts Filter</h1>";
   print "<input type='hidden' name='wall-filter' value='filter-color'>\n";
   printWallFiltersForColorTypeDimensions(0, 1, $bricklink_colors);
   print "</div>\n";

   print "<div class='clear'></div>\n";
}


printHTMLHeader("Wall of Bricks - Set Display", "");
$dbh = dbConnect();

$id = "4475-1";
if (array_key_exists('set_to_build', $_GET)) {
   $id = $_GET['set_to_build'];
   if (!preg_match("/\w+-\d+/", $id, $matches)) {
      $id = $id . "-1";
   }
}

printSetInventory($dbh, $id);
printHTMLFooter(0, 0, 0, 0);
activityLog($dbh, "VIEW", $id);
?>
