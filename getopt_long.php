<?php

# Getopt::Long in PHP.  An attempt to provide the features of the excellent
# Perl Getopt::Long module to PHP.  Written and copyright by Paul Wayper.
# Released to the world in December 2011.
#
# This software library is licensed under the GPL version 3.0.  If you do not
# have a copy of this license, you can find one at
# http://www.gnu.org/licenses/gpl-3.0.txt

# Version 1.0 - initial release.
# Version 1.1 - added array output for mandatory arguments

function GOCheckType($arg, $type) {
    if ($type == 's') {
        # Everything's a string
        return TRUE;
    } else if ($type == 'i') {
        # Use PHP to check its numericity
        return (floor($arg)+0 == $arg);
    } else {
        print("Warning: unknown type check '$type'.\n");
    }
}

function GetOptions($argDescriptions) {
	$debug = FALSE;
	# Preprocess argument descriptions to look up names and info
	$arg_lookup = array();
	# foreach key => val doesn't respect references - use keys only
	foreach (array_keys($argDescriptions) as $argdesc) {
		# Pull apart the arguments into a list of synonyms and then the
		# (optional) option information.
		# Make sure we reference the reference
		$opt_info = array('var' => &$argDescriptions[$argdesc]);
		# Get the synonyms and the optional options
	    preg_match('{^(\w+(?:\|\w+)*)([=:][sif]@?|\+)?$}', $argdesc, $matches);
		if (empty($matches)) {
			die("GetOptions Error: do not recognise description '$argdesc'\n");
		}
		$synonyms = $matches[1];
		if (count($matches) > 2) {
			$optstr = $matches[2];
			if ($optstr == '+') {
				$opt_info['opt'] = '+';
			} else {
				# Options of the form [=:][sif]@? - option type, variable type, destination
				$opt_info['opt'] = substr($optstr,0,1);
				$opt_info['type'] = substr($optstr,1,1);
				if (strlen($optstr) > 2) {
					$opt_info['dest'] = substr($optstr,2,1);
				}
				if ($debug) {
					print("Opt info opt = $opt_info[opt], type = $opt_info[type]\n");
				}
			}
		}
		foreach( explode('|', $synonyms) as $synonym) {
		    if (strlen($synonym) < 1) {
		        print("Warning: key $synonyms started or ended with |.\n");
		        continue;
		    }
			if ($debug) {
			    print("Putting synonym $synonym of $synonyms in arg_lookup\n");
		    }
			$arg_lookup[$synonym] = $opt_info;
		}
	}
	# Now go through the arguments.
	$unprocessed_args = array();
	$args = $_SERVER['argv'];
	array_shift($args); # Remove name of script from argument list.
	$i = 0; $numargs = count($args);
	while ($i < $numargs) {
		$arg = $args[$i];
		if ($debug) {
    		print("Processing argument $i: $arg\n");
	    }
		if ($arg == '--') {
			# Process no more arguments and exit while loop now.
			array_splice($unprocessed_args, count($unprocessed_args), 0, 
			 array_slice($args,$i+1));
			break;
		} else if (substr($arg,0,1) == '-') {
			# Starts with a - : does it start with --?
			if (substr($arg,0,2) == '--') {
				$opt = substr($arg,2);
			} else {
				$opt = substr($arg,1);
			}
		    if ($debug) {
    			print(" Looks like option $opt.\n");
	        }
			if (array_key_exists($opt,$arg_lookup)) {
		        if ($debug) {
    				print("  And it's an option we recognise\n");
	            }
				$opt_info = $arg_lookup[$opt];
				# Does it have any arguments?
				if (array_key_exists('opt',$opt_info)) {
					$opt = $opt_info['opt'];
					if ($opt == '=') { # mandatory argument
						$i++;
						# Is there still command line left?
						if ($i < $numargs) {
							# Yes: set the variable from the argument list
							# We're even allowed to take things that look
							# like options here!
							# Check its type here.
							if (array_key_exists('dest',$opt_info) and $opt_info['dest'] == '@') {
								# Explicitly require array
								# variable may not be array - convert if so
								if (is_array($opt_info['var'])) {
						            if ($debug) {
										print("  it takes an array parameter and is one: pushing $args[$i] to it\n");
					                }
									$opt_info['var'][] = $args[$i]; # Push to array
								} else {
						            if ($debug) {
										print("  it takes an array parameter and isn't one: setting its variable to an array of ($args[$i])\n");
					                }
									# Convert to two-value array.
									$opt_info['var'] = array($args[$i]);
								}
							} else if (is_array($opt_info['var'])) {
				                if ($debug) {
									print("  it takes a parameter and we've been given an array: pushing $args[$i] onto it\n");
			                    }
								# @ not specified but array reference given
								$opt_info['var'][] = $args[$i]; # Push to array
							} else {
				                if ($debug) {
									print("  it takes a parameter: setting its variable to $args[$i]\n");
			                    }
								$opt_info['var'] = $args[$i];
							}
						} else {
							# No: fail.
							die("GetOptions: argument $arg missing its parameter\n");
						}
					} else if ($opt == ':') { # optional argument
					    # Is there still another option left?
					    if ($i+1 == $numargs) {
					    	# No - no argument supplied, set the variable to 1
		                    if ($debug) {
    					    	print("  optional argument, none available: value 1\n");
	                        }
					    	$opt_info['var'] = 1;
					    } else {
					    	# Does the next option look like a flag?
					    	if (substr($args[$i+1],0,1) == '-') {
					    		# Yes - no argument supplied, set variable to 1
		                        if ($debug) {
    						    	print("  optional argument, next one starts with '-': value 1\n");
	                            }
						    	$opt_info['var'] = 1;
					    	} else {
					    		# No - it must be an argument, consume it
					    		$i++;
		                        if ($debug) {
    					    		print("  optional argument, one supplied, setting variable to $args[$i]\n");
	                            }
    							# Check its type here.
								$opt_info['var'] = $args[$i];
					    	}
					    }
					} else if ($opt == '+') { # incrementing argument
						$opt_info['var'] ++;
	                    if ($debug) {
    						print("  it's an incrementing argument, setting its variable to $opt_info[var]\n");
                        }
					}
				} else {
					# No args, just a boolean, set it:
		            if ($debug) {
    					print("  it's a boolean: setting its variable from $opt_info[var] to 1\n");
	                }
					$opt_info['var'] = 1;
				}
			} else {
				# Not a recognised argument argument: leave it unprocessed.
				$unprocessed_args[] = $arg;
			}
		} else {
			# Not an argument: leave it unprocessed.
			$unprocessed_args[] = $arg;
		}
		$i++;
	}
	return $unprocessed_args;
}

