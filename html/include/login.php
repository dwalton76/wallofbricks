<?php

if (!defined('INCLUDE_CHECK')) {
    die('You are not allowed to execute this file directly');
}

function addEmailToNewsletter($dbh, $email, $print_status)
{
    $query = "INSERT IGNORE INTO users_email(email, joined_on, client_ip) VALUES(?, NOW(), ?)";
    $sth = $dbh->prepare($query);
    $sth->bindParam(1, $email);
    $sth->bindParam(2, $_SERVER['REMOTE_ADDR']);
    $sth->execute();

    if ($print_status) {
        print "<strong>$email</strong> is now subscribed to our newsletter.";
    }
}

function checkPassword($str)
{
    if (strlen($str) < 6) {
        return 0;
    }

    return 1;
}

function handleUserLogout()
{
    global $username;
    $username = "";
    $_SESSION = array();
    session_destroy();
    header("Location: /index.php");
}

/* Modified from:
 * http://tutorialzine.com/2009/10/cool-login-system-php-jquery/
 *
 * Return TRUE if everything was ok, return FALSE if there was an error
 */
function handleUserLogin()
{
    global $username;

    // Starting the session
    session_name('wallofbricksLogin');

    // Making the cookie live for 12 weeks
    $max_session_time = 12*7*24*60*60;
    ini_set('session.gc_maxlifetime', $max_session_time);
    ini_set("session.cookie_lifetime", $max_session_time);
    session_set_cookie_params($max_session_time);

    session_start();

    if (isset($_GET['logoff'])) {
        handleUserLogout();

        return 1;
    }

    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
    } else {
        $username = "";
    }

    // Checking whether the Login form has been submitted
    if (!array_key_exists('submit', $_POST)) {
        return 1;
    }

    if ($_POST['submit'] == 'Login') {
        $dbh = dbConnect();

        // Will hold our errors
        $err = array();

        if (!$_POST['member_email_or_username'] || !$_POST['member_password']) {
            $err[] = 'Email and Password fields must be filled in!';
        }

        if (!count($err)) {
            if (checkEmail($_POST['member_email_or_username'])) {
                $query = "SELECT id, username FROM users WHERE email=? AND pass=? LIMIT 1";
            } else {
                $query = "SELECT id, username FROM users WHERE username=? AND pass=? LIMIT 1";
            }

            $sth = $dbh->prepare($query);
            $sth->bindParam(1, $_POST['member_email_or_username']);
            $sth->bindParam(2, md5($_POST['member_password']));
            $sth->execute();
            $row = $sth->fetch();

            // If everything is OK login
            if ($row[0]) {
                $_SESSION['id']  = $row[0];
                $_SESSION['username'] = $row[1];
                $username = $row[1];
            } else {
                unset($_SESSION['id']);
                unset($_SESSION['username']);
                $username = "";
                $err[]='Incorrect username or password!';
            }
        }

        // Save the error messages in the session
        if ($err) {
            $_SESSION['msg']['login-err'] = implode('<br />', $err);

            return 0;
        }

    // If the Register form has been submitted
    } elseif ($_POST['submit']=='Register') {
        $dbh = dbConnect();
        $err = array();

        if (strlen($_POST['register_username']) < 4 || strlen($_POST['register_username']) > 32) {
            $err[]='Your username must be between 4 and 32 characters';
        }

        if (strlen($_POST['register_email']) < 4 || strlen($_POST['register_email']) > 64) {
            $err[]='Your email address must be between 4 and 64 characters';
        }

        if (!checkEmail($_POST['register_email'])) {
            $err[]='Your email address is not valid!';
        }

        if (!count($err)) {
            $query = "SELECT id FROM users WHERE username=? LIMIT 1";
            $sth = $dbh->prepare($query);
            $sth->bindParam(1, $_POST['register_username']);
            $sth->execute();
            $row = $sth->fetch();

            if ($row[0]) {
                $err[] = 'That username is already taken :(';
            }
        }

        if (!count($err)) {
            $query = "SELECT id FROM users WHERE email=? LIMIT 1";
            $sth = $dbh->prepare($query);
            $sth->bindParam(1, $_POST['register_email']);
            $sth->execute();
            $row = $sth->fetch();

            if ($row[0]) {
                $err[] = 'That email address is already taken :(';
            }
        }

        if (!count($err)) {
            if (!checkPassword($_POST['register_password'])) {
                $err[] = 'Your password must be at least 6 characters.';
            }
        }

        // If there are no errors
        if (!count($err)) {

            // Escape the input data
            $query = "INSERT INTO users(username,email,pass,regIP,joined_on) VALUES(?,?,?,?,NOW())";
            $sth = $dbh->prepare($query);
            $sth->bindParam(1, $_POST['register_username']);
            $sth->bindParam(2, $_POST['register_email']);
            $sth->bindParam(3, md5($_POST['register_password']));
            $sth->bindParam(4, $_SERVER['REMOTE_ADDR']);
            $sth->execute();

            $query = "SELECT id, username FROM users WHERE username=? AND email=? LIMIT 1";
            $sth = $dbh->prepare($query);
            $sth->bindParam(1, $_POST['register_username']);
            $sth->bindParam(2, $_POST['register_email']);
            $sth->execute();
            $row = $sth->fetch();

            if ($row[0]) {
                $_SESSION['id'] = $row[0];
                $_SESSION['username'] = $row[1];
                $_SESSION['msg']['reg-success']='You are logged in!';
                addEmailToNewsletter($dbh, $_POST['register_email'], 0);
            } else {
                $err[] = 'Registration failed :(';
            }

            header("Location: /index.php");
        }

        if (count($err)) {
            $_SESSION['msg']['reg-err'] = implode('<br />', $err);

            return 0;
        }
    }

    return 1;
}

