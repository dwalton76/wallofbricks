#!/usr/bin/perl

use strict;
use HTML::TableExtract;
use DBI;

my $direction = "FORWARD";
for (my $i = 0; $i <= $#ARGV; $i++) {
   if ($ARGV[$i] eq "-r") {
      $direction = "REVERSE";
   }
}

# 36 = Technic
# 65 = Star Wars
# 67 = City
# 771 = Friends
my @set_categories = (516, 53, 609, 170, 54, 629, 733, 574, 293, 570, 48, 731, 273, 3, 388, 478, 748, 9, 423, 490, 746, 171, 486, 617, 567, 563, 183, 149, 167, 166, 568, 437, 153, 566, 545, 78, 444, 227, 749, 789, 226, 390, 85, 605, 497, 449, 787, 625, 277, 572, 764, 59, 394, 60, 710, 781, 284, 759, 757, 61, 761, 721, 689, 294, 734, 537, 179, 62, 395, 102, 169, 63, 34, 469, 573, 473, 174, 768, 790, 795, 157, 732, 124, 422, 755, 290, 548, 69, 623, 152 );

if ($direction eq "FORWARD") {
} else {
   @set_categories = reverse @set_categories;
}

foreach my $set_category (@set_categories) {

   # Download the first page
   my $i = 1;
   my $html_filename = "../private/sets/catString-$set_category-$i.html";
   if (!(-e $html_filename)) {
      system "wget --user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3' -O $html_filename 'http://www.bricklink.com/catalogList.asp?catType=S&catString=$set_category'";
   }

   #
   # Parse the first page to figure out how many pages there are
   #
   open(FH, $html_filename) || die("\nERROR: Could not open $html_filename\n");
   my $total_pages = 0;
   while(<FH>) {
      if (/Page <b>\d+<\/b> of <b>(\d+)<\/b>/i) {
         $total_pages = $1;
         last;
      }
   }
   close FH;

   print "SET_CATEGORY: $set_category has $total_pages pages\n";

   #
   # Download all the pages
   #
   for ($i = 2; $i <= $total_pages; $i++) {
      my $html_filename = "../private/sets/catString-$set_category-$i.html";
      if (!(-e $html_filename)) {
         system "wget --user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3' -O $html_filename 'http://www.bricklink.com/catalogList.asp?pg=$i&catString=$set_category&catType=S'";
      }
   }

   #
   # Now parse the model-version numbers out of each page 
   #
   for ($i = 1; $i <= $total_pages; $i++) {
      # dwalton - remove
      next if ($i < 9);

      my $html_filename = "../private/sets/catString-$set_category-$i.html";
      my $html_string;
      open(FH, $html_filename) || die("\nERROR: Could not open $html_filename\n");
      while(<FH>) {
         $html_string .= $_;
      }
      close FH;

      my $table_dom = HTML::TableExtract->new(depth => 4, count => 2, keep_html => 1);
      $table_dom->parse($html_string);
      foreach my $ts ($table_dom->tables) {
         foreach my $row_ptr ($ts->rows) {
            my @row = @$row_ptr;
            if ($row[0] =~ />(\w+)-(\d+)<\/a/i) {
               print "$1-$2\n";
               my $id = $1;
               my $version = $2;
               my $html_set_filename = "../private/sets/$id-$version.html";

               #if (!(-e $html_set_filename)) {
                  system "./download_set.pl -id $1 -v $2 -c $set_category";
               #}
            }
         }
      }
   }
}

