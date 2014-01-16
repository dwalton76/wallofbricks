<?php

define('INCLUDE_CHECK',true);
include "include/functions.php";
printHTMLHeader("", "");
$dbh = dbConnect();

$pab_store_rows = 8;
$pab_store_cols = 12;

$submit = "";
if (array_key_exists('submit', $_GET)) {
   $submit = $_GET['submit'];
}

$pab_country = "USA";
if (array_key_exists('pab_country', $_GET)) {
   $categories = $_GET['pab_country'];
}

$pab_store_id = 0;
if (array_key_exists('pab_store_id', $_GET)) {
   $pab_store_id = $_GET['pab_store_id'];
}

$bricks_up_to_date = 0;
if (array_key_exists('bricks_up_to_date', $_GET)) {
   $bricks_up_to_date = $_GET['bricks_up_to_date'];
}

$current_row = 1;
if (array_key_exists('current_row', $_GET)) {
   $current_row = $_GET['current_row'];
}

$current_col = 1;
if (array_key_exists('current_col', $_GET)) {
   $current_col = $_GET['current_col'];
}

$categories = "";
foreach ($_GET as $key=>$value) {
   if (preg_match("/cat-(\w+)-(\w+)/", $key, $matches)) {
      if ($categories) {
         $categories .= "::";
      }
      $categories .= $matches[1] . ":" . $matches[2];

   } else if (preg_match("/cat-(\w+)/", $key, $matches)) {
      if ($categories) {
         $categories .= "::";
      }
      $categories .= $matches[1];
   }
}

# TODO: do this
$sets = "";

if ($submit == "Update Rows & Columns") {
   if (array_key_exists('pab_store_rows', $_GET)) {
      $pab_store_rows = $_GET['pab_store_rows'];
   }
   
   if (array_key_exists('pab_store_cols', $_GET)) {
      $pab_store_cols = $_GET['pab_store_cols'];
   }

   if ($pab_store_rows && $pab_store_cols && $pab_store_id) {
      $query = "UPDATE lego_store SET `rows`=?, `cols`=?, rows_cols_set='1' WHERE id=?";
      $sth = $dbh->prepare($query);
      $sth->bindParam(1, $pab_store_rows);
      $sth->bindParam(2, $pab_store_cols);
      $sth->bindParam(3, $pab_store_id);
      $sth->execute();
   } else {
      # TODO: dump an error to an error table
   }
}

function pickAStore($dbh, $pab_country, $pab_store_id) {
   print "<div id='pick-a-brick-select'>\n"; 
   print "<select id='pab_store_id' class='auto-submit' name='pab_store_id'>\n";
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
   #print "<br>\n";
   #print "<a href='$url_to_display'>$url_to_display</a>\n";
   print "</div>\n";
}


print "<form method='get' action='pab-helper.php'>\n";
$rows_cols_set = 0;
if ($pab_store_id) {
   $query = "SELECT rows_cols_set FROM lego_store WHERE id=?";
   $sth = $dbh->prepare($query);
   $sth->bindParam(1, $pab_store_id);
   $sth->execute();
   $row = $sth->fetch();
   $rows_cols_set = $row[0];
}