/* Modified from:
 * http://tutorialzine.com/2009/10/cool-login-system-php-jquery/
 */
function printLoginDropdown()
{
    global $_GET;

    $file = $_SERVER["SCRIPT_NAME"];
    $break = Explode('/', $file);
    $page_name = $break[count($break) - 1];
    $username = isset($_POST['username']) ? $_POST['username'] : "";
    $url_args = $_SERVER['QUERY_STRING'];
?>

<div id="toppanel"><!-- Panel -->
<div id="panel">
<div class="content clearfix">
<div class="left">
&nbsp;
</div>
<?php
            if (!isset($_SESSION['id'])) {
?>
<div class="left">
<!-- Register Form -->
<form action="/<?php print "$page_name?$url_args "; ?>" method="post">
<span class="pseudo_h1">Not a member yet? Sign Up!</span>
<?php
                if (isset($_SESSION['msg']['reg-err'])) {
                    echo '<div class="err">'.$_SESSION['msg']['reg-err'].'</div>';
                    unset($_SESSION['msg']['reg-err']);
                    $show_login_panel = 1;
                } elseif (isset($_SESSION['msg']['reg-success'])) {
                    echo '<div class="success">'.$_SESSION['msg']['reg-success'].'</div>';
                    unset($_SESSION['msg']['reg-success']);
                }
?>
<label class="grey" for="register_username">Username:</label>
<input class="field" type="text" name="register_username" id="register_username" value="<?php if (isset($_POST['register_username'])) {print $_POST['register_username']; } ?>" size="23" />
<label class="grey" for="register_email">Email:</label>
<input class="field" type="text" name="register_email" id="register_email" value="<?php if (isset($_POST['register_email'])) {print $_POST['register_email']; } ?>" size="23" />
<label class="grey" for="register_password">Password:</label>
<input class="field" type="password" name="register_password" id="register_password" size="23" />
<input type="submit" name="submit" value="Register" class="bt_register" />
</form>
</div>
<div class="left right">
<!-- Login Form -->
<form class="clearfix" action="/<?php print "$page_name?$url_args ";?>" method="post">
<span class="pseudo_h1">Member Login</span>
<?php
                if (isset($_SESSION['msg']['login-err'])) {
                    print '<div class="err">' . $_SESSION['msg']['login-err'] . '</div>';
                    unset($_SESSION['msg']['login-err']);
                    $show_login_panel = 1;
                }
?>
<label class="grey" for="member_email_or_username">Username or Email:</label>
<input class="field" type="text" name="member_email_or_username" id="member_email_or_username" value="<?php if (isset($_POST['member_email_or_username'])) {print $_POST['member_email_or_username']; } ?>" size="23" />
<label class="grey" for="member_password">Password:</label>
<input class="field" type="password" name="member_password" id="member_password" size="23" />
<span id="password_reset"><a href="password-reset.php">Reset my password</a></span>

<div class="clear"></div>
<input type="submit" name="submit" value="Login" class="bt_login" />
</form>
</div>
<?php
            } else {
?>
<div class="left">
</div>
<div class="left right">
<span class="pseudo_h1">Members panel</span>
<a href="?logoff">Logout</a>
</div>
<?php
            }
?>
</div>
</div> <!-- /login -->

<!-- The tab on top -->
<div class="tab">
<div class="alignCenter">
<ul class="login">
<li class="left">&nbsp;</li>
<li>Hello <?php echo (array_key_exists('username', $_SESSION) && $_SESSION['username']) ? $_SESSION['username'] : 'Guest';?>!</li>
<li class="sep">|</li>
<li id="toggle">
<a id="open" class="open" href="#"><?php echo (array_key_exists('username', $_SESSION) && $_SESSION['id']) ? 'Open Panel' : 'Log In | Register';?></a>
<a id="close" style="display: none;" class="close" href="#">Close Panel</a>
</li>
<li class="right">&nbsp;</li>
</ul>
</div>
</div>
</div>
<?php

} // End of printLoginDropdown

?>
