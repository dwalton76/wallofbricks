#!/usr/bin/perl

use Algorithm::Combinatorics qw(combinations);

my @data = (0..50);
my $digits = 10;
# my @all_permutations = combinations(\@data, $digits);

foreach my $foo (combinations(\@data, $digits)) {
   print join(":", @$foo) . "\n";
}


