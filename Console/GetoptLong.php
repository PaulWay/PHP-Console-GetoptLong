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
 * @license   http://www.php.net/license/3_01.txt PHP 3.01
 * @svn       $Id: $
 * @link      <pear package page URL>
 */

 /**
 * Console_GetoptLong
 *
 * @category  Console
 * @package   Console_GetoptLong
 * @author    Paul Wayper <paulway@mabula.net>
 * @copyright 2012 Paul Wayper
 * @license   http://www.php.net/license/3_01.txt PHP 3.01
 * @version   Release: 1.6.4
 * @link      <pear package page URL>
 */
class Console_GetoptLong
{

    /**
     * Are we debugging mode?
     *
     * @var    boolean
     * @access private
     */
    private static $_debug = false;

    /**
     * Lookup of type letters to words
     *
     * @var    array
     * @access private
     */
    private static $_typeLookup = array(
        's' => 'a string',
        'i' => 'an integer',
        'f' => 'a float',
    );
    
    /**
     * _debug - print string if in debugging mode
     * 
     * @param string $string the string to print
     *
     * @return none
     */
    private function _debug($string)
    {
        if (Console_GetoptLong::$_debug) {
            echo $string;
        }
    }
    
    /**
     * Check argument against type spec and return true if it is that type.
     * 
     * @param string $arg  the argument to check
     * @param string $type the type spec ('s', 'i' or 'f')
     *
     * @return boolean whether the argument is of the specified type.
     */
    private function _checkType($arg, $type)
    {
        if ($type == 's') {
            Console_GetoptLong::_debug("  Checking $arg is a string - duh\n");
            // Everything's a string
            return true;
        } else if ($type == 'i') {
            Console_GetoptLong::_debug("  Checking $arg is an integer\n");
            return (is_numeric($arg) && floor($arg) == $arg);
        } else if ($type == 'f') {
            Console_GetoptLong::_debug("  Checking $arg is a float\n");
            return is_numeric($arg);
        } else {
            // This is actually handled in the regexp now.
            print("Warning: unknown type check '$type'.\n");
        }
    }
    
    /**
     * _showHelp - show the user the help descriptions supplied.
     *
     * This function takes the descriptions of each argument and its supplied
     * 
     * @param array $argHelp Describing the arguments and their help text.
     *
     * @return none
     */
    
    private function _showHelp($argHelp)
    {
        echo "Usage: $_SERVER[PHP_SELF] options...\n";
        ksort($argHelp);
        foreach ($argHelp as $synonyms => $help) {
            echo "  $synonyms : $help[help]\n";
        }
    }
    
    /**
     * _setVariable - set the variable from the option's argument.
     *
     * Takes the pre-processed knowledge of this option, the option as
     * supplied on the command line, and the argument (whether from an
     * option=argument or from the rest of the command line).
     *
     * It checks the argument's type, if required, and dies if it's not
     * the correct type.
     *
     * It then puts the argument into the variable, handling cases where
     * we need to work with arrays. 
     * 
     * @param array  $optInfo  the option's pre-processed information.
     * @param string $option   the option as supplied on the command line.
     * @param string $argument the argument from the command line (somehow).
     *
     * @return none
     */
    private function _setVariable($optInfo, $option, $argument)
    {
        $var = &$optInfo['var'];
        if (! Console_GetoptLong::_checkType(
            $argument, $optInfo['type']
        )) {
            die(
                "$option argument requires "
                . Console_GetoptLong::$_typeLookup[
                    $optInfo['type']
                ] . "\n"
            );
        }
        if (array_key_exists('dest', $optInfo) === true
            && $optInfo['dest'] === '@'
        ) {
            // Explicitly require array
            // variable may not be array - convert if so
            if (is_array($var) === true) {
                Console_GetoptLong::_debug(
                    "  it takes an array parameter and "
                    . "is one: pushing $argument to it\n"
                );

                // Push to array
                $var[] = $argument; 
            } else {
                Console_GetoptLong::_debug(
                    "  it takes an array parameter and"
                    . " isn't one: setting its variable"
                    . " to an array of ($argument)\n"
                );

                // Start with an array.
                $var = array($argument);
            }
        } else if (is_array($var) === true) {
            Console_GetoptLong::_debug(
                "  it takes a parameter and we've been"
                . " given an array: pushing $argument"
                . " onto it\n"
            );

            // @ not specified but array reference given
            // Push to array
            $var[] = $argument;
        } else {
            Console_GetoptLong::_debug(
                "  it takes a parameter: setting its"
                . " variable to $argument\n"
            );

            $var = $argument;
        }
    }
    