if (!$pab_store_id) {
   print "<div id='pick-a-store'>\n";
   print "<h1>Pick A Store before you Pick A Brick</h1>\n";
   pickAStore($dbh, $pab_country, $pab_store_id);
   # TODO: remember the store via a cookie
   print "</div>\n";

} else if (!$rows_cols_set) {
   print "<input type='hidden' name='pab_store_id' value='$pab_store_id' />\n";

   print "<div id='wall-dimensions'>\n";
   print "<h1>Wall Dimensions</h1>\n";
   print "<div id='wall-dimensions-rows'>\n";
   print "<h2><label for='pab_store_rows'>Rows</label></h2>\n";
   print "<input type='number' name='pab_store_rows' min='1' max='12' step='1' value='$pab_store_rows' />";
   print "</div>\n";

   print "<div id='wall-dimensions-cols'>\n";
   print "<h2><label for='pab_store_rows'>Columns</label></h2>\n";
   print "<input type='number' name='pab_store_cols' min='1' max='60' step='1' value='$pab_store_cols' />";
   print "</div>\n";
   print "<input type='submit' name='submit' value='Update Rows & Columns'>\n";

   #print "<input type='text' name='pab_store_rows' id='pab_store_rows' size='3'/>\n";
   print "</div>\n";

# Update what bricks are where
} else if (!$bricks_up_to_date) {
   print "<input type='hidden' name='pab_store_id' value='$pab_store_id' />\n";
   print "<input type='hidden' name='pab_store_rows' value='$pab_store_rows' />\n";
   print "<input type='hidden' name='pab_store_cols' value='$pab_store_cols' />\n";

   print "<h2>What brick is in Column $current_col x Row $current_row?</h2>\n";
/*
<select name='brick-generic-color' id='brick-generic-color'>
<option value=''></option>
<option value='brown'>Brown</option>
<option value='red' selected>Red</option>
<option value='orange'>Orange</option>
<option value='yellow'>Yellow</option>
<option value='green'>Green</option>
<option value='blue'>Blue</option>
<option value='purple'>Purple</option>
<option value='black'>Black</option>
<option value='white'>White</option>
<option value='gray'>Gray</option>
</select>
*/
?>
<br>
<h2>Generic Color</h2>
<div id='brick-generic-colors'>
<div class='color-sample generic brown' id='brown'><h3>Brown</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample generic red' id='red'><h3>Red</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample generic orange' id='orange'><h3>Orange</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample generic yellow' id='yellow'><h3>Yellow</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample generic green' id='green'><h3>Green</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample generic blue' id='blue'><h3>Blue</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample generic purple' id='purple'><h3>Purple</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample generic black' id='black'><h3>Black</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample generic white' id='white'><h3>White</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample generic gray' id='gray'><h3>Gray</h3><div class='color-fill'>&nbsp;</div></div>
</div>

<div id='brick-specific-colors'>
<h2>Specific Color</h2>
<div class='color-sample specific black' id='black'><h3>Black</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific gray' id='light-bluish-gray'><h3>Light Bluish Gray</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific white' id='white'><h3>White</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific red' id='red'><h3>Red</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific blue' id='blue'><h3>Blue</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific gray' id='dark-bluish-gray'><h3>Dark Bluish Gray</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific yellow' id='yellow'><h3>Yellow</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific gray' id='light-gray'><h3>Light Gray</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific brown' id='tan'><h3>Tan</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='green'><h3>Green</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific brown' id='reddish-brown'><h3>Reddish Brown</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific white' id='trans-clear'><h3>Transparent Clear</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific red' id='dark-red'><h3>Dark Red</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific orange' id='orange'><h3>Orange</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific red' id='trans-red'><h3>Transparent Red</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific brown' id='brown'><h3>Brown</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='lime'><h3>Lime</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific blue' id='dark-blue'><h3>Dark Blue</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific yellow' id='trans-yellow'><h3>Transparent Yellow</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific brown' id='dark-tan'><h3>Dark Tan</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='sand-green'><h3>Sand Green</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific blue' id='trans-dark-blue'><h3>Transparent Dark Blue</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific blue' id='medium-blue'><h3>Medium Blue</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='trans-neon-green'><h3>Transparent Neon Green</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific blue' id='trans-light-blue'><h3>Transparent Light Blue</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='dark-green'><h3>Dark Green</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific gold' id='pearl-gold'><h3>Pearl Gold</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific orange' id='trans-neon-orange'><h3>Transparent Neon Orange</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific gray' id='pearl-light-gray'><h3>Pearl Light Gray</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific black' id='trans-black'><h3>Transparent Black</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific orange' id='dark-orange'><h3>Dark Orange</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific blue' id='royal-blue'><h3>Royal Blue</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific orange' id='trans-orange'><h3>Transparent Orange</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='trans-green'><h3>Transparent Green</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific gray' id='flat-silver'><h3>Flat Silver</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific brown' id='dark-brown'><h3>Dark Brown</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific gray' id='chrome-silver'><h3>Chrome Silver</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific gray' id='metallic-silver'><h3>Metallic Silver</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific red' id='dark-pink'><h3>Dark Pink</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific purple' id='dark-purple'><h3>Dark Purple</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific blue' id='sand-blue'><h3>Sand Blue</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific red' id='bright-pink'><h3>Bright Pink</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific gray' id='pearl-dark-gray'><h3>Pearl Dark Gray</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='bright-green'><h3>Bright Green</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific red' id='trans-dark-pink'><h3>Transparent Dark Pink</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='dark-turquoise'><h3>Dark Turquoise</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific purple' id='purple'><h3>Purple</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific orange' id='bright-light-orange'><h3>Bright Light Orange</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific brown' id='medium-dark-flesh'><h3>Medium Dark Flesh</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific red' id='sand-red'><h3>Sand Red</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific purple' id='magenta'><h3>Magenta</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific blue' id='trans-medium-blue'><h3>Transparent Medium Blue</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific purple' id='trans-purple'><h3>Transparent Purple</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific purple' id='medium-lavender'><h3>Medium Lavender</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='medium-azure'><h3>Medium Azure</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific yellow' id='chrome-gold'><h3>Chrome Gold</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='olive-green'><h3>Olive Green</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='trans-bright-green'><h3>Transparent Bright Green</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='aqua'><h3>Aqua</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific yellow' id='metallic-gold'><h3>Metallic Gold</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific gray' id='very-light-bluish-gray'><h3>Very Light Bluish Gray</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific red' id='light-pink'><h3>Light Pink</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='light-aqua'><h3>Light Aqua</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific blue' id='dark-azure'><h3>Dark Azure</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific blue' id='bright-light-blue'><h3>Bright Light Blue</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific brown' id='light-flesh'><h3>Light Flesh</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific yellow' id='bright-light-yellow'><h3>Bright Light Yellow</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific green' id='chrome-green'><h3>Chrome Green</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific purple' id='lavender'><h3>Lavender</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific red' id='rust'><h3>Rust</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific yellow' id='trans-neon-yellow'><h3>Transparent Neon Yellow</h3><div class='color-fill'>&nbsp;</div></div>
<div class='color-sample specific black' id='speckle-black-silver'><h3>Speckle Black-Silver</h3><div class='color-fill'>&nbsp;</div></div>
</div>

<div id='lego-types'>
<div class='lego-type brick'>Brick</div>
<div class='lego-type brick'>Plate</div>
</div>
<?php


   // We've update the last row/column
   if ($current_col == $pab_store_cols &&
       $current_row == $pab_store_rows) {
      print "<input type='hidden' name='bricks_up_to_date' value='1' />\n";
   }

} else if (!$categories) {
   print "<input type='hidden' name='pab_store_id' value='$pab_store_id' />\n";

   print "What category of Lego sets would you like to focus on?";
   # TODO: remember the categories via a cookie
   # TODO: Also give option here to pick a set(s)

   $query = "SELECT DISTINCT (theme, subtheme) FROM sets ORDER BY theme, subtheme ASC ";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      $theme = $row[0];
      $subtheme = $row[0];
      if ($subtheme) {
         print "<li>\n";
         print "<label for='cat-$theme-$subtheme'>$theme $subtheme</label>\n";
         print "<input type='checkbox' name='cat-$theme-$subtheme' id='cat-$theme-$subtheme' value='1' />\n";
         print "</li>\n";
      } else {
         print "<li>\n";
         print "<label for='cat-$theme'>$theme</label>\n";
         print "<input type='checkbox' name='cat-$theme' id='cat-$theme' value='1' />\n";
         print "</li>\n";
      }
   }

} else {
}

print "</form>\n";

printHTMLFooter();
?>
