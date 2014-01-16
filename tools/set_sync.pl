#!/usr/bin/perl

use strict;
use Net::FTP;
# use Net::SSH::Perl;
use Net::OpenSSH;

my $ssh_hostname = "www.wallofbricks.com";
my $ftp_hostname = "ftp.wallofbricks.com";
my $username = "dwalto76";
my $password = 'PASSWORD';

#
# Open a SSH session
#
my $ssh = Net::OpenSSH->new("$username:$password\@$ssh_hostname:2222");
$ssh->error and die "Couldn't establish SSH connection: ". $ssh->error;


#
# Open a FTP session
#
my $ftp = Net::FTP->new($ftp_hostname, Debug => 0) or die "Cannot connect to some.host.name: $@";
$ftp->login($username, $password) or die "Cannot login ", $ftp->message;
$ftp->cwd("/public_html/sets_new") or die "Cannot change working directory ", $ftp->message;


sub createSetDirectories() {
   #
   # Build a hash of the directories that are already there
   #
   my %existing_dirs;
   my @ls = $ssh->capture("ls public_html/sets_new/");
   $ssh->error and die "remote ls command failed: " . $ssh->error;
   foreach my $i (@ls) {
      chomp($i);
      $existing_dirs{$i} = 1;
   }


   #
   # Create all of the set directories
   #
   open(DIRS, "ls /var/www/lego/html/sets | ") || die("\nERROR: Could not ls /var/www/lego/html/sets\n");
   while (<DIRS>) {
      chomp();
      my $dir_to_copy = $_;
      if (defined $existing_dirs{$dir_to_copy}) {
         print "SKIP: $dir_to_copy\n";
      } else {
         print "MAKE: $dir_to_copy\n";
         $ftp->mkdir($_) or die "Cannot mkdir", $ftp->message;
      }
   }
   close DIRS;
}

sub uploadSetContent() {
   #open(DIRS, "ls /var/www/lego/html/sets/ | sort -r | ") || die("\nERROR: Could not ls /var/www/lego/html/sets/\n");
   open(DIRS, "ls /var/www/lego/html/sets/ | ") || die("\nERROR: Could not ls /var/www/lego/html/sets/\n");
   while (<DIRS>) {
      chomp();
      my $set = $_;
      #next if ($set ne "10188-1");
      print "SET: $set\n";

      # See what files are already uploaded for this set
      my %existing_files;
      my @ls = $ssh->capture("ls public_html/sets_new/$set/");
      foreach my $i (@ls) {
         chomp($i);
         $existing_files{$i} = $i;
      }


      # Build a list of what files need to end up on wallofbricks
      # First get a list of all of the PDFs
      my %pdf;
      open(SET_CONTENT, "ls /var/www/lego/html/sets/$set/*.pdf |") || die("\nERROR: Could not ls /var/www/lego/html/sets/$set/*.pdf\n");
      while(<SET_CONTENT>) {
         chomp($_);
         if ($_ =~ /\/(\w+)\.pdf$/) {
            $pdf{$1} = $1;
            print "PDF: $1\n";
         }
      }
      close SET_CONTENT;


      my %images_needed;
      open(SET_CONTENT, "ls /var/www/lego/html/sets/$set/ |") || die("\nERROR: Could not ls /var/www/lego/html/sets/$set/\n");
      while(<SET_CONTENT>) {
         chomp($_);
         my $file = $_;
         if ($file =~ /^(.*)\.(\w+)$/) {
            my $root = $1;
            my $ext = $2;
            my $pdf_root = $root;
            if ($root =~ /^(.*?)\-/) {
               $pdf_root = $1;
            }

            #print "\nFILE: $file\n";
            #print "ROOT: $1\n";
            #print "PDF_ROOT: $pdf_root\n";
            #print "EXT: $ext\n";

            if ($ext eq "jpg" || $ext eq "gif" || $ext eq "png") {

               # We don't care about the PDF images for now
               #if (defined $pdf{$pdf_root}) {
               #   print "SKIP_PDF: $file\n";

               # We want all of the others though
               #} else {
                  $images_needed{$file} = $file;
               #}
            }
         }
      }
      close SET_CONTENT;


      # Delta the two lists and upload the ones that are missing
      foreach my $i (keys %images_needed) {
         if (!(defined $existing_files{$i})) {
            print "COPY: /var/www/lego/html/sets/$set/$i\n";
            $ssh->scp_put("/var/www/lego/html/sets/$set/$i", "/home2/dwalto76/public_html/sets_new/$set/$i") or die "scp failed: " . $ssh->error;
         } else {
            print "SKIP: /var/www/lego/html/sets/$set/$i\n";
         }
      }
   }
   close DIRS;
}

uploadSetContent();
# createSetDirectories();

$ftp->quit();


# dwalton76@dwalton-ubuntu:/var/www/lego/html/sets/4936-1$ ls
# 4507837-0.jpg         4507837-1.jpg         4507837.pdf    lego-4936-1.jpg         legoimages.txt  tn.jpg
# 4507837-0-medium.jpg  4507837-1-medium.jpg  brickset.html  lego-4936-1-medium.jpg  manual.json
# 4507837-0-small.jpg   4507837-1-small.jpg   inventory.csv  lego-4936-1-small.jpg   parts.jpg
# dwalton76@dwalton-ubuntu:/var/www/lego/html/sets/4936-1$

# Foreach directory upload all of the pics except the ones related to the pdfs.


