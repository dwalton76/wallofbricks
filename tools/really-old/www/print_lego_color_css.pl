#!/usr/bin/perl

use strict;
use DBI;

sub dbConnect() {
   #Database Attributes
   my $host     = "localhost";  # Host to connect to
   my $db       = "lego";
   my $user     = "lego_admin";
   my $password = "PASSWORD";

   # Connect to the databse and set the handle to $dbh
   my $dbh = DBI->connect("DBI:mysql:database=$db:host=$host", $user, $password) || die("\n\nERROR: Can't connect to database: $DBI::errstr\n");
   return $dbh;
}

my $dbh = dbConnect();
my $query = "SELECT name, rgb FROM  `lego_colors` WHERE  `max_year`=2013 ORDER BY  `lego_colors`.`number_parts` DESC";
my $sth = $dbh->prepare($query);
$sth->execute();
while (my @row = $sth->fetchrow()) {
   my $name_with_spaces = $row[0];
   my $css_name = lc($name_with_spaces);
   $css_name =~ s/ /-/g;
   $css_name =~ s/_/-/g;
   my $rgb = $row[1];

   #printf("%-27s div.color-fill { background-color: %s; }\n", "div#" . $css_name, $rgb);

   # print "<div class='color-sample' id='sand-red'><h3>Sand Red</h3><div class='color-fill'>&nbsp;</div></div>\n";
   printf("<div class='color-sample' id='%s'><h3>%s</h3><div class='color-fill'>&nbsp;</div></div>\n",
          $css_name, $name_with_spaces);
}
