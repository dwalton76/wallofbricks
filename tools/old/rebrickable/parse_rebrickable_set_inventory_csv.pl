#!/usr/bin/perl

# Don't use this...it updates the set inventories based on rebrickable's data
# but they tend to use some older part numbers than brickset so you end up with
# duplicate parts just with different part numbers.

use strict;
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
my %ldraw2brickset;
$ldraw2brickset{'0'} = 'Black';
$ldraw2brickset{'1'} = 'Bright Blue';
$ldraw2brickset{'2'} = 'Dark Green';
$ldraw2brickset{'3'} = 'Bright Bluish Green';
$ldraw2brickset{'4'} = 'Bright Red';
$ldraw2brickset{'5'} = 'Bright Purple';
$ldraw2brickset{'6'} = 'Earth Orange';
$ldraw2brickset{'7'} = 'Grey';
$ldraw2brickset{'8'} = 'Dark Grey';
$ldraw2brickset{'9'} = 'Light Blue';
$ldraw2brickset{'10'} = 'Bright Green';
$ldraw2brickset{'11'} = 'Medium Bluish Green';
$ldraw2brickset{'14'} = 'Bright Yellow';
$ldraw2brickset{'15'} = 'White';
$ldraw2brickset{'17'} = 'Light Green';
$ldraw2brickset{'18'} = 'Light Yellow';
$ldraw2brickset{'19'} = 'Brick Yellow';
$ldraw2brickset{'20'} = 'Light Bluish Violet';
$ldraw2brickset{'21'} = 'Phosphorescent White';
$ldraw2brickset{'22'} = 'Bright Violet';
$ldraw2brickset{'23'} = 'Dark Royal Blue';
$ldraw2brickset{'25'} = 'Bright Orange';
$ldraw2brickset{'26'} = 'Bright Reddish Violet';
$ldraw2brickset{'27'} = 'Bright Yellowish Green';
$ldraw2brickset{'28'} = 'Sand Yellow';
$ldraw2brickset{'29'} = 'Light Purple';
$ldraw2brickset{'30'} = 'Medium Lavender';
$ldraw2brickset{'31'} = 'Lavender';
$ldraw2brickset{'33'} = 'Tr. Blue';
$ldraw2brickset{'34'} = 'Tr. Green';
$ldraw2brickset{'35'} = 'Tr. Bright Green';
$ldraw2brickset{'36'} = 'Tr. Red';
$ldraw2brickset{'37'} = 'Tr. Bright Bluish Violet';
$ldraw2brickset{'40'} = 'Tr. Brown';
$ldraw2brickset{'41'} = 'Tr. Light Blue';
$ldraw2brickset{'42'} = 'Tr. Fluore. Green';
$ldraw2brickset{'45'} = 'Tr. Medium Reddish Violet';
$ldraw2brickset{'46'} = 'Tr. Yellow';
$ldraw2brickset{'47'} = 'Transparent';
$ldraw2brickset{'69'} = 'Bright Reddish Lilac';
$ldraw2brickset{'70'} = 'Reddish Brown';
$ldraw2brickset{'71'} = 'Medium Stone Grey';
$ldraw2brickset{'72'} = 'Dark Stone Grey';
$ldraw2brickset{'73'} = 'Medium Blue';
$ldraw2brickset{'74'} = 'Medium Green';
$ldraw2brickset{'78'} = 'Light Nougat';
$ldraw2brickset{'84'} = 'Medium Nougat';
$ldraw2brickset{'85'} = 'Medium Lilac';
$ldraw2brickset{'86'} = 'Brown';
$ldraw2brickset{'92'} = 'Nougat';
$ldraw2brickset{'110'} = 'Bright Bluish Violet';
$ldraw2brickset{'112'} = 'Medium Bluish Violet';
$ldraw2brickset{'114'} = 'Tr. M. Reddish-Viol W. Glit.2%';
$ldraw2brickset{'115'} = 'Medium Yellowish Green';
$ldraw2brickset{'118'} = 'Light Bluish Green';
$ldraw2brickset{'120'} = 'Light Yellowish Green';
$ldraw2brickset{'129'} = 'Tr. Br. Bluish.Viol.W.Gliter2%';
$ldraw2brickset{'134'} = 'Copper';
$ldraw2brickset{'135'} = 'Silver';
$ldraw2brickset{'142'} = 'Gold';
$ldraw2brickset{'148'} = 'Metallic Dark Grey';
$ldraw2brickset{'151'} = 'Light Stone Grey';
$ldraw2brickset{'179'} = 'Silver';
$ldraw2brickset{'191'} = 'Flame Yellowish Orange';
$ldraw2brickset{'212'} = 'Light Royal Blue';
$ldraw2brickset{'226'} = 'Cool Yellow';
$ldraw2brickset{'232'} = 'Dove Blue';
$ldraw2brickset{'272'} = 'Earth Blue';
$ldraw2brickset{'288'} = 'Earth Green';
$ldraw2brickset{'294'} = 'Phosph.Green';
$ldraw2brickset{'297'} = 'Warm Gold';
$ldraw2brickset{'308'} = 'Dark Brown';
$ldraw2brickset{'313'} = 'Pastel Blue';
$ldraw2brickset{'320'} = 'New Dark Red';
$ldraw2brickset{'321'} = 'Dark Azur';
$ldraw2brickset{'322'} = 'Medium Azur';
$ldraw2brickset{'323'} = 'Aqua';
$ldraw2brickset{'326'} = 'Olive Green';
$ldraw2brickset{'335'} = 'Sand Red';
$ldraw2brickset{'351'} = 'Medium Reddish Violet';
$ldraw2brickset{'366'} = 'Light Orange Brown';
$ldraw2brickset{'373'} = 'Sand Violet';
$ldraw2brickset{'378'} = 'Sand Green';
$ldraw2brickset{'379'} = 'Sand Blue';
$ldraw2brickset{'383'} = 'Silver';
$ldraw2brickset{'462'} = 'Bright Yellowish Orange';
$ldraw2brickset{'484'} = 'Dark Orange';
$ldraw2brickset{'503'} = 'Light Grey';

