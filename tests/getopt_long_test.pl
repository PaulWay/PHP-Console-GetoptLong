#!/usr/bin/perl -w

use warnings;
use strict;

my @tests;

# Read all tests so we know how many we're doing.
while (<>) {
    chomp;
    next if m{^#};
    my @args = split(";",$_);
    next unless scalar @args == 4;
    push @tests, \@args;
}

# Test anywhere protocol:
print "1..",scalar @tests, "\n";
my $testno = 0;
foreach my $aref (@tests) {
	$testno++;
	my ($description, $testprog, $args, $expected) = @$aref;
	$args =~ s{([|!])}{\\$1}g; # escape pipes in args for test_setup
	$expected =~ s{\\n}{\n}g; # turn \n into newlines for test_help
	#print "php test_$testprog.php $args\n";
    my $actual = qx{php test_$testprog.php $args};
    chomp $actual;
    if ($expected eq $actual) {
		print "ok $testno $description.\n";
	} else {
		print "not ok $testno $description: wanted $expected, got $actual.\n";
	}
}
