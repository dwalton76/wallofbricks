#!/usr/bin/perl

# Pack all the bitmaps into one larger file.
use strict;
use Text::CSV;
use Image::Magick;
use Image::Thumbnail 0.65;
use Mojo::DOM;
use DBI;

my $dbh = dbConnect();
my $ua = "--user-agent='Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092416 Firefox/3.0.3'";
my $query_update_manual_filename = "UPDATE sets_manual SET filename=? WHERE id=? AND url=?";
my $sth_update_manual_filename = $dbh->prepare($query_update_manual_filename);


#
# Connect to the database
#
sub dbConnect() {
    #Database Attributes
    my $host      = "localhost";  # Host to connect to
    my $db         = "dwalto76_lego";
    my $user      = "dwalto76_admin";
    my $password = "PASSWORD";

    # Connect to the databse and set the handle to $dbh
    my $dbh = DBI->connect("DBI:mysql:database=$db:host=$host", $user, $password) || die("\n\nERROR: Can't connect to database: $DBI::errstr\n");
    return $dbh;
}

sub processSetManual($$) {
    my $id = shift;
    my $core_id = shift;

    my $set_directory = "/var/www/lego/html/sets/$id/";
    if (!( -e $set_directory)) {
        print "mkdir $set_directory\n";
        system "mkdir $set_directory";
    }

    my $json_filename = "/var/www/lego/html/sets/$id/manual.json";

    # We haven't downloaded it
    if (!(-e $json_filename)) {
        my $url = "http://service.lego.com/Views/Service/Pages/BIService.ashx/GetCompletionListHtml?prefixText=$core_id&fromIdx=0";
        # TODO: Look in fix_crap.php for 'Used once to parse the json files about PDF manuals and put the data from the jsons in the sets_manual table'
        # system "wget $ua -O $json_filename '$url'";
        print STDERR "HEY...you need to implement the code that parses the json manual info\n";
        exit();
    }


    # ==============================================================
    # Download all of the manuals
    # ==============================================================
    my $query_sets_manual = "SELECT url, filename FROM sets_manual WHERE id='$id'";
    my $sth_sets_manual = $dbh->prepare($query_sets_manual);
    $sth_sets_manual->execute();
    while (my @row_sets_manual = $sth_sets_manual->fetchrow_array()) {
        my $url = $row_sets_manual[0];
        my $filename = "/var/www/lego/html/sets/$id/" . $row_sets_manual[1];
        if (!(-e $filename)) {
            print "wget $url -O $filename\n";
            system "wget $url -O $filename";
        } else {
            # print "CACHE: $filename\n";
        }

        if (-e $filename) {
            if ($filename =~ /^(.*).pdf/) {
                my $filename_minus_pdf = $1;
                my $first_jpg = $filename_minus_pdf . "-1.jpg";
                if (!(-e $first_jpg)) {
                    print "convert $filename $filename_minus_pdf.jpg\n";
                    system "convert $filename $filename_minus_pdf.jpg";
                } else {
                    print "ALREADY CONVERTED: $id $filename\n";
                }
            }
        }
    }
}

sub processSetManuals() {
    my $query = "SELECT id FROM sets";
    my $sth = $dbh->prepare($query);
    $sth->execute();
    while (my @row = $sth->fetchrow_array()) {
        my $id = $row[0];
        $id =~ /(\w+)\-\d+/;
        processSetManual($id, $1);
    }
}

sub trimSetImages() {
    my $query = "SELECT id, img  FROM `sets_image` WHERE `lego_img_id` IS NOT NULL";
    my $sth = $dbh->prepare($query);
    $sth->execute();
    while (my @row = $sth->fetchrow_array()) {
        my $id = $row[0];
        my $img  = $row[1];
        print "convert /var/www/lego/html/sets/$id/$img -trim /var/www/lego/html/sets/$id/$img\n";
        system "convert /var/www/lego/html/sets/$id/$img -trim /var/www/lego/html/sets/$id/$img";
    }
}

