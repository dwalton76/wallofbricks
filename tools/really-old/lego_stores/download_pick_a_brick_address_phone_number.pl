#!/usr/bin/perl

use strict;
use DBI;
use HTML::TableExtract;

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

#
# Parse the main page to get a list of store IDs
#
my $dbh = dbConnect();
my $query_update = "UPDATE lego_store SET address=?, phone_number=? WHERE id=?";
my $sth_update = $dbh->prepare($query_update);

my $query = "SELECT id, url FROM lego_store ";
my $sth = $dbh->prepare($query);
$sth->execute();
while (my @row = $sth->fetchrow_array()) {
   my $id = $row[0];
   my $url = $row[1];
   my $address = "";
   my $phone_number = "";
   my $max_col = 0;
   my $file = "/var/www/lego/private/pab/pickabrick-$id.html";

   if (!(-e $file)) {
      system "wget --user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3' -O $file $url" 
   }

   # <label for="telephone"><strong>Telephone</strong></label> <span id="telephone"><a title="Call store in Calgary, AB, Canada" href="tel:(403) 252-5346">(403) 252-5346</a></span>

   # <span id="address"><a target="_new" title="Map it" href="http://maps.google.com/maps?q=Chinook+Centre,+6455+Macleod+Trail+SW++Calgary,+Alberta++T2H+0K8">Chinook Centre, 6455 Macleod Trail SW  Calgary, Alberta  T2H 0K8</a></span>
   open(FH, $file) || die("\nERROR: Could not open $file\n\n");
   while(<FH>) {
      chomp();
      if (/span id="address"><a .*?>(.*?)<\/a/) {
         $address = $1;
      }

      if (/span id="telephone"><a .*?>(.*?)<\/a/) {
         $phone_number = $1;
      }
   }
   close FH;

   print "ID: $id, ADDRESS: $address, NUMBER: $phone_number\n";
   if ($address && $phone_number) {
      $sth_update->execute($address, $phone_number, $id);
   }
}

