<?php

if (!defined('INCLUDE_CHECK')) {
    die('You are not allowed to execute this file directly');
}

function printAccountBenefits()
{
    print "<div id='login_required'>";
    print "<div id='img_wrapper'>\n";
    print "<img src=/images/lego_imperial_guard.png></img>";
    print "</div>\n";
    print "<div id='benefits'>\n";
    print "<h1>Login Required</h1>\n";
    print "You must create an account and login to view this page. Creating an account allows you to:\n";
    print "<ul>\n";
    print "<li>Edit PaB walls</li>\n";
    print "<li>Track which sets you own</li>\n";
    print "<li>Create a wishlist for sets you would like</li>\n";
    print "<li>Track extra bricks that you have purchased</li>\n";
    print "</ul>\n";
    print "</div>\n";
    print "</div>\n";
}

function printHTMLHeader($title, $head_lines)
{
?>
<!DOCTYPE>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="keywords" content="lego, legos, pick a brick, pick-a-brick">
<meta name="description" content="WallOfBricks lets you view the 'Pick A Brick' inventory for your local LEGO store. If the inventory is incomplete or out of date you can edit the wall to correct it.">
<meta name="robots" content="all"/>
<link rel="shortcut icon" href="favicon.ico">

<link rel="stylesheet" type="text/css" href="/include/login.css" media="screen" />
<link rel="stylesheet" type="text/css" href="/include/lego.css" media="screen" />
<link rel="stylesheet" type="text/css" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" media="screen" />
<link href='http://fonts.googleapis.com/css?family=Armata' rel='stylesheet' type='text/css'>
<title><?php print $title; ?></title>
</head>
<body>
<?php printLoginDropdown(); ?>
<div class="alignCenter"><!-- Centered Content -->
<header>
<div id="logoHeader">
<span id="logoStart">Wall</span>
<span id="logoEnd">&nbsp;of Bricks</span>
</div>
<div class="clear"></div>
<nav class='red'>
<ul class="clear">
<li><a href="/index.php">Home</a></li>

<li><a href="/pab-display.php">LEGO Stores</a>
<ul>
<li><a href="/pab-display.php" id='view-a-store'>View A Store</a></li>
<li><a href="/pab-update.php">Update A Store</a></li>
<li><a href="/wall-activity.php">Wall Activity</a></li>
</ul>
</li>

<li><a href="/brick-search.php">Search</a>
<ul>
<li><a href="/brick-search.php">Brick Search</a></li>
<li><a href="/set-search.php">Set Search</a></li>
</ul>
</li>

<li><a href="/mysets.php">My Account</a>
<ul>
<li><a href="/mysets.php">My Sets</a></li>
<li><a href="/mybricks.php">My Bricks</a></li>
<li><a href="/myaccount.php">Settings</a></li>
</ul>
</li>

<li><a href="/about-us.php">About Us</a>
<ul>
<li><a href="/help.php">help</a>
</ul>
</li>

</ul>
</nav>
<div class="clear"></div>
</header>
<?php

    // Make this TRUE to put the site in maintenance mode
    if (0) {
        print "<div id='under-contruction'>\n";
        print "<img src='/images/under-construction.jpg' width='690' />\n";
        print "<h2>We are down for some quick maintenance.  We should be up and running again in a few minutes.</h2>";
        print "</div>\n";
        printHTMLFooter();
        exit();
    }
}

