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
 * @version   Release: @package_version@
 * @link      <pear package page URL>
 *
 * For the main documentation on how to use this module, see the documentation
 * for the 'getOptions' command or the README file supplied with this module.
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
     * Should we warn, die, ignore, or put on the stack an option (i.e. with a
     * - or -- prefix) that the user supplies on the command line that the
     * caller hasn't recognised?
     *
     * @var    string
     * @access private
     */
    private static $_unkOptHand = 'arg';
    
    /**
     * setUnknownOptionHandling - set how we handle getting an unknown option.
     *
     * We can handle an option supplied by the user that the caller hasn't
     * recognised in a number of ways:
     *
     * arg:     treat it as another non-option argument and put it in the 
     *          returned array.
     * warn:    warn the user about it but continue.
     * die:     stop processing entirely.
     * ignore:  discard it from processing entirely.
     *
     * Any string other than these will be ignored.
     * 
     * @param string $method The requested method for handling unknown options.
     *
     * @return none
     */
    function setUnknownOptionHandling($method = 'arg')
    {
        if ($method == 'arg' or $method == 'ignore'
            or $method == 'warn' or $method == 'die'
        ) {
            Console_GetoptLong::$_unkOptHand = $method;
        }
    }    
    
    /**
     * Should we check unrecognised options to be checked whether they're
     * possibly made up of single letter options?
     *
     * @var    boolean
     * @access private
     */
    private static $_allowMultiOptCheck = true;

    /**
     * setAllowMultipleOptionsCheck - set whether we allow multiple flag options
     * in one 'agglomerated' option.
     *
     * @param boolean $allow Whether or not multiple flags can be agglomerated
     *
     * @return none
     */
    function setAllowMultipleOptionsCheck($allow = true)
    {
        Console_GetoptLong::$_allowMultiOptCheck = $allow;
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
     * _optionIsSet - remember which argument descriptions have been set
     *
     * We want to catch the case where a person sets an option via a flag
     * on the command line, and not set it again when reading through the
     * unflagged arguments.  Annoyingly, it's much easier to have a
     * separate global variable to remember this than to try and remember
     * it in the option info, because of PHP's lack of local loop variables.
     * It's a long story - ask Paul about it sometime.
     *
     * @var    array
     * @access private
     */
    private static $_optionIsSet = array();
    
    /**
     * _checkMultiOpts - can this option be constructed out of single letters?
     *
     * Many older programs - tar being a classic example - allow many single
     * letter arguments to be glommed into one - for example, tar -cvfj is
     * equivalent to tar -c -v -f -j.  Here we only allow this to occur if
     * every single letter is an option that does not require an argument, to
     * avoid confusion about which (following) argument relates to which option.
     *
     * This is called from the if/else clause that steps through the
     * possibilities of option recognition, so we simply accept the string to
     * check and return true or false.  The caller can then step through the
     * letters with confidence.  We don't check $_allowMultiOptCheck here, the
     * caller can do that.
     *
     * @param array  $arg_lookup the option information array.
     * @param string $str        the string to check for single-letter options.
     * 
     * @return boolean whether all the letters were single-letter options
     */
    private function _checkMultiOpts($arg_lookup, $str)
    {
        Console_GetoptLong::_debug("   Checking '$str' for agglomerated flags\n");
        for ($c = 0; $c < strlen($str); $c++) {
            // if we don't have an option like that, fail now
            $letter = substr($str, $c, 1);
            if (! array_key_exists($letter, $arg_lookup)) {
                return false;
            }
            // if we have an option it has to not take any arguments
            // (this may be something we can handle in the future though)
            if (array_key_exists('opt', $arg_lookup[$letter])
                and ($arg_lookup[$letter]['opt'] == ':'
                or $arg_lookup[$letter]['opt'] == '=')
            ) {
                return false;
            }
        }
        Console_GetoptLong::_debug("   ... it is!\n");
        return true;
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
        Console_GetoptLong::_debug(
            " at _setVariable([" . implode(',', array_keys($optInfo)) 
            . "], $option, $argument)\n"
        );
        $var = &$optInfo['var'];
        if (array_key_exists('type', $optInfo)
            and ! Console_GetoptLong::_checkType($argument, $optInfo['type'])
        ) {
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
                    "  it takes an array parameter and isn't one: setting its"
                    . " variable to an array of ($argument)\n"
                );

                // Start with an array.
                $var = array($argument);
            }
        } else if (is_array($var) === true) {
            Console_GetoptLong::_debug(
                "  it takes a parameter and we've been given an array:"
                . " pushing $argument onto it\n"
            );

            // @ not specified but array reference given
            // Push to array
            $var[] = $argument;
        } else {
            Console_GetoptLong::_debug(
                "  it takes a parameter: setting its variable to $argument\n"
            );

            $var = $argument;
        }
        Console_GetoptLong::_debug("  we've set it now.\n");
        Console_GetoptLong::$_optionIsSet[$optInfo['descript']] = true;
    }

    /**
     * _setOrderedUnflaggedArgument - what it says on the tin.
     *
     * This is called in two places - one as the special case for the last
     * argument on the command line, named '-1', and then for each ordered
     * unflagged option thereafter.  It checks whether the variable that was
     * associated with this ordered unflagged argument has already been set,
     * and if not it sets it and removes it from the list of arguments.
     *
     * @param array  $pos              one-based array index of argument
     * @param array  $optInfo          standard options-info array entry
     * @param array  &$unprocessedArgs array of unprocessed arguments
     * @param string $argDesc          description of this argument's place
     *
     * @return none
     */
    private function _setOrderedUnflaggedArgument(
        $pos, $optInfo, &$unprocessedArgs, $argDesc
    ) {
        Console_GetoptLong::_debug(
            " Checking that we have an argument in position $pos.\n"
        );
        if (array_key_exists($pos-1, $unprocessedArgs)) {
            Console_GetoptLong::_debug(
                "  Yes has variable been set?\n"
            );
            if (array_key_exists(
                $optInfo['descript'],
                Console_GetoptLong::$_optionIsSet
            )) {
                Console_GetoptLong::_debug(
                    "  It's already set - nothing to do\n"
                );
            } else {
                // Set the variable - cheat on the name of the option
                Console_GetoptLong::_setVariable(
                    $optInfo, $argDesc,
                    $unprocessedArgs[$pos-1]
                );
                // Remove it from the unprocessed arguments list
                Console_GetoptLong::_debug(
                    "  Removing argument $pos from remaining arguments.\n"
                );
                array_splice($unprocessedArgs, $pos-1, 1);
            }
        } else {
            Console_GetoptLong::_debug(
                "  No - is it a mandatory argument and not already set?\n"
            );
            // We can assert that it has a type, since the initial
            // processing only allows mandatory and optional arguments
            // to have unflagged ordered synonyms
            if ($optInfo['opt'] == '='
                and ! array_key_exists(
                    $optInfo['descript'],
                    Console_GetoptLong::$_optionIsSet
                )
            ) {
                die(
                    "Mandatory argument required for $argDesc.\n"
                );
            }
            // else optional argument is blank - which is a valid
            // value.  Should it be set to 1, though, as optional
            // arguments are if they don't get given a value?
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
     * @param array $args            Optional 'command line' to process.
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
     * Normally the caller will not need to use the $args array, and in this
     * case the argument list will be taken from the command line.  However, if
     * the caller wants to process a list of arguments of their own, this list
     * can be passed as the second parameter to getOptions.
     *
     * To pick up options from the processed argument list in order, use the
     * synonym '_1' (or '_2', or in general '_(\d+)').  After all flagged
     * options are processed, if any such synonyms are found, they will be
     * taken from the related place in the array, numbered from 1.  Places
     * not numbered are ignored - so if you have _1, _2 and _4, the third
     * argument will be left in the argument list to be passed back.
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
    
    function getOptions($argDescriptions, Array $args = Array())
    {
        // Set debug if environment requests it
        if (getenv('Console_GetoptLong_Debug')) {
            Console_GetoptLong::$_debug = true;
        }

        // Preprocess argument descriptions to look up names and info
        $arg_lookup = array();

        $help_supplied = false; // Are we to generate help options?
        $argHelp = array();
        
        // Ordered unflagged arguments.  They're put in by number, but we
        // sort them before use because PHP arrays retain the order that
        // elements are put in (like a Perl Tied Hash::Ordered).
        $ordered_unflagged_args = array();
        
        // foreach key => val doesn't respect references - use keys only
        foreach (array_keys($argDescriptions) as $argdesc) {
            // Pull apart the arguments into a list of synonyms and then the
            // (optional) option information.
            
            // If we've been given an array and it contains elements keyed
            // 'var' and 'help', then we're going to assume it's a help 
            // description and enable the help system (if the user hasn't
            // set one up).
            $this_has_help = false;
            
            // Remember the argument description - it's unique
            $optInfo = array('descript' => $argdesc);
            
            // Make sure we reference the variable given so we set it later
            // Have we been given help text for this option?
            if (is_array($argDescriptions[$argdesc])
                && array_key_exists('var', $argDescriptions[$argdesc])
                && array_key_exists('help', $argDescriptions[$argdesc])
            ) {
                // Yes - process the help info later, and store the var
                $help_supplied = true;
                $this_has_help = true;
                // Make sure we reference the reference
                $optInfo['var'] = &$argDescriptions[$argdesc]['var'];
            } else {
                // No - take whatever reference we've got and store it.
                $optInfo['var'] = &$argDescriptions[$argdesc];
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
                        "Options $synonyms will get opt:$optInfo[opt],"
                        . " type:$optInfo[type]\n"
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

                // check for ordered unflagged synonym
                preg_match('{_([1-9]|-1)}', $synonym, $matches);
                if (! empty($matches)) {
                    $position = $matches[1];
                    Console_GetoptLong::_debug(
                        " Putting ordered unflagged option for position $position\n"
                    );
                    if ((! array_key_exists('opt', $optInfo))
                        or ($optInfo['opt'] !== '=' and $optInfo['opt'] !== ':')
                    ) {
                        die(
                            "Ordered unflagged option $position in '$synonyms'"
                            . " must take an argument."
                        );
                    }
                    if (array_key_exists($position, $ordered_unflagged_args)) {
                        print(
                            "Warning: ordered unflagged option $synonym"
                            . " declared again - ignoring declaration in "
                            . "'$synonyms'\n"
                        );
                    } else {
                        $ordered_unflagged_args[$position] = $optInfo;
                    }
                    continue; // foreach synonym - no need to check other things
                }
                // check for existing synonyms
                if (array_key_exists($synonym, $arg_lookup)) {
                    print("Warning: synonym $synonym declared twice - ignoring.\n");
                } else {
                    $arg_lookup[$synonym] = $optInfo;
                    Console_GetoptLong::_debug(
                        " Putting synonym $synonym of $synonyms in arg_lookup\n"
                    );
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
                        Console_GetoptLong::_debug(
                            " Got negatable option, added no$synonyms[0] option\n"
                        );
                    }
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
        // If we haven't been given an args array in the call, get it from
        // the server's command line
        if (count($args) == 0) {
            Console_GetoptLong::_debug(
                "No args list given by caller, getting them from ARGV."
                ."  This is normal\n"
            );
            $args = $_SERVER['argv'];
        }

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
                    "Received double dash at argument $i: ( "
                    . implode(',', $unprocessedArgs)
                    . ") so far unprocesed, rest is (" 
                    . implode(',', array_slice($args, $i + 1)) . ")\n"
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
                    if ($optInfo === 'help' // our magic keyword
                        and $help_supplied  // help has already been supplied
                    ) { 
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
                            // incrementing argument (then display it in debug)
                            $var ++;
                            Console_GetoptLong::_debug(
                                "  it's an incrementing argument, setting its"
                                . " variable to $var\n"
                            );
                            Console_GetoptLong::$_optionIsSet[
                                $optInfo['descript']
                            ] = true;
                        } else if ($opt === '!') {
                            // a negatable argument - check if we've been
                            // given the no variant and set accordingly.
                            $var = (substr($option, 0, 2) === 'no') ? 0 : 1;
                            
                            Console_GetoptLong::_debug(
                                "  it's negatable: set to $var because of $option\n"
                            );
                            Console_GetoptLong::$_optionIsSet[
                                $optInfo['descript']
                            ] = true;
                        }
                    } else {
                        // No args, just a boolean, set it:
                        Console_GetoptLong::_debug(
                            "  it's a boolean: setting its variable to 1\n"
                        );
                        $var = 1;
                        Console_GetoptLong::$_optionIsSet[
                            $optInfo['descript']
                        ] = true;
                    }
                } else if (substr($arg, 1, 1) != '-'
                    and array_key_exists(substr($arg, 1, 1), $arg_lookup)
                    and array_key_exists('opt', $arg_lookup[substr($arg, 1, 1)])
                    and $arg_lookup[substr($arg, 1, 1)]['opt'] == '='
                ) {
                    // Only single dash, and a single-letter option, and it takes
                    // a mandatory argument, and isn't already a longer option
                    // (which is true since we got here) it's a single-
                    // letter-plus-argument option - parameter is rest of argument
                    $val = substr($arg, 2);
                    $fullarg = substr($arg, 0, 2);
                    $shortarg = substr($arg, 1, 1);
                    $optInfo = $arg_lookup[$shortarg];
                    Console_GetoptLong::_setVariable($optInfo, $fullarg, $val);
                } else if (Console_GetoptLong::$_allowMultiOptCheck
                    and Console_GetoptLong::_checkMultiOpts($arg_lookup, $option)
                ) {
                    // A single option that's made up of multiple single letter
                    // options - process each option individually, allow repeats.
                    for ($i = 0; $i < strlen($option); $i++) {
                        $letter = substr($option, $i, 1);
                        // pretend that we were given the -$letter option; these
                        // can only be flags at the moment so just set them to 1.
                        Console_GetoptLong::_debug(
                            "  setting flag for de-agglomerated $letter\n"
                        );
                        $var = &$arg_lookup[$letter]['var'];
                        $var = 1;
                    }
                } else {
                    // Not a recognised argument argument: what do we do with it?
                    if (Console_GetoptLong::$_unkOptHand == 'arg') {
                        // push it on the unprocessed arguments array
                        $unprocessedArgs[] = $arg;
                    } else if (Console_GetoptLong::$_unkOptHand == 'warn') {
                        // throw the user a warning
                        echo "Warning: unrecognised option $arg\n";
                    } else if (Console_GetoptLong::$_unkOptHand == 'die') {
                        // throw the user an error
                        die("Error: unrecognised option $arg\n");
                    }
                    // 'ignore' - just discard it
                }
            } else {
                // Not an argument: leave it unprocessed.
                $unprocessedArgs[] = $arg;
            }
            $i++;
        }//end while

        if (! empty($ordered_unflagged_args)) {
            Console_GetoptLong::_debug(
                "Before ordered unflagged processing, unprocessed arguments are ("
                . implode(', ', $unprocessedArgs)
                . ")\n"
            );

            // Process ordered unflagged options from remaining command line
            Console_GetoptLong::_debug(
                'Processing ' . count($ordered_unflagged_args)
                . " ordered unflagged arguments ("
                . implode(':',array_keys($ordered_unflagged_args))
                . ").\n"
            );
            // Do we have a '-1' ordered option - i.e. the last argument
            // on the command line?  If so, process it first and remove it
            // from the list of ordered unflagged arguments
            if (array_key_exists('-1', $ordered_unflagged_args)) {
                // Remember, position is a one-based array index - no decrement
                $pos = count($unprocessedArgs);
                Console_GetoptLong::_setOrderedUnflaggedArgument(
                    $pos, $ordered_unflagged_args['-1'],
                    $unprocessedArgs, 'last command line parameter'
                );
                // Can't remove this option from the ordered unflagged options
                // because array_splice mangles array indexes.  Ignore it later.
            }
            // Read arguments in order, starting from the back.  This may sound
            // strange, but means we can splice the elements out of the array
            // without disturbing the order, thus processing the array in one go.
            krsort($ordered_unflagged_args);
            foreach ($ordered_unflagged_args as $pos => $optInfo) {
                if ($pos == "-1") { continue; }
                // We've numbered from 1, but array keys are from zero
                Console_GetoptLong::_setOrderedUnflaggedArgument(
                    $pos, $optInfo,
                    $unprocessedArgs,
                    "command line parameter $pos"
                );
            }
        }

        Console_GetoptLong::_debug(
            "Handing ("
            . implode(', ', $unprocessedArgs)
            . ") back as remaining arguments\n"
        );
        return $unprocessedArgs;

    }//end getOptions()


}//end class
