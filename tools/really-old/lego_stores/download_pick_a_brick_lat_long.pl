#!/usr/bin/perl

use strict;
use DBI;
use XML::LibXML;
use URI::Encode;

my $ua = "--user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3'";

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
# Parse the main page to get a list of store IDs
#
my $dbh = dbConnect();
my $query_update = "UPDATE lego_store SET latitude=?, longitude=? WHERE id=?";
my $sth_update = $dbh->prepare($query_update);

my $query = "SELECT id, address FROM lego_store WHERE latitude IS NULL OR longitude IS NULL ";
my $sth = $dbh->prepare($query);
$sth->execute();
while (my @row = $sth->fetchrow_array()) {
   my $id = $row[0];
   my $address = $row[1];
   my $uri     = URI::Encode->new( { encode_reserved => 0 } );
   my $encoded = $uri->encode($address);

   my $output = "/var/www/lego/private/$id.xml"; 
   my $url = "http://maps.googleapis.com/maps/api/geocode/xml?address=$encoded&sensor=false";
   # print "URL: $url\n";
  
   if (!(-e $output)) {
      print "wget $ua -O $output \"$url\"\n"; 
      system "wget $ua -O $output \"$url\""; 
      sleep 1;
   }

   #
   # Read the sets XML file
   #
   my $result_string;
   open(FH, $output) || die("\nERROR: Could not open $output\n");
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
   
   foreach my $i ($dom->findnodes('/GeocodeResponse/result/geometry/location')) {
      my $lat = $i->getChildrenByTagName('lat');
      my $lng = $i->getChildrenByTagName('lng');
      print "ID: $id, LAT: $lat, LONG: $lng\n";
      $sth_update->execute($lat, $lng, $id);
   }
}

