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

sub downloadBrickLink($) {
   my $set_to_download = shift;

   #
   # Download the inventory data from bricklink
   #
   my $html_filename = "/var/www/lego/private/sets/$set_to_download.html";
   if (!(-e $html_filename)) {
      system "wget -O $html_filename http://www.bricklink.com/catalogItemInv.asp?S=$set_to_download";
   }
   
   my $html_string;
   open(FH, $html_filename) || die("\nERROR: Could not open $html_filename\n\n");
   while(<FH>) {
      chomp();
      $html_string .= $_;
   }
   close FH;
   
   my $query = "DELETE FROM sets_inventory WHERE id='$set_to_download'";
   $dbh->do($query);
   $query = "INSERT INTO bricks (id, description, img, price, type) VALUES (?,?,?,?,?) ".
            "ON DUPLICATE KEY UPDATE description=?, img=?, price=?, type=?";
   my $sth_bricks = $dbh->prepare($query);
   
   #
   # Now parse the HTML we downloaded...this contains the inventory list
   #
   my $table_dom = HTML::TableExtract->new(depth => 3, count => 1, keep_html => 1);
   $table_dom->parse($html_string);

   my $name;
   foreach my $ts ($table_dom->tables) {
      foreach my $row_ptr ($ts->rows) {
         my @row = @$row_ptr;
         if ($row[0] =~ /<B>(.*?)<\/B>/i) {
            $name = $1;
         }
      }
   }
   
   $query = "INSERT INTO sets (id, name) VALUES (?,?) ON DUPLICATE KEY UPDATE name=?";
   my $sth_sets = $dbh->prepare($query);
   $sth_sets->execute($set_to_download, $name, $name);
   
   $query = "INSERT INTO sets_inventory (id, brick_id, brick_quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE brick_quantity=?";
   $sth_sets = $dbh->prepare($query);
   
   my $table_dom = HTML::TableExtract->new(attribs => {class => 'ta'}, keep_html => 1);
   $table_dom->parse($html_string);
   foreach my $ts ($table_dom->tables) {
      foreach my $row_ptr ($ts->rows) {
         my @row = @$row_ptr;
   
         if ($row[0] =~ /Extra Items/i) {
            last;
         }
   
         my $img;
         my $type = "";
         my $color;
         if ($row[0] =~ /SRC='(.*?)'/i) {
            $img = $1;
            if ($img =~ /P\/(\w+)\//) {
               $color = $1;
               $type = "P";
            } elsif ($img =~ /M\/(\w+)/) {
               $type = "M";
            } elsif ($img =~ /G\/(\w+)/) {
               $type = "G";
            } else {
               print "IMG_URL ERROR: $img\n";
            }
         }
   
         my $qty;
         if ($row[1] =~ /(\d+)/) {
            $qty = $1;
         } else {
            next;
         }
   
         my $id;
         if ($row[2] =~ />(\w+)</) {
            $id = $1;
         }
   
         my $desc;
         if ($row[3] =~ /^<B>(.*?)\s*<\/B>/i) {
            $desc = $1;
         }
   
         my $price_html = "/var/www/lego/private/sets/$id-$color.html";
         if (!(-e $price_html)) {
            system "wget $ua -O $price_html 'http://www.bricklink.com/catalogPG.asp?$type=$id&colorID=$color'";
            # sleep 2;
         } else {
            print "DOWNLOADED: $price_html\n";
         }
   
         $color = 0 if (!$color);
         if ($img =~ /\.(\w+)$/) {
            my $suffix = $1; 
            my $output_file = "images/$type$id-$color.$suffix";
            if (!(-e $output_file)) {
               system "wget $ua -O $output_file $img";
            }
            $img = "/$output_file";
         }
   
         my $price;
         open(FH, $price_html);
         while(<FH>) {
            if (/>Avg Price:<\/TD><TD><B>US&nbsp;\$(.*?)<\/B>.*?$/) {
               $price = $1 * 100;
               last;
            }
         }
         close FH;
    
         #if ($id =~ /sw/) {
         #   print "ID: $id, QTY: $qty, IMG: $img_url, COLOR: $color, TYPE: $type\n\n";
         #}
   
         $sth_bricks->execute("$id-$color", $desc, $img, $price, $type, $desc, $img, $price, $type);
         $sth_sets->execute($set_to_download, "$id-$color", $qty, $qty);
      }
   
      last; # No need to look at the summary table
   }
}

