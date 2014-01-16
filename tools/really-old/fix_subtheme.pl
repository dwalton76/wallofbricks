#!/usr/bin/perl

use strict;
use Mojo::DOM;
use HTML::TableExtract;
use DBI;
use Image::Thumbnail 0.65;

my $ua = "--user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3'";
my $dbh = dbConnect();

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

#
# Download the brickset page 
#
sub downloadBrickSet($) {
   my $set_to_download = shift;

   my $html_filename = "/var/www/lego/private/sets/brickset-$set_to_download.html";
   if (!(-e $html_filename) || !(-s $html_filename)) {
      return; # dwalton
      system "wget -O $html_filename http://www.brickset.com/detail/?set=$set_to_download";
   } else {
      print "DOWNLOADED BRICKSET PAGE: $set_to_download $html_filename\n";
   }
   
   my $html_string;
   open(FH, $html_filename) || die("\nERROR: Could not open $html_filename\n\n");
   while(<FH>) {
      chomp();
      $html_string .= $_;
   }

   # http://search.cpan.org/~sri/Mojolicious-4.16/lib/Mojo/DOM.pm
   my $dom = Mojo::DOM->new($html_string);
   close FH;

   my $theme_group;
   my $theme;
   my $subtheme;
   my $minifigs;
   my $year;
   my $cost;
   my $min_age;
   my $max_age;
   my $barcodes;
   my $lego_item_number;
   foreach my $i ($dom->find('div#menuPanel ul.setDetails li')->each) {
      my $i_span = $i->at('span');
      if ($i_span->text =~ /Change Log/i) {
         last;
      }

      # printf("DEBUG: %s -> %s\n", $i_span->text, $i->text);
      if ($i_span->text eq "Theme group") {
         $theme_group = $i->text;

      } elsif ($i_span->text eq "Theme") {
         my $i_href = $i->at('a');
         $theme = $i_href->text;

      } elsif ($i_span->text eq "Subtheme") {
         my $i_href = $i->at('a');
         $subtheme = $i_href->text;

      } elsif ($i_span->text eq "Minifigs") {
         $minifigs = $i->text;

      } elsif ($i_span->text eq "Barcodes") {
         $barcodes = $i->text;

      } elsif ($i_span->text eq "LEGO item numbers") {
         $lego_item_number = $i->text;

      } elsif ($i_span->text eq "Year released") {
         my $i_href = $i->at('a');
         $year = $i_href->text;
         #printf("%s -> %s\n", $i_span->text, $i_href->text);

      } elsif ($i_span->text eq "RRP") {
         if ($i->text =~ /US\$(.*)/) {
            $cost = $1 * 100;
         }

      } elsif ($i_span->text eq "Age range") {
         if ($i->text =~ /(\d+)\s*\-\s*(\d+)/) {
            $min_age = $1;
            $max_age = $2;
         }
      } else {
         # printf("SPAN_TEXT: %s\n", $i_span->text);
      }
   }

   if ($subtheme) {
      print "ID: $set_to_download, SUBTHEME: $subtheme\n";
      my $query = "UPDATE sets SET subtheme=? WHERE id=? ";
      my $sth = $dbh->prepare($query);
      $sth->execute($subtheme, $set_to_download);
   }
}


open(FILES, "ls ../private/sets/*brickset* | ") || die("ERROR: Could not ls directory");
while(<FILES>) {
   if (/brickset-(.*)\.html/) {
      #print "$1\n";
      downloadBrickSet($1);
   }
}
close FILES;

