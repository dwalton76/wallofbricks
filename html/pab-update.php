<?php

define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

function getMaxSlot($dbh, $pab_store_id, $row, $col)
{
    $query = "SELECT MAX(slot) FROM lego_store_inventory WHERE store_id='$pab_store_id' AND row='$row' AND col='$col'";
    $sth = $dbh->prepare($query);
    $sth->execute();
    $row = $sth->fetch();

    if ($row[0]) {
        return ($row[0]);
    }

    return 1;
}

function getStoreNameStateCountry($dbh, $pab_store_id)
{
    $query = "SELECT city, state, country ".
                "FROM lego_store ".
                "WHERE id='$pab_store_id' ";
                "LIMIT 1";
    $sth = $dbh->prepare($query);
    $sth->execute();
    $row = $sth->fetch();

    if ($row[0]) {
        return array($row[0], $row[1], $row[2]);
    } else {
        return array("", "", "");
    }
}

function getNextRowCol($dbh,
                              $pab_country,
                              $pab_store_id,
                              $pab_store_rows,
                              $pab_store_cols,
                              $current_row,
                              $current_col,
                              $current_slot,
                              $maximum_slot,
                              $starting_cell,
                              $then_go) {
    $last_cell = 0;
    $next_col = $current_col;
    $next_row = $current_row;

    if ($current_slot < $maximum_slot) {
        return array($next_row, $next_col, $current_slot + 1, $maximum_slot, $last_cell);
    }

    $next_slot = 1;

    // TOP LEFT
    if ($starting_cell == 'topleft') {

        if ($then_go == "leftrightsnake") {
            if ($current_row == 1 && $current_col == 1) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;

            // Far right side
            } elseif ($current_col == $pab_store_cols) {
                // Even row...move down
                if ($current_row % 2 == 0) {
                    $next_col = $current_col;
                    $next_row = $current_row - 1;

                // Odd row...move left
                } else {
                    $next_col = $current_col - 1;
                    $next_row = $current_row;
                }

            // Far left side
            } elseif ($current_col == 1) {
                // Even row...move right
                if ($current_row % 2 == 0) {
                    $next_col = $current_col + 1;
                    $next_row = $current_row;

                // Odd row...move down
                } else {
                    $next_col = $current_col;
                    $next_row = $current_row - 1;
                }

            // In the middle of an Even row...move right
            } elseif ($current_row % 2 == 0) {
                $next_col = $current_col + 1;
                $next_row = $current_row;

            // In the middle of an Odd row...move left
            } else {
                $next_col = $current_col - 1;
                $next_row = $current_row;
            }

        } elseif ($then_go == "leftrightalways") {
            if ($current_row == 1 && $current_col == $pab_store_cols) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;
            } elseif ($current_col == $pab_store_cols) {
                $next_col = 1;
                $next_row = $current_row - 1;
            } else {
                $next_row = $current_row;
                $next_col = $current_col + 1;
            }

        } elseif ($then_go == "topbottomsnake") {
            // We end in the top right corner
            if ($current_row == $pab_store_rows && $current_col == $pab_store_cols) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;

            } elseif ($current_row == 1) {
                // Even column...move up
                if ($current_col % 2 == 0) {
                    $next_row = $current_row + 1;
                    $next_col = $current_col;

                // Odd column...move to the right
                } else {
                    $next_row = $current_row;
                    $next_col = $current_col + 1;
                }

            } elseif ($current_row == $pab_store_rows) {

                // Even column...move to the right
                if ($current_col % 2 == 0) {
                    $next_row = $current_row;
                    $next_col = $current_col + 1;

                // Odd column...move down
                } else {
                    $next_row = $current_row - 1;
                    $next_col = $current_col;
                }

            // Even column...move up
            } elseif ($current_col % 2 == 0) {
                $next_row = $current_row + 1;

            // Odd column...move down
            } else {
                $next_row = $current_row - 1;
            }

        } elseif ($then_go == "topbottomalways") {
            if ($current_row == 1 && $current_col == $pab_store_cols) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;
            } elseif ($current_row == 1) {
                $next_col = $current_col + 1;
                $next_row = $pab_store_rows;
            } else {
                $next_col = $current_col;
                $next_row = $current_row - 1;
            }
        }

    // BOTTOM LEFT
    } elseif ($starting_cell == 'bottomleft') {

        if ($then_go == "leftrightsnake") {
            if ($current_row == $pab_store_rows && $current_col == 1) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;

            } elseif ($current_col == $pab_store_cols) {
                // Even row...move left
                if ($current_row % 2 == 0) {
                    $next_col = $current_col - 1;
                    $next_row = $current_row;

                // Odd row...move up
                } else {
                    $next_col = $current_col;
                    $next_row = $current_row + 1;
                }

            } elseif ($current_col == 1) {
                // Even row...move up
                if ($current_row % 2 == 0) {
                    $next_col = $current_col;
                    $next_row = $current_row + 1;

                // Odd row...move right
                } else {
                    $next_col = $current_col + 1;
                    $next_row = $current_row;
                }

            // In the middle of an Even row...move left
            } elseif ($current_row % 2 == 0) {
                $next_col = $current_col - 1;
                $next_row = $current_row;

            // In the middle of an Odd row...move right
            } else {
                $next_col = $current_col + 1;
                $next_row = $current_row;
            }

        } elseif ($then_go == "leftrightalways") {
            if ($current_row == $pab_store_rows && $current_col == $pab_store_cols) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;
            } elseif ($current_col == $pab_store_cols) {
                $next_col = 1;
                $next_row = $current_row + 1;
            } else {
                $next_row = $current_row;
                $next_col = $current_col + 1;
            }

        } elseif ($then_go == "bottomtopsnake") {

            // We end in the bottom right corner since all walls have an even number of columns
            if ($current_row == 1 && $current_col == $pab_store_cols) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;

            } elseif ($current_row == $pab_store_rows) {
                // Even column...start moving down
                if ($current_col % 2 == 0) {
                    $next_row = $current_row - 1;
                    $next_col = $current_col;

                // Odd column...move to the right
                } else {
                    $next_row = $current_row;
                    $next_col = $current_col + 1;
                }

            } elseif ($current_row == 1) {

                // Even column...move to the right
                if ($current_col % 2 == 0) {
                    $next_row = $current_row;
                    $next_col = $current_col + 1;

                // Odd column...move up
                } else {
                    $next_row = $current_row + 1;
                    $next_col = $current_col;
                }

            // Even column...move down
            } elseif ($current_col % 2 == 0) {
                $next_row = $current_row - 1;

            // Odd column...move up
            } else {
                $next_row = $current_row + 1;
            }

        } elseif ($then_go == "bottomtopalways") {
            if ($current_row == $pab_store_rows && $current_col == $pab_store_cols) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;
            } elseif ($current_row == $pab_store_rows) {
                $next_col = $current_col + 1;
                $next_row = 1;
            } else {
                $next_col = $current_col;
                $next_row = $current_row + 1;
            }
        }

    // TOP RIGHT
    } elseif ($starting_cell == 'topright') {

        if ($then_go == "rightleftsnake") {
            if ($current_row == 1 && $current_col == $pab_store_cols) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;

            } elseif ($current_col == 1) {
                // Even row...move down
                if ($current_row % 2 == 0) {
                    $next_col = $current_col;
                    $next_row = $current_row - 1;

                // Odd row...move right
                } else {
                    $next_col = $current_col + 1;
                    $next_row = $current_row;
                }

            } elseif ($current_col == $pab_store_cols) {
                // Even row...move left
                if ($current_row % 2 == 0) {
                    $next_col = $current_col - 1;
                    $next_row = $current_row;

                // Odd row...move down
                } else {
                    $next_col = $current_col;
                    $next_row = $current_row - 1;
                }

            // In the middle of an Even row...move left
            } elseif ($current_row % 2 == 0) {
                $next_col = $current_col - 1;
                $next_row = $current_row;

            // In the middle of an Odd row...move right
            } else {
                $next_col = $current_col + 1;
                $next_row = $current_row;
            }

        } elseif ($then_go == "rightleftalways") {
            if ($current_row == 1 && $current_col == 1) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;
            } elseif ($current_col == 1) {
                $next_col = $pab_store_cols;
                $next_row = $current_row - 1;
            } else {
                $next_row = $current_row;
                $next_col = $current_col - 1;
            }

        } elseif ($then_go == "topbottomsnake") {
            // We end in the bottom right corner since all walls have an even number of columns
            if ($current_row == $pab_store_rows && $current_col == 1) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;

            } elseif ($current_row == $pab_store_rows) {
                // Even column...move down
                if ($current_col % 2 == 0) {
                    $next_row = $current_row - 1;
                    $next_col = $current_col;

                // Odd column...move left
                } else {
                    $next_row = $current_row;
                    $next_col = $current_col - 1;
                }

            } elseif ($current_row == 1) {

                // Even column...move to the left
                if ($current_col % 2 == 0) {
                    $next_row = $current_row;
                    $next_col = $current_col - 1;

                // Odd column...move up
                } else {
                    $next_row = $current_row + 1;
                    $next_col = $current_col;
                }

            // Even column...move down
            } elseif ($current_col % 2 == 0) {
                $next_row = $current_row - 1;

            // Odd column...move up
            } else {
                $next_row = $current_row + 1;
            }
        } elseif ($then_go == "topbottomalways") {
            if ($current_row == 1 && $current_col == 1) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;
            } elseif ($current_row == 1) {
                $next_col = $current_col - 1;
                $next_row = $pab_store_rows;
            } else {
                $next_col = $current_col;
                $next_row = $current_row - 1;
            }
        }

    // BOTTOM RIGHT
    } elseif ($starting_cell == 'bottomright') {

        if ($then_go == "rightleftsnake") {
            if ($current_row == $pab_store_rows && $current_col == $pab_store_cols) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;

            } elseif ($current_col == 1) {
                // Even row...move right
                if ($current_row % 2 == 0) {
                    $next_col = $current_col + 1;
                    $next_row = $current_row;

                // Odd row...move up
                } else {
                    $next_col = $current_col;
                    $next_row = $current_row + 1;
                }

            } elseif ($current_col == $pab_store_cols) {
                // Even row...move up
                if ($current_row % 2 == 0) {
                    $next_col = $current_col;
                    $next_row = $current_row + 1;

                // Odd row...move left
                } else {
                    $next_col = $current_col - 1;
                    $next_row = $current_row;
                }

            // In the middle of an Even row...move right
            } elseif ($current_row % 2 == 0) {
                $next_col = $current_col + 1;
                $next_row = $current_row;

            // In the middle of an Odd row...move left
            } else {
                $next_col = $current_col - 1;
                $next_row = $current_row;
            }

        } elseif ($then_go == "rightleftalways") {
            if ($current_row == $pab_store_rows && $current_col == 1) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;
            } elseif ($current_col == 1) {
                $next_col = $pab_store_cols;
                $next_row = $current_row + 1;
            } else {
                $next_row = $current_row;
                $next_col = $current_col - 1;
            }

        } elseif ($then_go == "bottomtopsnake") {
            // We end in the bottom left corner
            if ($current_row == 1 && $current_col == 1) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;

            } elseif ($current_row == $pab_store_rows) {
                // Even column...move left
                if ($current_col % 2 == 0) {
                    $next_row = $current_row;
                    $next_col = $current_col - 1;

                // Odd column...move down
                } else {
                    $next_row = $current_row - 1;
                    $next_col = $current_col;
                }

            } elseif ($current_row == 1) {

                // Even column...move up
                if ($current_col % 2 == 0) {
                    $next_row = $current_row + 1;
                    $next_col = $current_col;

                // Odd column...move left
                } else {
                    $next_row = $current_row;
                    $next_col = $current_col - 1;
                }

            // Even column...move up
            } elseif ($current_col % 2 == 0) {
                $next_row = $current_row + 1;

            // Odd column...move down
            } else {
                $next_row = $current_row - 1;
            }

        } elseif ($then_go == "bottomtopalways") {
            if ($current_row == $pab_store_rows && $current_col == 1) {
                $last_cell = 1;
                $next_col = $current_col;
                $next_row = $current_row;
            } elseif ($current_row == $pab_store_rows) {
                $next_col = $current_col - 1;
                $next_row = 1;
            } else {
                $next_col = $current_col;
                $next_row = $current_row + 1;
            }
        }
    }

    // next max slot doesn't apply if we are on the last cell
    if ($last_cell) {
        $next_maximum = 1;

    // find the max slot # for the next slot
    } else {
        $next_maximum = getMaxSlot($dbh, $pab_store_id, $next_row, $next_col);
    }

    return array($next_row, $next_col, $next_slot, $next_maximum, $last_cell);
}

