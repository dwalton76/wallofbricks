<?php
define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

# http://stackoverflow.com/questions/10852964/sorting-a-multi-dimensional-array-according-to-length-in-php
function sortByLength($a, $b)
{
     return count($a) - count($b);
}

# http://www.emoticode.net/php/all-element-combinations-in-an-array.html
function getArrayCombinations($array)
{
     // initialize by adding the empty set
     $results = array(array( ));

     foreach ($array as $element)
          foreach ($results as $combination)
                array_push($results, array_merge(array($element), $combination));

     return $results;
}

function getShoppingListPrice($dbh, $brick_qtys_needed, $combination, $include_s_and_h)
{
    $in_string = implode(",", array_keys($brick_qtys_needed));
    $store_in_string = implode(",", $combination);

    # Fairly complex query...this lets you find the store that has the qty
    # we need with the lowest price...for each lego_id needed
    # http://www.xaprb.com/blog/2006/12/07/how-to-select-the-firstleastmax-row-per-group-in-sql/
    $query = "SELECT f.lego_id, f.price, f.store_id, f.lot_id, f.qty ".
             "FROM ( ".
             "SELECT lego_id, MIN(price) AS minprice ".
             "FROM www_store_inventory ".
             "WHERE store_id IN ($store_in_string) AND (";

    $first = 1;
    foreach ($brick_qtys_needed as $key => $value) {
        if ($first) {
            $first = 0;
        } else {
            $query .= " OR ";
        }

        $query .= "(lego_id='$key' AND qty >= '$value')";
    }

    $query .= ") GROUP BY lego_id ORDER BY lego_id ASC ";
    $query .= ") AS x INNER JOIN www_store_inventory AS f ON f.lego_id = x.lego_id AND f.price = x.minprice AND f.store_id IN ($store_in_string)";

    $sth = $dbh->prepare($query);
    $sth->execute();
    $total_price = 0;
    $part_types_found = 0;
    $parts_found = 0;
    $store_ids = array();
    $prev_lego_id = 0;
    $money_by_store_id = array();
    while ($row = $sth->fetch()) {
        $lego_id  = $row[0];
        $price    = $row[1];
        $store_id = $row[2];
        $lot_id   = $row[3];
        $qty      = $row[4];

        # If two stores have the same MIN price just skip the 2nd, 3rd, etc ones
        if ($lego_id == $prev_lego_id) {
            continue;
        }

        if (!array_key_exists($store_id, $money_by_store_id)) {
            $money_by_store_id[$store_id] = 0;
        }

        $money_by_store_id[$store_id] += $price * $brick_qtys_needed[$lego_id];
        $total_price += $price * $brick_qtys_needed[$lego_id];
        $part_types_found++;
        $parts_found += $brick_qtys_needed[$lego_id];
        $store_ids[$store_id] = 1;
        $prev_lego_id = $lego_id;
    }

    # Assumme $5 S&H per store
    if ($include_s_and_h) {
        $total_price += count($store_ids) * 500;
    }

    $foo = array_keys($store_ids);
    sort($foo);
    $store_ids_used = implode(' ', $foo);

    $money_spent_string = '';
    foreach ($money_by_store_id as $store_id => $money_spent) {
        if ($money_spent_string) {
            $money_spent_string .= ",";
        }
        $money_spent_string .= "$store_id:$money_spent";
    }

    return array($part_types_found, $parts_found, $total_price, $store_ids_used, $money_spent_string);
}

