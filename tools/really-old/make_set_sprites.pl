#!/usr/bin/perl

# Pack all the bitmaps into one larger file.
use strict;
use Image::Magick;
use POSIX;
use DBI;

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


my $dbh = dbConnect();
# my $query = "SELECT DISTINCT(id) FROM sets_inventory WHERE id='10144-1' ";
my $query = "SELECT DISTINCT(id) FROM sets_inventory";
my $sth = $dbh->prepare($query);
$sth->execute();

while (my @row = $sth->fetchrow_array()) {
   my $id = $row[0];

   my @images;
   my $query2 = "SELECT brick_id, bricks.img ".
                "FROM sets_inventory ".
                "INNER JOIN bricks ON brick_id = bricks.id ".
                "INNER JOIN lego_colors ON bricks.color = lego_colors.bricklink_color ".
                "WHERE sets_inventory.id='$id' ".
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
#print "SQL: $query2\n";
   next if (-e "/var/www/html/lego/sets/$id/parts.jpg");   
   print "SET: $id\n";

   my $found_one_image = 0;
   my $sth2 = $dbh->prepare($query2);
   $sth2->execute();
   while (my @inv_row = $sth2->fetchrow_array()) {
      my $brick_img = $inv_row[1];
      my $filename = "/var/www/lego/html" . $brick_img;
      if (-e $filename) {
         #push @images, "/var/www/lego/html" . $brick_img;
         push @images, $filename;
         $found_one_image = 1;
      }
   }

   if ($found_one_image) {
      my $im = new Image::Magick;
      die "Image::Magick->new failed" unless $im;

      for my $image (@images) {
          if ($id == "104-1") {
             print "IMG: $image\n";
          }
          $im->Read ($image);
      }
   
      my $num_images = scalar(@images);
      my $num_rows = ceil($num_images/10);
   
      if ($id == "104-1") {
         print "NUM_IMAGES: $num_images\n";
         print "NUM_ROWS: $num_rows\n";
      }

      # Each row should be 10 images wide so it matches up with set.php
      my $tile = "10x$num_rows";

      # Add eight pixels of space around each image.
      my $output = $im->Montage (tile => $tile, geometry=>'+1+1');
      $output->Write("/var/www/html/lego/sets/$id/parts.jpg");
   }
}