function printNextWallMapCoordinates($dbh,
                                                 $pab_country,
                                                 $pab_store_id,
                                                 $pab_store_rows,
                                                 $pab_store_cols,
                                                 $current_row,
                                                 $current_col,
                                                 $current_slot,
                                                 $maximum_slot,
                                                 $yes_prompt,
                                                 $no_prompt,
                                                 $starting_cell,
                                                 $then_go) {
    $last_cell = 0;

    list($next_row, $next_col, $next_slot, $next_maximum_slot, $last_cell) = getNextRowCol($dbh, $pab_country, $pab_store_id, $pab_store_rows, $pab_store_cols, $current_row, $current_col, $current_slot, $maximum_slot, $starting_cell, $then_go);

    $yes_prompt = "<img class='yes-no-prompt rounded_corners' src='/images/Checkmark-128.png' width='128'/>";
    $no_prompt = "<img class='yes-no-prompt rounded_corners' src='/images/red-x-128.png' width='128'/>";
    $url = "";
    $class = "";

    if ($last_cell) {
        $url = "/pab-display.php?pab_store_id=$pab_store_id&country=$pab_country";
        $class = "yes-link";
        $prompt = $yes_prompt;

    } else {
        $url = "/pab-update.php?pab_store_id=$pab_store_id&country=$pab_country&current_row=$next_row&current_col=$next_col" .
                 "&starting_cell=$starting_cell&then_go=$then_go&current_slot=$next_slot&maximum_slot=$next_maximum_slot";
        $class = "yes-link";
        $prompt = $yes_prompt;
    }

    printf("<a class='%s' href='%s'>%s</a>\n", $class, $url, $prompt);

    if ($no_prompt) {
        print "<a class='no-link' href='/pab-update.php?pab_store_id=$pab_store_id&country=$pab_country&current_row=$current_row&current_col=$current_col&edit_part=1&starting_cell=$starting_cell&then_go=$then_go&current_slot=$current_slot&maximum_slot=$maximum_slot'>$no_prompt</a>\n";
    }
}

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - Wall Edit", "");
if (!$username) {
    printAccountBenefits();
    printHTMLFooter(0, 0, 0, 0, $show_login_panel);
    exit();
}
$dbh = dbConnect();

