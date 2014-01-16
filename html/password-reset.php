<?php
define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - Password Reset", "", 0);

print "<div id=\"content\">\n";
if (isset($_POST['submit'])) {
    $submit_action = $_POST['submit'];

    if ($submit_action == "Reset Password" && isset($_POST['reset_email'])) {

        $dbh = dbConnect();

        // First see if this user exists
        $query = "SELECT email FROM users WHERE email=? LIMIT 1";
        $sth = $dbh->prepare($query);
        $sth->bindParam(1, $_POST['reset_email']);
        $sth->execute();
        $row = $sth->fetch();

        if ($row[0]) {
            $email = $row[0];

            // Generate a random password
            $pass = substr(md5($_SERVER['REMOTE_ADDR'].microtime().rand(1,100000)),0,8);

            // set the password in the database
            $query = "UPDATE users SET pass=? WHERE email=?";
            $sth = $dbh->prepare($query);
            $sth->bindParam(1, md5($pass));
            $sth->bindParam(2, $email);
            $sth->execute();
            $row = $sth->fetch();

            print "Your new password has been emailed to you at $email<br>\n";
            $subject = "New password for www.wallofbricks.com";
            $message = "Your new password is $pass\nLogin: www.wallofbricks.com\n";
            $headers = 'From: help@wallofbricks.com' . "\r\n" .
                          'Reply-To: help@wallofbricks.com' . "\r\n" .
                          'X-Mailer: PHP/' . phpversion();
            mail($email, $subject, $message, $headers);

        } else {
            print "Sorry, we do not have an account with that username or email addresss";
        }
    }

} else {
?>
    <form action="/password-reset.php"  method="post">
    <label class="grey" for="reset_email">Email Adddress</label><br>
    <input class="field" type="text" name="reset_email" id="reset_email" value="" size="23" /><br>
    <input type="submit" name="submit" value="Reset Password" />
    </form>
<?php
}

print "</div>\n";
printHTMLFooter(0, 0, 0, 0, $show_login_panel);
?>
