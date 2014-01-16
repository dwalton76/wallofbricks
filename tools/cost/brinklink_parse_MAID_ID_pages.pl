#!/usr/bin/perl

use strict;
use HTML::TableExtract;
use DBI;

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


sub filename2TableDom($$$$) {
   (my $html_filename, my $depth, my $count, my $keep_html) = @_;

   my $html_string;
   open(FH, $html_filename) || die("\nERROR: Could not open $html_filename\n\n");
   while(<FH>) {
      chomp();
      $html_string .= $_;
   }
   close FH;

   my $table_dom = HTML::TableExtract->new(depth => $depth, count => $count, keep_html => $keep_html);
   $table_dom->parse($html_string);

   return $table_dom;
}

sub crawlBrick($$$) {
   (my $id, my $color, my $qty) = @_;

   # 
   # Every bricklink part # (that has a color...so minifigs no included) has a different part number in the store database :(
   # Download the catalog page for the normal part #, parse it to get the store part #.
   # 
   my $html_filename = "files/$id-MAIN_ID.html";
#   my $html_string;
#   open(FH, $html_filename) || die("\nERROR: Could not open $html_filename\n\n");
#   while(<FH>) {
#      chomp();
#      $html_string .= $_;
#   }
#   close FH;
#   
#   #
#   # Now parse the HTML we downloaded...this contains the inventory list
#   #
#   my $table_dom = HTML::TableExtract->new(depth => 4, count => 3, keep_html => 1);
#   $table_dom->parse($html_string);

   my $table_dom = filename2TableDom($html_filename, 4, 3, 1);

   my $brick_store_id = 0;
   foreach my $ts ($table_dom->tables) {
      foreach my $row ($ts->rows) {
         foreach my $td (@$row) {
            if ($td =~ /ID=(\d+)\&colorID=$color/) {
               $brick_store_id = $1;
               last;
            }
         }
         last if $brick_store_id;
      }
      last if $brick_store_id;
   }
   print "BRICK_STORE_ID: $brick_store_id\n";
   
   #
   # Now we know the store part # for this brick.  Download the pages that show who
   # is selling this brick in the color we are looking for.
   #
   my $ua = "--user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3'";
   my $url = sprintf("http://www.bricklink.com/search.asp?pg=%d&colorID=%s&qMin=%d&itemID=%s&sellerLoc=R&viewFrom=sf&regionID=3&sz=500&searchSort=P", 1, $color, $qty, $brick_store_id);
   my $filename = "files/$brick_store_id-$color-$qty-page-1.html";
   if (!(-e $filename)) {
      print "wget $ua -O $filename \"$url\"\n\n";
      system "wget $ua -O $filename \"$url\"";
   }
   
   my $html_string = "";
   open(FH, $filename) || die("\nERROR: Could not open $filename\n\n");
   while(<FH>) {
      chomp();
      $html_string .= $_;
   }
   close FH;
   
   
   #
   # Parse the first page to figure out how many total pages there are
   #
   my $table_dom = HTML::TableExtract->new(depth => 3, count => 1, keep_html => 0);
   $table_dom->parse($html_string);
   
   my $last_page = 1;
   foreach my $ts ($table_dom->tables) {
      foreach my $row ($ts->rows) {
         foreach my $td (@$row) {
            if ($td =~ /Page 1 of (\d+)/) {
               $last_page = $1;
               last;
            }
         }
         last if $last_page;
      }
      last if $last_page;
   }
   print "LASTPAGE: $last_page\n";
   
   #
   # Download all of the pages of results
   #
   for (my $i = 2; $i <= $last_page; $i++) {
      my $url = sprintf("http://www.bricklink.com/search.asp?pg=%d&colorID=%s&qMin=%d&itemID=%s&sellerLoc=R&viewFrom=sf&regionID=3&sz=500&searchSort=P", $i, $color, $qty, $brick_store_id);
      my $filename = "files/$brick_store_id-$color-$qty-page-$i.html";
      if (!(-e $filename)) {
         print "wget $ua -O $filename \"$url\"\n\n";
         system "wget $ua -O $filename \"$url\"";
      }
   }
   
   #
   # Now parse all of the pages of results
   #
   for (my $i = 1; $i <= $last_page; $i++) {
      my $filename = "files/$brick_store_id-$color-$qty-page-$i.html";
      my $html_string = "";
      open(FH, $filename) || die("\nERROR: Could not open $filename\n\n");
      while(<FH>) {
         chomp();
         $html_string .= $_;
      }
      close FH;
   
      my $table_dom = HTML::TableExtract->new(depth => 4, count => 2, keep_html => 1);
      $table_dom->parse($html_string);
   
      # TODO: This page isn't parsing for 50943
      my $query = "INSERT IGNORE INTO bricklink_inventory (id, brick_store_id, store, quantity, price, min_buy, new_or_used) VALUES (?,?,?,?,?,?,?) ";
      my $sth = $dbh->prepare($query);
   
      foreach my $ts ($table_dom->tables) {
         foreach my $row ($ts->rows) {
            my $new_or_used;
            my $min_buy = 0;
            my $qty = 0;
            my $currency = 0;
            my $price = 0;
            my $store = "";
   
            foreach my $td (@$row) {
               #print "TD: $td\n";
               if ($td =~ />New</) {
                  $new_or_used = "new";
               } elsif ($td =~ />Used</) {
                  $new_or_used = "used";
               }
   
               if ($td =~ /Min Buy: (\w+)/) {
                  $min_buy = $1;
                  if ($min_buy eq "None") {
                     $min_buy = 0;
                  }
               }
   
               if ($td =~ /Qty:\&nbsp;<B>(.*?)</) {
                  $qty = $1;
               }
   
               if ($td =~ /Each:&nbsp;~<B>(\w+) \$(\d+).(\d+)</) {
                  $currency = $1;
                  $price = ($2 * 100) + $3;
               }
   
               if (!$store && $td =~ /<A HREF=\"\/store.asp\?p=.*?\&itemID=\d+">(.*?)</) {
                  $store = $1;
               }
   
               # print "$td\n";
            }
   
            if ($store && $qty) {
               printf("STORE: %s, QTY: %s, PRICE: %s, MIN_BUY: %s, NEW/USED: %s\n", $store, $qty, $price, $min_buy, $new_or_used);
               $sth->execute($id . '-' . $color, $brick_store_id, $store, $qty, $price, $min_buy, $new_or_used);
            } else {
               printf("STORE: %s, QTY: %s, PRICE: %s, MIN_BUY: %s, NEW/USED: %s\n", $store, $qty, $price, $min_buy, $new_or_used);
               print "HMMMM, store was blank\n";
            }
            # print "\n\n\n";
         }
      }
   }
}