$pab_store_rows = 8;
$pab_store_cols = 12;

$submit = "";
if (array_key_exists('submit', $_GET)) {
    $submit = $_GET['submit'];
}

$save_part = 0;
if (array_key_exists('save_part', $_GET)) {
    $save_part = $_GET['save_part'];
}

$pab_store_id = 0;
$pab_country = "USA";

if (array_key_exists('pab_store_id', $_GET)) {
    $pab_store_id = $_GET['pab_store_id'];

    if (!validStoreID($pab_store_id)) {
        print "ERROR: This is not a supported store ID<br>\n";
        printHTMLFooter();
        exit();
    }
    list ($pab_store_rows, $pab_store_cols, $address, $phone_number, $latitude, $longitude) = getWallInformation($dbh, $pab_store_id);

    if (array_key_exists('pab_country', $_GET)) {
        $pab_country = $_GET['pab_country'];
    }
}

$current_row = 0;
if (array_key_exists('current_row', $_GET)) {
    $current_row = $_GET['current_row'];
    $current_row = validateRow($current_row, $pab_store_rows);
}

$current_col = 0;
if (array_key_exists('current_col', $_GET)) {
    $current_col = $_GET['current_col'];
    $current_col = validateCol($current_col, $pab_store_cols);
}

$current_slot = 1;
if (array_key_exists('current_slot', $_GET)) {
    $current_slot = $_GET['current_slot'];
    if ($current_slot < 1) {
        $current_slot = 1;
    } elseif ($current_slot > 9) {
        $current_slot = 9;
    }
}

