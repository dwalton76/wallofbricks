#!/usr/bin/perl

use strict;
use DBI;

#
# Connect to the database
#
sub dbConnect() {
   #Database Attributes
   my $host     = "localhost";  # Host to connect to
   my $db       = "stackoverflow";
   my $user     = "dwalto76_admin";
   my $password = "PASSWORD";

   # Connect to the databse and set the handle to $dbh
   my $dbh = DBI->connect("DBI:mysql:database=$db:host=$host", $user, $password) || die("\n\nERROR: Can't connect to database: $DBI::errstr\n");
   return $dbh;
}

sub setUsedinsetMinyearMaxyear($) {
   my $dbh = shift;
   my $query_update_brick = "UPDATE bricks SET color=?, dimensions=?, part_type=?, bricklink_core_id=?, used_in_sets=?, min_year=?, max_year=? WHERE `id`=?";
   my $sth_update_brick = $dbh->prepare($query_update_brick);
   
   my $query = "SELECT id, type, description FROM bricks WHERE id='3001-1'";
   my $query = "SELECT id, type, description FROM bricks";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $id = $row[0];
      my $type = $row[1];
      my $desc = $row[2];
   
      my $color;
      my $bricklink_core_id;
      if ($id =~ /^(.*)\-(\d+)$/) {
         $bricklink_core_id = $1;
         $color = $2;
      }
   
      my $part_type;
      if ($type eq "M") {
         $part_type = "minifig";
      } elsif ($type eq "G") {
         $part_type = "G";
      } elsif ($desc =~ /brick/i) {
         $part_type = "brick";
      } elsif ($desc =~ /plate/i) {
         $part_type = "plate";
      } elsif ($desc =~ /tile/i) {
         $part_type = "tile";
      } elsif ($desc =~ /slope/i) {
         $part_type = "slope";
      } elsif ($desc =~ /technic/i) {
         $part_type = "technic";
      } elsif ($desc =~ /sticker/i) {
         $part_type = "sticker";
      }
   
      my $dimensions;
      if ($desc =~ /(\d+ x \d+ - \d+ x \d+)/) {
         $dimensions = $1;
         $dimensions =~ s/ //g;
      } elsif ($desc =~ /(\d+ x \d+ x \d+)/) {
         $dimensions = $1;
         $dimensions =~ s/ //g;
      } elsif ($desc =~ /(\d+ x \d+)/) {
         $dimensions = $1;
         $dimensions =~ s/ //g;
      }
   
      # Do a query to see how many sets this part has been used in and the min/max year it was used
      my $used_in_sets;
      my $min_year;
      my $max_year;
      my $set_type;
      my $pop_query = "SELECT COUNT(sets_inventory.id), MIN(year), MAX(year), sets.type ".
                      "FROM `sets_inventory` ".
                      "INNER JOIN sets ON sets.id = sets_inventory.id ".
                      "WHERE brick_id='$id' AND brick_type != 'S'";
   
      if ($id eq "970c05-11") {
         print "SQL: $pop_query\n";
      }
   
      my $pop_sth = $dbh->prepare($pop_query);
      $pop_sth->execute();
      my @pop_row = $pop_sth->fetchrow_array();
      $used_in_sets = $pop_row[0];
      $min_year = $pop_row[1];
      $max_year = $pop_row[2];
      $set_type = $pop_row[3];
   
   # dwalton TODO
      # For parts that are part of a minifig we have to lookup all the sets that the minifig is in to get the data we want.
      # FYI this takes HOURS to run so leave it commented out after you've run it once
      if (!$min_year && $set_type eq "minifig") {
         # A list of all the sets that use the minifig that use this part.
         # I don't use this for anything but figured I should save it once I had it figured out :)
         #$query = "SELECT sets_inventory.id FROM sets_inventory WHERE brick_id IN (SELECT sets_inventory.id ".
         #         "FROM `sets_inventory` ".
         #         "WHERE brick_id='970c05-11' AND brick_type != 'S' )";
   
         $pop_query = "SELECT COUNT(sets_inventory.id), MIN(year), MAX(year) ".
                      "FROM sets_inventory ".
                      "INNER JOIN sets ON sets.id = sets_inventory.id ".
                      "WHERE brick_id IN (SELECT sets_inventory.id FROM `sets_inventory` WHERE brick_id='$id' AND brick_type != 'S' ) ";
         my $pop_sth = $dbh->prepare($pop_query);
         $pop_sth->execute();
         my @pop_row = $pop_sth->fetchrow_array();
         $used_in_sets = $pop_row[0];
         $min_year = $pop_row[1];
         $max_year = $pop_row[2];
      }
   
      $sth_update_brick->execute($color, $dimensions, $part_type, $bricklink_core_id, $used_in_sets, $min_year, $max_year, $id);
   }
}

sub parseBrickLinkPage($$$) {
   my $id_minus_color = shift;
   my $filename = shift;
   my $update_sth = shift;

   my $alternate1 = $1;
   my $alternate2;

   open(FH, $filename) || die("ERROR: Could not open FH $filename");
   while(<FH>) {
      # <FONT COLOR="#666666">Alternate Item No:</FONT><BR><B>6141, 30057</B></TD>
      if (/Alternate Item No:<\/FONT><BR><B>(.*?)<\/B>/) {
         $alternate1 = $1;
         $alternate2;

         if ($alternate1 =~ /(\w+), (\w+)/) {
            $alternate1 = $1;
            $alternate2 = $2;
         }

         last;
      }
   }
   close FH;

   if ($alternate1) {
      print "ID: $id_minus_color ALT1 $alternate1  ALT2 $alternate2\n";
      $update_sth->execute($alternate1, $alternate2, $id_minus_color);
   }
}

