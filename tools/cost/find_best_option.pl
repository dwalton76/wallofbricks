#!/usr/bin/perl

use strict;
use HTML::TableExtract;
use DBI;
use Algorithm::Combinatorics qw(combinations);

my $dbh = dbConnect();
my %bricks_needed;
my %bricks_by_store;
my %bricks_by_id;
my @brick_IDs;
my $set_id = "10178-1"; # ATAT
my $set_id = "10144-1"; # Sandcrawler
my $set_id = "9396-1"; # Helicopter 
my $include_minifigs = 1;
my %storeID2Name;
my %storeName2ID;
my %store_combinations;

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

sub cents2Dollars($) {
   my $cents = shift;
   return "\$" . $cents / 100;
}

sub downloadBricksNeeded() {
   print "\ndownloadBricksNeeded\n";
   print "=============================\n";
   my $query = "SELECT brick_id, quantity ".
               "FROM `sets_bricks_needed` ".
               "WHERE `username`=? AND `id`=? ";
   if (!$include_minifigs) {
      $query .= "AND brick_id NOT LIKE 'sw%' ";
   }

   $query .= "ORDER BY `brick_id` ASC ";
   print "SQL: $query\n";

   my $sth = $dbh->prepare($query);
   $sth->execute('dwalton76', $set_id);
   while (my @row = $sth->fetchrow()) {
      my $id = $row[0];
      if ($id =~ /(.*)-0/) {
         $id = $1;
      }
      #print "ID $id, QTY $row[1]\n";
      $bricks_needed{$id} = $row[1];
      push @brick_IDs, $id;
   }
}

sub downloadBrickLinkInventory() {
   print "\ndownloadBrickLinkInventory\n";
   print "=============================\n";
   my $brick_IDs_string = "('" . join("','", sort(@brick_IDs)) . "')";

   my $query = "SELECT id, store, quantity, price ".
               "FROM `bricklink_inventory` ".
               "WHERE id IN $brick_IDs_string ";
   print "SQL: $query\n";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow()) {
      my $id = $row[0];
      my $store = $row[1];
      my $qty = $row[2];
      my $price = $row[3];
      $bricks_by_store{$store}{$id} = $price;
      $bricks_by_id{$id}{$store} = $price;
   }
}

sub mapStoreNamesToStoreID() {
   my $i = 0;

   foreach my $store (sort keys %bricks_by_store) {
      $storeID2Name{$i} = $store;
      $storeName2ID{$store} = $i;
      $i++;
   }
}

sub cheapestStoreWithAllBricks() {
   my @stores_with_all_bricks;
   foreach my $store (keys %bricks_by_store) {
      # print "$store\n";
   
      my $has_all = 1;
      foreach my $i (keys %bricks_needed) {
         if (!(exists $bricks_by_store{$store}{$i})) {
            $has_all = 0;
            last;
         }
      }
   
      if ($has_all) {
         push @stores_with_all_bricks, $store;
      }
   }

   my $min_cost = 99999999;
   my $min_cost_store = "";
   foreach my $store (@stores_with_all_bricks) {
      my $total_parts_cost = 0;
      foreach my $id (keys %bricks_needed) {
         my $qty = $bricks_needed{$id};
         my $price = $bricks_by_store{$store}{$id};
         $total_parts_cost += ($qty * $price);
      }
   
      if ($total_parts_cost < $min_cost) {
         $min_cost = $total_parts_cost;
         $min_cost_store = $store;
      }
   }

   print "\ncheapestStoreWithAllBricks\n";
   print "=============================\n";
   if ($min_cost == 99999999) {
      print "There isn't a store with every part\n";
   } else {
      my $dollars = cents2Dollars($min_cost);
      print "$dollars - $min_cost_store\n";
   }
}

sub cheapestComboIgnoringShipping() {
   my %stores_used;
   my $total_parts_cost = 0;
   foreach my $id (sort keys %bricks_by_id) {
      my $min_price = 999999;
      my $min_price_store = "";
      foreach my $store (keys %{$bricks_by_id{$id}}) {
         if ($bricks_by_id{$id}{$store} < $min_price) {
            $min_price = $bricks_by_id{$id}{$store};
            $min_price_store = $store;
         }
         # print "STORE: $store\n";
      }

      if ($min_price != 999999) {
         my $qty = $bricks_needed{$id};
         $total_parts_cost += ($qty * $min_price);
         print "$id: $qty * $min_price" . "c\n";
         $stores_used{$min_price_store} = $min_price_store;
      }
   }

   my $dollars = cents2Dollars($total_parts_cost);

   print "\ncheapestComboIgnoringShipping\n";
   print "=============================\n";
   print "Parts: $dollars\n";
   my $count = keys %stores_used;
   my $sh = $count * 300;
   printf("S&H: %d * \$3 = %s\n", $count, cents2Dollars($sh));
   printf("Total: %s\n", cents2Dollars($total_parts_cost + $sh));


   my @store_IDs;
   foreach my $i (sort keys %stores_used) {
      push @store_IDs,  $storeName2ID{$i};
   }

   @store_IDs =  sort { $a <=> $b } @store_IDs;
   my $store_id_string = join(":", @store_IDs);
   print "$store_id_string\n\n";

   return ($count, $total_parts_cost);
}

