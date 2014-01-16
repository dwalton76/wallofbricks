<?php

define('INCLUDE_CHECK',true);
include "include/functions.php";
printHTMLHeader("", "");
$dbh = dbConnect();

$theme = "";
if (array_key_exists('theme', $_GET)) {
   $theme = $_GET['theme'];
}

$subtheme = "";
if (array_key_exists('subtheme', $_GET)) {
   $subtheme = $_GET['subtheme'];
}

# TODO: add the interface for these
$min_year = 1960;
$max_year = 2013;
if (array_key_exists('year_range', $_GET)) {
   if (preg_match("/(.*) - (.*)/", $_GET['year_range'], $matches)) {
      $min_year = $matches[1];
      $max_year = $matches[1];
   }
}

$min_age = 0;
$max_age = 16;
if (array_key_exists('age_range', $_GET)) {
   if (preg_match("/(.*) - (.*)/", $_GET['age_range'], $matches)) {
      $min_age = $matches[1];
      $max_age = $matches[1];
   }
}

$min_price = 0;
$max_price = 1000;
if (array_key_exists('price_range', $_GET)) {
   if (preg_match("/\$(.*) - \$(.*)/", $_GET['price_range'], $matches)) {
      $min_price = $matches[1] * 100; // convert to cents
      $max_price = $matches[1] * 100; // convert to cents
   }
}

print "<span class='jquery_ver' id='min_year'>$min_year</span>\n";
print "<span class='jquery_ver' id='max_year'>$max_year</span>\n";

print "<span class='jquery_ver' id='min_age'>$min_age</span>\n";
print "<span class='jquery_ver' id='max_age'>$max_age</span>\n";

print "<span class='jquery_ver' id='min_price'>$min_price</span>\n";
print "<span class='jquery_ver' id='max_price'>$max_price</span>\n";

$page = 1;
if (array_key_exists('page', $_GET)) {
   $page = $_GET['page'];
}

?>
<form method='get' action='/category.php'>

<p>
  <label for="year_range">Year Range:</label>
  <input type="text" id="year_range" style="border: 0; color: #f6931f; font-weight: bold;" />
</p>
<div id="year_slider_range"></div>

<p>
  <label for="age_range">Age Range:</label>
  <input type="text" id="age_range" style="border: 0; color: #f6931f; font-weight: bold;" />
</p>
<div id="age_slider_range"></div>

<p>
  <label for="price_range">Price Range:</label>
  <input type="text" id="price_range" style="border: 0; color: #f6931f; font-weight: bold;" />
</p>
<div id="price_slider_range"></div>
<br><br>
<input type='hidden' name='theme' value='<? print $theme ?>'>
<input type='hidden' name='subtheme' value='<? print $subtheme ?>'>
<input type='submit'>
</form>
<?php

# Display all sets for a category
if ($theme) {
   $columns = array();
   array_push($columns, 'img');
   array_push($columns, 'id');
   array_push($columns, 'name');
   array_push($columns, 'year');
   array_push($columns, 'age');
   array_push($columns, 'price');
   array_push($columns, 'pieces');
   $sets_per_page = 9;

   $query_count = "SELECT COUNT(sets.id) FROM sets INNER JOIN sets_progress ON sets.id = sets_progress.id ";
   $query = "SELECT sets.id, img, name, `img-tn`, year, min_age, max_age, price, pieces, percent_complete, cost_to_complete, ".
               "(SELECT COUNT(sets_i_own.id) FROM sets_i_own WHERE sets_i_own.id = sets.id LIMIT 1) as i_own_it, ".
               "(SELECT COUNT(sets_wishlist.id) FROM sets_wishlist WHERE sets_wishlist.id = sets.id LIMIT 1) as on_wishlist ".
               "FROM sets ".
               "INNER JOIN sets_progress ON sets.id = sets_progress.id ";
   $where = "WHERE theme='$theme' ";
   if ($subtheme) {
      $where .= "AND subtheme='$subtheme' ";
   }

   if ($min_year) {
      $where .= "AND year >= $min_year AND year <= $max_year ";
   }

   if ($min_price) {
      $where .= "AND price >= $min_price ";
   }

   if ($max_price) {
      $where .= "AND price <= $max_price ";
   }

   if ($min_age) {
      $where .= "AND age >= $min_age ";
   }

   if ($max_age) {
      $where .= "AND age <= $max_age ";
   }

   $query .= $where . "ORDER BY sets.id " . sprintf("LIMIT %s, %s", ($page-1) * $sets_per_page, $sets_per_page);
   # print "SQL: $query\n";
   $sth = $dbh->prepare($query);
   $sth->execute();
   $position = "left";
   while ($row = $sth->fetch()) {
      $set = array();
      $set['id']      = $row[0];
      $set['img']     = $row[1];
      $set['name']    = $row[2];
      $set['img-tn']  = $row[3];
      $set['year']    = $row[4];
      $set['min_age'] = $row[5];
      $set['max_age'] = $row[6];
      $set['price']   = $row[7];
      $set['pieces']  = $row[8];
      $set['percent_complete']  = $row[9];
      $set['cost_to_complete']  = $row[10];
      $set['i_own_it']          = $row[11];
      $set['on_wishlist']       = $row[12];

      displaySetDiv($dbh, 0, $columns, $set, $position, $theme, $subtheme);

      if ($position == "left") {
         $position = "middle";
      } else if ($position == "middle") {
         $position = "right";
      } else if ($position == "right") {
         $position = "left";
      }
   }

   # Figure out how many sets there are in all so we can print the page "1 2 3 4" links 
   $query_count .= $where;
   # print "SQL: $query_count\n";
   $sth = $dbh->prepare($query_count);
   $sth->execute();
   $row = $sth->fetch();
   $total_sets = $row[0];
   # print "TOTAL: $total_sets<br>\n";

   $base_url = sprintf("/category.php?theme=%s&subtheme=%s", $theme, $subtheme);

   if ($min_age) {
      $base_url .= "&min_age=$min_age";
   }

   if ($max_age) {
      $base_url .= "&max_age=$max_age";
   }

   if ($min_price) {
      $base_url .= "&min_price=$min_price";
   }

   if ($max_price) {
      $base_url .= "&max_price=$max_price";
   }

   if ($min_year) {
      $base_url .= "&min_year=$min_year";
   }

   if ($max_year) {
      $base_url .= "&max_year=$max_year";
   }


   $base_url .= "&page=";
   printPrevNextLinks(10, 0, $total_sets, $sets_per_page, $base_url, $page, 1);

} else {
   $query = "SELECT DISTINCT theme FROM sets WHERE theme IS NOT NULL ";
   $sth = $dbh->prepare($query);
   $sth->execute();
   while ($row = $sth->fetch()) {
      print "<a href='/category.php?theme=$row[0]'>$row[0]</a><br>\n";
   }
}

printHTMLFooter();
?>
