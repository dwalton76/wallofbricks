#!/usr/bin/perl

use strict;
use DBI;

my $ua = "--user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3'";

#
# Connect to the database
#
sub dbConnect() {
   #Database Attributes
   my $host     = "localhost";  # Host to connect to
   my $db       = "dwalto76_lego";
   my $user     = "dwalto76_admin";
   my $password = "PASSWORD";

   # Connect to the databse and set the handle to $dbh
   my $dbh = DBI->connect("DBI:mysql:database=$db:host=$host", $user, $password) || die("\n\nERROR: Can't connect to database: $DBI::errstr\n");
   return $dbh;
}

my $dbh = dbConnect();

   my $query = "UPDATE lego_colors SET number_parts=?, min_year=?, max_year=? WHERE brickset_color=?";
   my $sth_update = $dbh->prepare($query);

   my $query = "SELECT color, COUNT( color ) , MIN( min_year ) , MAX( max_year ) FROM  `bricks` GROUP BY color";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $color = $row[0];
      my $count = $row[1];
      my $min_year = $row[2];
      my $max_year = $row[3];
      $sth_update->execute($count, $min_year, $max_year, $color);
   }
