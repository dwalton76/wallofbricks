#!/usr/bin/perl

use strict;
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

sub setUsedinsetMinyearMaxyear($) {
   my $dbh = shift;
   my $query_update_brick = "UPDATE bricks SET dimensions=?, part_type=?, used_in_sets=?, min_year=?, max_year=? WHERE `id`=?";
   my $sth_update_brick = $dbh->prepare($query_update_brick);

   my $query = "SELECT id, type, description FROM bricks ORDER BY id ASC";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $id = $row[0];
      my $type = $row[1];
      my $desc = $row[2];
      print "BRICK: $id\n";

      my $part_type;
      if ($desc =~ /technic/i) {
         $part_type = "technic";

      } elsif ($type =~ /slope/i) {
         $part_type = "slope";

      } elsif ($type =~ /plate/i) {
         if ($desc =~ /tile/i) {
            $part_type = "tile";
         } else {
            $part_type = "plate";
         }

      } elsif ($type =~ /tile/i) {
         $part_type = "tile";

      } elsif ($type=~ /brick/i) {
         $part_type = "brick";

      } elsif ($desc =~ /slope/i) {
         $part_type = "slope";

      } elsif ($desc =~ /plate/i) {
         $part_type = "plate";

      } elsif ($desc =~ /tile/i) {
         $part_type = "tile";

      } elsif ($desc =~ /brick/i) {
         $part_type = "brick";

      } elsif ($desc =~ /figure/i || $type =~ /figure/i ||
               $desc =~ /minifig/i || $type =~ /minifig/i) {
         $part_type = "minifig";
      }

      my $dimensions;
      if ($desc =~ /(\d+\s*x\s*\d+\s*-\s*\d+\s*x\s*\d+)/i) {
         $dimensions = $1;
         $dimensions =~ s/ //g;
      } elsif ($desc =~ /(\d+\s*x\s*\d+\s*x\s*\d+)/i) {
         $dimensions = $1;
         $dimensions =~ s/ //g;
      } elsif ($desc =~ /(\d+\s*x\s*\d+)/i) {
         $dimensions = $1;
         $dimensions =~ s/ //g;
      }

      # Do a query to see how many sets this part has been used in and the min/max year it was used
      my $used_in_sets;
      my $min_year;
      my $max_year;
      my $set_type;
      my $pop_query = "SELECT COUNT(sets_inventory.id), MIN(year), MAX(year) ".
                      "FROM `sets_inventory` ".
                      "INNER JOIN sets ON sets.id = sets_inventory.id ".
                      "WHERE brick_id='$id' ";

      my $pop_sth = $dbh->prepare($pop_query);
      $pop_sth->execute();
      my @pop_row = $pop_sth->fetchrow_array();
      $used_in_sets = $pop_row[0];
      $min_year = $pop_row[1];
      $max_year = $pop_row[2];

      # For parts that are part of a minifig we have to lookup all the sets that the minifig is in to get the data we want.
      # FYI this takes HOURS to run so leave it commented out after you've run it once
#      if (!$min_year && $set_type eq "minifig") {
#         # A list of all the sets that use the minifig that use this part.
#         # I don't use this for anything but figured I should save it once I had it figured out :)
#         #$query = "SELECT sets_inventory.id FROM sets_inventory WHERE brick_id IN (SELECT sets_inventory.id ".
#         #         "FROM `sets_inventory` ".
#         #         "WHERE brick_id='970c05-11' AND brick_type != 'S' )";
#
#         $pop_query = "SELECT COUNT(sets_inventory.id), MIN(year), MAX(year) ".
#                      "FROM sets_inventory ".
#                      "INNER JOIN sets ON sets.id = sets_inventory.id ".
#                      "WHERE brick_id IN (SELECT sets_inventory.id FROM `sets_inventory` WHERE brick_id='$id' AND brick_type != 'S' ) ";
#         my $pop_sth = $dbh->prepare($pop_query);
#         $pop_sth->execute();
#         my @pop_row = $pop_sth->fetchrow_array();
#         $used_in_sets = $pop_row[0];
#         $min_year = $pop_row[1];
#         $max_year = $pop_row[2];
#      }

      $sth_update_brick->execute($dimensions, $part_type, $used_in_sets, $min_year, $max_year, $id);
   }
}

sub downloadPartImages($) {
   my $dbh = shift;

   my $query = "SELECT design_id, id FROM `bricks` ORDER BY design_id, id ASC ";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $design_id = $row[0];
      my $id = $row[1];

      # http://cache.lego.com/media/bricks/5/1/300426.jpg small
      # http://cache.lego.com/media/bricks/5/2/300426.jpg large
      my $small_img_url = "http://cache.lego.com/media/bricks/5/1/$id.jpg";;
      my $large_img_url = "http://cache.lego.com/media/bricks/5/2/$id.jpg";;

      my $dir = "/var/www/lego/html/parts/$design_id/";
      if (!(-e $dir)) {
         system "mkdir $dir";
      }

      my $small_filename = "/var/www/lego/html/parts/$design_id/$id.jpg";
      if (!(-e $small_filename) || !(-s $small_filename)) {
         print "wget -O $small_filename '$small_img_url'\n";
         system "wget $ua -O $small_filename '$small_img_url'";

         if (!(-e $small_filename) || !(-s $small_filename)) {
             $small_img_url = "http://cache.lego.com/media/bricks/4/1/$id.jpg";;
             print "wget -O $small_filename '$small_img_url'\n";
             system "wget $ua -O $small_filename '$small_img_url'";
         }
      }

      my $large_filename = "/var/www/lego/html/parts/$design_id/$id-large.jpg";
      if (!(-e $large_filename) || !(-s $large_filename)) {
         print "wget -O $large_filename '$large_img_url'\n";
         system "wget $ua -O $large_filename '$large_img_url'";

         if (!(-e $large_filename) || !(-s $large_filename)) {
             my $large_img_url = "http://cache.lego.com/media/bricks/4/2/$id.jpg";;
             print "wget -O $large_filename '$large_img_url'\n";
             system "wget $ua -O $large_filename '$large_img_url'";
         }
      }
   }
}

sub printDimensionsList($) {
   my $dbh = shift;
   my $query = "SELECT DISTINCT(dimensions) FROM  `bricks` ORDER BY  `bricks`.`dimensions` ASC ";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $dimension = $row[0];
      if ($dimension) {
         print ",'$dimension'";
      }
   }
}

sub printColorList($) {
   my $dbh = shift;
   my $query = "SELECT DISTINCT(color) FROM `bricks` ORDER BY  `bricks`.`color` ASC ";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   print "\n";
   while (my @row = $sth->fetchrow_array()) {
      my $color = $row[0];
      if ($color) {
         print ",'$color'";
      }
   }
   print "\n";
}

sub printTypeList($) {
   my $dbh = shift;
   my $query = "SELECT DISTINCT(type) FROM `bricks` ORDER BY  `bricks`.`type` ASC ";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   print "\n";
   while (my @row = $sth->fetchrow_array()) {
      my $type = $row[0];
      if ($type) {
         print ",'$type'";
      }
   }
}

my $dbh = dbConnect();
setUsedinsetMinyearMaxyear($dbh);
downloadPartImages($dbh);

#printDimensionsList($dbh);
#printColorList($dbh);
#printTypeList($dbh);