    /**
     * getOptions - set referenced variables from argument descriptions.
     *
     * This is the only function you will usually call in this module.
     * It takes an array where the keys are descriptions of how each
     * option should be processed and the values are references to the
     * variable to set when that option is supplied on the command line.
     *
     * @param array $argDescriptions Describing the arguments to the program.
     *
     * The arguments are described in description => reference pairs.
     *
     * The description is a list of synonyms (separated by | characters)
     * possibly followed by a specifier for the arguments to the option.
     * That specifier starts with a = (mandatory) or : (optional), then
     * either i (integer), s (string) or f (floating point).  This can
     * also be followed by an @ symbol, meaning to store multiple 
     * values in an array.  Alternately, the specifier can be +, which
     * means that more than one of this option on the command line increments
     * the references variable, or !, which means that the option is a flag
     * but also takes a 'no' prefix (e.g. --sort and --nosort).
     *
     * Some examples are best supplied here:
     *
     * quiet|q          = a single flag, takes no arguments
     * ouput|o=s        = an option with a mandatory string argument
     * debug|d:i        = an option with an optional integer argument
     * input|i=s@       = take multiple instances, store in array
     * verbose|v+       = more -v options increment the verbosity
     * invert!          = takes --invert and --noinvert
     *
     * So we might ask for those arguments to be processed from the command
     * line with the following invocation:
     *
     * $args = Console_GetoptLong::getOptions(
     *      'quiet|q'       => &$quiet,
     *      'verbose|v+'    => &$verbose,
     *      'input|i=s@'    => &$inputs,
     *      'output|o=s'    => &$outupt,
     *      'debug|d+'      => &$debug,
     * );
     *
     * Argument descriptions can be in any order (naturally).  Each option
     * can have one or more synonyms, or none.  Both single dash (-) and
     * double dash (--) are taken to signify options of any length (i.e.
     * the above description allows you to specify -input and --i as well
     * as the more usual -i and --input).
     *
     * If you do not supply a @ descriptor, but reference an array, the items
     * will be put into the array automatically (i.e. the argument will be
     * treated as if described by @).
     *
     * Showing help: if you supply a keyed array instead of a variable
     * reference, and it contains the keys 'var' and 'help', then the 'var'
     * element will be taken as the variable reference and the 'help' element
     * will be taken as the help text to display for this option.
     * If any argument description value has this form, and the 'help' or 'h' 
     * options have not already been supplied, getOptions will automatically
     * add the 'help' and 'h' options to the list of options recognised and,
     * if supplied on the command line, will print a simple derived usage
     * explanation and exit cleanly.  Any option that you don't supply a 'help'
     * keyword for in this fashion will not be printed in the help display.
     * If you supply your own description that includes 'help' or 'h' as
     * synonyms, you're on your own and automated help will not come forth. 
     * 
     * TODO: handle --option=argument style options.
     * TODO: handle -abcd (where a, b, c and d are single letter options).
     *
     * @return array the remaining list of command line parameters that
     * weren't options or their arguments.  These can occur anywhere in the
     * command line, so (with the above argument description) it would be
     * valid to call the related program with the arguments:
     *
     * -v -v convert_to_proteins -i viruses.fasta -o viruses.prot
     *
     * Which would then return an array with the word 'convert_to_proteins'.
     *
     */
    
