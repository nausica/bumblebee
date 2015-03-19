<?php
/**
* Database connection script
*
* Parses the {@link db.ini } file and connects to the database. 
* If the db login doesn't work then die() as there is no point in continuing
* without a database connection.
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: db.php,v 1.15.2.2 2006/05/12 11:57:36 stuart Exp $
* @package    Bumblebee
* @subpackage Misc
*/

$db_ini = parse_ini_file($CONFIGLOCATION.'db.ini');
$CONFIG['database']['dbhost']     = $db_ini['host'];
$CONFIG['database']['dbusername'] = $db_ini['username'];
$CONFIG['database']['dbpasswd']   = $db_ini['passwd'];
$CONFIG['database']['dbname']     = $db_ini['database'];

/**
* $TABLEPREFIX is added to the beginning of all SQL table names to allow database sharing
* @global string $TABLEPREFIX 
*/
$TABLEPREFIX = $db_ini['tableprefix'];

$dberrmsg = '<p>Sorry, I couldn\'t connect to the database, '
           .'so there\'s nothing I can presently do. '
           .'This could be due to a booking system misconfiguration, or a failure of '
           .'the database subsystem.</p>'
           .'<p>If this persists, please contact the '
           .'<a href="mailto:'.$ADMINEMAIL.'">booking system administrator</a>.</p>';

$DB_CONNECT_DEBUG = isset($DB_CONNECT_DEBUG) ? $DB_CONNECT_DEBUG : false;
$NON_FATAL_DB     = isset($NON_FATAL_DB)     ? $NON_FATAL_DB     : false;

// $connection = mysql_pconnect($CONFIG['database']['dbhost'], 
//                              $CONFIG['database']['dbusername'], 
//                              $CONFIG['database']['dbpasswd'])
//               or (! $NON_FATAL_DB && die ($dberrmsg.($DB_CONNECT_DEBUG ? mysql_error() : '')))
//               or                     trigger_error($dberrmsg.($DB_CONNECT_DEBUG ? mysql_error() : ''), E_USER_NOTICE);
// $db = mysql_select_db($CONFIG['database']['dbname'], $connection)
//               or (! $NON_FATAL_DB && die ($dberrmsg.($DB_CONNECT_DEBUG ? mysql_error() : '')))
//               or                     trigger_error($dberrmsg.($DB_CONNECT_DEBUG ? mysql_error() : ''), E_USER_NOTICE);

if (($connection = mysql_pconnect($CONFIG['database']['dbhost'], 
                             $CONFIG['database']['dbusername'], 
                             $CONFIG['database']['dbpasswd']) )
    && ($db = mysql_select_db($CONFIG['database']['dbname'], $connection)) ) {
  // then we successfully logged on to the database
} else {
  $errcode = $NON_FATAL_DB ? E_USER_NOTICE : E_USER_ERROR;
  $errmsg  = $dberrmsg;
  if ($DB_CONNECT_DEBUG) {
    $errmsg .= mysql_error() 
              .'<br />Connected using parameters <pre>'
              .print_r($CONFIG['database'],true).'</pre>';
  }
  trigger_error($errmsg, $errcode);
}

/**
* import SQL functions for database lookups
*/
require_once 'inc/formslib/sql.php';

?> 
