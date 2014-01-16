#!/usr/bin/perl

# This was used to download the list of lego stores from brickbuilder

use strict;
use DBI;
use HTML::TableExtract;

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

# TODO: Have the rm all of the pab html files at the start


#
# Parse the main page to get a list of store IDs
#
my $dbh = dbConnect();
my $query = "TRUNCATE TABLE lego_store";
$dbh->do($query);
my $query = "INSERT INTO lego_store(id, city, state, country, url, last_update_date) VALUES (?,?,?,?,?,?)";
my $sth = $dbh->prepare($query);
my $url = "http://www.brickbuildr.com/view/pab/";
my $file = "../private/pab/store_list.html";
my $ua = "--user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3'";
if (!(-e $file)) {
   system "wget $ua -O $file $url";
}

my $html_string;
open(FH, $file) || die("\nERROR: Could not open $file\n");
while(<FH>) {
   chomp();
   $html_string .= $_;
}
close FH;

# my $table_dom = HTML::TableExtract->new(depth => 3, count => 1, keep_html => 1);
my $table_dom = HTML::TableExtract->new(depth => 0, count => 0, keep_html => 1);
$table_dom->parse($html_string);

my $prev_country;
foreach my $ts ($table_dom->tables) {
   #print "Table found at ", join(',', $ts->coords), ":\n";
   #foreach my $row ($ts->rows) {
   #   print "   ", join(',', @$row), "\n\n\n";
   #}
   foreach my $row_ptr ($ts->rows) {
      my @row = @$row_ptr;
      my $url;
      my $id;
      my $city;
      my $state;
      my $country;
      my $last_update_date;

      if ($row[0] =~ /<a href="(http:\/\/www.brickbuildr.com\/.*?\/)(\d+)\/">(.*?)<\/a>/) {
         $url = $1 . $2 . "/";
         $id = $2;
         $city = $3;

         if ($city =~ /(.*), (UK)$/) {
            $city = $1;
            $country = $2;

         # USA
         } elsif ($city =~ /(.*), (\w\w)$/) {
            $city = $1;
            $state = $2;
            $country = "USA";

         # Calgary, AB, Canada
         } elsif ($city =~ /^(.*),\s*(.*?),\s*(.*?)$/) {
            $city = $1;
            $state = $2;
            $country = $3;
         
         # Hamburg, Germany
         } elsif ($city =~ /^(.*),\s*(.*?)$/) {
            $city = $1;
            $country = $2;
         }
      }

      if ($row[1] =~ />(\d\d\d\d\-\d\d\-\d\d)<\/span/) {
         $last_update_date = $1;
      }

      if ($id && $last_update_date) {
         # Special cases for
         # Atlanta (Legoland Discovery Centre)	2013-01-11	(5 months ago)	
         # Chicago (Legoland Discovery Centre)	-	-	
         # Dallas/Fort Worth (Legoland Discovery Centre)	2013-06-18	(3 days ago)	
         # Kansas City (Legoland Discovery Centre)
         if (!$country) {
            $country = $prev_country;
            if ($city =~ /Atlanta/) {
               $state = "GA";
            } elsif ($city =~ /Chicago/) {
               $state = "IL";
            } elsif ($city =~ /Dallas/) {
               $state = "TX";
            } elsif ($city =~ /Kansas City/) {
               $state = "MO";
            }
         }

         # print "ID: $id, CITY: $city, STATE: $state, COUNTRY: $country, LAST_UPDATE: $last_update_date, URL: $url\n";
         $sth->execute($id, $city, $state, $country, $url, $last_update_date);
         $prev_country = $country;
      }
   }
}

my $query = "TRUNCATE TABLE lego_store_inventory";
$dbh->do($query);

my $query = "SELECT id, url FROM lego_store";
my $sth = $dbh->prepare($query);
$sth->execute();

my $query_insert = "INSERT IGNORE INTO lego_store_inventory(store_id, brick_id, row, col) VALUES (?,?,?,?)";
my $sth_insert = $dbh->prepare($query_insert);

while (my @row = $sth->fetchrow_array()) {
   my $id = $row[0];
   my $url = $row[1];

   # dwalton - remove
   # next if ($id != 18);

   my $file = "../private/pab/pickabrick-$id.html";
   print "URL: $url\n";

   if (!(-e $file)) {
      system "wget --user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3' -O $file $url" 
   }

   my $html_string;
   open(FH, $file) || die("\nERROR: Could not open $file\n\n");
   while(<FH>) {
      chomp();
      $html_string .= $_;
   }
   close FH;


   # my $table_dom = HTML::TableExtract->new(depth => 3, count => 1, keep_html => 1);
   my $table_dom = HTML::TableExtract->new(keep_html => 1);
   $table_dom->parse($html_string);

#   foreach my $ts ($table_dom->tables) {
#      print "Table found at ", join(',', $ts->coords), ":\n";
#      foreach my $row ($ts->rows) {
#         print "   ", join(',', @$row), "\n\n\n";
#      }
#   }

   my $wall = 0;
   foreach my $ts ($table_dom->tables) {

      my $row = 6;
      my $init_col = 1 + ($wall * 10);
      my $increment_wall = 0;

      foreach my $row_ptr ($ts->rows) {

         my $col = $init_col;
         my @row = @$row_ptr;
         my $decrement_row = 0;

         foreach my $td (@row) {

            # http://www.brickbuildr.com/images/parts/5/3004.png 
            if ($td =~ /images\/parts\/(\d+)\/(\w+)\./) {
               $increment_wall = 1;
               my $brick_id = $2 . "-" . $1;
               #print "PUSHING $col x $row: $brick_id\n";
               $sth_insert->execute($id, $brick_id, $row, $col);
               $decrement_row = 1;
               $col++;
            } elsif ($td =~ /images\/empty\.png/) {
               $increment_wall = 1;
               $decrement_row = 1;
               $col++;
            }
         }

         if ($decrement_row) {
            $row--;
         }
      }

      if ($increment_wall) {
         $wall++;
      }
   }
}

