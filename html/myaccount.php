<?php
define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - Set Display", "");
if (!$username) {
    printAccountBenefits();
    printHTMLFooter(0, 0, 0, 0, $show_login_panel);
    exit();
}

$dbh = dbConnect();

function importRebrickable($dbh, $username, $rebrickable_username, $rebrickable_password) {
    $ldraw2brickset = array();
    $ldraw2brickset['0'] = 'Black';
    $ldraw2brickset['1'] = 'Bright Blue';
    $ldraw2brickset['2'] = 'Dark Green';
    $ldraw2brickset['3'] = 'Bright Bluish Green';
    $ldraw2brickset['4'] = 'Bright Red';
    $ldraw2brickset['5'] = 'Bright Purple';
    $ldraw2brickset['6'] = 'Earth Orange';
    $ldraw2brickset['7'] = 'Grey';
    $ldraw2brickset['8'] = 'Dark Grey';
    $ldraw2brickset['9'] = 'Light Blue';
    $ldraw2brickset['10'] = 'Bright Green';
    $ldraw2brickset['11'] = 'Medium Bluish Green';
    $ldraw2brickset['14'] = 'Bright Yellow';
    $ldraw2brickset['15'] = 'White';
    $ldraw2brickset['17'] = 'Light Green';
    $ldraw2brickset['18'] = 'Light Yellow';
    $ldraw2brickset['19'] = 'Brick Yellow';
    $ldraw2brickset['20'] = 'Light Bluish Violet';
    $ldraw2brickset['21'] = 'Phosphorescent White';
    $ldraw2brickset['22'] = 'Bright Violet';
    $ldraw2brickset['23'] = 'Dark Royal Blue';
    $ldraw2brickset['25'] = 'Bright Orange';
    $ldraw2brickset['26'] = 'Bright Reddish Violet';
    $ldraw2brickset['27'] = 'Bright Yellowish Green';
    $ldraw2brickset['28'] = 'Sand Yellow';
    $ldraw2brickset['29'] = 'Light Purple';
    $ldraw2brickset['30'] = 'Medium Lavender';
    $ldraw2brickset['31'] = 'Lavender';
    $ldraw2brickset['33'] = 'Tr. Blue';
    $ldraw2brickset['34'] = 'Tr. Green';
    $ldraw2brickset['35'] = 'Tr. Bright Green';
    $ldraw2brickset['36'] = 'Tr. Red';
    $ldraw2brickset['37'] = 'Tr. Bright Bluish Violet';
    $ldraw2brickset['40'] = 'Tr. Brown';
    $ldraw2brickset['41'] = 'Tr. Light Blue';
    $ldraw2brickset['42'] = 'Tr. Fluore. Green';
    $ldraw2brickset['45'] = 'Tr. Medium Reddish Violet';
    $ldraw2brickset['46'] = 'Tr. Yellow';
    $ldraw2brickset['47'] = 'Transparent';
    $ldraw2brickset['69'] = 'Bright Reddish Lilac';
    $ldraw2brickset['70'] = 'Reddish Brown';
    $ldraw2brickset['71'] = 'Medium Stone Grey';
    $ldraw2brickset['72'] = 'Dark Stone Grey';
    $ldraw2brickset['73'] = 'Medium Blue';
    $ldraw2brickset['74'] = 'Medium Green';
    $ldraw2brickset['78'] = 'Light Nougat';
    $ldraw2brickset['84'] = 'Medium Nougat';
    $ldraw2brickset['85'] = 'Medium Lilac';
    $ldraw2brickset['86'] = 'Brown';
    $ldraw2brickset['92'] = 'Nougat';
    $ldraw2brickset['110'] = 'Bright Bluish Violet';
    $ldraw2brickset['112'] = 'Medium Bluish Violet';
    $ldraw2brickset['114'] = 'Tr. M. Reddish-Viol W. Glit.2%';
    $ldraw2brickset['115'] = 'Medium Yellowish Green';
    $ldraw2brickset['118'] = 'Light Bluish Green';
    $ldraw2brickset['120'] = 'Light Yellowish Green';
    $ldraw2brickset['129'] = 'Tr. Br. Bluish.Viol.W.Gliter2%';
    $ldraw2brickset['134'] = 'Copper';
    $ldraw2brickset['135'] = 'Silver';
    $ldraw2brickset['142'] = 'Gold';
    $ldraw2brickset['148'] = 'Metallic Dark Grey';
    $ldraw2brickset['151'] = 'Light Stone Grey';
    $ldraw2brickset['179'] = 'Silver';
    $ldraw2brickset['191'] = 'Flame Yellowish Orange';
    $ldraw2brickset['212'] = 'Light Royal Blue';
    $ldraw2brickset['226'] = 'Cool Yellow';
    $ldraw2brickset['232'] = 'Dove Blue';
    $ldraw2brickset['272'] = 'Earth Blue';
    $ldraw2brickset['288'] = 'Earth Green';
    $ldraw2brickset['294'] = 'Phosph.Green';
    $ldraw2brickset['297'] = 'Warm Gold';
    $ldraw2brickset['308'] = 'Dark Brown';
    $ldraw2brickset['313'] = 'Pastel Blue';
    $ldraw2brickset['320'] = 'New Dark Red';
    $ldraw2brickset['321'] = 'Dark Azur';
    $ldraw2brickset['322'] = 'Medium Azur';
    $ldraw2brickset['323'] = 'Aqua';
    $ldraw2brickset['326'] = 'Olive Green';
    $ldraw2brickset['335'] = 'Sand Red';
    $ldraw2brickset['351'] = 'Medium Reddish Violet';
    $ldraw2brickset['366'] = 'Light Orange Brown';
    $ldraw2brickset['373'] = 'Sand Violet';
    $ldraw2brickset['378'] = 'Sand Green';
    $ldraw2brickset['379'] = 'Sand Blue';
    $ldraw2brickset['383'] = 'Silver';
    $ldraw2brickset['462'] = 'Bright Yellowish Orange';
    $ldraw2brickset['484'] = 'Dark Orange';
    $ldraw2brickset['503'] = 'Light Grey';

    # The legoID hash is keyed by design_id and brickset color to get the lego ID#
    $legoID = array();
    $query = "SELECT id, design_id, color FROM bricks";
    $sth = $dbh->prepare($query);
    $sth->execute();
    while ($row = $sth->fetch()) {
        $legoID[$row[1]][$row[2]] = $row[0];
        # printf("legoID[%s][%s] = %d\n", $row[1], $row[2], $row[0]);
    }

    $filename = "/tmp/rebrickable-$username.csv";

    # dwalton
    if (!$username) {
        print "ERROR: You must specify your WoB username\n";
        printHTMLFooter(0, 0, 0, 0, $show_login_panel);
        exit();
    }

    if (!$rebrickable_username) {
        print "ERROR: You must specify your rebrickable username\n";
        printHTMLFooter(0, 0, 0, 0, $show_login_panel);
        exit();
    }

    if (!$rebrickable_password) {
        print "ERROR: You must specify your rebrickable password\n";
        printHTMLFooter(0, 0, 0, 0, $show_login_panel);
        exit();
    }

    system("wget -O $filename 'http://rebrickable.com/api/get_user_sets?key=S21AaFDTL7&email=$rebrickable_username&pass=$rebrickable_password&format=csv'");

    $fh = fopen($filename, "r");
    if ($fh) {
       $insert_query = "INSERT INTO sets_i_own (username, id, quantity, added_via) VALUE (?,?,?,'rebrickable') ON DUPLICATE KEY UPDATE quantity=?";
       $insert_sth = $dbh->prepare($insert_query);

       while (($line = fgets($fh)) !== false) {
          list($setid, $qty) = split(",", $line);
          # print "SETID: $setid, QTY: $qty\n";
          $insert_sth->bindParam(1, $username);
          $insert_sth->bindParam(2, $setid);
          $insert_sth->bindParam(3, $qty);
          $insert_sth->bindParam(4, $qty);
          $insert_sth->execute();
        }
    }
    unlink($filename);

    system("wget -O $filename 'http://rebrickable.com/api/get_user_parts?key=S21AaFDTL7&email=$rebrickable_username&pass=$rebrickable_password&format=csv'");

    $fh = fopen($filename, "r");
    if ($fh) {
       $insert_query = "INSERT INTO bricks_i_own (username, id, quantity, added_via) VALUE (?,?,?,'rebrickable') ON DUPLICATE KEY UPDATE quantity=?";
       $insert_sth = $dbh->prepare($insert_query);

       while (($line = fgets($fh)) !== false) {
          $line = trim($line);
          list($design_id, $ldraw_color, $qty) = split(",", $line);

          if (preg_match('/^(\d+)[a-zA-Z]$/', $design_id, $matches)) {
             $design_id = $matches[1];
          }

          $lego_id = 0;
          $brickset_color = $ldraw2brickset{$ldraw_color};
          if ($brickset_color && array_key_exists($design_id, $legoID)) {
             if (array_key_exists($brickset_color, $legoID[$design_id])) {
                $lego_id = $legoID[$design_id][$brickset_color];
             }
          }

          if ($lego_id) {
             #print "LEGO_ID: $lego_id, QTY: $qty\n";
             $insert_sth->bindParam(1, $username);
             $insert_sth->bindParam(2, $lego_id);
             $insert_sth->bindParam(3, $qty);
             $insert_sth->bindParam(4, $qty);
             $insert_sth->execute();
          }
       }
    }

    unlink($filename);
}


