#!/usr/bin/perl

# I used this to build the original lego_colors table...I've since tweaked that table manuall so don't run this again
exit();

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

my $dbh = dbConnect();
my $url = "http://rebrickable.com/colors";
my $file = "../private/lego_colors.html";
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

my $query = "INSERT INTO lego_colors(id, name, rgb, number_parts, number_sets, min_year, max_year, lego_color, ldraw_color, bricklink_color, peeron_color) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
my $sth = $dbh->prepare($query);

# my $table_dom = HTML::TableExtract->new(keep_html => 1);
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
      my $id;
      my $name;
      my $rgb;
      my $num_parts;
      my $num_sets;
      my $min_year;
      my $max_year;
      my $lego_color;
      my $ldraw_color;
      my $bricklink_color;
      my $peeron_color;

      $id              = $row[1];
      $name            = $row[2] if ($row[2]);
      $rgb             = $row[3] if ($row[3]);
      $num_parts       = $row[4] if ($row[4]);
      $num_sets        = $row[5] if ($row[5]);
      $min_year        = $row[6] if ($row[6]);
      $max_year        = $row[7] if ($row[7]);
      $lego_color      = $row[8] if ($row[8]);
      $ldraw_color     = $row[9] if ($row[9]);
      $bricklink_color = $row[10] if ($row[10]);
      $peeron_color    = $row[11] if ($row[11]);

      if ($lego_color =~ /\{(\d+)\}/) {
         $lego_color = $1;
      }

      if ($ldraw_color =~ /\{(\d+)\}/) {
         $ldraw_color = $1;
      }

      if ($bricklink_color =~ /\{(\d+)\}/) {
         $bricklink_color = $1;
      }

      if ($peeron_color =~ /\{(\w+)\}/) {
         $peeron_color = $1;
      }

      next if ($id eq "ID");
      $sth->execute($id, $name, $rgb, $num_parts, $num_sets, $min_year, $max_year, $lego_color, $ldraw_color, $bricklink_color, $peeron_color);
   }
}

