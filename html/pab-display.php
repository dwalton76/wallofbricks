<?php
define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$dbh = dbConnect();

$pab_store_id = 18;
$pab_country = 'USA';
$load_store_from_cookie = 0;

if (array_key_exists('country', $_GET)) {
    $pab_country = $_GET['country'];
}

if (array_key_exists('pab_store_id', $_GET)) {
    $pab_store_id = $_GET['pab_store_id'];

    if (!validStoreID($pab_store_id)) {
        print "ERROR: This is not a supported store ID<br>\n";
        printHTMLFooter();
        exit();
    }

    if (array_key_exists('country', $_GET)) {
        $pab_country = $_GET['country'];
    }

// Load the store id from a cookie
} else {
    $load_store_from_cookie = 1;
}

function getPickABrickLastUpdateOn($dbh, $store_id)
{
    $query = "SELECT MAX(DATE(updated_on)) ".
                "FROM lego_store_inventory ".
                "WHERE store_id='$store_id'";
    $sth = $dbh->prepare($query);
    $sth->execute();
    $row = $sth->fetch();

    return ($row[0]);
}

function getWallMinMaxDates($dbh, $store_id)
{
    $query = "SELECT MIN( DATE( updated_on ) ) , MAX( DATE( updated_on ) ) ".
                "FROM lego_store_inventory ".
                "WHERE store_id='$store_id'";
    $sth = $dbh->prepare($query);
    $sth->execute();
    $row = $sth->fetch();

    return array($row[0], $row[1]);
}

// roundUpTo(2.4, 1) Will round up to 3
// roundUpTo(2.4, 5) Will round up to 5
function roundUpTo($number, $increments)
{
     $increments = 1 / $increments;

     return (ceil($number * $increments) / $increments);
}

function printWallFilters($dbh, $pab_store_id, $all_filters, $brickset_colors)
{
    list ($min_date, $max_date) = getWallMinMaxDates($dbh, $pab_store_id);

    print "<div id='wall-filters'>\n";
    print "<h1>Wall Filters</h1>\n";

    if ($all_filters) {
?>
<h2>Select Filter</h2>
<div id='filter-select'>
<input type='radio' name='wall-filter' id='filter-default' value='filter-default' checked>
<label for='filter-default'>No Filters - Display Everything</label>
<br>
<input type='radio' name='wall-filter' id='filter-duplicates' value='filter-duplicates'>
<label for='filter-duplicates'>Hide Duplicate Parts</label>
<br>
<input type='radio' name='wall-filter' id='filter-age' value='filter-age'>
<label for='filter-age'>Filter Based On When The Part Was Added To The Wall</label>
<br>

<input type='radio' name='wall-filter' id='filter-color' value='filter-color'>
<label for='filter-color'>Filter Based On Color, Type, and Dimensions</label>
</div>

<div id='filter-options'>
<h2>Filter Options</h2>
<div id='filter-age-options' class='filter-option'>
<div id='min-date-wrapper'>
<label for='min-date'>From</label>
<br>
<input type='date' id='min-date' name='min-date' value='<?php print $min_date ?>' />
</div>
<div id='max-date-wrapper'>
<label for='min-date'>To</label>
<br>
<input type='date' id='max-date' name='max-date' value='<?php print $max_date ?>'/>
</div>
</div>
<?php
        printWallFiltersForColorTypeDimensions($dbh, 1, 0, $brickset_colors, 0);
    } else {
        // This is needed in applyBrickFadeFilters() in lego_brick_fade_filters.js
        print "<input type='radio' name='wall-filter' id='filter-color' value='filter-color' style='display: none' checked>\n";
        printWallFiltersForColorTypeDimensions($dbh, 0, 0, $brickset_colors, 1);
    }
    print "</div>\n";
    print "<div class='clear'></div>\n";
}

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - Pick-A-Brick Display", "");
print "<form method='get' id='view-a-store' action='/pab-display.php' autocomplete='off'>\n";
$pab_store_city = pickAStore($dbh, $pab_country, $pab_store_id, 1, 1);
print "</form>\n";

