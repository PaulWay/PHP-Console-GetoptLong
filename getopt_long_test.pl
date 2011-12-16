#!/usr/bin/perl -w

use warnings;
use strict;

while (<>) {
    chomp;
    next if m{^#};
    my ($desc, $args, $expected) = split(":",$_);
    next unless $desc and $args and $expected;
    my $actual = qx{php getopt_long.php $args};
    chomp $actual;
    my $result = ($expected eq $actual) ? "Success" : "fail";
    print "Test $desc: args=$args, expected $expected, actual $actual; result = $result\n";
}
