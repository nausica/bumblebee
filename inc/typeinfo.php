<?php
/**
* functions for handling types, comparisons, conversions, validation etc
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: typeinfo.php,v 1.27.2.1 2006/05/16 15:05:14 stuart Exp $
* @package    Bumblebee
* @subpackage Misc
*/

/**
* If an array key is set, return that value, else return a default
*
* Combines isset and ternary operator to make for cleaner code that
* is quiet when run with E_ALL.
* 
* @param array &$a (passed by ref for efficiency only) array to lookup
* @param string $k  the key to be checked
* @param mixed $default (optional) the default value to return if not index not set
* @return mixed  returns either $a[$k] if it exists or $default
*/
function issetSet(&$a, $k, $default=NULL) {
  return (isset($a[$k]) ? $a[$k] : $default);
}

/**
* simple debugging function to print out arrays and objects
*
* uses print_r within HTML pre tags for easier inspection of classes and arrays
* @param mixed $v  object or array to print 
*/
function preDump($v) {
  #echo "<pre>".print_r($v,1)."</pre>\n";
  echo '<pre>';
  var_dump($v);
  #print_r($v);
  echo '</pre>'."\n";
}

/**
* debugging function to conditionally print data to the browser
* @param mixed $v  variable to be printed
* @param boolean $DEBUG   print data if true
*/
function echoData($v, $DEBUG=0) {
  global $VERBOSEDATA;
  if ($VERBOSEDATA || $DEBUG) {
    preDump($v);
  }
}


/**
* is variable composed purely of alphabetic data [A-Za-z_-]
* @param string $var string to be tested
* @return boolean 
*/
function is_alphabetic($var) {
  return preg_match('/^\w+$/', $var);
}

/**
* Quote data for passing to the database, enclosing data in quotes etc
*
* Fixes programatically generated data so that it is correctly escaped. Deals
* with magic_quotes_gpc to remove slashes so that the input is sensible and
* doesn't end up accummulating escape characters with multiple submissions.
* @param string $v string to be quoted
* @return string '$v' with slashes added as appropriate.
*/
function qw($v) {
  // magic-quotes-gpc is a pain in the backside: I would rather I was just given
  // the data the user entered.
  // We can't just return the data if magic_quotes_gpc is turned on because 
  // that would be wrong if there was programatically set data in there.
  if (get_magic_quotes_gpc()) { 
    // first remove any (partial or full) escaping then add it in properly
    $v = addslashes(stripslashes($v));
  } else {
    // just add in the slashes
    $v = addslashes($v);
  }
  return "'".$v."'";
}

/**
* Remove quoting around expressions and remove slashes in data to escape bad chars
*
* @param string $v string to be unquoted
* @return string unquoted string
*/
function unqw($v) {
  if (preg_match("/'(.+)'/", $v, $match)) {
    $v = $match[1];
  }
  return stripslashes($v);
}

/**
* quote words against XSS attacks by converting tags to html entities
*
* replace some bad HTML characters with entities to protext against 
* cross-site scripting attacks. the generated code should be clean of 
* nasty HTML
*
* @param string $v string to be quoted
* @return string $v with html converted to entities
*/
function xssqw($v) {
  // once again magic_quotes_gpc gets in the way
  if (get_magic_quotes_gpc()) { 
    // first remove any (partial or full) escaping then we'll do it properly below
    $v = stripslashes($v);
  }
  return htmlentities($v, ENT_QUOTES);
}

/**
* quote all elements of an array against XSS attacks using xssqw function
*
* @param array $a array of strings to be quoted
* @return array $a of strings quoted
*/
function array_xssqw($a) {
  return array_map('xssqw', $a);
}

/**
* tests if string is non-empty
*
* note that in PHP, '' == '0' etc so test has to use strlen
* @param string $v string to test for emptiness
* @return boolean
*/
function is_nonempty_string($v) {
  #echo "'val=$v' ";
  return !(strlen($v) == 0);
}

/**
* tests if string is a plausible member of a radio-button choice set
*
* @param string $v string to test
* @return boolean
*/
function choice_set($v) {
  #echo "'val=$v' ";
  return !($v == NULL || $v == '');
}

/**
* tests if string is a member of a radio button choice set
*
* @param string $v string to test
* @return boolean
*/
function is_valid_radiochoice($v) {
  #echo "'val=$v' ";
  return (choice_set($v) && is_numeric($v) && $v >= -1);
}

/**
* tests if string is a sensible email format
*
* does not test full RFC822 compliance or that the address exists, just that it looks
* like a standard email address with a username part @ and domain part with at least one dot
* @param string $v string to test for email format
* @return boolean
*/
function is_email_format($v) {
  #echo "'val=$v' ";
  #$pattern = '/^\w.+\@[A-Z_\-]+\.[A-Z_\-]/i';
  $pattern = '/^[_a-z0-9\-]+(?:\.[_a-z0-9\-]+)*@[a-z0-9\-]+(?:\.[a-z0-9\-]{2,})+$/i';
  return (preg_match($pattern, $v));
}

/**
* tests if string is number
*
* @param string $v string to test if it is a number
* @return boolean
*/
function is_number($v) {
  return is_numeric($v);
}

/**
* tests if string is a amount for a price
*
* @param string $v string to test if it is a valid cost
* @return boolean
* @todo strengthen this test?
*/
function is_cost_amount($v) {
   return is_numeric($v);
}

/**
* tests if string is valid date-time expression YYYY-MM-DD HH:MM
*
* @param string $v string to test it is a date-time string
* @return boolean
* @todo can this be relaxed to be more user-friendly without introducing errors
*/
function is_valid_datetime($v) {
  return (preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d/',$v));
}

/**
* tests if string is valid time expression HH:MM or HH:MM:SS format
*
* @param string $v string to test it is a time string
* @return boolean
* @todo can this be relaxed to be more user-friendly without introducing errors
*/
function is_valid_time($v) {
  return (preg_match('/^\d\d:\d\d/',$v) || preg_match('/^\d\d:\d\d:\d\d/',$v));
}

/**
* tests if string is valid time expression HH:MM or HH:MM:SS format other than 00:00:00
*
* @param string $v string to test it is a time string
* @return boolean
* @todo can this be relaxed to be more user-friendly without introducing errors
*/
function is_valid_nonzero_time($v) {
  return (preg_match('/^\d\d:\d\d/',$v) || preg_match('/^\d\d:\d\d:\d\d/',$v)) 
            && ! preg_match('/^00:00/',$v) && ! preg_match('/^00:00:00/',$v);
}

/**
* tests if a set of numbers add to 100 (set of percentages should add to 100)
*
* @param array $vs list of values to test if sum is 100
* @return boolean
*/
function sum_is_100($vs) {
  #echo "<br/>Checking sum<br/>";
  $sum=0;
  foreach ($vs as $v) {
    #echo "'$v', ";
    $sum += $v;
  }
  return ($sum == 100);
}


/*
echo "<pre>qw test\n";
$test = array();
$test[] = "test";
$test[] = "test data";
$test[] = "stuart's data";
$test[] = "magic quoted stuart\\'s data";
$test[] = "test";

foreach ($test as $t) {
  echo "$t => ". qw($t) ."\n";
}
*/

?> 
