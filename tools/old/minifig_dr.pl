#!/usr/bin/perl

# Pack all the bitmaps into one larger file.
use strict;
use Image::Magick;
use Image::Thumbnail 0.65;
use HTML::TableExtract;
use POSIX;
use DBI;

my $dbh = dbConnect();
my $ua = "--user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3'";
my $query_update_manual_filename = "UPDATE sets_manual SET filename=? WHERE id=? AND url=?";
my $sth_update_manual_filename = $dbh->prepare($query_update_manual_filename);


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

sub getColorFromDescription($$) {
   my $dbh = shift;
   my $desc = shift;

   my $query = "SELECT bricklink_color, bricklink_name ".
               "FROM  `lego_colors` ".
               "WHERE bricklink_name IS NOT NULL ".
               "ORDER BY CHAR_LENGTH( bricklink_name ) DESC ";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $color = $row[0];
      my $name = $row[1];
      if ($desc =~ /^$name/) {
         return $color;
      }
   }

   return 0;
}


sub processMiniFig($$$$) {
   my $dbh = shift;
   my $id = shift;
   my $core_id = shift;
   my $type = shift;

   #
   # Download the inventory data from bricklink
   #
   my $html_filename = "/var/www/lego/tools/minifigs/$id.html";
   if (!(-e $html_filename)) {
      if ($type eq "M") {
         print "\nwget -O $html_filename 'http://www.bricklink.com/catalogItemInv.asp?M=$core_id'\n";
         system "wget -O $html_filename 'http://www.bricklink.com/catalogItemInv.asp?M=$core_id'";
      } elsif ($type eq "P") {
         print "\nwget -O $html_filename 'http://www.bricklink.com/catalogItemInv.asp?P=$core_id'\n";
         system "wget -O $html_filename 'http://www.bricklink.com/catalogItemInv.asp?P=$core_id'";
      } else {
         die("ERROR: Type '$type' is not supported\n");
      }
   }

   my $html_string;
   open(FH, $html_filename) || die("\nERROR: Could not open $html_filename\n\n");
   while(<FH>) {
      chomp();
      $html_string .= $_;
   }
   close FH;

   my $query = "DELETE FROM sets_inventory WHERE id='$id'";
   $dbh->do($query);
   $query = "INSERT INTO bricks (id, description, img, type, color) VALUES (?,?,?,?,?) ".
            "ON DUPLICATE KEY UPDATE description=?, img=?, type=?, color=?";
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

   $query = "INSERT INTO sets_inventory (id, brick_type, brick_id, brick_quantity) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE brick_quantity=?";
   my $sth_sets_inventory = $dbh->prepare($query);

   my @minifigs_parts_with_inv;
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
            } elsif ($img =~ /S\/(\w+)/) {
               $type = "S";
            } elsif ($img =~ /noImage/) {
               $type = "P";
            } else {
               die("IMG_URL ERROR: $img\n");
            }
         }

         my $qty;
         if ($row[1] =~ /(\d+)/) {
            $qty = $1;
         } else {
            next;
         }

         my $brick_id;
         if ($row[2] =~ />(.*?)</) {
            $brick_id = $1;
         }

         my $has_sub_inventory = 0;
         if ($row[2] =~ />Inv</) {
            $has_sub_inventory = 1;
         }

         my $desc;
         if ($row[3] =~ /^<B>(.*?)\s*<\/B>/i) {
            $desc = $1;
         }

         $color = 0 if (!$color);

         if ($color == 0) {
            $color = getColorFromDescription($dbh, $desc);
         }

         if ($img =~ /\.(\w+)$/) {
            my $suffix = $1;
            my $output_dir = "/var/www/lego/html/parts/$brick_id/";
            if (!(-e $output_dir)) {
               system "mkdir $output_dir";
            }

            my $output_file = $output_dir . "$type$brick_id-$color.$suffix";
            if (!(-e $output_file)) {

               print "wget $ua -O $output_file '$img'\n";
               system "wget $ua -O $output_file '$img'";
            }
            $img = "/parts/$brick_id/$type$brick_id-$color.$suffix";
         }

         if ($brick_id) {
            if ($has_sub_inventory ) {
               push @minifigs_parts_with_inv, "$brick_id-$color";
            }

            print "SET: $id; BRICK: $brick_id-$color, QTY: $qty, TYPE: $type\n";
            $sth_bricks->execute("$brick_id-$color", $desc, $img, $type, $color, $desc, $img, $type, $color);
            $sth_sets_inventory->execute($id, $type, "$brick_id-$color", $qty, $qty);

         } else {
            print "\nSET: $id; BRICK: $brick_id-$color, QTY: $qty, IMG: $img,  TYPE: $type\n";
            print "ROW[0]: $row[0]\n";
            print "ROW[1]: $row[1]\n";
            print "ROW[2]: $row[2]\n";
            print "ROW[3]: $row[3]\n\n";
            exit();
         }
      }

      last; # No need to look at the summary table
   }

   foreach my $m (@minifigs_parts_with_inv) {
      my $core_id = $m;
      if ($m =~ /^(.*)\-\d+/) {
         $core_id = $1;
      }
      
      print "\nSUBINV: $m ($core_id)\n";
      processMiniFig($dbh, $m, $core_id, "P");
   }
}


my $update_set_query = "UPDATE sets SET img_type=? WHERE id=?";
my $update_set_sth = $dbh->prepare($update_set_query);

my $query = "SELECT DISTINCT(brick_id) FROM `sets_inventory` WHERE `brick_type` = 'M' AND brick_id='lor012-0' ";
my $query = "SELECT DISTINCT(brick_id) FROM `sets_inventory` WHERE `brick_type` = 'M' ORDER BY `sets_inventory`.`brick_id`  ASC";
my $sth = $dbh->prepare($query);
$sth->execute();
my $skip = 1;

while (my @row = $sth->fetchrow_array()) {
   my $id = $row[0];
   my $core_id = $id;
   if ($id =~ /^(.*)\-0/) {
      $core_id = $1;
   }

   print "MINIFIG: $id\n";

   my $img_query = "SELECT img FROM `bricks` WHERE id='$id'";
   my $img_sth = $dbh->prepare($img_query);
   $img_sth->execute();
   my @img_row = $img_sth->fetchrow_array();
   my $img = $img_row[0];
   if ($img =~ /^.*\/.*?\.(\w+)/) {
      # print "IMG: $img\n";
      my $suffix = $1;

      if (!(-e "/var/www/lego/html/sets/$id/")) {
         system "mkdir /var/www/lego/html/sets/$id/";
      }

      my $main_filename = "/var/www/lego/html/sets/$id/main.$suffix";
      if (!(-e $main_filename)) {
         print "cp /var/www/lego/html$img $main_filename\n";
         system "cp /var/www/lego/html$img $main_filename";
      }

      my $tn_filename = "/var/www/lego/html/sets/$id/tn.$suffix";
      if (!(-e $tn_filename)) {
         print "cp /var/www/lego/html$img $tn_filename\n";
         system "cp /var/www/lego/html$img $tn_filename";
      }

      $update_set_sth->execute($suffix, $id);
   }

   processMiniFig($dbh, $id, $core_id, "M");
}

