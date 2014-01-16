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


   my $dbh = dbConnect();
   my $query = "UPDATE lego_store_inventory_activity SET brick_id=? WHERE id=? AND row=? AND col=? AND slot=?";
   my $sth_update = $dbh->prepare($query);

   my $query = "SELECT id, row, col, slot, brick_id FROM lego_store_inventory_activity WHERE brick_id IS NOT NULL";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $store_id = $row[0];
      my $store_row = $row[1];
      my $store_col = $row[2];
      my $store_slot = $row[3];
      my $bricklink_id = $row[4];

      my $lego_id_query = "SELECT id FROM `bricks` WHERE `bricklink_id` LIKE '$bricklink_id'";
      my $lego_id_sth = $dbh->prepare($lego_id_query);
      $lego_id_sth->execute();
      my @lego_id_row = $lego_id_sth->fetchrow_array();
      my $lego_id = $lego_id_row[0];

      if (!$lego_id) {
         print "ID: $bricklink_id -> NULL\n";
         my $query = "DELETE FROM lego_store_inventory_activity WHERE brick_id='$bricklink_id'";
         $dbh->do($query);
      } else {
         print "ID: $bricklink_id -> $lego_id\n";
         $sth_update->execute($lego_id, $store_id, $store_row, $store_col, $store_slot);
      }
   }

   my $query = "UPDATE lego_store_inventory SET brick_id=? WHERE store_id=? AND row=? AND col=? AND slot=?";
   my $sth_update = $dbh->prepare($query);

   my $query = "SELECT store_id, row, col, slot, brick_id FROM lego_store_inventory WHERE brick_id IS NOT NULL";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $store_id = $row[0];
      my $store_row = $row[1];
      my $store_col = $row[2];
      my $store_slot = $row[3];
      my $bricklink_id = $row[4];

      my $lego_id_query = "SELECT id FROM `bricks` WHERE `bricklink_id` LIKE '$bricklink_id'";
      my $lego_id_sth = $dbh->prepare($lego_id_query);
      $lego_id_sth->execute();
      my @lego_id_row = $lego_id_sth->fetchrow_array();
      my $lego_id = $lego_id_row[0];

      if (!$lego_id) {
         print "ID: $bricklink_id -> NULL\n";
         my $query = "DELETE FROM lego_store_inventory WHERE brick_id='$bricklink_id'";
         $dbh->do($query);
      } else {
         print "ID: $bricklink_id -> $lego_id\n";
         $sth_update->execute($lego_id, $store_id, $store_row, $store_col, $store_slot);
      }
   }