$edit_part = 0;
if (array_key_exists('edit_part', $_GET)) {
    $edit_part = $_GET['edit_part'];
}

$starting_cell = 'topleft';
if (array_key_exists('starting_cell', $_GET)) {
    $starting_cell = $_GET['starting_cell'];
}

$then_go = "topbottomalways";
if (array_key_exists('then_go', $_GET)) {
    $then_go = $_GET['then_go'];
}

if ($pab_store_id && (!$current_row || !$current_col)) {
    if ($starting_cell == 'topleft') {
        $current_row = $pab_store_rows;
        $current_col = 1;

    } elseif ($starting_cell == 'bottomleft') {
        $current_row = 1;
        $current_col = 1;

    } elseif ($starting_cell == 'topright') {
        $current_row = $pab_store_rows;
        $current_col = $pab_store_cols;

    } elseif ($starting_cell == 'bottomright') {
        $current_row = 1;
        $current_col = $pab_store_cols;
    }
}


$maximum_slot = 1;
if (array_key_exists('maximum_slot', $_GET)) {
    $maximum_slot = $_GET['maximum_slot'];
    if ($maximum_slot < 1) {
        $maximum_slot = 1;
    } elseif ($maximum_slot > 9) {
        $maximum_slot = 9;
    }

    // Delete anything in this bin whose slot # is over the max slot
    if (array_key_exists('delete_slots', $_GET)) {
        $query = "DELETE FROM lego_store_inventory WHERE store_id='$pab_store_id' AND row='$current_row' AND col='$current_col' AND slot > '$maximum_slot'";
        $sth = $dbh->prepare($query);
        $sth->execute();
    }

} else {
    if ($pab_store_id) {
        $maximum_slot = getMaxSlot($dbh, $pab_store_id, $current_row, $current_col);
    }
}

