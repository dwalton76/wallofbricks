#!/usr/bin/perl

use strict;
use Mojo::DOM;
use HTML::TableExtract;
use DBI;
use Image::Thumbnail 0.65;

my $ua = "--user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3'";
my $dbh = dbConnect();

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

sub parsePartsForYearPage($) {
   my $year = shift;

   my $html_string;
   my $filename = "/var/www/lego/private/parts/brickset-$year.html";
   open(FH, $filename) || die("ERROR: Could not open FH $filename");
   while(<FH>) {
      chomp();
      $html_string .= $_;
   }
   close FH;

   my $query = "INSERT INTO bricks_lego (design_id, part_id, name, color, category, dimensions, min_year) VALUES (?,?,?,?,?,?,?)";
   my $sth = $dbh->prepare($query);

   # http://search.cpan.org/~sri/Mojolicious-4.16/lib/Mojo/DOM.pm
   my $dom = Mojo::DOM->new($html_string);
   foreach my $i ($dom->find('ul.inventory li div.highslide-caption')->each) {
      my $design_id;
      my $part_id;
      my $name;
      my $color;
      my $category;
      my $dimensions;
   
      foreach my $b ($i->find('b')->each) {
         printf("%s %s\n", $b->text, $b->text_after);

         if ($b->text =~ /Design ID/) {
            $design_id = $b->text_after;

         } elsif ($b->text =~ /Part ID/) {
            $part_id = $b->text_after;

         } elsif ($b->text =~ /Part Name/) {
            $name = $b->text_after;

         } elsif ($b->text =~ /Colour/) {
            $color = $b->text_after;

         } elsif ($b->text =~ /Category/) {
            $category = $b->text_after;
         }
      }
      print "\n";

      if ($name =~ /(\d+\s?x\s?\d+\s?x\s?\-\d+\s?x\s?\d+)/i) {
         $dimensions = $1;
      } elsif ($name =~ /(\d+\s?x\s?\d+\s?x\s?\d+)/i) {
         $dimensions = $1;
      } elsif ($name =~ /(\d+\s?x\s?\d+)/i) {
         $dimensions = $1;
      }

      $sth->execute($design_id, $part_id, $name, $color, $category, $dimensions, $year);
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
         
         print "ALT1 '$alternate1'\n";
         if ($alternate2) {
            print "ALT2 '$alternate2'\n";
         }
         last;
      }
   }
   close FH;

   if ($alternate1) {
      $update_sth->execute($alternate1, $alternate2, $id_minus_color);
   }
}

# I've already run this part...no need to run it again
if (0) {
   for (my $i = 2013; $i >= 1980; $i--) {
      parsePartsForYearPage($i);
   }
}

# Set the lego_id_alt1 and lego_id_alt2 fields in the database
if (0) {
# dwalton - run this next
   my $update_query = "UPDATE bricks SET lego_id_alt1=?, lego_id_alt2=? WHERE bricklink_core_id=?";
   my $update_sth = $dbh->prepare($update_query);

   # my $query = "SELECT DISTINCT (bricklink_core_id) FROM `bricks` WHERE bricklink_core_id='4073' ORDER BY `bricks`.`id` ASC";
   my $query = "SELECT DISTINCT (bricklink_core_id) FROM `bricks` ";
   my $sth = $dbh->prepare($query);
   $sth->execute();
   while (my @row = $sth->fetchrow_array()) {
      my $id_minus_color = $row[0];
      my $output_filename = "/var/www/lego/tools/parts/$id_minus_color.html";

      print "\nID: $id_minus_color\n";
      if (-e $output_filename) {
         parseBrickLinkPage($id_minus_color, $output_filename, $update_sth);
      }
   }
}

