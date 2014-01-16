#!/usr/bin/perl

use strict;
use XML::LibXML;
use WWW::Mechanize;
use DBI;

#
# This parset the sets XML file from brinklink to get all the set IDs.
# It then calls 'download_set.pl' for any set built in the target year.
#
my $target_year = "2010";

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


#
# Read the sets XML file
#
my $result_string;
open(FH, "xml/Sets.txt") || die("\nERROR: Could not open xml/sets.txt");
while(<FH>) {
   chomp();
   $result_string .= $_;
}
close FH;

#
# Load the XML in $result_string into a XML::LibXML object.  Catch any
# exceptions that are thrown if the XML is invalid.
#
my $dom;
eval {
   $dom = XML::LibXML->load_xml(string => $result_string, recover => 0);
};

# If this is TRUE then the XML is invalid
if ($@) {
   die("\nERROR INVALID XML: $@\n\n");
}


#
# Download a list of the sets we already know about
#
my $query = "SELECT id FROM `sets` ";
my $dbh = dbConnect();
my $sth = $dbh->prepare($query);
$sth->execute();
my %known_ID;
while (my @row = $sth->fetchrow_array()) {
   my $id = $row[0];
   $known_ID{$id} = $id;
}

my $already_done = 0;
my $this_year = 0;
my $skip = 0;
foreach my $i ($dom->findnodes('/CATALOG/ITEM')) {
   my $type = $i->getChildrenByTagName('ITEMTYPE');
   my $id   = $i->getChildrenByTagName('ITEMID');
   my $name = $i->getChildrenByTagName('ITEMNAME');
   my $year = $i->getChildrenByTagName('ITEMYEAR');
   my $category = $i->getChildrenByTagName('CATEGORY');

   if (defined $known_ID{$id}) {
      print "ALREADY DONE - YEAR: $year, TYPE: $type, ID: $id, NAME: $name\n";
      $already_done++;

   } else  {
      print "               YEAR: $year, TYPE: $type, ID: $id, NAME: $name\n";
      system "./download_set.pl -id $id";
   }

#   } elsif ($year eq $target_year) {
#      print "THIS YEAR    - YEAR: $year, TYPE: $type, ID: $id, NAME: $name\n";
#      $this_year++;
#      system "./download_set.pl -id $id";
#      #exit();
#
#   } else {
#      print "SKIP          -YEAR: $year, TYPE: $type, ID: $id, NAME: $name\n";
#      $skip++;
#   }
}

print "ALREADY DONE: $already_done\n";
print "THIS YEAR:    $this_year\n";
print "SKIP:         $skip\n";

