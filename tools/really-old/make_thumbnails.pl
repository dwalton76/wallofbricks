#!/usr/bin/perl

# NOTE: This won't work as-is since set images have moved into set directories now....rework this

use strict;
use Image::Thumbnail 0.65;

my $i = 0;
open(FH, "ls images/S*-[1-9]\.* |");
while(<FH>) {
   
   chomp();
   if (/images\/(S.*).(jpg)/ ||
       /images\/(S.*).(png)/ ||
       /images\/(S.*).(gif)/) {
      my $id = $1;
      my $suffix = $2;
      my $input = "images/$id.$suffix";
      my $output = "images/$id-tn.$suffix";

      my $input_size = -s "images/$id.$suffix";

      if ($input_size && !(-e $output)) {
         if ($input_size > 10000) {
            print "$i THUMBNAIL: $input -> $output\n";
            my $t = new Image::Thumbnail(
               size       => 180,
               create     => 1,
               input      => $input,
               outputpath => $output,
            );

            $i++;
         } else {
            print "$i THUMBNAIL COPY: $input -> $output\n";
            system "cp $input $output";
            $i++;
         }
      }
   }

}
close FH;
