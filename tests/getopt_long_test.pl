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

# Add tests to see that test files are in package.xml
my %testprog_required;
foreach my $aref (@tests) {
    my $testprog = $$aref[1];
    next if $testprog eq 'package.xml' or $testprog eq 'subversion';
    unless (exists $testprog_required{$testprog}) {
        push @tests, [
            "file test_$testprog.php must be in package.xml",
            'package.xml',
            $testprog,
            'yes',
        ];
        # This file-in-subversion test could be expanded for other files...
        push @tests, [
            "file test_$testprog.php must be in Subversion",
            'subversion',
            $testprog,
            'yes',
        ];
        $testprog_required{$testprog} = 1;
    }
}
# Now read through the package XML and find the mentions of test files,
# saving them to a handy hash for later reference
my %testprog_in_package;
open my $fh, '<', '../package.xml' or die "Can't open ../package.xml: $!";
while (<$fh>) {
    $testprog_in_package{$1} = 'yes'
     if m{<file baseinstalldir="/tests" name="tests/test_(\w+).php" role="test" />};
}
close $fh;
#print 'required: (',join(',',sort keys %testprog_required),'), in package: ('
#    , join(',',sort keys %testprog_in_package),")\n";

# Test anywhere protocol:
print "1..",scalar @tests, "\n";
my $testno = 0;
foreach my $aref (@tests) {
	$testno++;
    my $actual;
	my ($description, $testprog, $args, $expected) = @$aref;
	$args =~ s{([|!])}{\\$1}g; # escape pipes in args for test_setup
	#print "php test_$testprog.php $args\n";
	if ($testprog eq 'package.xml') {
	    $actual = $testprog_in_package{$args} || 'no';
    } elsif ($testprog eq 'subversion') {
        my $fname = "test_$args.php";
        my $svninfo = qx{svn info $fname 2>/dev/null};
        $actual = ($svninfo =~ m{Name: $fname}) ? 'yes' : 'no';
	} else {
    	$expected =~ s{\\n}{\n}g; # turn \n into newlines for test_help
        $actual   = qx{php test_$testprog.php $args};
        chomp $actual;
    }
    if ($expected eq $actual) {
		print "ok $testno $description.\n";
	} else {
		print "not ok $testno $description: wanted $expected, got $actual.\n";
	}
}
