#!/usr/bin/perl

# This was used to download the number of rows and cols for each lego store...I was getting
# this from brickbuilder

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

# TODO: Have the rm all of the pab html files at the start


#
# Parse the main page to get a list of store IDs
#
my $dbh = dbConnect();
my $query_update = "UPDATE lego_store SET rows=?, cols=?, rows_cols_set='1' WHERE id=?";
my $sth_update = $dbh->prepare($query_update);

my $query = "SELECT id, url FROM lego_store WHERE rows_cols_set != 1";
my $sth = $dbh->prepare($query);
$sth->execute();
while (my @row = $sth->fetchrow_array()) {
   my $id = $row[0];
   my $url = $row[1];
   my $max_col = 0;
   my $file = "/var/www/lego/private/pab/pickabrick-$id.html";

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


   my $table_dom = HTML::TableExtract->new(keep_html => 1);
   $table_dom->parse($html_string);

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

         if ($col > $max_col) {
            $max_col = $col;
         }
      }

      if ($increment_wall) {
         $wall++;
      }
   }

   # Subtract 1 for the header column on the left
   $max_col--;
   print "ID: $id, ROW 6, COL $max_col\n";
   $sth_update->execute(6, $max_col, $id);
}