sub crawlMiniFig($$) {
   (my $id, my $qty) = @_;

   #
   # Now we know the store part # for this brick.  Download the pages that show who
   # is selling this brick in the color we are looking for.
   #
   my $ua = "--user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3'";
   my $url = sprintf("http://www.bricklink.com/search.asp?pg=%d&q=%s&qMin=%d&sellerLoc=R&viewFrom=sf&regionID=3&sz=10&searchSort=P", 1, $id, $qty);
   
   my $filename = "files/$id-$qty-page-1.html";
   if (!(-e $filename)) {
      print "wget $ua -O $filename \"$url\"\n\n";
      system "wget $ua -O $filename \"$url\"";
   }
   my $table_dom = filename2TableDom($filename, 3, 1, 0);
   
   my $last_page = 0;
   foreach my $ts ($table_dom->tables) {
      foreach my $row ($ts->rows) {
         foreach my $td (@$row) {
            if ($td =~ /Page 1 of (\d+)/) {
               $last_page = $1;
               last;
            }
         }
         last if $last_page;
      }
      last if $last_page;
   }
   print "LASTPAGE: $last_page\n";
   
   #
   # Download all of the pages of results
   #
   for (my $i = 2; $i <= $last_page; $i++) {
      my $url = sprintf("http://www.bricklink.com/search.asp?pg=%d&q=%s&qMin=%d&sellerLoc=R&viewFrom=sf&regionID=3&sz=10&searchSort=P", $i, $id, $qty);
      my $filename = "files/$id-$qty-page-$i.html";
      if (!(-e $filename)) {
         print "wget $ua -O $filename \"$url\"\n\n";
         system "wget $ua -O $filename \"$url\"";
      }
   }
   
   #
   # Now parse all of the pages of results
   #
   for (my $i = 1; $i <= $last_page; $i++) {
      my $filename = "files/$id-$qty-page-$i.html";
      my $html_string = "";
      open(FH, $filename) || die("\nERROR: Could not open $filename\n\n");
      while(<FH>) {
         chomp();
         $html_string .= $_;
      }
      close FH;
   
      my $table_dom = HTML::TableExtract->new(depth => 4, count => 2, keep_html => 1);
      $table_dom->parse($html_string);
   
      my $query = "INSERT IGNORE INTO bricklink_inventory (id, brick_store_id, store, quantity, price, min_buy, new_or_used) VALUES (?,?,?,?,?,?,?) ";
      my $sth = $dbh->prepare($query);
   
      foreach my $ts ($table_dom->tables) {
         foreach my $row ($ts->rows) {
            my $new_or_used;
            my $min_buy = 0;
            my $qty = 0;
            my $currency = 0;
            my $price = 0;
            my $store = "";
   
            foreach my $td (@$row) {
               if ($td =~ />New</) {
                  $new_or_used = "new";
               } elsif ($td =~ />Used</) {
                  $new_or_used = "used";
               }
   
               if ($td =~ /Min Buy: (\w+)/) {
                  $min_buy = $1;
                  if ($min_buy eq "None") {
                     $min_buy = 0;
                  }
               }
   
               if ($td =~ /Qty:\&nbsp;<B>(.*?)</) {
                  $qty = $1;
               }
   
               if ($td =~ /Each:&nbsp;~<B>(\w+) \$(\d+).(\d+)</) {
                  $currency = $1;
                  $price = ($2 * 100) + $3;
               }
   
               if (!$store && $td =~ /<A HREF=\"\/store.asp\?p=.*?\&itemID=\d+">(.*?)</) {
                  $store = $1;
               }
   
               # print "$td\n";
            }
   
            if ($store && $qty) {
               printf("STORE: %s, QTY: %s, PRICE: %s, MIN_BUY: %s, NEW/USED: %s\n", $store, $qty, $price, $min_buy, $new_or_used);
               $sth->execute($id, $id, $store, $qty, $price, $min_buy, $new_or_used);
            } else {
               printf("STORE: %s, QTY: %s, PRICE: %s, MIN_BUY: %s, NEW/USED: %s\n", $store, $qty, $price, $min_buy, $new_or_used);
               print "HMMMM, store was blank\n";
            }
            # print "\n\n\n";
         }
      }
   }
}

my $id = 0;
my $color = 0;
my $qty = 0;

for (my $i = 0; $i <= $#ARGV; $i++) {
   if ($ARGV[$i] eq "-id") {
      $id = $ARGV[++$i];
   } elsif ($ARGV[$i] eq "-c" || $ARGV[$i] eq "-color") {
      $color = $ARGV[++$i];
   } elsif ($ARGV[$i] eq "-q" || $ARGV[$i] eq "-qty") {
      $qty = $ARGV[++$i];
   }
}

if (!$id || !$qty) {
   print "ERROR: -id -c and -q must all be passed\n";
}

if ($color) {
   crawlBrick($id, $color, $qty);
} else {
   crawlMiniFig($id, $qty);
}

#exit();
#my $table_dom = HTML::TableExtract->new(keep_html => 0);
#$table_dom->parse($html_string);
#
#foreach my $ts ($table_dom->tables) {
#   print "Table found at ", join(',', $ts->coords), ":\n";
#   foreach my $row ($ts->rows) {
#      print "   ", join(',', @$row), "\n";
#   }
#}