if (!$pab_store_id) {
    // When this happens jquery will auto load the last store visited into the select
    // dropdown (we store this in a cookie) and will auto-submit the form. So you'll
    // see the page load with a valid pab_store_id immediately after this
    printHTMLFooter($load_store_from_cookie, $address, $latitude, $longitude, $show_login_panel);
    exit();
}

//
// Get an array of all of the bricks available in the store
//
print "<div id='pick-a-brick-display'>\n";
list ($pab_store_rows, $pab_store_cols, $address, $phone_number, $latitude, $longitude) = getWallInformation($dbh, $pab_store_id);
$bricks_at_pab = array();
$bricks_at_pab = getPickABrickAvailableBricks($dbh, $pab_store_id);
$IDs_for_pab_bricks = array();
foreach ($bricks_at_pab as $brick) {
    $brick_id = $brick['id'];
    array_push($IDs_for_pab_bricks, $brick_id);
}

//
// Now get the ID, description, img, etc for each of those bricks.
// Build an array called TD_array that holds the contents that we
// will print in the table that shows what is on the wall.
//
$brickset_colors= array();
$TD_array = array();
$TD_url = array();
if (!empty($IDs_for_pab_bricks)) {

    $IDs_for_pab_bricks = array_unique($IDs_for_pab_bricks);
    asort($IDs_for_pab_bricks);
    $IDs_for_pab_bricks_string = "'";
    $IDs_for_pab_bricks_string .= implode("','", $IDs_for_pab_bricks);
    $IDs_for_pab_bricks_string .= "'";

    $query = "SELECT id, design_id, `row`, col, slot, DATE(updated_on), part_type, dimensions, ".
                "(SELECT COUNT(lego_store_inventory.brick_id) FROM lego_store_inventory WHERE lego_store_inventory.store_id='$pab_store_id' AND lego_store_inventory.brick_id = id GROUP BY lego_store_inventory.brick_id) AS dup_count, ".
                "color, price ".
                "FROM bricks ".
                "INNER JOIN lego_store_inventory ON lego_store_inventory.store_id='$pab_store_id' AND lego_store_inventory.brick_id = id ".
                "WHERE id IN ($IDs_for_pab_bricks_string) ".
                "ORDER BY `col` ASC, `row` DESC, slot ASC";
    $brick_ids_printed = array();
    # print "SQL: $query\n";
    $sth = $dbh->prepare($query);
    $sth->execute();
    while ($row = $sth->fetch()) {
        $brick = array();
        $brick['id']    = $row[0];
        $brick['design_id'] = $row[1];
        $brick['row']  = $row[2];
        $brick['col']  = $row[3];
        $brick['slot'] = $row[4];
        $brick['updated_on'] = $row[5];
        $brick['type'] = $row[6];
        $brick['dimensions'] = $row[7];
        $brick['color'] = $row[9];
        $brick['price'] = $row[10];

        $brick['img'] = "/parts/" . $brick['design_id'] . "/" . $brick['id'] . ".jpg";

        $brick_id  = $brick['id'];
        $brick_row = $brick['row'];
        $brick_col = $brick['col'];
        $brick_slot= $brick['slot'];

        if ($brick_id != "dont-know" && $row[8] > 1) {
            if (array_key_exists($brick_id, $brick_ids_printed)) {
                $brick['duplicates'] = 1;
            } else {
                $brick_ids_printed[$brick_id] = $brick_id;
            }
        }

        $TD_array[$brick_col][$brick_row][$brick_slot] = getBrickTDDisplay($brick);

        if ($brick_id != "dont-know") {
            $TD_url[$brick_col][$brick_row][$brick_slot] = "/brick.php?id=$brick_id";

            if (!array_key_exists($brick['color'], $brickset_colors)) {
                $brickset_colors[$brick['color']] = $brick['color'];
            }
        }
    }
}