$submit = False;
if (array_key_exists('submit', $_POST)) {
    $submit = $_POST['submit'];
}

if ($submit == 'Change Password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    if (checkPassword($new_password)) {
        $query = "SELECT id, username FROM users WHERE username=? AND pass=? LIMIT 1";
        $sth = $dbh->prepare($query);
        $sth->bindParam(1, $username);
        $sth->bindParam(2, md5($current_password));
        $sth->execute();
        $row = $sth->fetch();
        if ($row[0]) {
            $query = "UPDATE users SET pass=? WHERE username=?";
            $sth = $dbh->prepare($query);
            $sth->bindParam(1, md5($new_password));
            $sth->bindParam(2, $username);
            $sth->execute();
            print "Password changed!\n";

        } else {
          print "ERROR: Incorrect username or password!";
        }

    } else {
        print "ERROR: Your password must be at least 6 characters.\n";
    }

} elseif ($submit == 'Import From Rebrickable') {
    $rebrickable_username = $_POST['rebrickable_username'];
    $rebrickable_password = $_POST['rebrickable_password'];
    importRebrickable($dbh, $username, $rebrickable_username, $rebrickable_password);
}

?>
<h1>Change Password</h1>
<form method='post' action='myaccount.php'>
<table>
<tr>
<td><label class="grey" for="current_password">Current Password</label></td>
<td><input class="field" type="password" name="current_password" id="current_password" size="23" /></td>
</tr>
<tr>
<td><label class="grey" for="new_password">New Password</label></td>
<td><input class="field" type="password" name="new_password" id="new_password" size="23" /></td>
</tr>
<tfoot>
<tr>
<td colspan='2' align='center'><input type="submit" name="submit" value="Change Password" /></td>
</tr>
</tfoot>
</table>
</form>

<h1>Rebrickable</h1>
<?php
    if ($submit == 'Import From Rebrickable') {
        print "Your sets and bricks inventory has been imported from rebrickable.com.  You can view your sets on your <a href='/mysets.php'>My Sets</a> page.";
    } else {
?>
You can import your sets list and parts list from rebrickable.com.<br>We will not store your username or password.<br>
<form method='post' action='myaccount.php' id='rebrickable-login-info'>
<table>
<tr>
<td><label class="grey" for="rebrickable_username">Rebrickable Username:</label></td>
<td><input class="field" type="text" name="rebrickable_username" id="rebrickable_username" size="23" /></td>
</tr>
<tr>
<td><label class="grey" for="rebrickable_password">Rebrickable Password:</label></td>
<td><input class="field" type="password" name="rebrickable_password" id="rebrickable_password" size="23" /></td>
</tr>

<tfoot>
<tr>
<td colspan='2' align='center'><input type="submit" name="submit" value="Import From Rebrickable" /></td>
</tr>
</tfoot>
</table>
</form>
<?php
    }

printHTMLFooter(0, 0, 0, 0, $show_login_panel);
?>