function printHTMLFooter($load_store_from_cookie = 0, $address = 0, $latitude = 0, $longitude = 0, $show_login_panel = 0)
{
//<span class="copyright">Copyright (c) 2013 legoinventory.net. All rights reserved.</span>
?>
<footer>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<script type="text/javascript" src="/include/jquery.cookie.js"></script>
<script type="text/javascript" src="/include/login.js"></script>
<script type="text/javascript" src="/include/lego.js"></script>
<?php
    preg_match("/^\/(.*\.php)/", $_SERVER["REQUEST_URI"], $match);
    $page = $match[1];

    if ($page == "" || !$page ||
         $page == "index.php" ||
         $page == "pab-display.php" ||
         $page == "pab-update.php" ||
         $page == "wall-activity.php") {
        print "<script type='text/javascript' src='/include/lego_stores.js'></script>\n";
    }

    if ($page == "pab-update.php" || $page == "brick-search.php" || $page == "set-search.php") {
        print "<script type='text/javascript' src='http://code.jquery.com/ui/1.10.3/jquery-ui.min.js'></script>\n";
    }

    if ($page == "set-search.php") {
        print "<script type='text/javascript' src='/include/lego_set_search.js'></script>\n";
    }

    if ($page == "set.php") {
        print "<script type='text/javascript' src='/include/jquery.rs.slideshow.js'></script>\n";
        print "<script type='text/javascript' src='/include/lego_set.js'></script>\n";
        print "<link rel='stylesheet' type='text/css' href='/include/lego_set.css' media='screen' />\n";
    }

    if ($page == "instructions.php") {
        print "<script type='text/javascript' src='/include/elevatezoom-master/jquery.elevateZoom-3.0.8.min.js'></script>\n";
        print "<script type='text/javascript' src='/include/lego_instructions.js'></script>\n";
        print "<link rel='stylesheet' type='text/css' href='/include/lego_instructions.css' media='screen' />\n";
    }

    if ($page == "pab-update.php" || $page == "brick-search.php") {
        print "<script type='text/javascript' src='/include/lego_brick_search.js'></script>\n";
    }

    if ($page == "pab-display.php" || $page == "set.php" || $page == "mybricks.php" || $page == "instructions.php") {
        print "<script type='text/javascript' src='/include/lego_brick_fade_filters.js'></script>\n";
    }

    if ($page == "brick.php" || $page == 'minifig.php') {
        print "<script type='text/javascript' src='/include/lego_brick.js'></script>\n";
    }

    if ($page == 'minifig-brick.php') {
        print "<script type='text/javascript' src='/include/lego_minifig_brick.js'></script>\n";
    }
?>
<script>
  (function (i,s,o,g,r,a,m) {i['GoogleAnalyticsObject']=r;i[r]=i[r]||function () {
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-42051917-1', 'wallofbricks.com');
  ga('send', 'pageview');
</script>

<?php

    if ($load_store_from_cookie) {
?>
<script>
$(document).ready(function () {
    // The last time the user visited the page we saved the store he viewed in a cookie.
    // Use that to return to the same store.
    var pab_store_id = $.cookie("pab_store_id");
    var pab_country = $.cookie("pab_country");
    if (pab_store_id && pab_country) {
        // console.log("Load store_id cookie: "+ pab_store_id);
        // console.log("Load country cookie: "+ pab_country);
        $('#pab_store_id').val(pab_store_id);
        $('#country').val(pab_country);
        loadPerCountryCityStateOptions();
    }
});
</script>
<?php
    } // End of if ($load_store_from_cookie)

    if ($address && $latitude && $longitude) {
?>
<script src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
<script>
    var map;
    function initialize()
    {
      var myLatLong = new google.maps.LatLng(<?php print "$latitude, $longitude" ?>);
      var mapOptions = {
         zoom: 16,
         center: myLatLong,
         mapTypeId: google.maps.MapTypeId.ROADMAP
      };

      var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

      var marker = new google.maps.Marker({
          position: myLatLong,
          map: map,
          title: '<?php print $address?>'
      });
    }

    google.maps.event.addDomListener(window, 'load', initialize);
</script>
<?php
    } // End of if ($address && $latitude && $longitude)

    /*
     * If you want the sliding panel to be down when the page loads run this:
     */
    if ($show_login_panel) {
?>
<script>
$(document).ready(function () {
    $("div#panel").show();
    $("#toggle a").toggle();
});
</script>
<?php
    } // End of if ($show_login_panel)

?>

<div id='twitter'>
<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.wallofbricks.com" data-via="dwalton76" data-size="large">Tweet</a>
<script>!function (d,s,id) {var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if (!d.getElementById(id)) {js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
</div>

<div id='googleplus'>
<div class="g-plusone" data-annotation="none"></div>

<script type="text/javascript">
  (function () {
     var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
     po.src = 'https://apis.google.com/js/plusone.js';
     var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
  })();
</script>
</div>

<div id="fb-root"></div>
<script>(function (d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>

<div id='facebook'>
<div class="fb-like" data-href="http://www.wallofbricks.com/" data-send="true" data-width="450" data-show-faces="true"></div>
</div>

</footer>
</div>
</body>
</html>
<?php
}

function centsToPrice($cents)
{
    return sprintf("$%.2f", $cents/100);
}

function getPickABrickAvailableBricks($dbh, $store_id)
{
    $bricks = array();

    $query = "SELECT brick_id, description, design_id, `row`, col, slot ".
             "FROM lego_store_inventory ".
             "INNER JOIN bricks ON brick_id = bricks.id ".
             "WHERE store_id='$store_id'";
    # print "SQL: $query<br>\n";
    $sth = $dbh->prepare($query);
    $sth->execute();
    while ($row = $sth->fetch()) {
        $brick = array();
        $brick['id']     = $row[0];
        $brick['desc']  = $row[1];
        $brick['design_id'] = $row[2];
        $brick['row']    = $row[3];
        $brick['col']    = $row[4];
        $brick['slot']  = $row[5];
        $brick['img']    = "/parts/" . $brick['design_id'] . "/" . $brick['id'] . ".jpg";
        array_push($bricks, $brick);
    }

    return $bricks;
}

//
// Print the select dropdowns so the user can pick the store they want to work on
//
function pickAStore($dbh, $pab_country, $pab_store_id, $autosubmit, $one_line)
{
    $pab_store_name = "";
    print "<div id='pick-a-brick-select'>\n";
    print "<h1>Pick A Store</h1>\n";

    # First pick the country
    $query = "SELECT country FROM lego_store GROUP BY country ORDER BY country ASC ";
    print "<select id='country' name='country'>\n";
    // print "TARGET: $pab_country";
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

    # Then pick the store in that country
    $query = "SELECT id, city, state, country, url ".
                "FROM lego_store ".
                "WHERE country='$pab_country' AND id > 0 ".
                "ORDER BY state, city";
    # print "SQL: $query<br>\n";

    if ($autosubmit) {
        print "<select id='pab_store_id' class='auto-submit' name='pab_store_id'>\n";
    } else {
        print "<select id='pab_store_id' name='pab_store_id'>\n";
    }

    $sth = $dbh->prepare($query);
    $sth->execute();
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
            $pab_store_city = $city;
        } elseif ($id == $pab_store_id) {
            $selected = " selected";
            $pab_store_city = $city;
        }

        if ($state) {
            print "<option value='$id'$selected>$state - $city</option>\n";
        } else {
            print "<option value='$id'$selected>$city</option>\n";
        }
    }
    print "</select>\n";

    if (!$one_line) {
        print "<br>\n";
    }

    print "<input type='submit' id='pick-a-store-submit' />\n";
    print "</div>\n";

    // Return Cardiff instead of Cardiff, Wales
    if (preg_match("/(.*),/", $pab_store_city, $matches)) {
        $pab_store_city = $matches[1];
    }

    return $pab_store_city;
}

function getWallInformation($dbh, $pab_store_id)
{
    if (!$pab_store_id) {
        return array(0, 0, 0);
    }

    $query = "SELECT rows, cols, address, phone_number, latitude, longitude FROM lego_store WHERE id=?";
    $sth = $dbh->prepare($query);
    $sth->bindParam(1, $pab_store_id);
    $sth->execute();
    $row = $sth->fetch();

    return array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5]);
}

function getBrickTDDisplay($brick)
{
    $brick_id = $brick['id'];

    $brick_img = 0;
    if (array_key_exists('img', $brick)) {
        $brick_img = $brick['img'];
    }

    $brick_img_large = 0;
    if (array_key_exists('img-large', $brick)) {
        $brick_img_large = $brick['img-large'];
    }

    $brick_desc = 0;
    if (array_key_exists('desc', $brick)) {
        $brick_desc = $brick['desc'];
    }

    $brick_type = 0;
    if (array_key_exists('type', $brick)) {
        $brick_type = $brick['type'];
    }

    $brick_dimensions = 0;
    if (array_key_exists('dimensions', $brick)) {
        $brick_dimensions = $brick['dimensions'];
    }

    $brick_updated_on = 0;
    if (array_key_exists('updated_on', $brick)) {
        $brick_updated_on = $brick['updated_on'];
    }

    $shortage = 0;
    if (array_key_exists('shortage', $brick)) {
        $shortage = $brick['shortage'];
    }

    $price = 0;
    if (array_key_exists('price', $brick) && $brick['price']) {
        $price = centsToPrice($brick['price']);
    }

    if (array_key_exists('pab', $brick) && $brick['pab']) {
        $on_pab_wall = 1;
    } else {
        $on_pab_wall = 0;
    }

    $id_only = $brick['id'];
    if (preg_match("/(\w+)\-/", $brick_id, $matches)) {
        $id_only = $matches[1];
    }

    $color = 0;
    if (array_key_exists('color', $brick)) {
        $color = $brick['color'];
    }

    $extra_classes = "";
    if (array_key_exists('duplicates', $brick)) {
        $extra_classes = " duplicate";
    }

    $string = "<div class='td-guts$extra_classes'";
    if ($color) {
        $color_no_spaces = str_replace(" ", "-", $color);
        $string .= " color='$color_no_spaces'";
    }

    if ($brick_desc) {
        $string .= " title='$brick_desc'";
    }

    if ($brick_type) {
        $string .= " brick_type='$brick_type'";
    }

    $qty = 0;
    if (array_key_exists('qty', $brick)) {
        $qty= $brick['qty'];
    }

    $string .= " brick_dimensions='$brick_dimensions'";
    $string .= " shortage='$shortage'";
    $string .= " on_pab_wall='$on_pab_wall'>";

    if ($brick_img_large) {
        $string .= "<a href='/brick.php?id=$brick_id'><img src='$brick_img_large' width='192px' height='192px' alt='LEGO Part $brick_id' /></a><br>";
    } elseif ($brick_img) {
        $string .= "<a href='/brick.php?id=$brick_id'><img src='$brick_img' width='64px' height='64px' alt='LEGO Part $brick_id' /></a><br>";
    }

    if (array_key_exists('qty', $brick)) {
        $string .= "<span class='qty'>QTY: " . $brick['qty'] . "</span>\n";
    }

    if ($shortage) {
        $string .= "<span class='qty-shortage'>NEED ". $shortage ."/". $brick['qty'] . "</span>\n";
    } elseif ($qty) {
        $string .= "<span class='qty-shortage'>QTY: " . $brick['qty'] . "</span>\n";
    }

    #if ($price != "$0.00") {
    if ($price) {
        $string .= "<span class='price'>$price</span>\n";
    } elseif ($brick_updated_on) {
        $string .= "<span class='updated-on'>$brick_updated_on</span>\n";
    } else {
        $string .= "<span class='price'>&nbsp;</span>\n";
    }

    $string .= "</div>";

    return $string;
}

function getBrickTDDisplayWithSprite($brick, $set_id, $col, $row)
{
    $brick_id = $brick['id'];

    $brick_img = 0;
    if (array_key_exists('img', $brick)) {
        $brick_img = $brick['img'];
    }

    $brick_desc = 0;
    if (array_key_exists('desc', $brick)) {
        $brick_desc = $brick['desc'];
    }

    $brick_type = 0;
    if (array_key_exists('type', $brick)) {
        $brick_type = $brick['type'];
    }

    $brick_dimensions = 0;
    if (array_key_exists('dimensions', $brick)) {
        $brick_dimensions = $brick['dimensions'];
    }

    $brick_updated_on = 0;
    if (array_key_exists('updated_on', $brick)) {
        $brick_updated_on = $brick['updated_on'];
    }

    if (array_key_exists('pab', $brick) && $brick['pab']) {
        $on_pab_wall = 1;
    } else {
        $on_pab_wall = 0;
    }

    $id_only = $brick['id'];
    if (preg_match("/(\w+)\-/", $brick_id, $matches)) {
        $id_only = $matches[1];
    }

    $blcolor = 0;
    if (preg_match("/\-(\d+)/", $brick_id, $matches)) {
        $blcolor = $matches[1];
    }

    $extra_classes = "";
    if (array_key_exists('duplicates', $brick) && $brick['duplicates'] > 1) {
        $extra_classes = " duplicate";
    }

    $string = "<div class='td-guts$extra_classes'";
    if ($blcolor) {
        $string .= " blcolor='$blcolor'";
    }

    if ($brick_type) {
        $string .= " brick_type='$brick_type'";
    }

    #if ($brick_dimensions) {
        $string .= " brick_dimensions='$brick_dimensions'";
    #}

    $string .= " on_pab_wall='$on_pab_wall'>";

    if ($brick_desc) {
        $string .= "<h2>$brick_desc</h2>";
    }

    if ($brick_img) {
        $left_offset = ($col - 1) * -82;
        $top_offset = ($row - 1) * -62;

        $string .= sprintf("<a href='/brick.php?id=%s'><img class='sprite-brick' src='/images/img_trans.gif' style='background:url(/sets/%s/parts.jpg) %dpx %dpx;' /></a><br>",
                                  $brick_id, $set_id, $left_offset, $top_offset);
    }

    if ($brick_updated_on) {
        $string .= "<span class='updated-on'>$brick_updated_on</span>\n";
    }

    if (array_key_exists('qty', $brick)) {
        $string .= "<span class='qty'>QTY: " . $brick['qty'] . "</span>\n";
    }

    $string .= "</div>";

    return $string;
}

function getBrickTDDisplayForInstructions($brick)
{
    $brick_id = $brick['id'];

    $brick_img = 0;
    if (array_key_exists('img', $brick)) {
        $brick_img = $brick['img'];
    }

    $brick_img_large = 0;
    if (array_key_exists('img-large', $brick)) {
        $brick_img_large = $brick['img-large'];
    }

    $brick_desc = 0;
    if (array_key_exists('desc', $brick)) {
        $brick_desc = $brick['desc'];
    }

    $brick_type = 0;
    if (array_key_exists('type', $brick)) {
        $brick_type = $brick['type'];
    }

    $brick_dimensions = 0;
    if (array_key_exists('dimensions', $brick)) {
        $brick_dimensions = $brick['dimensions'];
    }

    $id_only = $brick['id'];
    if (preg_match("/(\w+)\-/", $brick_id, $matches)) {
        $id_only = $matches[1];
    }

    $color = 0;
    if (array_key_exists('color', $brick)) {
        $color = $brick['color'];
    }

    $string = "<div class='td-guts'";
    if ($color) {
        $color_no_spaces = str_replace(" ", "-", $color);
        $string .= " color='$color_no_spaces'";
    }

    if ($brick_desc) {
        $string .= " title='$brick_desc'";
    }

    if ($brick_type) {
        $string .= " brick_type='$brick_type'";
    }

    $qty = 0;
    if (array_key_exists('qty', $brick)) {
        $qty = $brick['qty'];
    }

    $string .= " brick_dimensions='$brick_dimensions'";
    $string .= ">";

    $string .= "<a href='/brick.php?id=$brick_id'><img src='$brick_img' width='64px' height='64px' alt='LEGO Part $brick_id' /></a>";

    if (array_key_exists('on-page', $brick)) {
        $string .= "<span class='qty'>Used: <span class='used'>" . $brick['on-page'] . "</span></span>\n";
    } else {
        $string .= "<span class='qty'>Used: <span class='used'>0</span></span>\n";
    }

    $string .= "<span class='qty'>Avail: <span class='available'>$qty</span></span>\n";
    $string .= "<img class='jquery_set_inventory_add_part' src='/images/plus.png' width='26px' />\n";
    $string .= "<img class='jquery_set_inventory_del_part' src='/images/minus.png' width='26px' />\n";
    $string .= "</div>";

    return $string;
}

function printPartSearchForm($dbh, $save_part)
{
    print "<div id='brick-color-and-keyword'>\n";
    print "<div id='brick-color-selection'>\n";
    print "<div id='brick-generic-colors'>\n";
    print "<h2>Generic Color</h2>\n";

    $query = "SELECT color_group, rgb FROM lego_colors ".
            "WHERE color_group_rep=1 ".
            "ORDER BY CASE ".
            "WHEN color_group='Black' THEN 1 ".
            "WHEN color_group='Red' THEN 2 ".
            "WHEN color_group='Blue' THEN 3 ".
            "WHEN color_group='Grey' THEN 4 ".
            "WHEN color_group='Brown' THEN 5 ".
            "WHEN color_group='Yellow' THEN 6 ".
            "WHEN color_group='Green' THEN 7 ".
            "WHEN color_group='White' THEN 8 ".
            "WHEN color_group='Orange' THEN 9 ".
            "WHEN color_group='Purple' THEN 10 ".
            "ELSE 99 END";

    # print "SQL: $query<br>\n";
    $sth = $dbh->prepare($query);
    $sth->execute();

    $selected = " color-selected";
    while ($row = $sth->fetch()) {
        $color_group = $row[0];
        $rgb = $row[1];
        print "<div class='color-sample generic $color_group$selected' id='$color_group'><h3>$color_group</h3><div class='color-fill' style='background-color: $rgb'>&nbsp;</div></div>\n";
        $selected = "";
    }
    print "</div>\n";

    # Get a count of how many colors there are in each major color group
    $color_group_count = array();
    $query = "SELECT color_group, COUNT(brickset_color) ".
                "FROM lego_colors ".
                "WHERE brickset_color IS NOT NULL AND number_parts IS NOT NULL ".
                "GROUP BY (color_group)";
    $sth = $dbh->prepare($query);
    $sth->execute();
    while ($row = $sth->fetch()) {
        $color_group_count[$row[0]] = $row[1];
        # $color_group = $row[0];
        # $count = $row[1];
    }

    $query = "SELECT color_group, brickset_color, rgb FROM lego_colors ".
            "WHERE brickset_color IS NOT NULL AND number_parts IS NOT NULL ".
            "ORDER BY CASE ".
            "WHEN color_group='Black' THEN 1 ".
            "WHEN color_group='Red' THEN 2 ".
            "WHEN color_group='Blue' THEN 3 ".
            "WHEN color_group='Grey' THEN 4 ".
            "WHEN color_group='Brown' THEN 5 ".
            "WHEN color_group='Yellow' THEN 6 ".
            "WHEN color_group='Green' THEN 7 ".
            "WHEN color_group='White' THEN 8 ".
            "WHEN color_group='Orange' THEN 9 ".
            "WHEN color_group='Purple' THEN 10 ".
            "ELSE 99 ".
            "END ASC, number_parts DESC ";

    # print "SQL: $query<br>\n";
    $sth = $dbh->prepare($query);
    $sth->execute();
    $prev_color_group = 0;
    $index_in_color_group = 0;

    while ($row = $sth->fetch()) {
        $color_group = $row[0];
        $color = $row[1];
        $rgb = $row[2];
        $color_no_spaces = str_replace(" ", "-", $color);
        $selected = "";

        if (!$prev_color_group) {
            print "<div class='brick-specific-colors $color_group' color='$color_group'>\n";
            print "<h2>Specific Color</h2>\n";
            print "<div class='go-back-to-generic'>\n";
            print "<img src='/images/Arrow-Prev.png' width='128px' />\n";
            print "</div>\n";
            print "<div class='color-squares'>\n";
            $selected = " color-selected";
            $index_in_color_group = 1;

        } elseif ($prev_color_group != $color_group) {
            $index_in_color_group = 1;
            print "</div>\n";
            print "</div>\n";
            print "<div class='brick-specific-colors $color_group' color='$color_group'>\n";
            print "<h2>Specific Color</h2>\n";
            print "<div class='go-back-to-generic'>\n";
            print "<img src='/images/Arrow-Prev.png' width='128px' />\n";
            print "</div>\n";
            print "<div class='color-squares'>\n";
            $selected = " color-selected";

        } else {
            $index_in_color_group++;
        }

        $corner_case = '';

        if ($color_group_count[$color_group] > 8) {
            if ($index_in_color_group >= 8) {
                 $corner_case = ' corner-case';
            }

            if ($index_in_color_group == 8) {
                print "<div class='color-sample $color_group show-more' id='show-more-$color_group'><h3>More $color_group Colors</h3><div class='color-fill' style='background-color: #FFFFFF'>&nbsp;</div></div>\n";
            }
        }

        print "<div class='color-sample$corner_case specific $color_group$selected' id='$color_no_spaces'><h3>$color</h3><div class='color-fill' style='background-color: $rgb'>&nbsp;</div></div>\n";

        $prev_color_group = $color_group;
    }
    print "</div>\n";
    print "</div>\n";
?>
</div>

<div class='clear'></div>
<div id='keyword-filters'>
<div id='keyword-wrapper'>
<h2>Keyword</h2>
<input type='text' size='12' id='keyword_filter' name='keyword_filter' value='' />
</div>
<div id='lego-id-wrapper'>
<h2>LEGO ID</h2>
<input type='text' size='12' id='lego_id' name='lego_id' value='' />
</div>
</div>
</div>

<div id='non-color-attributes'>
<div id='lego-types'>
<h2>LEGO Type</h2>
<div id='brick' class='lego-type color-selected'>Brick</div>
<div id='plate' class='lego-type'>Plate</div>
<div id='tile' class='lego-type'>Tile</div>
<div id='slope' class='lego-type'>Slope</div>
<div id='technic' class='lego-type'>Technic</div>
<div id='minifig' class='lego-type'>Minifig</div>
<div id='other' class='lego-type'>Other</div>
</div>
<?php printDimensionsSelector(2, 4); ?>
</div>

<div class='clear'></div>
<div id='lego-choices'></div>
<?php
    if ($save_part == 1) {
        print "<input type='hidden' id='save_part' name='save_part' value='1' />\n";
    }
}

function validStoreID($pab_store_id)
{
    if ($pab_store_id < 1 || !is_numeric($pab_store_id)) {
        return 0;
    }

    return 1;
}

function validateRow($brick_row, $pab_store_rows)
{
    if ($brick_row < 1) {
        return 1;
    } elseif ($brick_row > $pab_store_rows) {
        return $pab_store_rows;
    } elseif (!is_numeric($brick_row)) {
        return $pab_store_rows;
    }

    return $brick_row;
}

function validateCol($brick_col, $pab_store_cols)
{
    if ($brick_col < 1) {
        return 1;
    } elseif ($brick_col > $pab_store_cols) {
        return $pab_store_cols;
    } elseif (!is_numeric($brick_col)) {
        return $pab_store_cols;
    }

    return $brick_col;
}

function displaySetDiv($set, $username = 0, $show_delete = 0)
{
    printf("<div class='set_display rounded_corners shadow dom-link' url='/set.php?set_id=%s'>\n", $set['id']);

    $qty_bricks_string = "";
    if (!array_key_exists('subset', $set) &&
         array_key_exists('qty_bricks', $set) && $set['qty_bricks']) {
        $qty_bricks_string = "<br>This Brick: ". $set['qty_bricks'];
    }
    print "<div class='set_image'>\n";
    printf("<img src='%s' width='180px' alt='%s Image' />", $set['img-tn'], $set['id']);
    printf("<span class='set_name'><a href='/set.php?set_id=%s'>#%s %s</a>%s</span>",
             $set['id'], $set['id'], $set['name'], $qty_bricks_string);
    print "</div>\n";

    if ($username) {
        $own_it = 0;
        $wishlist = 0;
        if (isset($set['i_own_it']) && $set['i_own_it']) {
            $own_it = 1;
        } elseif (isset($set['on_wishlist']) && $set['on_wishlist']) {
            $wishlist = 1;
        }

        print "<div class='set_info'>\n";

        if ($show_delete) {
            if ($own_it) {
                printf("<span class='jquery_remove_set owned amazon clickable' remove_id='%s'>Delete</span>\n", $set['id']);
            } elseif ($wishlist) {
                printf("<span class='jquery_remove_set wishlist amazon clickable' remove_id='%s'>Delete</span>\n", $set['id']);
            }
        }

        #if (isset($set['i_own_it']) && $set['i_own_it']) {
        if ($own_it) {
            if (!$show_delete) {
                print "<div class='own-status'>\n";
                print "<img src='/images/Checkmark.png' width='50' alt='I Own It' />\n";
                print "</div>";
            }

        } else {
            print "<div class='own-status'>\n";
            printf("<span class='jquery_add_set owned amazon clickable' add_id='%s'>Add To<br>My Sets</span>\n", $set['id']);
            print "</div>\n";

            if (!$show_delete) {
                print "<div class='wishlist-status'>\n";
                # if (isset($set['on_wishlist']) && $set['on_wishlist']) {
                if ($wishlist) {
                    printf("<img src='/images/wishlist.png' width='50' alt='On Wish List' />");

                } else {
                    # When the user clicks this button jquery will catch it and will use ajax
                    # to add the item to the wishlist and replace the button with the wishlist icon
                    printf("<span class='jquery_add_set wishlist amazon clickable' add_id='%s'>Add To Wishlist</span>\n", $set['id']);
                }
                print "</div>\n";
            }
        }
        print "</div>\n";
    }

    print "</div>\n";
}

function displayMinifigDiv($set)
{
    printf("<div class='minifig_display rounded_corners shadow dom-link' url='/minifig.php?id=%s'>\n", $set['id']);

    print "<div class='minifig_image'>\n";
    printf("<img src='%s' alt='%s Image' />", $set['img-tn'], $set['id']);
    printf("<span class='set_name'><a href='/minifig.php?id=%s'>#%s %s</a></span>",
             $set['id'], $set['id'], $set['name']);
    print "</div>\n";
    print "</div>\n";
}

function printLegoTypeSelector()
{
?>
<div id='lego-types'>
<h2>LEGO Type</h2>
<div id='all-types' class='lego-type color-selected'>All</div>
<div id='brick' class='lego-type'>Brick</div>
<div id='plate' class='lego-type'>Plate</div>
<div id='tile' class='lego-type'>Tile</div>
<div id='slope' class='lego-type'>Slope</div>
<div id='technic' class='lego-type'>Technic</div>
<div id='minfig' class='lego-type'>Minifig</div>
<div id='other' class='lego-type'>Other</div>
</div>
<?php
}

function printDimensionsSelector($default_x, $default_y)
{
?>
<div id='lego-dimensions'>
<h2>Dimensions</h2>
<span class='tiny'>Use '0 x 0' to search all dimensions</span>
<input type='hidden' id='dimension_x' name='dimension_x' value='<?php print $default_x ?>' />
<input type='hidden' id='dimension_y' name='dimension_y' value='<?php print $default_y ?>' />
<div id='dimension_xy_display'>
<span id='dimension_x_display'><?php print $default_x ?></span> x <span id='dimension_y_display'><?php print $default_y ?></span>
</div>

<div id='dimension_x_modifiers'>
<div class='dimension_modifier' id='increment_dimension_x'><img src='/images/plus.png' width='80' /></div>
<div class='dimension_modifier' id='decrement_dimension_x'><img src='/images/minus.png' width='80' /></div>
</div>

<div id='dimension_y_modifiers'>
<div class='dimension_modifier' id='increment_dimension_y'><img src='/images/plus.png' width='80' /></div>
<div class='dimension_modifier' id='decrement_dimension_y'><img src='/images/minus.png' width='80' /></div>
</div>
</div>
<?php
}

function printColorSelector($dbh, &$brickset_colors)
{
    print "<h3>Colors</h3>\n";
    print "<div class='color-filter color-selected' id='all-colors'><h3>All Colors</h3><div class='color-fill'>&nbsp;</div></div>\n";

    $color_string = "('" . implode("','", $brickset_colors) . "')";
    # print "COLOR_STRING: $color_string<br>\n";
    $query = "SELECT brickset_color, rgb FROM lego_colors WHERE brickset_color IN $color_string ".
            "ORDER BY CASE ".
            "WHEN color_group='Black' THEN 1 ".
            "WHEN color_group='Red' THEN 2 ".
            "WHEN color_group='Blue' THEN 3 ".
            "WHEN color_group='Grey' THEN 4 ".
            "WHEN color_group='Brown' THEN 5 ".
            "WHEN color_group='Yellow' THEN 6 ".
            "WHEN color_group='Green' THEN 7 ".
            "WHEN color_group='White' THEN 8 ".
            "WHEN color_group='Orange' THEN 9 ".
            "WHEN color_group='Purple' THEN 10 ".
            "ELSE 99 ".
            "END ASC, brickset_color ASC ";

    # print "SQL: $query<br>\n";
    $sth = $dbh->prepare($query);
    $sth->execute();
    while ($row = $sth->fetch()) {
        $color = $row[0];
        $rgb = $row[1];
        $color_no_spaces = str_replace(" ", "-", $color);
        print "<div class='color-filter' id='$color_no_spaces'><h3>$color</h3><div class='color-fill' style='background-color: $rgb'>&nbsp;</div></div>\n";
    }
}

function printWallFiltersForColorTypeDimensions($dbh, $filter_option, $pab_wall_checkbox, &$brickset_colors, $colors_first)
{
    if ($filter_option) {
        print "<div id='filter-color-options' class='filter-option'>\n";
    } else {
        print "<div id='filter-color-options'>\n";
    }

    if ($colors_first) {
        print "<div id='top-side'>\n";
        printColorSelector($dbh, $brickset_colors);
        print "</div>\n";

        print "<div class='clear'></div>\n";
        print "<div id='bottom-side'>\n";
        printLegoTypeSelector();
        printDimensionsSelector(0, 0);
        print "</div>\n";

    } else {
        print "<div id='left-side'>\n";
        printColorSelector($dbh, $brickset_colors);
        print "</div>\n";

        print "<div id='right-side'>\n";
        printLegoTypeSelector();
        printDimensionsSelector(0, 0);

        if ($pab_wall_checkbox) {
            print "<h2>Pick-A-Brick Parts</h2>\n";
            print "<input type='checkbox' name='on_pab_wall' id='on_pab_wall' value='1' />\n";
            print "<label for='on_pab_wall'>Only Show Parts Available On Pick-A-Brick Walls</label>\n";
        }
        print "</div>\n";
    }

    print "</div>\n";
}

function getMyBricks($dbh, $username, $IDs_to_search)
{
    $in_string = '';
    if ($IDs_to_search) {
        $IDs_to_search_array = array();
        $IDs_to_search_array = explode(",", $IDs_to_search);
        $in_string = "AND bricks.id IN (". implode(",", $IDs_to_search_array) .") ";
    }

    # This one is a beast but what it does is
    # - pulls all of the bricks from $in_string out of bricks_i_own for $username
    # - pulls all of the bricks from $in_string out of sets_i_own for $username
    # - does a UNION ALL of those two queries
    # - The results of that UNION ALL go in derivedTable
    # - derivedTable does a 'GROUP BY id' to combine any duplicate rows while SUM(quantity)
    #    gives us the total of how many we own of an id
    # - At last we do the normal sorting
    # http://stackoverflow.com/questions/12490695/mysql-union-and-group-by
    $query = "SELECT id, SUM(quantity), design_id, description, part_type, dimensions, color_group, color
FROM
(SELECT bricks.id AS id, quantity, bricks.design_id AS design_id, bricks.description AS description, bricks.part_type AS part_type, bricks.dimensions AS dimensions, color_group, bricks.color AS color
 FROM bricks_i_own
 INNER JOIN bricks ON bricks_i_own.id = bricks.id
 INNER JOIN lego_colors ON bricks.color = lego_colors.brickset_color
 WHERE bricks_i_own.username='$username' $in_string
UNION ALL
 SELECT brick_id AS id, SUM(brick_quantity) AS quantity, bricks.design_id AS design_id, bricks.description AS description, bricks.part_type AS part_type, bricks.dimensions AS dimensions, color_group, bricks.color AS color
 FROM sets_i_own
 INNER JOIN sets_inventory ON sets_i_own.id = sets_inventory.id
 INNER JOIN bricks ON sets_inventory.brick_id = bricks.id
 INNER JOIN lego_colors ON bricks.color = lego_colors.brickset_color
 WHERE username='$username' $in_string
 GROUP BY brick_id) derivedTable
GROUP BY id
ORDER BY CASE
WHEN color_group='Black' THEN 1
WHEN color_group='Red' THEN 2
WHEN color_group='Blue' THEN 3
WHEN color_group='Grey' THEN 4
WHEN color_group='Brown' THEN 5
WHEN color_group='Yellow' THEN 6
WHEN color_group='Green' THEN 7
WHEN color_group='White' THEN 8
WHEN color_group='Orange' THEN 9
WHEN color_group='Purple' THEN 10
ELSE 99
END ASC, color ASC , part_type, design_id, dimensions, id
";

    # print "SQL: $query\n";
    $bricks = array();
    $sth = $dbh->prepare($query);
    $sth->execute();
    while ($row = $sth->fetch()) {
        $id = $row[0];
        $qty = $row[1];

        $brick = array();
        $brick['id']     = $id;
        $brick['qty']    = $qty;
        $brick['extras']= 0;
        $brick['design_id']  = $row[2];
        $brick['desc']         = $row[3];
        $brick['type']         = $row[4];
        $brick['dimensions'] = $row[5];
        $brick['color_group']= $row[6];
        $brick['color']        = $row[7];
        $brick['img']          = "/parts/" . $brick['design_id'] . "/$id.jpg";
        $bricks[$id] = $brick;
    }

    return ($bricks);
}

function getSetInstructions($dbh, $id)
{
    $instructions_for_set = array();

    $query = "SELECT sets_model_instruction.model, sets_model_instruction.filename, sets_model_instruction.book, sets_model_instruction.book_max, url, page_start, page_end ".
             "FROM sets_model_instruction ".
             "INNER JOIN sets_manual ON sets_model_instruction.filename = sets_manual.filename ".
             "WHERE sets_model_instruction.id='$id' ".
             "ORDER BY model, book";
    #print "SQL: $query<br>\n";

    $sth = $dbh->prepare($query);
    $sth->execute();
    while ($row = $sth->fetch()) {
        $booklet = array();
        $booklet['model']      = $row[0];
        $booklet['filename']   = $row[1];
        $booklet['book']       = $row[2];
        $booklet['book_max']   = $row[3];
        $booklet['url']        = $row[4];

        if ($row[5]) {
            $booklet['page_start'] = $row[5];
        } else {
            $booklet['page_start'] = 1;
        }

        $booklet['page_end']   = $row[6];

        array_push($instructions_for_set, $booklet);
    }

    return $instructions_for_set;
}

function getSetParts($dbh, $id, $username)
{
    $query = "SELECT DISTINCT(brick_id), brick_quantity, bricks.design_id, bricks.description, bricks.part_type, bricks.dimensions, ".
             "(SELECT store_id FROM lego_store_inventory WHERE lego_store_inventory.brick_id= sets_inventory.brick_id LIMIT 1) AS pab_available, ".
             "bricks.color, ".
             "(SELECT MIN(price) FROM www_store_inventory WHERE www_store_inventory.lego_id=sets_inventory.brick_id) AS price, ".
             "(SELECT color_group FROM lego_colors WHERE bricks.color = lego_colors.brickset_color LIMIT 1) AS color_group ".
             "FROM sets_inventory ".
             "INNER JOIN bricks ON brick_id = bricks.id ".
             "WHERE sets_inventory.id='$id' ".
             "ORDER BY CASE ".
             "WHEN color_group='Black' THEN 1 ".
             "WHEN color_group='Red' THEN 2 ".
             "WHEN color_group='Blue' THEN 3 ".
             "WHEN color_group='Grey' THEN 4 ".
             "WHEN color_group='Brown' THEN 5 ".
             "WHEN color_group='Yellow' THEN 6 ".
             "WHEN color_group='Green' THEN 7 ".
             "WHEN color_group='White' THEN 8 ".
             "WHEN color_group='Orange' THEN 9 ".
             "WHEN color_group='Purple' THEN 10 ".
             "ELSE 99 ".
             "END ASC, color ASC , bricks.part_type, bricks.design_id, bricks.dimensions, bricks.id";
    #print "SQL: $query<br>\n";

    $sth = $dbh->prepare($query);
    $sth->execute();
    $IDs_to_search_array = array();
    $bricks_for_set = array();

    while ($row = $sth->fetch()) {
        $brick = array();
        $lego_id             = $row[0];
        $brick['id']         = $lego_id;
        $brick['qty']        = $row[1];
        $brick['design_id']  = $row[2];
        $brick['desc']       = $row[3];
        $brick['type']       = $row[4];
        $brick['dimensions'] = $row[5];
        $brick['img']        = "/parts/" . $brick['design_id'] . "/" . $brick['id'] . ".jpg";

        if ($row[6]) {
            $brick['pab'] = 1;
        }

        $brick['color'] = $row[7];
        $brick['price'] = $row[8];
        $brick['shortage'] = $brick['qty'];
        $brick['qty_owned'] = 0;

        $bricks_for_set[$lego_id] = $brick;
        #array_push($bricks_for_set, $brick);
        array_push($IDs_to_search_array, $brick['id']);
    }
    $IDs_to_search = implode(",", $IDs_to_search_array);

    #
    # Find all of the bricks I own
    #
    if ($username) {
        $bricks_owned = array();
        $bricks_owned = getMyBricks($dbh, $username, $IDs_to_search);
        # print "IDs_to_search: $IDs_to_search<br>\n";

        foreach ($bricks_for_set as $lego_id => &$brick) {
            if (array_key_exists($lego_id, $bricks_owned)) {
                $mybrick = $bricks_owned[$lego_id];
                $brick['qty_owned'] = $mybrick['qty'] + $mybrick['extras'];

                # I don't own any of this brick
                if (!$brick['qty_owned']) {
                    $brick['shortage'] = $brick['qty'];

                # I own some but not enough
                } else if ($brick['qty_owned'] < $brick['qty']) {
                    $brick['shortage'] = $brick['qty'] - $brick['qty_owned'];

                # I own enough
                } else {
                    $brick['shortage'] = 0;
                    $brick['status'] = "<img src='images/Checkmark.png' width='50'/>";
                }
                #printf("lego_id: %d, qty owned: %d, qty needed %d, shortage %d<br>\n", $lego_id, $brick['qty_owned'], $brick['qty'], $brick['shortage']);
            }
        }
        #foreach ($bricks_for_set as $lego_id => $brick) {
        #    printf("lego_id: %d, qty owned: %d, qty needed %d, shortage %d<br>\n", $lego_id, $brick['qty_owned'], $brick['qty'], $brick['shortage']);
        #}
    }

    return $bricks_for_set;
}


?>