function printShoppingList($dbh, $bricks_required, $brick_qtys_needed, $min_price_combination)
{
    $in_string = implode(",", array_keys($brick_qtys_needed));
    $store_in_string = implode(",", $min_price_combination);

    # Fairly complex query...this lets you find the store that has the qty
    # we need with the lowest price...for each lego_id needed
    # http://www.xaprb.com/blog/2006/12/07/how-to-select-the-firstleastmax-row-per-group-in-sql/
    $query = "SELECT f.lego_id, f.price, f.store_id, f.lot_id, f.qty ".
             "FROM ( ".
             "SELECT lego_id, MIN(price) AS minprice ".
             "FROM www_store_inventory ".
             "WHERE store_id IN ($store_in_string) AND (";

    $first = 1;
    foreach ($brick_qtys_needed as $key => $value) {
        if ($first) {
            $first = 0;
        } else {
            $query .= " OR ";
        }

        $query .= "(lego_id='$key' AND qty >= '$value')";
    }

    $query .= ") GROUP BY lego_id ";
    $query .= "ORDER BY lego_id ASC ";
    $query .= ") AS x INNER JOIN www_store_inventory AS f ON f.lego_id = x.lego_id AND f.price = x.minprice AND f.store_id IN ($store_in_string) ";
    #print "SQL: $query<br>\n";

    $sth = $dbh->prepare($query);
    $sth->execute();
    $total_price = 0;
    $part_types_found = 0;
    $parts_found = 0;
    $prev_lego_id = 0;
    $brick_qtys_found = array();
    $brick_by_store = array();
    $lot_id_by_lego_id = array();
    while ($row = $sth->fetch()) {
        $lego_id  = $row[0];
        $price    = $row[1];
        $store_id = $row[2];
        $lot_id   = $row[3];
        $qty      = $row[4];

        # If two stores have the same MIN price just skip the 2nd, 3rd, etc ones
        if ($lego_id == $prev_lego_id) {
            continue;
        }

        if (!array_key_exists($store_id, $brick_by_store)) {
            $brick_by_store[$store_id] = array();
        }

        array_push($brick_by_store[$store_id], $lego_id);
        $lot_id_by_lego_id[$lego_id] = $lot_id;
        $brick_qtys_found[$lego_id] = 1;
        $total_price += $price * $brick_qtys_needed[$lego_id];
        $part_types_found++;
        $parts_found += $brick_qtys_needed[$lego_id];
        $prev_lego_id = $lego_id;
    }

    # Get the store names and urls based on id
    $query = "SELECT id, name, url FROM www_stores WHERE id IN ($store_in_string) ";
    $sth = $dbh->prepare($query);
    $sth->execute();
    $store_name = array();
    $store_url = array();
    while ($row = $sth->fetch()) {
        $id   = $row[0];
        $name = $row[1];
        $url  = $row[2];
        $store_name[$id] = $name;
        $store_url[$id] = $url;
    }

    foreach ($brick_by_store as $store_id => $bricks_in_store) {
        print "<div class='parts-needed-by-store'>\n";

        $data_string = '{"items":[';
        $first_part = 1;
        $table_string = '';
        $col = 1;
        $row = 1;
        foreach ($bricks_in_store as $lego_id) {
            $brick = $bricks_required[$lego_id];

            if ($col == 1) {
                $table_string .= "<tr>\n";
            }

            if ($first_part) {
                $first_part = 0;
            } else {
                $data_string .= ",";
            }

            # {"items":[{"lot_id":"36235","qty":"1"},{"lot_id":"212991","qty":"2"}]}
            $data_string .= sprintf("{\"lot_id\":\"%d\",\"qty\":\"%d\"}", $lot_id_by_lego_id[$lego_id], $brick['shortage']);

            $brick_img = $brick['img'];
            $table_string .= "<td class='td-link center' url='/brick.php?id=$lego_id'>\n";
            $table_string .= "<a href='/brick.php?id=$lego_id'>\n";
            $table_string .= "<img src='$brick_img' width='32px' height='32px' alt='LEGO Part $lego_id' />";
            $table_string .=  "</a>\n";
            $table_string .=  "</td>\n";

            if ($col++ == 10) {
                $table_string .= "</tr>\n";
                $col = 1;
                $row++;
            }
        }

        $data_string .= "]}";
        printf("<h3><a href='%s'>" . $store_name[$store_id] . "</a></h3>", $store_url[$store_id]);
        print "<table>\n";
        print "<tbody>\n";
        print $table_string;
        if ($col != 1) {
            print "</tr>\n";
        }
        print "</table>\n";
        print "</tbody>\n";

        if ($store_name[$store_id] != "LEGO Online PaB") {
            printf("<form action='%s/addtocart' method='POST' target='_blank'>\n", $store_url[$store_id]);
            print "<input type='hidden' name='utm_source' value='wallofbricks' />\n";
            printf("<input type='hidden' name='data' value='%s' />\n", $data_string);
            print "<input type='submit' class='clickable' value='Add To Cart'>\n";
            print "</form>\n";
        }

        print "</div>\n";
    }

    $print_header = 1;
    $col = 1;
    $row = 1;
    foreach ($brick_qtys_needed as $lego_id => $qty) {
        if (!array_key_exists($lego_id, $brick_qtys_found)) {

            if ($print_header) {
                $print_header = 0;
                print "<div class='parts-needed-by-store'>\n";
                print "<h3>Parts Not Available</h3>\n";
                print "<table>\n";
                print "<tbody>\n";
            }

            $brick = $bricks_required[$lego_id];
            $brick_img = $brick['img'];

            if ($col == 1) {
                print "<tr>\n";
            }

            print "<td class='td-link center' url='/brick.php?id=$lego_id'>\n";
            print "<a href='/brick.php?id=$lego_id'>\n";
            print "<img src='$brick_img' width='32px' height='32px' alt='LEGO Part $lego_id' />";
            print "</a>\n";
            print "</td>\n";

            if ($col++ == 10) {
                print "</tr>\n";
                $col = 1;
                $row++;
            }
        }
    }

    if (!$print_header) {
        if ($col != 1) {
            print "</tr>\n";
        }
        print "</table>\n";
        print "</tbody>\n";
        print "</div>\n";
    }
    print "<div class='clear'></div>\n";
}

