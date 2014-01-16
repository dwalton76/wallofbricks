<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';
$dbh = dbConnect();

$type  = 0;
if (array_key_exists("type", $_POST)) {
    $type = $_POST['type'];
}

$color = 0;
if (array_key_exists("color", $_POST)) {
    $color = str_replace("-", " ", $_POST['color']);
}

$dimension_x = 0;
if (array_key_exists("dimension_x", $_POST)) {
    $dimension_x = $_POST['dimension_x'];
}

$dimension_y = 0;
if (array_key_exists("dimension_y", $_POST)) {
    $dimension_y = $_POST['dimension_y'];
}

if (!$dimension_x || !$dimension_y) {
    $dimensions = "";
} else {
    $dimensions = $dimension_x ."x". $dimension_y;
}

$keyword = "";
if (array_key_exists("keyword", $_POST)) {
    $keyword = $_POST['keyword'];
}

$save_part = 0;
if (array_key_exists("save_part", $_POST)) {
    $save_part = $_POST['save_part'];
}

$lego_id = "";
if (array_key_exists("lego_id", $_POST)) {
    $lego_id = $_POST['lego_id'];
}

$search_parameters = "";
if ($lego_id) {
    $query = "SELECT bricks.id, description, design_id, color, ".
                "(SELECT color_group FROM lego_colors WHERE bricks.color = lego_colors.brickset_color LIMIT 1) AS color_group ".
             "FROM bricks ".
             "WHERE (bricks.id='$lego_id' OR design_id='$lego_id') ".
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
             "END ASC, color ASC";
    $search_parameters = "<li>LEGO ID or LEGO Design ID must be $lego_id</li>\n";

} elseif ($type) {
    $query = "SELECT bricks.id, description, design_id, color ".
                "FROM `bricks` ";

    # If the user is searching by keyword then all other search terms have been
    # grayed out so we ignore them.
    if ($keyword) {
        $color_group = '';
        if (preg_match('/black(.*)/i', $keyword, $matches)) {
             $color_group = "black";
             $keyword = $matches[1];

        } elseif (preg_match('/red(.*)/i', $keyword, $matches)) {
             $color_group = "red";
             $keyword = $matches[1];

        } elseif (preg_match('/blue(.*)/i', $keyword, $matches)) {
             $color_group = "blue";
             $keyword = $matches[1];

        } elseif (preg_match('/grey(.*)/i', $keyword, $matches)) {
             $color_group = "grey";
             $keyword = $matches[1];

        } elseif (preg_match('/brown(.*)/i', $keyword, $matches)) {
             $color_group = "brown";
             $keyword = $matches[1];

        } elseif (preg_match('/yellow(.*)/i', $keyword, $matches)) {
             $color_group = "yellow";
             $keyword = $matches[1];

        } elseif (preg_match('/green(.*)/i', $keyword, $matches)) {
             $color_group = "green";
             $keyword = $matches[1];

        } elseif (preg_match('/white(.*)/i', $keyword, $matches)) {
             $color_group = "white";
             $keyword = $matches[1];

        } elseif (preg_match('/orange(.*)/i', $keyword, $matches)) {
             $color_group = "orange";
             $keyword = $matches[1];

        } elseif (preg_match('/purple(.*)/i', $keyword, $matches)) {
             $color_group = "purple";
             $keyword = $matches[1];
        }

        # Replace spaces with % so the sql search is a little more forgiving
        $keyword = trim($keyword);
        $keyword =  str_replace(" ", "%", $keyword);

        if ($color_group) {
             $query .= "INNER JOIN lego_colors ON bricks.color = lego_colors.brickset_color AND color_group='$color_group' ";
        }

        $query .= "WHERE description LIKE '%$keyword%' ";

        if ($color_group) {
            $search_parameters .= "<li>Part must be in color group '$color_group'</li>\n";
        }

        $search_parameters .= "<li>Part name must contain '$keyword'</li>\n";

    } else {
        $search_parameters = "<li>Part must be $color</li>\n";
        $query .= "WHERE color='$color' ";
        if ($type == "Other") {
            $query .= "AND `part_type` IS NULL ";
        } else {
            $query .= "AND `part_type`='$type' ";
        }

        if ($dimensions) {
            $query .= "AND (dimensions ='$dimensions' OR dimensions LIKE '%x$dimensions' OR dimensions LIKE '$dimensions". "x%') ";
        }

        if ($type == "Other") {
            $search_parameters .= "<li>Part must NOT be a brick, plate, tile or slope</li>\n";
        } else {
            $search_parameters .= "<li>Part must be a $type</li>\n";
        }

        if ($dimensions) {
            $search_parameters .= "<li>Part must be $dimensions</li>\n";
        } else {
            $search_parameters .= "<li>Part can be any dimension</li>\n";
        }
    }

    #
    # If the user is searching to save the part to a wall then limit the results to everything after 2008.
    # Also limit it to parts that are in more than 1 set....those corner case parts will never be on a PaB wall.
    #
    # If they are just playing around with the search interface though (no save_part) then don't limit
    # by the year.
    #
    if ($save_part) {
        $query .= " AND max_year >= '2008' AND used_in_sets > 1 ";
    }

    $query .= "ORDER BY `bricks`.`used_in_sets` DESC ".
                 "LIMIT 0, 100";
}

# print "SQL: $query<br>\n";

if ($save_part) {
    print "<div id='save-part'>\n";
    print "<input type='submit' class='save-button' name='save-part' id='save-part' value=''><br>\n";
    print "Save";
    print "</div>\n";
}

print "<div id='search-parameters'>\n";
print "<h1>Search Parameters</h1>\n";
print $search_parameters;
print "</div>\n";
print "<div class='clear'></div>\n";

$sth = $dbh->prepare($query);
$sth->execute();
$first = 1;
$selected_part_id = "";
while ($row = $sth->fetch()) {
    $id    = $row[0];
    $desc = $row[1];
    $design_id = $row[2];
    $color = $row[3];
    $img = "/parts/$design_id/$id.jpg";
    if ($color) {
        $desc = "$color $desc";
    }

    if ($save_part) {
        if ($first) {
            $selected = " selected shadow";
            $selected_part_id = $id;
            $first = 0;
        } else {
            $selected = "";
        }

        print "<div id='$id' class='lego-part$selected'><img src='$img' width='80px' /><span>$desc</span></div>\n";

    // If we aren't updating a wall then navigate to the brick.php page when the user clicks on the part
    } else {
        print "<div id='$id' class='lego-part dom-link' url='/brick.php?id=$id'><img src='$img' width='80px' /><span>$desc</span></div>\n";
    }
}

print "<div class='clear'></div>\n";

if ($save_part) {
    print "<div id='empty' class='lego-part'><img src='/images/empty-80.png' width='80' /><span>Empty Bin</span></div>\n";
    print "<div id='dont-know' class='lego-part'><img src='/images/Questionmark-icon-80.png' width='80' /><span>Unknown Part?????</span></div>\n";
    print "<div class='clear'></div>\n";
    print "<input type='hidden' name='part-id' id='part-id' value='$selected_part_id' />\n";
}

$options_brief = "COLOR:$color::TYPE:$type::DIMENSIONS:$dimensions::KEYWORD:$keyword";

// close the connection
$dbh = null;

return 1;
