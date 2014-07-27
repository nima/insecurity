#!/usr/bin/perl
use strict;
use warnings;
use Text::CSV;
use File::Basename;

my %byzip;
my %bycity;

my $e = 0;
if($#ARGV == 0) {
    my @titles;
    my @columns;

    my $csv = Text::CSV->new();

    my $file = $ARGV[0];
    open (CSV, "<", $file) or die $!;
    while(<CSV>) {
        if ($csv->parse($_)) {
            if($. > 1) {
                @columns = $csv->fields();
                $byzip{ $titles[1] } = @columns;
            } else {
                @titles = $csv->fields();
            };
        } else {
            my $err = $csv->error_input;
            print "Failed to parse line: $err";
        }
    }
    close CSV;
} else {
    printf("Usage: %s <csv>\n", basename($0));
    $e = 1;
}

#for(my $i=0;$i<@titles;++$i) {
#    printf("%-32s $i: %s\n", $titles[$i], $columns[$i]);
#}

exit $e;