//
// Print the contents of the wall but do it in sections of 10 columns at a time.
// This is so the page isn't a mile wide for the stores with 30+ columns.
//
print "<h1>Wall Contents</h1>\n";
for ($i = 0; $i < roundUpTo($pab_store_cols/10, 1); $i++) {
    print "<div class='pick-a-brick'>\n";
    print "<table class='pick-a-brick'>\n";
    print "<thead>\n";

    $first_col = 1 + ($i * 10);
    $last_col = $first_col + 9;
    if ($last_col > $pab_store_cols) {
        $last_col = $pab_store_cols;
    }

    print "<tr>";
    print "<th></th>\n";
    for ($col = $first_col; $col <= $last_col; $col++) {
            print "<th>$col</th>";
    }
    print "</tr>\n";
    print "</thead>\n";

    print "<tbody>\n";
    for ($row = $pab_store_rows; $row >= 1; $row--) {
        print "<tr>\n";
        print "<th>$row</th>\n";
        for ($col = $first_col; $col <= $last_col; $col++) {

            $maximum_slot = 1;
            for ($slot = 9; $slot >= 1; $slot--) {
                if (isset($TD_array[$col][$row][$slot])) {
                    $maximum_slot = $slot;
                    break;
                }
            }

            for ($slot = 1; $slot <= $maximum_slot; $slot++) {

                // Normal scenario...just print one part in the bin
                if ($maximum_slot == 1) {

                    if (isset($TD_array[$col][$row][$slot])) {
                        if (isset($TD_url[$col][$row][$slot])) {
                            printf("<td class='td-link' url='%s'>%s</td>",
                                     $TD_url[$col][$row][$slot], $TD_array[$col][$row][$slot]);
                        } else {
                            printf("<td class='td-link'>%s</td>", $TD_array[$col][$row][$slot]);
                        }

                    // Print a link to this row/col on the pab-helper page so they can edit this cell
                    } else {
                        print "<td class='td-link' url='/pab-update.php?pab_store_id=$pab_store_id&current_col=$col&current_row=$row&current_slot=$slot&maximum_slot=$maximum_slot'></td>\n";
                    }

                // Multiple parts in a bin :(
                } else {
                    if ($slot == 1) {
                        print "<td>\n";
                        print "<table class='slot-map'>\n";
                    }

                    // Draw a 2x2 table
                    if ($maximum_slot <= 4) {
                        if ($slot == 1 || $slot == 3) {
                            print "<tr>\n";
                        }

                        if (isset($TD_array[$col][$row][$slot])) {
                            $display = $TD_array[$col][$row][$slot];
                            $display = str_replace("width='64px'", "width='32px'", $display);
                            $display = str_replace("height='64px'", "height='32px'", $display);
                            $display = preg_replace("/<span class='updated-on'>.*?<\/span>/", "", $display);

                            if (isset($TD_url[$col][$row][$slot])) {
                                printf("<td class='td-link two-by-two' url='%s'>%s</td>",
                                         $TD_url[$col][$row][$slot], $display);
                            } else {
                                printf("<td class='td-link two-by-two'>%s</td>", $display);
                            }

                        // Print a link to this row/col on the pab-helper page so they can edit this cell
                        } else {
                            print "<td class='td-link' url='/pab-update.php?pab_store_id=$pab_store_id&current_col=$col&current_row=$row&current_slot=$slot&maximum_slot=$maximum_slot'></td>\n";
                        }

                        // Print an empty cell for the bottom right corner since our max is 3
                        if ($slot == 3 && $maximum_slot == 3) {
                            print "<td>&nbsp;</td>\n";
                        }

                        if ($slot == 2 || $slot == 4) {
                            print "</tr>\n";
                        }

                    // Draw a 3x3 table
                    } elseif ($maximum_slot <= 9) {
                        if ($slot == 1 || $slot == 4 || $slot == 7) {
                            print "<tr>\n";
                        }

                        if (isset($TD_array[$col][$row][$slot])) {
                            $display = $TD_array[$col][$row][$slot];
                            $display = str_replace("width='64px'", "width='32px'", $display);
                            $display = str_replace("height='64px'", "height='32px'", $display);
                            $display = preg_replace("/<span class='updated-on'>.*?<\/span>/", "", $display);

                            if (isset($TD_url[$col][$row][$slot])) {
                                printf("<td class='td-link three-by-three' url='%s'>%s</td>",
                                         $TD_url[$col][$row][$slot], $display);
                            } else {
                                printf("<td class='td-link three-by-three'>%s</td>", $display);
                            }

                        // Print a link to this row/col on the pab-helper page so they can edit this cell
                        } else {
                            print "<td class='td-link' url='/pab-update.php?pab_store_id=$pab_store_id&current_col=$col&current_row=$row&current_slot=$slot&maximum_slot=$maximum_slot'></td>\n";
                        }

                        // Print empty cells to fill out the row
                        if (($slot == 4 && $maximum_slot == 4) ||
                             ($slot == 7 && $maximum_slot == 7)) {
                            print "<td>&nbsp;</td>\n";
                            print "<td>&nbsp;</td>\n";

                        } elseif (($slot == 5 && $maximum_slot == 5) ||
                                      ($slot == 8 && $maximum_slot == 8)) {
                            print "<td>&nbsp;</td>\n";
                        }

                        if ($slot == 3 || $slot == 6 || $slot == 9) {
                            print "</tr>\n";
                        }
                    }

                    if ($slot == $maximum_slot) {
                        print "</table>\n";
                        print "</td>\n";
                    }
                }
            }
        }
        print "</tr>\n";
    }

    print "</tbody>\n";
    print "</table>\n";
    print "</div>\n";
}