sub processSetImages() {
    my $query = "INSERT IGNORE INTO sets_image (id, img, lego_img_id) VALUES (?,?,?)";
    my $sth_images = $dbh->prepare($query);

    my $query = "SELECT id FROM sets WHERE id='76016-1' ";
    my $query = "SELECT id FROM sets";
    my $sth = $dbh->prepare($query);
    $sth->execute();
    while (my @row = $sth->fetchrow_array()) {
        my $id = $row[0];
        my $core_id = $id;
        if ($id =~ /^(.*)\-\d+/) {
            $core_id = $1;
        }

        my $directory = "/var/www/lego/html/sets/$id/";
        if (!(-e $directory)) {
            system "mkdir $directory";
        }

        #
        # Download images from lego
        #
        my $url = "http://cache.lego.com/e/dynamic/is/image/LEGO/$core_id" . "_is?req=imageset";
        my $filename = "/var/www/lego/html/sets/$id/legoimages.txt";
        if (!(-e $filename)) {
            print "wget -O $filename '$url'\n";
            system "wget $ua -O $filename '$url'";
        }

        my $have_lego_images = 0;
        if (-e $filename) {
            # LEGO/7965;LEGO/7965,LEGO/7965_alt1;LEGO/7965_alt1,LEGO/7965_alt2;LEGO/7965_alt2,LEGO/7965_alt3;LEGO/7965_alt3,LEGO/7965_alt4;LEGO/7965_alt4,LEGO/7965_alt5;LEGO/7965_alt5
            open(FH, $filename);
            while (<FH>) {
                chomp();
                foreach my $i (split(";", $_)) {
                    #chomp();
                    next if ($i =~ /^\s*$/);

                    foreach my $lego_img_id (split(",", $i)) {
                        $lego_img_id =~ s/LEGO\///;
                        print "LEGO_ID: $lego_img_id\n";
                        # http://cache.lego.com/e/dynamic/is/image/LEGO/7965?op_sharpen=1&resMode=sharp2&wid=3200&hei=2400
                        # http://cache.lego.com/e/dynamic/is/image/LEGO/7965_alt5?op_sharpen=1&resMode=sharp2&wid=3200&hei=2400
                        my $url = "http://cache.lego.com/e/dynamic/is/image/LEGO/$lego_img_id?op_sharpen=1&resMode=sharp2&wid=3200&hei=2400";

                        my $alt = "";
                        if ($lego_img_id =~ /_alt(\d+)/) {
                            $alt = "-alt$1";
                        }

                        my $img = "lego-$id$alt.jpg";
                        my $filename = "/var/www/lego/html/sets/$id/$img";
                        if (!(-e $filename )) {
                            print "wget -O $filename '$url'\n";
                            system "wget $ua -O $filename '$url'";
                        }

                        if (-e $filename && -s $filename) {
                            $have_lego_images = 1;
                            $sth_images->execute($id, $img, $lego_img_id)
                        }
                    }
                }
            }
            close FH;
        }

        #
        # Download the image from brickset if we didn't get an image from lego and update the database
        #
        my $img = "lego-$id.jpg";
        my $filename = "/var/www/lego/html/sets/$id/$img";
        if (!(-e $filename)) {

            print "wget -O $filename 'http://www.1000steine.com/brickset/images/$id.jpg'\n";
            system "wget -O $filename 'http://www.1000steine.com/brickset/images/$id.jpg'";

            if (-e $filename) {
                my $lego_img_id;
                $sth_images->execute($id, $img, $lego_img_id)

            # We've already tried to download images...if we don't have one at this point then we aren't going to get one
            } else {
                $query = "DELETE FROM sets_image WHERE id='$id' ";
                $dbh->do($query);
            }
        }

#        my $tn_name = "/var/www/lego/html/sets/$id/tn.jpg";
#        if (-e $filename && !(-e $tn_name)) {
#            my $img_size = -s $filename;
#
#            if ($img_size > 10000) {
#                print "THUMBNAIL: $filename -> $tn_name\n";
#                my $t = new Image::Thumbnail(
#                    size         => 180,
#                    create      => 1,
#                    input        => $filename,
#                    outputpath => $tn_name,
#                );
#
#            } else {
#                print "THUMBNAIL COPY: $filename -> $tn_name\n";
#                system "cp $filename $tn_name";
#            }
#        }
    }
}

#
# The images from lego are huge so build a small and medium copy of each.  The small will be used for thumbnails.
#
sub resizeSetImages() {
    my $query = "SELECT id, img FROM sets_image WHERE id LIKE '9493-1' ";
    my $query = "SELECT id, img FROM sets_image";
    my $sth = $dbh->prepare($query);
    $sth->execute();
    my $i = 0;
    while (my @row = $sth->fetchrow_array()) {
        my $id = $row[0];
        my $img  = $row[1];

        my $filename = "/var/www/lego/html/sets/$id/$img";
        my $filename_minus_jpg = $filename;
        $filename_minus_jpg =~ s/\.jpg//;

        my $medium = "$filename_minus_jpg-medium.jpg";
        my $small = "$filename_minus_jpg-small.jpg";

        # dwalton - remove the 1...I goofed and ran this once without trimming the origin
        if (1 || !(-e $medium)) {
            print "$i: convert $filename -resize 600 $medium\n";
            system "convert $filename -resize 600 $medium";
            $i++;
        }

        if (1 || !(-e $small)) {
            print "$i: convert $filename -resize 180 $small\n";
            system "convert $filename -resize 180 $small";
            $i++;
        }
    }
}

#
#
sub resizePDFImages() {
    my $query = "SELECT id, filename FROM sets_manual WHERE id='10144-1'";
    my $query = "SELECT id, filename FROM sets_manual";
    my $sth = $dbh->prepare($query);
    $sth->execute();
    my $i = 0;
    while (my @row = $sth->fetchrow_array()) {
        my $id = $row[0];
        my $pdf  = $row[1];

        # dwalton - remove this
        system "rm -rf /var/www/lego/html/sets/$id/*-small-*";
        system "rm -rf /var/www/lego/html/sets/$id/*-medium-*";

        my $filename_with_pdf = "/var/www/lego/html/sets/$id/$pdf";
        my $filename_minus_pdf = $filename_with_pdf;
        $filename_minus_pdf =~ s/\.pdf//;
        open(FH, "ls $filename_minus_pdf*.jpg |") || die("\nERROR: Could not ls $filename_minus_pdf*.jpg");
        while(<FH>) {
            chomp();
            my $filename = $_;
            my $filename_minus_jpg = $filename;
            $filename_minus_jpg =~ s/\.jpg//;

            if ($filename =~ /small/ || $filename =~ /medium/) {
                next;
            }

            my $medium = "$filename_minus_jpg-medium.jpg";
            my $small = "$filename_minus_jpg-small.jpg";

            if (!(-e $medium)) {
                print "$i: convert $filename -resize 900 $medium\n";
                system "convert $filename -resize 900 $medium";
                $i++;
            }

            if (!(-e $small)) {
                print "$i: convert $filename -resize 180 $small\n";
                system "convert $filename -resize 180 $small";
                $i++;
            }

        }
        close FH;
    }
}

#processSetImages();
#trimSetImages();
#resizeSetImages();

#system "php /var/www/lego/tools/download-manuals.php";
#processSetManuals();
resizePDFImages();