sub getPartsCost($) {
   (my $store_combination) = @_;
   #my %parts_to_check = %$parts_to_check_ref;

   my $debug = 1;
   #if ($store_combination =~ /40:75:111:217:597/) {
   #   $debug = 1;
   #}

   my $can_buy_all_parts = 1;
   my $total_parts_cost = 0;

   printBricksNeeded("C");
   foreach my $id (sort keys %bricks_needed) {
      my $min_price = 999999;
      my $min_price_store = "";

      # Loop through the stores at our disposal and find the one with the lowest cost for this brick
      foreach my $store_id (split(":", $store_combination)) {
         my $store = $storeID2Name{$store_id};
         if (exists $bricks_by_id{$id}{$store} && $bricks_by_id{$id}{$store} < $min_price) {
            $min_price = $bricks_by_id{$id}{$store};
            $min_price_store = $store;
         }
      }

      print "PART_ID $id, MIN_PRICE $min_price\n" if $debug;

      if ($min_price == 999999) {
         $can_buy_all_parts = 0;
         # last;
      } else {
         my $qty = $bricks_needed{$id};
         $total_parts_cost += ($qty * $min_price);
      }
   }

   return ($can_buy_all_parts, $total_parts_cost);
}

sub printAvailabilityMatrix($) {
   (my $max_stores_to_use) = shift;

   printf("%-35s", '');
   foreach my $id (sort keys %bricks_by_id) {
      printf("%10s", $id);
   }
   print "\n";

   foreach my $store (sort keys %bricks_by_store) {
      printf("%3d: %-30s", $storeName2ID{$store}, $store);

      foreach my $id (sort keys %bricks_by_id) {
         if (exists $bricks_by_store{$store}{$id}) {
            printf("%10s", 'X')
         } else {
            printf("%10s", '')
         }
      }

      print "\n";
   }
}

sub findBricksRarestFirst() {
   my $brick_index = 0;
   my %parts_to_check;

   my $debug = 1;

   #
   # Loop through all the bricks...start with the one that is available in the least
   # number of stores and work your way up ending with the one that is available in
   # the most number of stores.
   #
   my %stores_to_use;
   foreach my $id (sort {keys %{$bricks_by_id{$a}} <=> keys %{$bricks_by_id{$b}}} keys %bricks_by_id) {
      my $store_count = keys %{$bricks_by_id{$id}};
      $parts_to_check{$id} = 1;
      printf("\nID: %s, QTY: %s, STORES: [%d]\n", $id, $bricks_needed{$id}, $store_count) if $debug;

      my $max_parts_we_need = 0;
      my $max_parts_we_need_store = "";
      my $max_parts_we_need_store_id = 0;
      my $price = 0;

      my $available_via_store_in_use = 0;
      foreach my $store (keys %{$bricks_by_id{$id}}) {
         my $store_id = $storeName2ID{$store};
         if (exists $stores_to_use{$store_id}) {
            $available_via_store_in_use = 1;
            print "AVAILABLE via store $store_id\n" if $debug;
            #last;
         }
      }

      if ($available_via_store_in_use) {
         next;
      }

      foreach my $store (keys %{$bricks_by_id{$id}}) {
         my $store_id = $storeName2ID{$store};
         my $parts_we_need = scalar keys %{$bricks_by_store{$store}};

         if ($parts_we_need > $max_parts_we_need) {
            $max_parts_we_need = $parts_we_need;
            $max_parts_we_need_store = $store;
            $max_parts_we_need_store_id = $store_id;
            $price = $bricks_by_id{$id}{$store};
         } elsif ($parts_we_need == $max_parts_we_need) {
            if ($bricks_by_id{$id}{$store} < $price) {
               $max_parts_we_need = $parts_we_need;
               $max_parts_we_need_store = $store;
               $max_parts_we_need_store_id = $store_id;
               $price = $bricks_by_id{$id}{$store};
            }
         }

         if ($debug) {
            printf("%3s - %s...other parts %s\n",
                   $store_id,
                   cents2Dollars($bricks_by_id{$id}{$store}),
                   $parts_we_need);
         }
      }
      print "USE: $max_parts_we_need_store_id $max_parts_we_need_store with $max_parts_we_need parts\n";
      $stores_to_use{$max_parts_we_need_store_id} = 1;
   }

   my @foo = sort {$a <=> $b} keys %stores_to_use;
   my $i = join(":", @foo);
   print "$i\n";

   printBricksNeeded("B");
   (my $can_buy_all_parts, my $total_parts_cost) = getPartsCost($i);

   my $sh = ($#foo + 1) * 300;

   printf("Parts: %s\n", cents2Dollars($total_parts_cost));
   printf("S&H: %d * \$3 = %s\n", $#foo + 1, cents2Dollars($sh));
   printf("Total: %s\n", cents2Dollars($total_parts_cost + $sh));
}

sub printBricksNeeded($) {
   my $desc = shift;

   foreach my $i (sort keys %bricks_needed) {
      if ($i =~ /sw/) {
         print "$desc: $i\n";
      }
   }
}

downloadBricksNeeded();
downloadBrickLinkInventory();
mapStoreNamesToStoreID();
# cheapestStoreWithAllBricks();
(my $store_count, my $total_parts_cost) = cheapestComboIgnoringShipping();

# my $min_cost = ($store_count * 300) + $total_parts_cost;
# printAvailabilityMatrix($store_count);
printBricksNeeded("A");

findBricksRarestFirst();