# Set the lego_id_alt1 and lego_id_alt2 fields in the database
sub setAlt1Alt2($) {
   my $dbh = shift;

   my $update_query = "UPDATE bricks SET lego_id_alt1=?, lego_id_alt2=? WHERE bricklink_core_id=?";
   my $update_sth = $dbh->prepare($update_query);

   # my $query = "SELECT DISTINCT (bricklink_core_id) FROM `bricks` WHERE bricklink_core_id='4073' ORDER BY `bricks`.`id` ASC";
   my $query = "SELECT DISTINCT (bricklink_core_id) FROM `bricks` ";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $id_minus_color = $row[0];
      my $output_filename = "/var/www/lego/tools/parts/$id_minus_color.html";

      if (-e $output_filename) {
         parseBrickLinkPage($id_minus_color, $output_filename, $update_sth);
      }
   }
}

#
# Set the bricklink_id for parts in bricks_lego for simple cases where there aren't alt IDs
#
sub setBricklinkIDWhenNoAltIDs($) {
   my $dbh = shift;

   my $update_query = "UPDATE bricks_lego SET bricklink_id=? WHERE part_id=?";
   my $update_sth = $dbh->prepare($update_query);

   my $query = "SELECT design_id, part_id, bricks_lego.color, bricklink_color, bricks.id ".
                "FROM  `bricks_lego`  ".
                "INNER JOIN lego_colors ON bricks_lego.color = lego_colors.brickset_color ".
                "INNER JOIN bricks ON bricks.color = lego_colors.bricklink_color ".
                "AND bricklink_core_id = design_id";
                #"AND lego_id_alt1 IS NULL ".
                #"AND lego_id_alt2 IS NULL  ";
   #print "SQL: $query\n";

   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $part_id = $row[1];
      my $bricklink_id = $row[4];
      $update_sth->execute($bricklink_id, $part_id);
      print "PART_ID $part_id -> $bricklink_id\n";
   }
}

#
# Set the bricklink_id for parts in bricks_lego when there is an alt1 but not an alt2
#
sub setBricklinkIDWhenAlt1ButNoAlt2($) {
   my $dbh = shift;

   my $update_query = "UPDATE bricks_lego SET bricklink_id=? WHERE part_id=?";
   my $update_sth = $dbh->prepare($update_query);

   my $query = "SELECT design_id, part_id, bricks_lego.color, bricklink_color, bricks.id ".
               "FROM  `bricks_lego` ".
               "INNER JOIN lego_colors ON bricks_lego.color = lego_colors.brickset_color ".
               "INNER JOIN bricks ON bricks.color = lego_colors.bricklink_color AND lego_id_alt1 = design_id AND lego_id_alt1 IS NOT NULL AND lego_id_alt2 IS NULL ";
   #print "SQL: $query\n";

   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $part_id = $row[1];
      my $bricklink_id = $row[4];
      $update_sth->execute($bricklink_id, $part_id);
      print "PART_ID $part_id -> $bricklink_id\n";
   }
}

#
# Set the bricklink_id for parts in bricks_lego when there is an alt1 and an alt2...based on the alt1 value
#
sub setBricklinkIDWhenAlt1AndAlt2BasedOnAlt1($) {
   my $dbh = shift;

   my $update_query = "UPDATE bricks_lego SET bricklink_id=? WHERE part_id=?";
   my $update_sth = $dbh->prepare($update_query);

   my $query = "SELECT design_id, part_id, bricks_lego.color, bricklink_color, bricks.id ".
               "FROM  `bricks_lego` ".
               "INNER JOIN lego_colors ON bricks_lego.color = lego_colors.brickset_color ".
               "INNER JOIN bricks ON bricks.color = lego_colors.bricklink_color AND lego_id_alt1 = design_id AND lego_id_alt1 IS NOT NULL AND lego_id_alt2 IS NOT NULL ".
               "WHERE bricklink_id IS NULL";

   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $part_id = $row[1];
      my $bricklink_id = $row[4];
      $update_sth->execute($bricklink_id, $part_id);
      print "PART_ID $part_id -> $bricklink_id\n";
   }
}

#
# Set the bricklink_id for parts in bricks_lego when there is an alt1 and an alt2...based on the alt2 value
#
sub setBricklinkIDWhenAlt1AndAlt2BasedOnAlt2($) {
   my $dbh = shift;

   my $update_query = "UPDATE bricks_lego SET bricklink_id=? WHERE part_id=?";
   my $update_sth = $dbh->prepare($update_query);

   my $query = "SELECT design_id, part_id, bricks_lego.color, bricklink_color, bricks.id ".
               "FROM  `bricks_lego` ".
               "INNER JOIN lego_colors ON bricks_lego.color = lego_colors.brickset_color ".
               "INNER JOIN bricks ON bricks.color = lego_colors.bricklink_color AND lego_id_alt2 = design_id AND lego_id_alt1 IS NOT NULL AND lego_id_alt2 IS NOT NULL ".
               "WHERE bricklink_id IS NULL";

   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $part_id = $row[1];
      my $bricklink_id = $row[4];
      $update_sth->execute($bricklink_id, $part_id);
      print "PART_ID $part_id -> $bricklink_id\n";
   }
}


my $dbh = dbConnect();
#setUsedinsetMinyearMaxyear($dbh);
#setAlt1Alt2($dbh);
#setBricklinkIDWhenNoAltIDs($dbh);
#setBricklinkIDWhenAlt1ButNoAlt2($dbh);
#setBricklinkIDWhenAlt1AndAlt2BasedOnAlt1($dbh);
#setBricklinkIDWhenAlt1AndAlt2BasedOnAlt2($dbh);


