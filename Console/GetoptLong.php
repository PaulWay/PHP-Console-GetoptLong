<?php
/**
 * Getopt::Long in PHP. An attempt to provide the features of the excellent
 * Perl Getopt::Long module to PHP.
 *
 * PHP version 5
 *
 * @category  Console
 * @package   Console_GetoptLong
 * @author    Paul Wayper <paulway@mabula.net>
 * @copyright 2012 Paul Wayper
 * @license   <licence URL> <lincence name>
 * @link      <pear package page URL>
 */

 /**
 * Console_GetoptLong
 *
 * @category  Console
 * @package   Console_GetoptLong
 * @author    Paul Wayper <paulway@mabula.net>
 * @copyright 2012 Paul Wayper
 * @license   <licence URL> <lincence name>
 * @version   Release: @package_version@
 * @link      <pear package page URL>
 */
class Console_GetoptLong
{


    /**
     * One line function comment.
     *
     * Info about how to use could go here, including lots of info on
     * the argument and the format of the return value.
     *
     * @param array $argDescriptions Short description here.
     *
     * @return array
     */
    function getOptions($argDescriptions)
    {
        $debug = false;

        // Preprocess argument descriptions to look up names and info
        $arg_lookup = array();

        // foreach key => val doesn't respect references - use keys only
        foreach (array_keys($argDescriptions) as $argdesc) {
            // Pull apart the arguments into a list of synonyms and then the
            // (optional) option information.
            // Make sure we reference the reference
            $optInfo = array('var' => &$argDescriptions[$argdesc]);

            // Get the synonyms and the optional options
            preg_match('{^(\w+(?:\|\w+)*)([=:][sif]@?|\+)?$}', $argdesc, $matches);
            if (empty($matches) === true) {
                die("GetOptions Error: do not recognise description '$argdesc'\n");
            }

            $synonyms = $matches[1];
            if (count($matches) > 2) {
                $optstr = $matches[2];
                if ($optstr === '+') {
                    $optInfo['opt'] = '+';
                } else {
                    // Options of the form
                    // [=:][sif]@? - option type, variable type, destination
                    $optInfo['opt']  = substr($optstr, 0, 1);
                    $optInfo['type'] = substr($optstr, 1, 1);
                    if (strlen($optstr) > 2) {
                        $optInfo['dest'] = substr($optstr, 2, 1);
                    }

                    if ($debug) {
                        print("Opt info opt = $optInfo[opt], type = $optInfo[type]\n");
                    }
                }
            }

            foreach (explode('|', $synonyms) as $synonym) {
                if (strlen($synonym) < 1) {
                    print("Warning: key $synonyms started or ended with |.\n");
                    continue;
                }

                if ($debug) {
                    print("Putting synonym $synonym of $synonyms in arg_lookup\n");
                }

                $arg_lookup[$synonym] = $optInfo;
            }
        }//end foreach

        // Now go through the arguments.
        $unprocessedArgs = array();
        $args = $_SERVER['argv'];

        // Remove name of script from argument list.
        array_shift($args);

        $i = 0;
        $numArgs = count($args);
        while ($i < $numArgs) {
            $arg = $args[$i];
            if ($debug) {
                print("Processing argument $i: $arg\n");
            }

            if ($arg === '--') {
                // Process no more arguments and exit while loop now.
                array_splice(
                    $unprocessedArgs,
                    count($unprocessedArgs),
                    0,
                    array_slice($args, $i + 1)
                );
                break;
            } else if (substr($arg, 0, 1) === '-') {
                // Starts with a - : does it start with --?
                if (substr($arg, 0, 2) == '--') {
                    $opt = substr($arg, 2);
                } else {
                    $opt = substr($arg, 1);
                }

                if ($debug) {
                    print(" Looks like option $opt.\n");
                }

                if (array_key_exists($opt, $arg_lookup) === true) {
                    if ($debug) {
                        print("  And it's an option we recognise\n");
                    }

                    $optInfo = $arg_lookup[$opt];

                    // Does it have any arguments?
                    if (array_key_exists('opt', $optInfo) === true) {
                        $opt = $optInfo['opt'];
                        if ($opt === '=') {
                            // mandatory argument
                            $i++;

                            // Is there still command line left?
                            if ($i < $numArgs) {
                                // Yes: set the variable from the argument list
                                // We're even allowed to take things that look
                                // like options here!
                                // Check its type here.
                                if (array_key_exists('dest', $optInfo) === true
                                    && $optInfo['dest'] === '@'
                                ) {
                                    // Explicitly require array
                                    // variable may not be array - convert if so
                                    if (is_array($optInfo['var']) === true) {
                                        if ($debug) {
                                            print("  it takes an array parameter and is one: pushing $args[$i] to it\n");
                                        }

                                        // Push to array
                                        $optInfo['var'][] = $args[$i]; 
                                    } else {
                                        if ($debug) {
                                            print("  it takes an array parameter and isn't one: setting its variable to an array of ($args[$i])\n");
                                        }

                                        // Convert to two-value array.
                                        $optInfo['var'] = array($args[$i]);
                                    }
                                } else if (is_array($optInfo['var']) === true) {
                                    if ($debug) {
                                        print("  it takes a parameter and we've been given an array: pushing $args[$i] onto it\n");
                                    }

                                    // @ not specified but array reference given
                                    // Push to array
                                    $optInfo['var'][] = $args[$i];
                                } else {
                                    if ($debug) {
                                        print("  it takes a parameter: setting its variable to $args[$i]\n");
                                    }

                                    $optInfo['var'] = $args[$i];
                                }
                            } else {
                                // No: fail.
                                die("GetOptions: argument $arg missing its parameter\n");
                            }//end if
                        } else if ($opt === ':') {
                            // optional argument
                            // Is there still another option left?
                            if (($i + 1) === $numArgs) {
                                // No - no argument supplied, set the variable to 1
                                if ($debug) {
                                    print("  optional argument, none available: value 1\n");
                                }

                                $optInfo['var'] = 1;
                            } else {
                                // Does the next option look like a flag?
                                if (substr($args[($i+1)], 0, 1) === '-') {
                                    // Yes - no argument supplied, set variable to 1
                                    if ($debug) {
                                        print("  optional argument, next one starts with '-': value 1\n");
                                    }

                                    $optInfo['var'] = 1;
                                } else {
                                    // No - it must be an argument, consume it
                                    $i++;
                                    if ($debug) {
                                        print("  optional argument, one supplied, setting variable to $args[$i]\n");
                                    }

                                    // Check its type here.
                                    $optInfo['var'] = $args[$i];
                                }
                            }//end if
                        } else if ($opt === '+') {
                            // incrementing argument
                            $optInfo['var'] ++;
                            if ($debug) {
                                print("  it's an incrementing argument, setting its variable to $optInfo[var]\n");
                            }
                        }
                    } else {
                        // No args, just a boolean, set it:
                        if ($debug) {
                            print("  it's a boolean: setting its variable from $optInfo[var] to 1\n");
                        }

                        $optInfo['var'] = 1;
                    }
                } else {
                    // Not a recognised argument argument: leave it unprocessed.
                    $unprocessedArgs[] = $arg;
                }
            } else {
                // Not an argument: leave it unprocessed.
                $unprocessedArgs[] = $arg;
            }
            $i++;
        }//end while

        return $unprocessedArgs;

    }//end getOptions()


}//end class