# The legoID hash is keyed by design_id and brickset color to get the lego ID#
my %legoID;
my $query = "SELECT id, design_id, color FROM `bricks` ORDER BY `bricks`.`design_id` ASC, color ASC";
my $sth = $dbh->prepare($query);
$sth->execute();
while (my @row = $sth->fetchrow_array()) {
    $legoID{$row[1]}{$row[2]} = $row[0];
}


# set_inventory is keyed by set_id and lego_id to get the number of that lego_id in the set
my %set_inventory;

my $filename = "set_pieces.csv";
open(FH, $filename) || die("ERROR: Could not open $filename");
while(<FH>) {
    chomp();
    (my $set_id, my $design_id, my $num, my $ldraw_color, my $type) = split(/,/, $_);
    if ($set_id =~ /^(\d+)\w$/) {
        $set_id = $1;
    }

    if ($set_id ne "31313-1") {
        next;
    }

    # print "$design_id $color\n";
    my $brickset_color = $ldraw2brickset{$ldraw_color};
    if ($brickset_color) {
        my $lego_id = $legoID{$design_id}{$brickset_color};
        if ($lego_id) {
            $set_inventory{$set_id}{$lego_id} = $num;
        }
    }
}
close FH;

my $query = "INSERT IGNORE INTO sets_inventory (id, brick_id, brick_quantity) VALUES (?,?,?)";
my $sth = $dbh->prepare($query);

foreach my $set_id (keys %set_inventory) {
    foreach my $lego_id (keys %{$set_inventory{$set_id}}) {
        print "SET: $set_id, PART: $lego_id, QTY: $set_inventory{$set_id}{$lego_id}\n";
        $sth->execute($set_id, $lego_id, $set_inventory{$set_id}{$lego_id});
    }
}