function getStoreWhereWeSpentTheLeast($money_spent_string) {
    $min_money_spent = 9999999;
    $min_money_spent_store = 0;
    $money_spent_pairs = explode(',', $money_spent_string);

    # dwalton
    #print "money_spent_string: $money_spent_string<br>\n";

    foreach ($money_spent_pairs as $store_money_pair) {
        $foo = explode(':', $store_money_pair);
        $store_id = $foo[0];
        $money_spent = $foo[1];
        #printf("%s in %d<br>\n", centsToPrice($money_spent), $store_id);
        if ($money_spent <= $min_money_spent) {
            $min_money_spent = $money_spent;
            $min_money_spent_store = $store_id;
        }
    }
    #printf("\n<br>MIN SPENT IN STORE: %s in %d<br>\n", centsToPrice($min_money_spent), $min_money_spent_store);
    return $min_money_spent_store;
}

function printSetInventory($dbh, $set_id, $username)
{
    #
    # Find all of the crap needed for the lego set we want to build
    #
    $instructions_required = getSetInstructions($dbh, $set_id);
    $bricks_required = getSetParts($dbh, $set_id, $username);

    $brickset_colors = array();
    $IDs_to_search_array = array();
    foreach ($bricks_required as $brick) {
        array_push($IDs_to_search_array, $brick['id']);
        if (!array_key_exists($brick['color'], $brickset_colors)) {
            $brickset_colors[$brick['color']] = $brick['color'];
        }
    }
    $IDs_to_search = implode(",", $IDs_to_search_array);

    $query = "SELECT name, pieces, price, year, min_age, max_age, theme, subtheme ";
    if ($username) {
        $query .= ", (SELECT username FROM sets_wishlist WHERE username='$username' AND sets_wishlist.id = sets.id LIMIT 1) AS on_wishlist, ".
                  "(SELECT username FROM sets_i_own WHERE username='$username' AND sets_i_own.id = sets.id LIMIT 1) AS i_own_it ";
    }

    $query .= "FROM sets ".
              "WHERE id='$set_id'";
    # print "SQL: $query\n";
    $sth = $dbh->prepare($query);
    $sth->execute();
    $row = $sth->fetch();
    $set_name     = $row[0];
    $set_pieces   = $row[1];
    $set_price    = $row[2];
    $set_year     = $row[3];
    $set_min_age  = $row[4];
    $set_max_age  = $row[5];
    $set_theme    = $row[6];
    $set_subtheme = $row[7];
    $own_it       = 0;
    $wishlist     = 0;

    if ($username) {
        $set['on_wishlist'] = $row[8];
        $set['i_own_it']     = $row[9];
    }

    if (!$set_name) {
        print "Sorry, we do not have any data on set '$set_id'\n";

        return;
    }

    print "<h1>#$set_id - $set_name</h1>\n";

    # Slideshow begin
    $query = "SELECT COUNT(img) FROM sets_image WHERE id='$set_id'";
    $sth = $dbh->prepare($query);
    $sth->execute();
    $row = $sth->fetch();
    $img_count = $row[0];

    print "<div id='slideshow-wrapper'>\n";

    if ($img_count > 1) {
        print "<div id='slideshow-div' class='rs-slideshow'>";
        $query = "SELECT img FROM sets_image WHERE id='$set_id'";
        $sth = $dbh->prepare($query);
        $sth->execute();
        $first = 1;
        while ($row = $sth->fetch()) {
            $img = $row[0];
            $medium_img = $img;

            if (preg_match("/(.*).jpg/", $row[0], $matches)) {
                $medium_img = $matches[1] . "-medium.jpg";
            }

            if ($first) {
                # Set up an initial slide -- this provides an image for users without JavaScript
                print "<div class='slide-container'>\n";
                print "<img src='/sets/$set_id/$medium_img' class='position-relative' />\n";
                print "</div>\n";
                print "<ol class='slides'>\n";
                $first = 0;
            }

            # This list contains data about each slide.
            # So that the slide images aren't loaded with the page, we use <a> tags.
            # With some extra CSS rules, this allows for users without JavaScript to
            # access the images by clicking the links.
            print "<li>\n";
            print "<a href='/sets/$set_id/$medium_img'>$medium_img</a>\n";
            print "</li>\n";
        }

        print "</ol>\n";
        print "</div>\n";

    } else {
        print "<img src='/sets/$set_id/lego-$set_id-medium.jpg' width='600px' /><br>";
    }
    print "</div>\n";
    # Slideshow end

    print "<div id='slideshow-set-info' class='set_info'>\n";

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

    printf("<li>BrickLink: <a href='http://www.bricklink.com/catalogItem.asp?S=%s' target='_blank'>%s</a></li>", $set_id, $set_id);
    printf("<li>BrickSet: <a href='http://www.brickset.com/detail/?set=%s' target='_blank'>%s</a></li>", $set_id, $set_id);
    printf("<li>Rebrickable: <a href='http://rebrickable.com/build_set?s=%s&setlist=1&partlist=1' target='_blank'>%s</a></li>", $set_id, $set_id);

    if ($username) {
        if (isset($set['i_own_it']) && $set['i_own_it']) {
            $own_it = 1;
        } elseif (isset($set['on_wishlist']) && $set['on_wishlist']) {
            $wishlist = 1;
        }

        print "<br>\n";
        if ($own_it) {
            print "<div class='own-status'>\n";
            print "<img src='/images/Checkmark.png' width='50' alt='I Own It' />\n";
            print "</div>";
        } elseif ($wishlist) {
            printf("<div class='own-status'><span class='jquery_add_set owned amazon clickable' add_id='%s'>Add To<br>My Sets</span></div>\n", $set_id);
            printf("<div class='wishlist-status'><img src='/images/wishlist.png' width='50' alt='On Wish List' /></div>");
        } else {
            printf("<div class='own-status'><span class='jquery_add_set owned amazon clickable' add_id='%s'>Add To<br>My Sets</span></div>\n", $set_id);
            printf("<div class='wishlist-status'><span class='jquery_add_set wishlist amazon clickable' add_id='%s'>Add To Wishlist</span></div>\n", $set_id);
        }
    }

    print "</div>\n";
    print "<div class='clear'></div>\n";

    if (0 && !empty($instructions_required)) {
        print "<div id='instructions-list' class='set-guts'>\n";
        print "<h1>Instructions</h1>";
        print "<table>\n";
        print "<tr>".
              "<th>Cover</th>".
              "<th>Description</th>".
              "<th>PDF</th>".
              "</tr>\n";
        foreach ($instructions_required as $booklet) {
            preg_match("/^(.*)\.pdf$/", $booklet['filename'], $match);
            $filename_minus_pdf = $match[1];
            printf("<tr>".
                   "<td><a href='instructions.php?set_id=%s&model=%s&filename=%s&page=%s'><img setid='%s' src='/sets/%s/%s-0-small.jpg' width='180px' /></a></td>".
                   "<td>Model %s - Book %d/%d</td>".
                   "<td><a href='%s' target='_onblank'>%s</a></td>".
                   "</tr>\n",
                   $set_id,
                   $booklet['model'],
                   $filename_minus_pdf,
                   $booklet['page_start'],
                   $set_id,
                   $set_id,
                   $filename_minus_pdf,
                   $booklet['model'],
                   $booklet['book'],
                   $booklet['book_max'],
                   $booklet['url'],
                   $booklet['filename']);
        }
        print "</table>\n";

        print "</div>\n";
        print "<div class='clear'></div>\n";
    }

    print "<div id='parts-list' class='set-guts'>\n";
    print "<h1>Parts</h1>";
    print "<table>\n";
    print "<tbody>\n";
    $col = 1;
    $row = 1;

    $bricks_needed = 0;
    $brick_qtys_needed = array();

    foreach ($bricks_required as $brick) {
        $brick_id = $brick['id'];

        # To skip minifigs...
        #if ($brick['shortage'] && $brick['type'] != 'minifig') {
        if ($brick['shortage']) {
            $brick_qtys_needed[$brick_id] = $brick['shortage'];
            $bricks_needed += $brick['shortage'];
        }

        if ($col == 1) {
            print "<tr>\n";
        }

        $brick_id = $brick['id'];
        print "<td class='td-link center' url='/brick.php?id=$brick_id'>\n";
        print getBrickTDDisplay($brick);
        print "</td>\n";

        if ($col++ == 10) {
            print "</tr>\n";
            $col = 1;
            $row++;
        }
    }

    if ($col != 1) {
        print "</tr>\n";
    }

    print "</tbody>\n";
    print "</table>\n";
    print "</div>\n";

    if ($username && !$own_it) {
        $debug_parts_cost = 0;
        print "<div id='parts-cost' class='set-guts'>\n";
        print "<h1>Parts Cost</h1>";

        $optional_stores = array();

        # Build a list of every store that has the qty of a part that we need
        $query = "SELECT DISTINCT store_id FROM `www_store_inventory` ".
                 "WHERE (";
        $first = 1;
        foreach ($brick_qtys_needed as $key => $value) {
            if ($first) {
                $first = 0;
            } else {
                $query .= " OR ";
            }

            $query .= "(lego_id='$key' AND qty >= '$value')";
        }
        $query .= ") ORDER BY store_id";
        #print "<br>SQL: $query<br>\n";

        $all_stores = array();
        $sth = $dbh->prepare($query);
        $sth->execute();
        while ($row = $sth->fetch()) {
            array_push($all_stores, $row[0]);
        }

        if ($debug_parts_cost) {
            print "\n<br>ALL STORES: ";
            foreach ($all_stores as $store) {
                print "$store ";
            }
            print "<br>\n";
        }

        # Get a baseline cost if we used every store
        $foo = getShoppingListPrice($dbh, $brick_qtys_needed, $all_stores, 1);
        $base_part_types_found = $foo[0];
        $base_parts_found = $foo[1];
        $base_price = $foo[2];
        $base_stores = explode(' ', $foo[3]);
        $money_spent_string = $foo[4];

        $final_part_types_found = $base_part_types_found;
        $final_parts_found = $base_parts_found;
        $final_price = $base_price;
        $final_stores = $base_stores;

        # If we assume that we can use ALL STORES then BASE STORES are the
        # ones we would actually use.
        if ($debug_parts_cost) {
            print "\nBASE PARTS: $base_parts_found<br>\n";
            print "\nBASE TYPES: $base_part_types_found<br>\n";
            printf("\nBASE PRICE: %s<br>\n", centsToPrice($base_price));
            print "\nBASE MONEY: $money_spent_string<br>\n";
            print "\nBASE STORES: ";
            foreach ($base_stores as $store) {
                print "$store ";
            }
            print "<br>\n";
        }

        # Remove the store that we are ordering the least from
        $getting_cheaper = 1;
        while ($getting_cheaper) {
            $min_money_spent_store = getStoreWhereWeSpentTheLeast($money_spent_string);
            $tmp_stores = array_diff($base_stores, array($min_money_spent_store));

            $foo = getShoppingListPrice($dbh, $brick_qtys_needed, $tmp_stores, 1);
            $part_types_found = $foo[0];
            $parts_found = $foo[1];
            $price = $foo[2];
            $stores = explode(' ', $foo[3]);
            $money_spent_string = $foo[4];

            if ($debug_parts_cost) {
                print "\n<br>PARTS: $parts_found<br>\n";
                print "\nTYPES: $part_types_found<br>\n";
                printf("\nPRICE: %s<br>\n", centsToPrice($price));
                print "\nSTORES: ";
                foreach ($tmp_stores as $store) {
                    print "$store ";
                }
                print "<br>\n";
            }

            if ($part_types_found == $base_part_types_found &&
                $parts_found == $base_parts_found &&
                $price <= $base_price) {
                $getting_cheaper = 1;
                $base_stores = $tmp_stores;
                $base_price = $price;

                $final_part_types_found = $part_types_found;
                $final_parts_found = $parts_found;
                $final_price = $price;
                $final_stores = $base_stores;
            } else {
                $getting_cheaper = 0;
            }
        }

        $original_final_stores = $final_stores;
        foreach ($original_final_stores as $store) {
            $tmp_stores = array_diff($final_stores, array($store));

            $foo = getShoppingListPrice($dbh, $brick_qtys_needed, $tmp_stores, 1);
            $part_types_found = $foo[0];
            $parts_found = $foo[1];
            $price = $foo[2];
            $stores = explode(' ', $foo[3]);
            $money_spent_string = $foo[4];

            if ($debug_parts_cost) {
                print "\n<br>**PARTS: $parts_found<br>\n";
                print "\n**TYPES: $part_types_found<br>\n";
                printf("\n**PRICE: %s<br>\n", centsToPrice($price));
                print "\n**STORES: ";
                foreach ($tmp_stores as $store) {
                    print "$store ";
                }
                print "<br>\n";
            }

            if ($part_types_found == $base_part_types_found &&
                $parts_found == $base_parts_found &&
                $price <= $final_price) {

                $final_part_types_found = $part_types_found;
                $final_parts_found = $parts_found;
                $final_price = $price;
                $final_stores = $tmp_stores;
            }
        }

        print "<div id='parts-cost-description'>";
        print "<div>\n";
        print "<input type='checkbox' name='shortage_parts' id='shortage_parts' value='1' />\n";
        print "<label for='shortage_parts' class='clickable'>Only Show The Parts I Need</label>\n";
        print "</div>";

        if ($bricks_needed == $final_parts_found) {
            printf("You need %d parts (%d types of parts) to complete this set.\n", $bricks_needed, count($brick_qtys_needed));
        } else {
            printf("You need %d parts (%d types of parts) to complete this set but only %d of these parts are available for purchase online.\n", $bricks_needed, count($brick_qtys_needed), $final_parts_found);
        }

        printf("Assuming each store charges $5 for shipping and handling, the cheapest option is to purchase the following parts from the following stores.  This will allow you to purchase %d parts (%d types of parts) at a cost of %s", $final_parts_found, $final_part_types_found, centsToPrice($final_price));
        print "</div>";

        if ($debug_parts_cost) {
            foreach ($final_stores as $store) {
                print "$store ";
            }
        }

        printShoppingList($dbh, $bricks_required, $brick_qtys_needed, $final_stores);
        print "</div>\n";
    }

    print "<div id='parts-filters' class='set-guts'>\n";
    print "<h1>Parts Filter</h1>";
    print "<input type='hidden' name='wall-filter' value='filter-color'>\n";
    printWallFiltersForColorTypeDimensions($dbh, 0, 1, $brickset_colors, 0);
    print "</div>\n";
    print "<div class='clear'></div>\n";
}

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - Set Display", "");
print "<span class='hide' id='username'>$username</span>\n";
$dbh = dbConnect();

$id = "4475-1";
if (array_key_exists('set_id', $_GET)) {
    $id = $_GET['set_id'];
    if (!preg_match("/\w+-\d+/", $id, $matches)) {
        $id = $id . "-1";
    }
}

printSetInventory($dbh, $id, $username);
printHTMLFooter(0, 0, 0, 0, $show_login_panel);