printWallFilters($dbh, $pab_store_id, True, $brickset_colors);

if ($address) {
    print "<div id='store-information'>\n";

    // printHTMLFooter will print the javascript to draw the google map
    print "<h1>Store Information</h1>\n";
    print "<div id='map-canvas'></div>\n";

    $last_updated_on = getPickABrickLastUpdateOn($dbh, $pab_store_id);
    if ($last_updated_on) {
        print "<div id='last-updated-on'>\n";
        print "<h2>Last Updated On</h2>\n";
        print "$last_updated_on<br>\n";
        print "</div>\n";
    }

    print "<div id='store-contact-info'>\n";
    print "<h2>Address</h2>\n";

    $pretty_address = $address;
    if ($pab_country == "USA") {
        // if (preg_match("/^(.*), (.*?, \w\w \d+.*?)$/", $address, $matches)) {
        if (preg_match("/^(.*?)\,?\s*($pab_store_city.*)$/i", $address, $matches)) {
            $pretty_address = $matches[1] . "<br>" . $matches[2];
        }
    } else {
        if (preg_match("/^(.*)($pab_store_city.*)$/i", $address, $matches)) {
            $pretty_address = $matches[1] . "<br>" . $matches[2];
        }
    }

    print "<div id='snail-mail-address'>$pretty_address</div>\n";

    if ($phone_number) {
        print "<div id='telephone'><a href='tel:$phone_number'>$phone_number</a><br><br>Some stores will mail you a Pick-A-Brick cup if you tell them exactly what you want. If you don't live near this store but would like some parts from their wall try giving them a call.</div>\n";
    }
    print "</div>\n";
    print "</div>\n";
    print "<div class='clear'></div>\n";
}

print "</div>\n";
printHTMLFooter($load_store_from_cookie, $address, $latitude, $longitude, $show_login_panel);

// close the connection
$dbh = null;

?>
