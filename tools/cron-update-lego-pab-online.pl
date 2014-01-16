#!/usr/bin/perl

use strict;
use HTML::TableExtract;
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


my $filename = 'lego-online-pab.html';
my $url = 'http://customization.lego.com/en-US/pab/service/getBricks.aspx?sid=0.7470760336145759&st=5&sv=allbricks&pn=0&ps=2000&cat=US';
if (!(-e $filename)) {
    system "wget -O $filename '$url'";
}

my $html_string;
open(FH, $filename) || die("\nERROR: Could not open $filename\n\n");
while(<FH>) {
    chomp();
    $html_string .= $_;
}
close FH;

# my $te = HTML::TableExtract->new(keep_html => 1);
# foreach my $ts ($te->tables) {
#   print "Table (", join(',', $ts->coords), "):\n";
#   foreach my $row ($ts->rows) {
#      print join(',', @$row), "\n";
#   }
# }

my $te = HTML::TableExtract->new(depth => 0, count => 1, keep_html => 1);
$te->parse($html_string);

my $dbh = dbConnect();
my %price_by_ID;
foreach my $ts ($te->tables) {
    foreach my $row_ptr ($ts->rows) {
        my @row = @$row_ptr;
        my $id = 0;
        my $price = 0;

        if ($row[1] =~ /getBrick\((\d+)\)/) {
            $id = $1;
        }

        if ($row[2] =~ /(\d+)\.(\d\d)/) {
            $price = ($1 * 100) + $2;
        }

        if ($id) {
            $price_by_ID{$id} = $price;
        }
    }
}

# Nuke the old inventory
my $query = "DELETE FROM www_store_inventory WHERE store_id=1";
$dbh->do($query);

my $query = "INSERT INTO www_store_inventory (store_id, lego_id, qty, cond, price, url) VALUES (?,?,?,?,?,?)";
my $insert_sth = $dbh->prepare($query);

foreach my $id (keys %price_by_ID) {
    $insert_sth->execute(1, $id, 1000, 'new', $price_by_ID{$id}, "http://shop.lego.com/en-US/Pick-A-Brick-ByTheme?txbElementId=$id");
}

