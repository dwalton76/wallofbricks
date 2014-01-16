<?php
define('INCLUDE_CHECK',true);
include "include/functions.php";
printHTMLHeader("", "");
$debug = 0;
$dbh = dbConnect();

# TODO: The shopping list page needs to subtract your qty+extras from the Buy amount
# TODO: have it load the city options dynamically based on the country selected
# TODO: print a map to the store location
# TODO: print some sort of summary at the top (# of bricks, cost to buy individually, etc)
# STRETCH: print the total volume consumed and how many cups it will take to hold it all?
# STRETCH: print the optimal way to pack a cup

# display list of PAB stores to choose from
$pab_store_id = 0;
if (array_key_exists('id', $_GET)) {
   $pab_store_id = $_GET['id'];
}

$pab_country = 'USA';
if (array_key_exists('country', $_GET)) {
   $pab_country = $_GET['country'];
}


print "<div id='pick-a-brick-select'>\n";
print "<form method='get' action='shopping-list.php'>\n";

print "<select id='pab_store_id' class='auto-submit' name='id'>\n";
$query = "SELECT id, city, state, country, url ".
         "FROM lego_store ".
         "WHERE country='$pab_country'".
         "ORDER BY state, city";
$sth = $dbh->prepare($query);
$sth->execute();
$url_to_display;
while ($row = $sth->fetch()) {
   $id      = $row[0];
   $city    = $row[1];
   $state   = $row[2];
   $country = $row[3];
   $url     = $row[4];

   $selected = "";
   if (!$pab_store_id) {
      $pab_store_id = $id;
      $selected = " selected";
      $url_to_display = $url;
   } else if ($id == $pab_store_id) {
      $selected = " selected";
      $url_to_display = $url;
   }

   if ($state) {
      print "<option value='$id'$selected>$state - $city</option>\n";
   } else {
      print "<option value='$id'$selected>$city</option>\n";
   }
}
print "</select>\n";

$query = "SELECT country FROM lego_store GROUP BY country ORDER BY country ASC ";
print "<select id='country' name='country'>\n";
$sth = $dbh->prepare($query);
$sth->execute();
$prev_country;
while ($row = $sth->fetch()) {
   $country = $row[0];

   $selected = "";
   if ($country == $pab_country) {
      $selected = " selected";
   }

   print "<option value='$country'$selected>$country</option>\n";
}
print "</select>\n";
print "</form>\n";
print "<a href='$url_to_display'>$url_to_display</a>\n";
print "</div>\n";


print "<div id='pick-a-brick-display'>\n";
$bricks_at_pab = array();
$bricks_at_pab = getPickABrickAvailableBricks($dbh, $pab_store_id);
$IDs_for_pab_bricks = array();
foreach ($bricks_at_pab as $brick) {
   $brick_id = $brick['id'];
   array_push($IDs_for_pab_bricks, $brick_id);
}

$IDs_for_pab_bricks = array_unique($IDs_for_pab_bricks);
asort($IDs_for_pab_bricks);
$IDs_for_pab_bricks_string = "'";
$IDs_for_pab_bricks_string .= implode("','", $IDs_for_pab_bricks);
$IDs_for_pab_bricks_string .= "'";


$columns = array();
array_push($columns, 'img');
array_push($columns, 'id');
array_push($columns, 'desc');
array_push($columns, 'qty');
array_push($columns, 'pab');

# print "IDs_for_pab_bricks_string: $IDs_for_pab_bricks_string<br>\n";
$mybricks_index = array();
$mybricks = array();
$mybricks = getMyBricks($dbh, $IDs_for_pab_bricks_string, "", "", 0);
foreach ($mybricks as $mybrick) {
   $id = $mybrick['id'];
   $mybricks_index[$id] = $mybrick;
}

#
# Now see which of the PickABrickAvailableBricks are used by the sets on our wishlist
#
$query = "SELECT sets_inventory.brick_id, MAX(brick_quantity), description, img, row, col, price ".
         "FROM sets_wishlist ".
         "INNER JOIN sets_progress ON sets_wishlist.id = sets_progress.id ".
         "INNER JOIN sets_inventory ON sets_wishlist.id = sets_inventory.id ".
         "INNER JOIN bricks ON sets_inventory.brick_id = bricks.id ".
         "INNER JOIN lego_store_inventory ON sets_inventory.brick_id = lego_store_inventory.brick_id ".
         "WHERE lego_store_inventory.store_id='$pab_store_id' AND ".
            "sets_inventory.brick_id IN ($IDs_for_pab_bricks_string) ".
         "GROUP BY sets_inventory.brick_id ".
         "ORDER BY row DESC, col ASC";
#print "SQL: $query\n";
$sth = $dbh->prepare($query);
$sth->execute();
$TD_array = array();
$max_col = 0;
$bricks_to_buy = 0;
$total_cost = 0;
while ($row = $sth->fetch()) {
   $brick = array();
   $brick['id']   = $row[0];
   $brick['qty']  = $row[1];
   $brick['desc'] = $row[2];
   $brick['img']  = $row[3];
   $brick['row']  = $row[4];
   $brick['col']  = $row[5];
   $brick['price']= $row[6];
   $brick_id = $brick['id'];

   if (isset($mybricks_index[$brick_id])) {
      $mybrick = $mybricks_index[$brick_id];
      if ($brick['qty'] > $mybrick['qty']) {
         $brick['qty'] -= $mybrick['qty'];

         if ($brick['qty'] > $mybrick['extras']) {
            $brick['qty'] -= $mybrick['extras'];
         } else {
            $brick['qty'] = 0;
         }
      } else {
         $brick['qty'] = 0;
      }

      if (!$brick['qty']) {
         continue;
      }
   }

   $row = $brick['row'];
   $col = $brick['col'];
   if ($col > $max_col) {
      $max_col = $col;
   }

   if (preg_match("/(\w+)\-/", $brick['id'], $matches)) {
      $id_only = $matches[1];
   }

   $total_cost += $brick['qty'] * $brick['price'];
   $bricks_to_buy += $brick['qty'];
   $TD_array[$col][$row] = sprintf("<img src='%s' /><br><a href='/brick.php?id=%s'>%s</a><br>Buy: %s",
                                   $brick['img'],
                                   $brick['id'],
                                   $id_only,
                                   $brick['qty']);
}

for ($i = 0; $i < roundUpTo($max_col/10, 1); $i++) {
   print "<div class='pick-a-brick'>\n";
   print "<table class='pick-a-brick'>\n";
   print "<thead>\n";

   $first_col = 1 + ($i * 10);
   $last_col = $first_col + 9; 
   print "<tr>";
   for ($col = $first_col; $col <= $last_col; $col++) {
      print "<th>$col</th>";
   }
   print "</tr>\n";
   print "</thead>\n";

   print "<tbody>\n";
   for ($row = 6; $row >= 1; $row--) {
      print "<tr>\n";
      for ($col = $first_col; $col <= $last_col; $col++) {
         printf("<td>%s</td>", $TD_array[$col][$row]);
      }
      print "</tr>\n";
   }

   print "</tbody>\n";
   print "</table>\n";
   print "</div>\n";
}

print "Bricks To Buy: $bricks_to_buy<br>\n";
printf("Cost via Bricklink: %s<br>\n", centsToPrice($total_cost));
print "</div>\n";

printHTMLFooter();
?>