if ($current_slot > $maximum_slot) {
    $current_slot = $maximum_slot;
}

if ($save_part) {
    $part_id = 0;
    if (array_key_exists('part-id', $_GET)) {
        $part_id = $_GET['part-id'];
    }

    if (!$part_id) {
        print "ERROR: You must select a LEGO part for this row/column<br>\n";
        printHTMLFooter();
        exit();
    }

    if ($part_id && $pab_store_id && $current_row && $current_col) {
        $query = "INSERT INTO lego_store_inventory_activity (id, `row`, col, slot, brick_id, client_ip, updated_on, updated_by) VALUE (?,?,?,?,?,?,NOW(),?)";
        $sth = $dbh->prepare($query);
        $sth->bindParam(1, $pab_store_id);
        $sth->bindParam(2, $current_row);
        $sth->bindParam(3, $current_col);
        $sth->bindParam(4, $current_slot);
        $sth->bindParam(5, $part_id);
        $sth->bindParam(6, $_SERVER['REMOTE_ADDR']);
        $sth->bindParam(7, $username);
        $sth->execute();

        $query = "INSERT INTO lego_store_inventory (store_id, `row`, col, slot, brick_id) VALUE (?,?,?,?,?) ".
                 "ON DUPLICATE KEY UPDATE brick_id=?, updated_on=NOW()";
        $sth = $dbh->prepare($query);
        $sth->bindParam(1, $pab_store_id);
        $sth->bindParam(2, $current_row);
        $sth->bindParam(3, $current_col);
        $sth->bindParam(4, $current_slot);
        $sth->bindParam(5, $part_id);
        $sth->bindParam(6, $part_id);
        $sth->execute();

        // The user just saved the part in this bin so don't prompt him yes/no "Is
        // this correct"...just move them on to the next bin.
        list($current_row, $current_col, $current_slot, $maximum_slot, $last_cell) = getNextRowCol($dbh, $pab_country, $pab_store_id, $pab_store_rows, $pab_store_cols, $current_row, $current_col, $current_slot, $maximum_slot, $starting_cell, $then_go);
    }
}

//
// Start the form
//
print "<form method='get' action='pab-update.php'>\n";

