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

sub processSetInventory($$$$) {
   my $dbh = shift;
   my $id = shift;
   my $core_id = shift;
   my $set_or_minifig = shift;

   #
   # Download the inventory data from bricklink
   #
   my $html_filename = "/var/www/lego/tools/sets/$id.html";
   print "FILE: $html_filename\n";
   if (!(-e $html_filename)) {
      if ($set_or_minifig eq "S") {
         system "wget -O $html_filename 'http://www.bricklink.com/catalogItemInv.asp?S=$id'";
      } elsif ($set_or_minifig eq "M") {
         print "\nwget -O $html_filename 'http://www.bricklink.com/catalogItemInv.asp?M=$core_id'\n";
         system "wget -O $html_filename 'http://www.bricklink.com/catalogItemInv.asp?M=$core_id'";
      }
   }

   my $debug = 0;
   if ($html_filename eq "/var/www/lego/tools/sets/lor012-0.html") {
      $debug = 1;
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

   if ($debug) {
     # print "HTML: $html_string\n";
   }

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
   $sth_sets->execute($id, $name, $name);

   $query = "INSERT INTO sets_inventory (id, brick_type, brick_id, brick_quantity) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE brick_quantity=?";
   my $sth_sets_inventory = $dbh->prepare($query);

   my @minifigs;
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
            } elsif ($img =~ /noImage\.gif/) {
               $type = "P";
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

         my $brick_id;
         if ($row[2] =~ />(.*?)</) {
            $brick_id = $1;
         }

         my $desc;
         if ($row[3] =~ /^<B>(.*?)\s*<\/B>/i) {
            $desc = $1;
         }

         $color = 0 if (!$color);
         if ($type ne "S" && $img =~ /\.(\w+)$/) {
            my $suffix = $1;
            my $output_dir = "/var/www/lego/html/parts/$brick_id/";
            if (!(-e $output_dir)) {
               system "mkdir $output_dir";
            }

            if ($color == 0) {
               $color = getColorFromDescription($dbh, $desc);
            }

            my $output_file = $output_dir . "$type$brick_id-$color.$suffix";
            if (!(-e $output_file) || !(-s $output_file)) {

               print "wget $ua -O $output_file '$img'\n";
               system "wget $ua -O $output_file '$img'";

               # If this is true there is no image for this part
               if (!(-s $output_file)) {

                  # Long story on why this is 1 if there isn't an image...
                  if ($color == 1) {
                     $color = getColorFromDescription($dbh, $desc);
                  }

                  print "wget $ua -O $output_file 'http://www.bricklink.com/images/noImage.gif'\n";
                  system "wget $ua -O $output_file 'http://www.bricklink.com/images/noImage.gif'";
               }
            }
            $img = "/parts/$brick_id/$type$brick_id-$color.$suffix";
         }

         if ($brick_id) {

            # Co-pack of two or more sets under one set id :(
            if ($type eq "S") {
               print "SET: $id; CO-PACK: $brick_id, QTY: $qty\n";
               $sth_sets_inventory->execute($id, $type, $brick_id, $qty, $qty);

            # "Minifig" in the set inventory
            } elsif ($type eq "M") {
               print "SET: $id; MINIFIG: $brick_id-$color, QTY: $qty, TYPE: $type\n";
               $sth_bricks->execute("$brick_id-$color", $desc, $img, $type, $color, $desc, $img, $type, $color);
               $sth_sets_inventory->execute($id, $type, "$brick_id-$color", $qty, $qty);

               push @minifigs, $brick_id;

            # Normal "Part" in the set inventory
            } else { 
               print "SET: $id; BRICK: $brick_id-$color, COLOR: $color, QTY: $qty, TYPE: $type\n";
               $sth_bricks->execute("$brick_id-$color", $desc, $img, $type, $color, $desc, $img, $type, $color);
               $sth_sets_inventory->execute($id, $type, "$brick_id-$color", $qty, $qty);
            }

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

   foreach my $m (@minifigs) {
      processSetInventory($dbh, $m . "-0", $m, "M");
   }
}

sub processSetGeneralData($$$) {
   my $dbh = shift;
   my $id = shift;
   my $core_id = shift;

   # So far all of these have been .jpg
#   my $img_url = "http://www.1000steine.com/brickset/images/$id.jpg";
#   my $img_filename = "/var/www/lego/html/sets/$id/main.jpg";
#   if (!(-e $img_filename) || !(-s $img_filename)) {
#      system "wget -O $img_filename '$img_url'";
#   } else {
#      print "BRICKSET IMAGE CACHED: $id\n";
#   }

   my $html_filename = "/var/www/lego/toold/sets/brickset-$id.html";
   if (!(-e $html_filename) || !(-s $html_filename)) {
      system "wget -O $html_filename 'http://www.brickset.com/detail/?set=$id'";
   } else {
      print "BRICKSET PAGE CACHED: $id $html_filename\n";
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

   my $pieces;
   my $query = "SELECT SUM(brick_quantity) FROM sets_inventory WHERE id='$id'";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   my @row = $sth->fetchrow_array();
   my $pieces = $row[0];

   my $price_per_piece;
   if ($pieces) {
      $price_per_piece = $cost/$pieces;
   }

   my $query = "SELECT COUNT(brick_id) FROM sets_inventory WHERE id='$id'";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   my @row = $sth->fetchrow_array();
   my $brick_types = $row[0];

   $query = "INSERT INTO sets (id, price, year, min_age, max_age, pieces, theme_group, theme, subtheme, minifigs, price_per_piece, brick_types, barcodes, lego_item_number) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?) ".
            "ON DUPLICATE KEY UPDATE price=?, year=?, min_age=?, max_age=?, pieces=?, theme_group=?, theme=?, subtheme=?, minifigs=?, price_per_piece=?, brick_types=?, barcodes=?, lego_item_number=?";
   my $sth = $dbh->prepare($query);
   $sth->execute($id, $cost, $year, $min_age, $max_age, $pieces, $theme_group, $theme, $subtheme, $minifigs, $price_per_piece, $brick_types, $barcodes, $lego_item_number,
                      $cost, $year, $min_age, $max_age, $pieces, $theme_group, $theme, $subtheme, $minifigs, $price_per_piece, $brick_types, $barcodes, $lego_item_number)
      or die "Can't execute statement: $DBI::errstr";

}

sub processSetManuals($$$) {
   my $dbh = shift;
   my $id = shift;
   my $core_id = shift;

   # Process the json file from lego that tells what manuals belong to this set
   my $original_json_filename = "/var/www/lego/tools/manuals/lego-$core_id.json";
   my $json_filename = "/var/www/lego/html/sets/$id/manual.json";

   # We've downloaded it but it is in the wrong place
   if (-e $original_json_filename) {
      system "mv /var/www/lego/tools/manuals/lego-$core_id.json /var/www/lego/html/sets/$id/manual.json";

   # We haven't downloaded it
   } elsif (!(-e $original_json_filename) && !(-e $json_filename)) {
      my $url = "http://service.lego.com/Views/Service/Pages/BIService.ashx/GetCompletionListHtml?prefixText=$core_id&fromIdx=0";
      # TODO: Look in fix_crap.php for 'Used once to parse the json files about PDF manuals and put the data from the jsons in the sets_manual table'
      system "wget $ua -O $json_filename '$url'";
      print STDERR "HEY...you need to implement the code that parses the json manual info\n";
      exit();
   }

   # ==============================================================
   # Set the filename for the manual in the sets_manual table and
   # and move it to the correct directory
   # ==============================================================
#   my $query_update_manual_filename = "UPDATE sets_manual SET filename=? WHERE id=? AND url=?";
#   my $sth_update_manual_filename = $dbh->prepare($query_update_manual_filename);
#
#   my $query_sets_manual = "SELECT url FROM sets_manual WHERE id='$id'";
#   my $sth_sets_manual = $dbh->prepare($query_sets_manual);
#   $sth_sets_manual->execute();
#
#   while (my @row_sets_manual = $sth_sets_manual->fetchrow_array()) {
#      my $url = $row_sets_manual[0];
#      #print "URL: $url\n";
#      if ($url =~ /^.*\/(.*?\.pdf)/) {
#         my $filename = $1;
#         print "PDF: $filename\n";
#         $sth_update_manual_filename->execute($filename, $id, $url);
#         my $old_filename = "/var/www/lego/tools/manuals/$filename";
#         my $new_filename = "/var/www/lego/html/sets/$id/$filename";
#         if (-e $old_filename && !(-e $new_filename)) {
#            system "mv $old_filename $new_filename";
#         }
#      }
#   }

   # ==============================================================
   # Download all of the manuals
   # ==============================================================
   my $query_sets_manual = "SELECT url, filename FROM sets_manual WHERE id='$id'";
   my $sth_sets_manual = $dbh->prepare($query_sets_manual);
   $sth_sets_manual->execute();
   while (my @row_sets_manual = $sth_sets_manual->fetchrow_array()) {
      my $url = $row_sets_manual[0];
      my $filename = "/var/www/lego/html/sets/$id/" . $row_sets_manual[1];
      if (!(-e $filename)) {
         print "wget $url -O $filename\n";
         system "wget $url -O $filename";
      }

      if (-e $filename) {
         if ($filename =~ /^(.*).pdf/) {
            my $filename_minus_pdf = $1;
            my $first_jpg = $filename_minus_pdf . "-1.jpg";
            if (!(-e $first_jpg)) {
               print "convert $filename $filename_minus_pdf.jpg\n";
               system "convert $filename $filename_minus_pdf.jpg";
            } else {
               print "ALREADY CONVERTED: $id\n";
            }
         }
      }
   }

}


sub buildSetPartsSprite($$$) {
   my $dbh = shift;
   my $id = shift;
   my $core_id = shift;

   my $debug = 0;
   if ($id == "7142-1") {
      print "\n\nBuilding sprite for $id\n";
      $debug = 1;
   }

   # ==============================================================
   # Create a sprite for all of the parts images for this set
   # ==============================================================
   #if (!(-e "/var/www/lego/html/sets/$id/parts.jpg")) {
      my @images;
      my $query_set_parts = "SELECT brick_id, bricks.img ".
                   "FROM sets_inventory ".
                   "INNER JOIN bricks ON brick_id = bricks.id ".
                   "INNER JOIN lego_colors ON bricks.color = lego_colors.bricklink_color ".
                   "WHERE sets_inventory.id='$id' AND brick_type='P' ".
            "ORDER BY CASE ".
            "WHEN color_group='Black' THEN 1 ".
            "WHEN color_group='Red' THEN 2 ".
            "WHEN color_group='Blue' THEN 3 ".
            "WHEN color_group='Gray' THEN 4 ".
            "WHEN color_group='Brown' THEN 5 ".
            "WHEN color_group='Yellow' THEN 6 ".
            "WHEN color_group='Green' THEN 7 ".
            "WHEN color_group='White' THEN 8 ".
            "WHEN color_group='Orange' THEN 9 ".
            "WHEN color_group='Purple' THEN 10 ".
            "ELSE 99 ".
            "END ASC, color ASC , bricks.part_type, bricks.dimensions, bricks.id";

      if ($debug) {
         print "SQL: $query_set_parts\n";
      }
   
      my $found_one_image = 0;
      my $sth_set_parts = $dbh->prepare($query_set_parts);
      $sth_set_parts->execute();
      while (my @row_set_parts = $sth_set_parts->fetchrow_array()) {
         my $brick_img = $row_set_parts[1];
         my $filename = "/var/www/lego/html" . $brick_img;

         if (!(-e $filename)) {
            print STDERR "ERROR: $filename is missing\n";
            system "cp /var/www/lego/images/noImage.gif $filename\n";
         }

         if (!(-s $filename)) {
            print STDERR "ERROR: $filename is size zero\n";
            system "cp /var/www/lego/images/noImage.gif $filename\n";
         }

         #push @images, "/var/www/lego/html" . $brick_img;
         push @images, $filename;
         $found_one_image = 1;
      }
   
      if ($found_one_image) {
         my $im = new Image::Magick;
         die "Image::Magick->new failed" unless $im;
   
         for my $image (@images) {
             if ($debug) {
                print "IMG: $image\n";
             }
             $im->Read ($image);
         }
      
         my $num_images = scalar(@images);
         my $num_rows = ceil($num_images/10);
      
         if ($debug) {
            print "NUM_IMAGES: $num_images\n";
            print "NUM_ROWS: $num_rows\n";
         }
   
         # Each row should be 10 images wide so it matches up with set.php
         my $tile = "10x$num_rows";
   

         if (!(-e "/var/www/lego/html/sets/$id/")) {
            system "mkdir /var/www/lego/html/sets/$id/";
         }

         # Add eight pixels of space around each image.
         my $output = $im->Montage (tile => $tile, geometry=>'+1+1');
         $output->Write("/var/www/lego/html/sets/$id/parts.jpg");
      }
   #}
}

sub processSetImage($$$$) {
   my $dbh = shift;
   my $id = shift;
   my $core_id = shift;
   my $img_type = shift;

   # ==============================================================
   # Create the thumbnail for the set image
   # ==============================================================
   my $img_name = "/var/www/lego/html/sets/$id/main.$img_type";
   my $img_size = -s $img_name;
   my $build_thumbnail = 0;
   #if (!(-e $img_name) || !(-s $img_name)) {
   if (!(-e $img_name) || (-s $img_name) < 20000) {
      print STDERR "NOTE: IMG $img_name does not exist or is empty or is very small\n";
      my $query = "UPDATE sets SET img_type=NULL WHERE id='$id'";
      $dbh->do($query);

      $img_type = "jpg";
      $img_name = "/var/www/lego/html/sets/$id/main.$img_type";
      system "wget http://www.1000steine.com/brickset/images/$id.jpg -O $img_name";

      if (!(-e $img_name) || !(-s $img_name)) {
         system "wget http://www.bricklink.com/IL/$id.jpg -O $img_name";
      }

      if (!(-e $img_name) || !(-s $img_name)) {
         system "wget http://www.bricklink.com/OL/$id.jpg -O $img_name";
      }

      if (!(-e $img_name) || !(-s $img_name)) {
         system "wget http://www.bricklink.com/SL/$id.jpg -O $img_name";
      }

       
      if (!(-e $img_name) || !(-s $img_name)) {
         $img_type = "gif";
         $img_name = "/var/www/lego/html/sets/$id/main.$img_type";
         system "wget http://www.bricklink.com/IL/$id.gif -O $img_name";

         if (!(-e $img_name) || !(-s $img_name)) {
            system "wget http://www.bricklink.com/OL/$id.gif -O $img_name";
         }

         if (!(-e $img_name) || !(-s $img_name)) {
            system "wget http://www.bricklink.com/SL/$id.gif -O $img_name";
         }

         if (!(-e $img_name) || !(-s $img_name)) {
            system "wget http://img.bricklink.com/S/$id.gif -O $img_name";
         }
      }


      if (-e $img_name && -s $img_name) {
         my $query = "UPDATE sets SET img_type='$img_type' WHERE id='$id'";
         $dbh->do($query);
         $build_thumbnail = 1;
      }
   }


   if (-s $img_name) {
      my $img_size = -s $img_name;
      my $tn_name = "/var/www/lego/html/sets/$id/tn.$img_type";
      if (!(-e $tn_name) || $build_thumbnail) {
         if ($img_size > 10000) {
            print "THUMBNAIL: $img_name -> $tn_name\n";
            my $t = new Image::Thumbnail(
               size       => 180,
               create     => 1,
               input      => $img_name,
               outputpath => $tn_name,
            );

         } else {
            print "THUMBNAIL COPY: $img_name -> $tn_name\n";
            system "cp $img_name $tn_name";
         }

      }
   }
}

sub assignSetType($) {
   my $dbh = shift;
   my $query = "UPDATE sets SET `type`='brickpack' ".
               "WHERE pieces IS NULL OR `name` LIKE '%bricks%' OR `name` LIKE '%pack of%' OR `name` LIKE '% bulk %' OR `name` LIKE '%superset%' OR `name` LIKE '%brick pack%' OR `name` LIKE '% bucket%' OR name LIKE '% plates %' OR name LIKE '%building set%' OR name LIKE '% collection%' OR name LIKE '% tiles' OR name LIKE '% plates' OR name LIKE '% beams' OR name LIKE '% roof tile %' OR name LIKE '% set %' OR name LIKE '% set' OR name LIKE '% tub %' OR name LIKE '% tub'";
   $dbh->do($query);

   $query = "UPDATE sets SET type='minifig' WHERE id IN (SELECT DISTINCT(brick_id)  FROM `sets_inventory` WHERE `brick_type`='M')";
   $dbh->do($query);

   $query = "UPDATE sets SET type='set-pack' WHERE id IN (SELECT DISTINCT(id)  FROM `sets_inventory` WHERE `brick_type` = 'S')";
   $dbh->do($query);
}

# Set the pieces column
my $query = "UPDATE sets SET pieces = (SELECT SUM(brick_quantity)  FROM `sets_inventory` WHERE `id`=sets.id AND brick_type='P')";
$dbh->do($query);

# Set the minfigs column
my $query = "UPDATE sets SET minifigs = (SELECT SUM(brick_quantity)  FROM `sets_inventory` WHERE `id`=sets.id AND brick_type='M')";
$dbh->do($query);

#my $query = "SELECT id FROM  `sets` WHERE  `name` IS NULL ";
#my $sth = $dbh->prepare($query);
#$sth->execute();
#while (my @row = $sth->fetchrow_array()) {
#   my $id = $row[0];
#   print "rm -rf sets/$id.html\n";
#   system "rm -rf sets/$id.html";
#}

my $query = "SELECT sets.id, img_type FROM sets WHERE id='9473-1' ";
my $query = "SELECT sets.id, img_type FROM sets ORDER BY sets.id ";
my $query = "SELECT sets.id, img_type FROM sets WHERE type != 'minifig' ORDER BY sets.id "; # Use this for SetManuals

#print "SQL: $query\n";

my $sth = $dbh->prepare($query);
$sth->execute();
my $skip = 1;

while (my @row = $sth->fetchrow_array()) {
   my $id = $row[0];
   my $img_type = $row[1];

#   if ($id eq "1381-1") {
#     $skip = 0;
#   }
#   next if ($skip);

   my $core_id = $id;
   if ($id =~ /^(.*)\-\d+/) {
      $core_id = $1;
   }

#   processSetInventory($dbh, $id, $core_id, "S");
   # processSetGeneralData($dbh, $id, $core_id);
#   buildSetPartsSprite($dbh, $id, $core_id);
   processSetImage($dbh, $id, $core_id, $img_type);

   # When you run this one exclude the minifig sets
#   processSetManuals($dbh, $id, $core_id);
}

assignSetType($dbh);