#
# Set the bricklink_id for parts in bricks_lego for simple cases where there aren't alt IDs
#
if (0) {
   my $update_query = "UPDATE bricks_lego SET bricklink_id=? WHERE part_id=?";
   my $update_sth = $dbh->prepare($update_query);

   my $query = "SELECT design_id, part_id, bricks_lego.color, bricklink_color, bricks.id ".
                "FROM  `bricks_lego`  ".
                "INNER JOIN lego_colors ON bricks_lego.color = lego_colors.brickset_color ".
                "INNER JOIN bricks ON bricks.color = lego_colors.bricklink_color ".
                "AND bricklink_core_id = design_id ".
                "AND lego_id_alt1 IS NULL ".
                "AND lego_id_alt2 IS NULL  ";
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
if (0) {
   my $update_query = "UPDATE bricks_lego SET bricklink_id=? WHERE part_id=?";
   my $update_sth = $dbh->prepare($update_query);

   my $query = "SELECT design_id, part_id, bricks_lego.color, bricklink_color, bricks.id ".
               "FROM  `bricks_lego` ".
               "INNER JOIN lego_colors ON bricks_lego.color = lego_colors.brickset_color ".
               "INNER JOIN bricks ON bricks.color = lego_colors.bricklink_color AND lego_id_alt1 = design_id AND lego_id_alt1 IS NOT NULL AND lego_id_alt2 IS NULL ".
               "WHERE bricklink_id IS NULL";
   print "SQL: $query\n";

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
# Set the bricklink_id for parts in bricks_lego when there is an alt1 and an alt2
# This is for the case where alt1 matches....just change the "lego_id_alt1 = design_id" to alt2 to fun this for alt2 matching...after you've run it for alt1 matching :)
#
if (0) {
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


# Download a list of all minifigs and their inventories
if (0) {
   my $url = "http://www.bricklink.com/catalogTree.asp?itemType=M";
   my $filename = "/var/www/lego/private/parts/minifig-catalog.html";
   if (-e $filename) {
      print "SKIP: $filename\n";
   } else {
      print "wget $ua -O $filename '$url'<br>\n";
      system "wget $ua -O $filename '$url'";
   }

   open(FH, $filename) || die("ERROR: Could not open FH $filename");
   while(<FH>) {
      chomp();
      # /catalogList.asp?catType=M&catString=65
      if (/a href='\/catalogList\.asp\?catType=M&catString=(\d+)'>(.*?)</i) {
         my $category_name = lc($2);
         my $category_id = $1; 
         $category_name =~ s/ /-/g;

         # next if ($category_id != 65);  # Star Wars only for now

         my $url = "http://www.bricklink.com/catalogList.asp?catType=M&catString=$category_id"; 
         my $category_filename = "/var/www/lego/private/parts/minifig-category-$category_id-pg1.html";

         if (-e $category_filename) {
            print "SKIP: $category_filename\n";
         } else {
            print "wget $ua -O $category_filename '$url'<br>\n";
            system "wget $ua -O $category_filename '$url'";
         }

         # Download all of the pages of minifigs for this category...
         my $max_page = 1;
         open(FH2, $category_filename) || die("ERROR: Could not open $category_filename");
         while(<FH2>) {
            if (/Page <B>1<\/B> of <B>(\d+)<\/B>/) {
               $max_page = $1;
               if ($max_page == 1) {
                  next;
               }

               for (my $i = 2; $i <= $max_page; $i++) {
                  my $url = "http://www.bricklink.com/catalogList.asp?pg=$i&catString=$category_id&catType=M";
                  my $page_filename = "/var/www/lego/private/parts/minifig-category-$category_id-pg$i.html";

                  if (-e $page_filename) {
                     print "SKIP: $page_filename\n";
                  } else {
                     print "wget $ua -O $page_filename '$url'<br>\n";
                     system "wget $ua -O $page_filename '$url'";
                  }
               }
               last;
            }
         }
         close FH2;

         # For all of the pages of minifigs for this category...
         for (my $i = 1; $i <= $max_page; $i++) {
            my $page_filename = "/var/www/lego/private/parts/minifig-category-$category_id-pg$i.html";

            my @table_row;
            my $html_string;
            open(PAGE, $page_filename) || die("ERROR: Could not open $page_filename");
            while(<PAGE>) {
               chomp();
               $html_string .= $_;
            }
            close PAGE;

           
            while ($html_string =~ /(<tr.*?<\/tr>)(.*)$/i) {
               push @table_row, $1;
               #print "PUSH: $1\n";
               $html_string = $2;
            } 

            my $minifig_id;
            my $minifig_inv_url;

            # Parse the page of minifigs, download the inv page for each of them
            foreach (@table_row) {
               # print "HERE: $_\n"; 
               # <a href="catalogItem.asp?M=sw453">sw453</a>
               if (/a href="catalogItem\.asp\?M=(\w+)"/i) {
                  $minifig_id = $1;
                  # print "MINIFIG_ID: $minifig_id\n";
               }

               if ($minifig_id) {
                  if (/a href="(catalogItemInv.asp\?M=$minifig_id)">Inv</i) {
                     $minifig_inv_url = $1;
                     #print "MINIFIG: $minifig_id -> $minifig_inv_url\n";

                     my $minifig_inv_filename = "/var/www/lego/private/parts/minifig-inventory-$minifig_id.html";
                     my $url = "http://www.bricklink.com/$minifig_inv_url";

                     if (-e $minifig_inv_filename) {
                        print "SKIP: $minifig_inv_filename\n";
                     } else {
                        print "wget $ua -O $minifig_inv_filename '$url'<br>\n";
                        system "wget $ua -O $minifig_inv_filename '$url'";
                     }

                     $minifig_id = 0;
                     $minifig_inv_url = 0;
                  }
               }
            }

            # last; # page 1 only for now
         }
      }
   }
   close FH;
}