#
# Download the brickset page 
#
sub downloadBrickSet($) {
   my $set_to_download = shift;

   # So far all of these have been .jpg
   my $img_url = "http://www.1000steine.com/brickset/images/$set_to_download.jpg"; 
   my $img_filename = "/var/www/lego/html/images/S$set_to_download.jpg"; 
   if (!(-e $img_filename) || !(-s $img_filename)) {
      system "wget -O $img_filename $img_url";
   } else {
      print "DOWNLOADED BRICKSET IMAGE: $set_to_download\n";
   }

   my $thumbnail_filename = "/var/www/lego/html/images/S$set_to_download-tn.jpg"; 
   if (!(-e $thumbnail_filename) || !(-s $thumbnail_filename)) {
      print "Creating thubmnail\n";
      my $t = new Image::Thumbnail(
         size       => 180,
         create     => 1,
         input      => $img_filename,
         outputpath => $thumbnail_filename,
      );
   }
   my $img_url = "/$img_filename";
   my $thumbnail_url = "/$thumbnail_filename";

 
   my $html_filename = "/var/www/lego/private/sets/brickset-$set_to_download.html";
   if (!(-e $html_filename) || !(-s $html_filename)) {
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
         $subtheme = $i->text;

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
      }
   }

   my $pieces;
   my $query = "SELECT SUM(brick_quantity) FROM sets_inventory WHERE id='$set_to_download'";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   my @row = $sth->fetchrow_array();
   my $pieces = $row[0];

   my $price_per_piece;
   if ($pieces) {
      $price_per_piece = $cost/$pieces;
   }

   my $query = "SELECT COUNT(brick_id) FROM sets_inventory WHERE id='$set_to_download'";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   my @row = $sth->fetchrow_array();
   my $brick_types = $row[0];

   $query = "INSERT INTO sets (id, price, year, min_age, max_age, pieces, theme_group, theme, subtheme, minifigs, price_per_piece, brick_types, barcodes, lego_item_number, img, `img-tn`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ".
            "ON DUPLICATE KEY UPDATE price=?, year=?, min_age=?, max_age=?, pieces=?, theme_group=?, theme=?, subtheme=?, minifigs=?, price_per_piece=?, brick_types=?, barcodes=?, lego_item_number=?, img=?, `img-tn`=?";
   my $sth = $dbh->prepare($query);
   $sth->execute($set_to_download, $cost, $year, $min_age, $max_age, $pieces, $theme_group, $theme, $subtheme, $minifigs, $price_per_piece, $brick_types, $barcodes, $lego_item_number, $img_url, $thumbnail_url,
                                   $cost, $year, $min_age, $max_age, $pieces, $theme_group, $theme, $subtheme, $minifigs, $price_per_piece, $brick_types, $barcodes, $lego_item_number, $img_url, $thumbnail_url)
      or die "Can't execute statement: $DBI::errstr";
;
}

#
# Parse command line arguments and make sure we have all the info we need
#
my $set_to_download = 0;
for (my $i = 0; $i <= $#ARGV; $i++) {
   if ($ARGV[$i] eq "-id") {
      $set_to_download = $ARGV[++$i];
   }
}

if (!$set_to_download) {
   die("\n\nERROR: You must specify which set to download via '-id 12345'\n\n");
}

if ($set_to_download !~ /-\d+$/) {
   $set_to_download .= "-1";
}

downloadBrickLink($set_to_download);
downloadBrickSet($set_to_download);
