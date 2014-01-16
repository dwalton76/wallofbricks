<?php
define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - FAQ", "");
?>
<div style='margin-bottom: 20px;'>
<h1>About Me</h1>
My name is Daniel Walton and I am the man behind the curtain of this little website. If you have questions or feedback please send me an email at <a href='mailto:help@wallofbricks.com'>help@wallofbricks.com</a>. You can follow me on twitter at <a href='https://twitter.com/dwalton76'>@dwalton76</a>
<br>
<br>
In addition to this site I also have a blog where I post about my lego robot projects.  The blog is <a href='http://programmablebrick.blogspot.com/'>programmablebrick.blogspot.com</a>.
</div>

<div style='margin-bottom: 20px;'>
<h1>Discussion</h1>
There is a fairly active discussion about the site on the brickset forums:<br>
<a href='http://www.bricksetforum.com/discussion/11262/new-site-for-tracking-pick-a-brick-pab'>http://www.bricksetforum.com/discussion/11262/new-site-for-tracking-pick-a-brick-pab</a>
</div>

<div>
<h1>History</h1>
This site started with a botched trip to the LEGO store in Orlando, FL. I found some information online in regards to what bricks were available on the Orlando wall so I printed out a shopping list and off I went.  I got to the store and my shopping list was useless because the info I found online about what was on the wall was months out of date :( I felt like the information that I found may have been out of date because it was difficult for users to update the data.
<br>
<br>
I went back to my hotel (I was in Orlando for work) and coded a very basic version of this website. A few days later I went back to the Orlando store and inventoried all 36 columns in about 90 minutes. Several people in the store asked me what app I was using (I was using my iPhone) and asked lots of questions. When I got home from work I launched the site and announced it on a few forums.
</div>
<?php
printHTMLFooter(0, 0, 0, 0, $show_login_panel);
$dbh = dbConnect();
?>