    function getOptions($argDescriptions)
    {

        // Preprocess argument descriptions to look up names and info
        $arg_lookup = array();

        $help_supplied = false; // Are we to generate help options?
        $argHelp = array();
        
        // foreach key => val doesn't respect references - use keys only
        foreach (array_keys($argDescriptions) as $argdesc) {
            // Pull apart the arguments into a list of synonyms and then the
            // (optional) option information.
            
            // If we've been given an array and it contains elements keyed
            // 'var' and 'help', then we're going to assume it's a help 
            // description and enable the help system (if the user hasn't
            // set one up).
            $this_has_help = false;
            if (is_array($argDescriptions[$argdesc])
                && array_key_exists('var', $argDescriptions[$argdesc])
                && array_key_exists('help', $argDescriptions[$argdesc])
            ) {
                $help_supplied = true;
                $this_has_help = true;
                // Make sure we reference the reference
                $optInfo = array(
                    'var'    => &$argDescriptions[$argdesc]['var'],
                );
            } else {
                // Take whatever reference we've got and store it.
                // Make sure we reference the reference
                $optInfo = array('var' => &$argDescriptions[$argdesc]);
            }

            // Get the synonyms and the optional options
            preg_match(
                '{^([\w-]+(?:\|[\w-]+)*)([=:][sif]@?|[+!])?$}',
                $argdesc, $matches
            );
            if (empty($matches)) {
                die("Do not recognise description '$argdesc'\n");
            }

            $synonyms = $matches[1];
            $optstr = '';
            if (count($matches) > 2) {
                $optstr = $matches[2];
                if (strlen($optstr) == 1) {
                    // Handles single-character descriptions like + and !
                    $optInfo['opt'] = $optstr;

                    Console_GetoptLong::_debug(
                        "Opt info opt = $optInfo[opt]\n"
                    );
                } else {
                    // Options of the form
                    // [=:][sif]@? - option type, variable type, destination
                    $optInfo['opt']  = substr($optstr, 0, 1);
                    $optInfo['type'] = substr($optstr, 1, 1);
                    if (strlen($optstr) > 2) {
                        $optInfo['dest'] = substr($optstr, 2, 1);
                    }

                    Console_GetoptLong::_debug(
                        "Opt info opt = $optInfo[opt], type = $optInfo[type]\n"
                    );
                }
            }
            // Now that we've got the synonyms and the option string, save all
            // that by the full list of synonyms for the help system if needed.
            if ($this_has_help) {
                $argHelp[$synonyms] = array(
                    'help'    => &$argDescriptions[$argdesc]['help'],
                    // other stuff here?
                );
            }

            foreach (explode('|', $synonyms) as $synonym) {
                // This is actually handled by the regexp now.
                if (strlen($synonym) < 1) {
                    print("Warning: key $synonyms started or ended with |.\n");
                    continue;
                }

                Console_GetoptLong::_debug(
                    "Putting synonym $synonym of $synonyms in arg_lookup\n"
                );

                // check for existing synonyms
                if (array_key_exists($synonym, $arg_lookup)) {
                    print("Warning: synonym $synonym declared twice - ignoring.\n");
                } else {
                    $arg_lookup[$synonym] = $optInfo;
                }
                if ($optstr === '!') {
                    // Add a 'no' prefix option to the list of synonyms
                    // for this option.  In its options it will recognise
                    // that it's been set as a negatable option.
                    // check for existing synonyms
                    if (array_key_exists("no$synonym", $arg_lookup)) {
                        print(
                            "Warning: synonym no$synonym declared twice - "
                            . "ignoring.\n"
                        );
                    } else {
                        $arg_lookup["no$synonym"] = $optInfo;
                    }
                    Console_GetoptLong::_debug(
                        "Got negatable option, added no$synonyms[0] option\n"
                    );
                }
            }
        }//end foreach
        
        // If we've got help descriptions supplied, add the help arguments
        // as lookup with our special magic value.
        if ($help_supplied) {
            // warn if help supplied in parameters and also as an option
            if (array_key_exists('help', $arg_lookup)) {
                echo "Warning: option 'help' already supplied, ignoring help "
                    . "supplied in targets\n";
            } else if (array_key_exists('h', $arg_lookup) ) {
                echo "Warning: option 'h' already supplied, ignoring help "
                    . "supplied in targets\n";
            } else {
                // Help supplied and the caller hasn't specified their own
                // option for it - let's handle that ourselves.
                $arg_lookup['help'] = 'help'; // magic keyword
                $arg_lookup['h']    = 'help';
            }
        }

        // Now go through the arguments.
        $unprocessedArgs = array();
        $args = $_SERVER['argv'];

        // Remove name of script from argument list.
        array_shift($args);

        $i = 0;
        $numArgs = count($args);
        while ($i < $numArgs) {
            $arg = $args[$i];
            Console_GetoptLong::_debug("Processing argument $i: $arg\n");

            if ($arg === '--') {
                // Process no more arguments and exit while loop now.
                Console_GetoptLong::_debug(
                    "Received double dash at argument $i: already "
                    . implode(',', $unprocessedArgs) . ", rest is " 
                    . implode(',', array_slice($args, $i + 1)) . "\n"
                );
                array_splice(
                    $unprocessedArgs,
                    count($unprocessedArgs),
                    0,
                    array_slice($args, $i + 1)
                );
                break;
            } else if (substr($arg, 0, 1) === '-') {
                // Starts with a - : does it start with --?
                $dashes = '';
                if (substr($arg, 0, 2) == '--') {
                    $option = substr($arg, 2);
                    $dashes = '--';
                } else {
                    $option = substr($arg, 1);
                    $dashes = '-';
                }

                Console_GetoptLong::_debug(" Looks like option $option.\n");

                $argInEquals = false;
                if (strpos($option, '=') > 0) { // must be at least one character
                    // we can't insert the value in the array, because
                    // several things will break - e.g. argument supplied to
                    // non-argument option
                    list($option, $argInEquals) = explode('=', $option);
                    // reconstruct full option with dashes (because people
                    // expect to be warned about -m, not -m=foo)
                    $arg = $dashes . $option;
                }
                if (array_key_exists($option, $arg_lookup) === true) {
                    Console_GetoptLong::_debug(
                        "  And it's an option we recognise\n"
                    );

                    $optInfo = $arg_lookup[$option];
                    // TODO: only handle help here if we've been asked to.
                    if ($optInfo === 'help') {
                        // magic keyword
                        Console_GetoptLong::_showHelp($argHelp);
                        exit(0);
                    }
                    // otherwise it's a normal reference
                    $var = &$optInfo['var'];

                    // Does it have any arguments?
                    if (array_key_exists('opt', $optInfo) === true) {
                        $opt = $optInfo['opt'];
                        if ($opt === '=') {
                            // mandatory argument
                            // $argInEquals may be = '', check for falseness
                            // explicitly.
                            if ($argInEquals !== false) {
                                Console_GetoptLong::_setVariable(
                                    $optInfo, $arg, $argInEquals
                                );
                            } else {
                                $i++;

                                // Is there still command line left?
                                if ($i >= $numArgs) {
                                    // No: fail.
                                    die("Argument $arg missing its parameter\n");
                                }//end if
                                Console_GetoptLong::_setVariable(
                                    $optInfo, $arg, $args[$i]
                                );
                            }
                        } else if ($opt === ':') {
                            // optional argument
                            if ($argInEquals !== false) {
                                Console_GetoptLong::_setVariable(
                                    $optInfo, $arg, $argInEquals
                                );
                            } elseif (($i + 1) === $numArgs) {
                                // Is there still another option left?
                                // No - no argument supplied, set the variable to 1
                                Console_GetoptLong::_debug(
                                    "  optional argument, none available: value 1\n"
                                );

                                Console_GetoptLong::_setVariable(
                                    $optInfo, $arg, 1
                                );
                            } else {
                                // Yes - Does the next option look like a flag?
                                if (substr($args[($i+1)], 0, 1) === '-') {
                                    // Yes - no argument supplied, set variable to 1
                                    Console_GetoptLong::_debug(
                                        "  optional argument, next one starts with"
                                        ." '-': value 1\n"
                                    );

                                    Console_GetoptLong::_setVariable(
                                        $optInfo, $arg, 1
                                    );
                                } else {
                                    // No - it must be an argument, consume it
                                    $i++;
                                    Console_GetoptLong::_debug(
                                        "  optional argument, one supplied, setting"
                                        . " variable to $args[$i]\n"
                                    );

                                    Console_GetoptLong::_setVariable(
                                        $optInfo, $arg, $args[$i]
                                    );
                                }
                            }//end if
                        } else if ($opt === '+') {
                            // incrementing argument
                            $var ++;
                            Console_GetoptLong::_debug(
                                "  it's an incrementing argument, setting its"
                                . " variable to $optInfo[var]\n"
                            );
                        } else if ($opt === '!') {
                            // a negatable argument - check if we've been
                            // given the no variant and set accordingly.
                            $var = (substr($option, 0, 2) === 'no') ? 0 : 1;
                            
                            Console_GetoptLong::_debug(
                                "  it's negatable: set to $var because of $option\n"
                            );
                        }
                    } else {
                        // No args, just a boolean, set it:
                        Console_GetoptLong::_debug(
                            "  it's a boolean: setting its variable to 1\n"
                        );

                        $var = 1;
                    }
                } else {
                    // Not a recognised argument argument: leave it unprocessed.
                    // TODO: maybe we should warn about an unrecognised option?
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
