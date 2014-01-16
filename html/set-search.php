<?php
define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - Set Search", "");
print "<span class='hide' id='username'>$username</span>\n";
$dbh = dbConnect();

    // Display form to find sets
    $min_year = 1970;
    $max_year = 2015;
    $min_age = 0;
    $max_age = 16;
    $min_price = 0;
    $max_price = 500;
    $min_pieces = 1;
    $max_pieces = 6000;
?>

<div id='find-sets'>
<h1>Find Sets</h1>

<div id='set-id'>
<h2>Basic Search</h2>
<form id='set-id-form' method='get' action='/set.php' >
<label for='set-name-or-id'><h3>Set Name or ID</h3></label><br>
<input type='text' id='set-name-or-id' name='set_id' size='40' />
<input type='submit' class='hidden'>
</form>
</div>

<div id='sets-filters'>
<span class='hidden' id='min_year'><?php print $min_year ?></span>
<span class='hidden' id='max_year'><?php print $max_year ?></span>
<span class='hidden' id='min_age'><?php print $min_age ?></span>
<span class='hidden' id='max_age'><?php print $max_age ?></span>
<span class='hidden' id='min_price'><?php print $min_price ?></span>
<span class='hidden' id='max_price'><?php print $max_price ?></span>
<span class='hidden' id='min_pieces'><?php print $min_pieces ?></span>
<span class='hidden' id='max_pieces'><?php print $max_pieces ?></span>
<form>
<h2>Advanced Search</h2>
<label for="theme">Theme</label>
<br>
<input type="text" id="theme" name="theme" value="Star Wars"/>
<br><br>
<label for="year_range">Year:</label>
<input type="text" id="year_range" style="border: 0; color: #f6931f; font-weight: bold;" />
<div id="year_slider_range"></div>
<br>
<label for="age_range">Age:</label>
<input type="text" id="age_range" style="border: 0; color: #f6931f; font-weight: bold;" />
<div id="age_slider_range"></div>
<br>
<label for="price_range">Price:</label>
<input type="text" id="price_range" style="border: 0; color: #f6931f; font-weight: bold;" />
<div id="price_slider_range"></div>
<br>
<label for="pieces_range">Pieces:</label>
<input type="text" id="pieces_range" style="border: 0; color: #f6931f; font-weight: bold;" />
<div id="pieces_slider_range"></div>
<br>
</form>
</div>

</div>

<div class='clear'></div>
<div id='set-choices'></div>
<div class='clear'></div>

<div id='page_x_of_y'>Page <span id='page'>1</span>/<span id='last_page'>0</span></div>
<div id='sets-browse-controls'>
<a id='prev-set-by-search'><img src='/images/Arrow-Prev.png' class='clickable' width='128' /></a>
<a id='next-set-by-search'><img src='/images/Arrow-Next.png' class='clickable' width='128' /></a>
</div>
<div class='clear'></div>

<?php

printHTMLFooter(0, 0, 0, 0, $show_login_panel);
?>