if (!$pab_store_id) {
    print "<div id='pick-a-store'>\n";
    pickAStore($dbh, $pab_country, $pab_store_id, 0, 1);
    print "</div>\n";

?>
<div id='navigation-method'>
<h1>Traverse The Bins</h1>

<div id='start-from-the'>
<h2>Start From The</h2>
<input type='radio' name='starting_cell' id='topleft' value='topleft' checked>
<label for='topleft'>Top Left</label><br>
<input type='radio' name='starting_cell' id='bottomleft' value='bottomleft'>
<label for='bottomleft'>Bottom Left</label><br>
<input type='radio' name='starting_cell' id='topright' value='topright'>
<label for='topright'>Top Right</label><br>
<input type='radio' name='starting_cell' id='bottomright' value='bottomright'>
<label for='bottomright'>Bottom Right</label>
</div>

<div id='then-go-choices'>
<h2>Then Go</h2>
<div class='then-go startleft'>
<input type='radio' name='then_go' id='leftrightsnake' value='leftrightsnake' checked>
<label for='leftrightsnake'>Left to Right, then Right to Left, then Left to Right, etc</label>
</div>

<div class='then-go startleft'>
<input type='radio' name='then_go' id='leftrightalways' value='leftrightalways'>
<label for='leftrightalways'>Left to Right every time</label>
</div>

<div class='then-go startright'>
<input type='radio' name='then_go' id='rightleftsnake' value='rightleftsnake'>
<label for='rightleftsnake'>Right to Left, then Left to Right, then Right to Left, etc</label>
</div>

<div class='then-go startright'>
<input type='radio' name='then_go' id='rightleftalways' value='rightleftalways'>
<label for='rightleftalways'>Right to Left every time</label>
</div>

<div class='then-go starttop'>
<input type='radio' name='then_go' id='topbottomsnake' value='topbottomsnake'>
<label for='topbottomsnake'>Top to Bottom, then Bottom to Top, then Top to Bottom, etc</label>
</div>

<div class='then-go starttop'>
<input type='radio' name='then_go' id='topbottomalways' value='topbottomalways'>
<label for='topbottomalways'>Top to Bottom every time</label>
</div>

<div class='then-go startbottom'>
<input type='radio' name='then_go' id='bottomtopsnake' value='bottomtopsnake'>
<label for='bottomtopsnake'>Bottom to Top, then Top to Bottom, then Bottom to Top, etc</label>
</div>

<div class='then-go startbottom'>
<input type='radio' name='then_go' id='bottomtopalways' value='bottomtopalways'>
<label for='bottomtopalways'>Bottom to Top every time</label>
</div>
</div>
</div>
<?php
//
// Update what bricks are where
//
} else {
    // Write these in the page but hide them.  jQuery will use this later
    // to store the current store/country as a cookie.  This is needed in case:
    // - user is updating the wall for store X
    // - user views store Y in another tab.  The cookies now remember store Y
    // - when the user saves a part for store X we need to set the cookies back to X
    print "<input type='hidden' id='pab_store_id' name='pab_store_id' value='$pab_store_id' />\n";
    print "<input type='hidden' id='country' name='country' value='$pab_country' />\n";
    print "<input type='hidden' id='current_row' name='current_row' value='$current_row' />\n";
    print "<input type='hidden' id='current_col' name='current_col' value='$current_col' />\n";
    print "<input type='hidden' id='current_slot' name='current_slot' value='$current_slot' />\n";
    print "<input type='hidden' id='maximum_slot' name='maximum_slot' value='$maximum_slot' />\n";
    print "<input type='hidden' id='starting_cell' name='starting_cell' value='$starting_cell' />\n";
    print "<input type='hidden' id='then_go' name='then_go' value='$then_go' />\n";

    $bricks = array();
    $bricks = getPickABrickAvailableBricks($dbh, $pab_store_id);
    $bricks_index = array();
    foreach ($bricks as $brick) {
        $row = $brick['row'];
        $col = $brick['col'];
        $slot = $brick['slot'];
        $bricks_index[$row][$col][$slot] = $brick;
    }

    //
    // Print a "X x Y" table of the wall so the user can navigate easily
    //
    list($city, $state, $country) = getStoreNameStateCountry($dbh, $pab_store_id);
    $h1_title;
    if ($city && $state) {
        $h1_title = "$city, $state";
    } else {
        $h1_title = "$city, $country";
    }
    print "<h1>$h1_title</h1><br>\n";
    print "<table id='wall-map'>\n";
    print "<thead>\n";
    print "<tr>\n";
    print "<th></th>\n";

    // Some stores have 48 columns which is way too many to display at once.
    // Showing 16 at a time works well.
    $start_col = 1;
    $end_col = $pab_store_cols;
    if ($pab_store_cols > 16) {
        // On one of the first columns
        if ($current_col <= 7) {
            $start_col = 1;
            $end_col = min(16, $pab_store_cols);

        // On one of the last columns
        } elseif ($current_col >= ($pab_store_cols - 7)) {
            $start_col = $pab_store_cols - 14;
            $end_col = $pab_store_cols;

        // In the middle
        } else {
            $start_col = $current_col - 7;
            $end_col = $current_col + 8;
        }
    }

    for ($col = $start_col; $col <= $end_col; $col++) {
        print "<th>$col</th>\n";
    }
    print "</tr>\n";
    print "</thead>\n";
    for ($row = $pab_store_rows; $row >= 1; $row--) {
        print "<tr>\n";
        print "<th>$row</th>";
        for ($col = $start_col; $col <= $end_col; $col++) {

            if ($col == $current_col &&
                 $row == $current_row) {
                $maximum_slot_tmp = $maximum_slot;
            } else {
                $maximum_slot_tmp = 1;
                for ($i = 9; $i >= 1; $i--) {
                    if (isset($bricks_index[$row][$col][$i])) {
                        $maximum_slot_tmp = $i;
                        break;
                    }
                }
            }

            for ($slot = 1; $slot <= $maximum_slot_tmp; $slot++) {
                $selected = "";
                if ($col == $current_col &&
                     $row == $current_row &&
                     $slot== $current_slot) {
                    $selected = " selected";
                }

                // Normal scenario...just print one part in the bin
                if ($maximum_slot_tmp == 1) {

                    // TODO: this should be a function
                    printf("<td class='td-link$selected' url='/pab-update.php?pab_store_id=%s&country=%s&current_col=%s&current_row=%s&starting_cell=%s&then_go=%s'>\n",
                             $pab_store_id, $pab_country, $col, $row, $starting_cell, $then_go);
                    if (isset($bricks_index[$row][$col][1])) {
                        $brick = $bricks_index[$row][$col][1];
                        printf("<img src='%s' width='45px' />\n", $brick['img']);
                    } else {
                        print "&nbsp;\n";
                    }
                    print "</td>\n";

                // Multiple parts in a bin :(
                } else {

                    if ($slot == 1) {
                        print "<td>\n";
                        print "<table class='slot-map'>\n";
                    }

                    // Draw a 2x2 table
                    if ($maximum_slot_tmp <= 4) {

                        if ($slot == 1 || $slot == 3) {
                            print "<tr>\n";
                        }

                        // TODO: this should be a function
                        printf("<td class='td-link$selected' url='/pab-update.php?pab_store_id=%s&country=%s".
                                                                                "&current_col=%s&current_row=%s&current_slot=%s&maximum_slot=%s".
                                                                                "&starting_cell=%s&then_go=%s'>\n",
                                 $pab_store_id, $pab_country, $col, $row, $slot, $maximum_slot_tmp, $starting_cell, $then_go);

                        if (isset($bricks_index[$row][$col][$slot])) {
                            $brick = $bricks_index[$row][$col][$slot];
                            printf("<img src='%s' width='22' />\n", $brick['img']);
                        } else {
                            print "&nbsp;\n";
                        }
                        print "</td>\n";

                        // Print an empty cell for the bottom right corner since our max is 3
                        if ($slot == 3 && $maximum_slot_tmp == 3) {
                            print "<td>&nbsp;</td>\n";
                        }

                        if ($slot == 2 || $slot == 4) {
                            print "</tr>\n";
                        }

                    // Draw a 3x3 table
                    } elseif ($maximum_slot_tmp <= 9) {

                        if ($slot == 1 || $slot == 4 || $slot == 7) {
                            print "<tr>\n";
                        }

                        // TODO: this should be a function
                        printf("<td class='td-link$selected' url='/pab-update.php?pab_store_id=%s&country=%s".
                                                                                "&current_col=%s&current_row=%s&current_slot=%s&maximum_slot=%s".
                                                                                "&starting_cell=%s&then_go=%s'>\n",
                                 $pab_store_id, $pab_country, $col, $row, $slot, $maximum_slot_tmp, $starting_cell, $then_go);

                        if (isset($bricks_index[$row][$col][$slot])) {
                            $brick = $bricks_index[$row][$col][$slot];
                            printf("<img src='%s' width='22' />\n", $brick['img']);
                        } else {
                            print "&nbsp;\n";
                        }
                        print "</td>\n";

                        // Print empty cells to fill out the row
                        if (($slot == 4 && $maximum_slot_tmp == 4) ||
                             ($slot == 7 && $maximum_slot_tmp == 7)) {
                            print "<td>&nbsp;</td>\n";
                            print "<td>&nbsp;</td>\n";

                        } elseif (($slot == 5 && $maximum_slot_tmp == 5) ||
                                      ($slot == 8 && $maximum_slot_tmp == 8)) {
                            print "<td>&nbsp;</td>\n";
                        }

                        if ($slot == 3 || $slot == 6 || $slot == 9) {
                            print "</tr>\n";
                        }
                    }

                    if ($slot == $maximum_slot_tmp) {
                        print "</table>\n";
                        print "</td>\n";
                    }
                }
            }
        }
        print "</tr>\n";
    }
    print "</table>\n";

    if ($pab_store_cols > 16) {
        $prev_col = 0;
        if ($current_col >= 9) {
            $prev_col = $current_col - 8;
        }
        if ($prev_col) {
            printf("<a id='prev' href='/pab-update.php?pab_store_id=%s&country=%s&current_col=%s&current_row=%s'><img src='/images/Arrow-Prev.png' width='128' /></a>\n",
                     $pab_store_id, $pab_country, $prev_col, $current_row);
        }

        $next_col = 0;
        if ($end_col < $pab_store_cols) {
            $next_col = $current_col + 8;
        }

        if ($next_col) {
            printf("<a id='next' href='/pab-update.php?pab_store_id=%s&country=%s&current_col=%s&current_row=%s'><img src='/images/Arrow-Next.png' width='128' /></a>\n",
                     $pab_store_id, $pab_country, $next_col, $current_row);
        }
    }

    print "<div class='clear'></div>\n";

    $query = "SELECT id, description, design_id, DATE(updated_on), color ".
                "FROM lego_store_inventory ".
                "INNER JOIN bricks ON brick_id = bricks.id ".
                "WHERE store_id=? AND row=? AND col=? AND slot=? LIMIT 1";
    $sth = $dbh->prepare($query);
    $sth->bindParam(1, $pab_store_id);
    $sth->bindParam(2, $current_row);
    $sth->bindParam(3, $current_col);
    $sth->bindParam(4, $current_slot);
    $sth->execute();
    $row = $sth->fetch();

    $brick = array();
    $brick['id']            = $row[0];
    $brick['desc']         = $row[4] . " " . $row[1];
    $brick['design_id']  = $row[2];
    $brick['updated_on'] = $row[3];
    $brick_id    = $brick['id'];
    $brick['img'] = "/parts/" . $brick['design_id'] . "/" . $brick['id'] . ".jpg";

    if ($edit_part || !$brick_id) {
        print "<div id='part-variables'>\n";
        if ($maximum_slot > 1) {
            print "<h1>What part is in Column $current_col x Row $current_row, Slot $current_slot?</h1>\n";
        } else {
            print "<h1>What part is in Column $current_col x Row $current_row?</h1>\n";
        }
        printPartSearchForm($dbh, 1);

    //
    // There is a brick in the database for this XY.  Display and present a Yes/No prompt
    // so the user can say if it is up to date.
    //
    } else {
        print "<div id='current-row-col-slot'>\n";
        print "<div id='current-brick'>\n";

        if ($maximum_slot > 1) {
            print "<h1>Current Part - Slot $current_slot</h1>\n";
        } else {
            print "<h1>Current Part</h1>\n";
        }

        $brick['img-large'] = str_replace(".jpg", "-large.jpg", $brick['img']);
        print getBrickTDDisplay($brick);
        print "</div>\n";

        print "<div id='max-slot-count'>\n";
        print "<h1># Parts In Bin</h1>\n";
        $url = $_SERVER["REQUEST_URI"];
        if (preg_match("/^(.*)&maximum_slot=\d(.*)/", $url, $match)) {
            $url = $match[1] . $match[2];
        }

        for ($i = 1; $i <= 9; $i++) {
            print "<span class='max-slot-option";
            if ($i == $maximum_slot) {
                print " selected-slot";
            }
            print "'><a href='$url&maximum_slot=$i";

            if ($i < $maximum_slot) {
                print "&delete_slots=1";
            }
            print "'>$i</a></span>";
        }
        print "</div>\n";
        print "</div>\n";

        print "<div id='user-question'>\n";
        print "<h1>Is this correct?</h1>\n";
        printNextWallMapCoordinates($dbh, $pab_country, $pab_store_id, $pab_store_rows, $pab_store_cols, $current_row, $current_col, $current_slot, $maximum_slot, "Yes", "No", $starting_cell, $then_go);
        print "</div>\n";
        print "<div class='clear'></div>\n";
    }
}
print "</form>\n";

printHTMLFooter(0, 0, 0, 0, $show_login_panel);
?>
