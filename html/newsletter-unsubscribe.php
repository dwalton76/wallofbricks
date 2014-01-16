<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';
include 'include/functions.php';

printHTMLHeader("Wall of Bricks - Newsletter", "");

$action = "";
$email = "";
$key = "";

if (isset($_POST['submit'])) {
    $action = $_POST['submit'];

} elseif (isset($_GET['email']) && isset($_GET['key'])) {
    $action = "UnsubscribeFinal";
}

// Send an email that the user can use to unsubscribe
if ($action == "Unsubscribe") {
    $email = $_POST['email'];
    $key = md5($email . "FOOBAR");

    $subject = "unsubscribe from www.wallofbricks.com";
    $message = "To unsubscribe from our newsletter please follow this link:\n".
               "http://www.wallofbricks.com/newsletter-unsubscribe.php?email=$email&key=$key\n";
    $headers = 'From: help@wallofbricks.com' . "\r\n" .
               'Reply-To: help@wallofbricks.com' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    mail($email, $subject, $message, $headers);

// Remove this email address from the users_email table
} elseif ($action == "UnsubscribeFinal") {
    $email = $_GET['email'];
    $key = $_GET['key'];
    $expected_key = md5($email . "FOOBAR");

    if ($email && $key == $expected_key) {
        $query = "DELETE FROM users_email WHERE email=?";
        $sth = $dbh->prepare($query);
        $sth->bindParam(1, $email);
        $sth->execute();
        print "$email has been removed from our newsletter\n";
    } else {
        print "We were not able to authenticate your unsubscribe request.  You should be able to unsubscribe by following the link we emailed you.  If that is not the case please email us at <a href='mailto:help@wallofbricks.com'>help@wallofbricks.com</a>.";
    }

} else {
?>
<div id='newsletter'>
<h1>Unsubsribe</h1>
Enter the email address that you would like to unsubscribe.  We'll send you an email with a link that will allow you to remove yourself from our mailing list.<br>
<form action="/newsletter-unsubscribe.php" method="post">
<label for="email"><h2>Email Address</h2></label>
<input type="text" name="email" id="email" value="" size="30" /><br><br>
<input type="submit" class="button" name="submit" value="Unsubscribe" />
</form>
</div>
<?php
}
printHTMLFooter(0, 0, 0, 0);
?>
