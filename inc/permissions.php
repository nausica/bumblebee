<?php
/**
* Permission codes for actions
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: permissions.php,v 1.2 2006/01/16 23:52:33 stuart Exp $
* @package    Bumblebee
* @subpackage Misc
*/

$perm = 1;
// SYSTEM LEVEL FUNCTIONS: NORMAL USERS
/** Permission: Anyone can do it */
define('BBPERM_USER_ALL',      1);
/** Permission: user can change their own password */
define('BBPERM_USER_PASSWD',   ($perm<<=1));
/** Permission: user can masquerade as another user */
define('BBPERM_MASQ',          ($perm<<=1));

// SYSTEM LEVEL FUNCTIONS: ADMIN USERS
/** Permission: Admin user required (a user *never* has this permission) */
define('BBPERM_ADMIN',                 ($perm<<=1));
/** Permission: Permission to edit groups */
define('BBPERM_ADMIN_GROUPS',          ($perm<<=1) | BBPERM_ADMIN);
/** Permission: Permission to edit projects */
define('BBPERM_ADMIN_PROJECTS',        ($perm<<=1) | BBPERM_ADMIN);
/** Permission: Permission to edit users */
define('BBPERM_ADMIN_USERS',           ($perm<<=1) | BBPERM_ADMIN);
/** Permission: Permission to edit instruments */
define('BBPERM_ADMIN_INSTRUMENTS',     ($perm<<=1) | BBPERM_ADMIN);
/** Permission: Permission to edit consumables */
define('BBPERM_ADMIN_CONSUMABLES',     ($perm<<=1) | BBPERM_ADMIN);
/** Permission: Permission to record consumable usage */
define('BBPERM_ADMIN_CONSUME',         ($perm<<=1) | BBPERM_ADMIN);
/** Permission: Permission to edit costs */
define('BBPERM_ADMIN_COSTS',           ($perm<<=1) | BBPERM_ADMIN);
/** Permission: Permission to view deleted bookings */
define('BBPERM_ADMIN_DELETEDBOOKINGS', ($perm<<=1) | BBPERM_ADMIN);
/** Permission: Permission to collect email lists  */
define('BBPERM_ADMIN_EMAILLIST',       ($perm<<=1) | BBPERM_ADMIN);
/** Permission: Permission to export data */
define('BBPERM_ADMIN_EXPORT',          ($perm<<=1) | BBPERM_ADMIN);
/** Permission: Permission to send out billing reports  */
define('BBPERM_ADMIN_BILLING',         ($perm<<=1) | BBPERM_ADMIN);
/** Permission: Permission to backup database */
define('BBPERM_ADMIN_BACKUPDB',        ($perm<<=1) | BBPERM_ADMIN);

/** Permission: Admin user can do anything */
define('BBPERM_ADMIN_ALL',      -1);

// FINE-GRAINED INSTRUMENT PERMISSIONS
/** Permission: View instrument booking sheet */
define('BBPERM_INSTR_VIEW',           $perm=1);
/** Permission: View instrument booking sheet without restrictions on viewing future bookings */
define('BBPERM_INSTR_VIEW_FUTURE',    ($perm<<=1));
/** Permission: Book instrument */
define('BBPERM_INSTR_BOOK',           ($perm<<=1));
/** Permission: Book instrument any time into the future */
define('BBPERM_INSTR_BOOK_FUTURE',    ($perm<<=1));
/** Permission: Book instrument without timeslot restrictions */
define('BBPERM_INSTR_BOOK_FREE',      ($perm<<=1));
/** Permission: Delete own bookings with appropriate notice */
define('BBPERM_INSTR_UNBOOK',         ($perm<<=1));
/** Permission: Delete own bookings without restrictions for appropriate notice  */
define('BBPERM_INSTR_UNBOOK_PAST',    ($perm<<=1));
/** Permission: Delete others' bookings */
define('BBPERM_INSTR_UNBOOK_OTHER',   ($perm<<=1));

/** Permission: Instrument admin all functions */
define('BBPERM_INSTR_ALL',       -1);




// print 'BBPERM_USER_ALL='.BBPERM_USER_ALL.'<br/>';
// print 'BBPERM_USER_PASSWD='.BBPERM_USER_PASSWD.'<br/>';
// print 'BBPERM_MASQ='.BBPERM_MASQ.'<br/>';
// print 'BBPERM_ADMIN='.BBPERM_ADMIN.'<br/>';
// print 'BBPERM_ADMIN_GROUPS='.BBPERM_ADMIN_GROUPS.'<br/>';
// print 'BBPERM_ADMIN_PROJECTS='.BBPERM_ADMIN_PROJECTS.'<br/>';
// print 'BBPERM_ADMIN_USERS='.BBPERM_ADMIN_USERS.'<br/>';
// print 'BBPERM_ADMIN_INSTRUMENTS='.BBPERM_ADMIN_INSTRUMENTS.'<br/>';
// print 'BBPERM_ADMIN_CONSUMABLES='.BBPERM_ADMIN_CONSUMABLES.'<br/>';
// print 'BBPERM_ADMIN_CONSUME='.BBPERM_ADMIN_CONSUME.'<br/>';
// print 'BBPERM_ADMIN_COSTS='.BBPERM_ADMIN_COSTS.'<br/>';
// print 'BBPERM_ADMIN_DELETEDBOOKINGS='.BBPERM_ADMIN_DELETEDBOOKINGS.'<br/>';
// print 'BBPERM_ADMIN_EMAILLIST='.BBPERM_ADMIN_EMAILLIST.'<br/>';
// print 'BBPERM_ADMIN_EXPORT='.BBPERM_ADMIN_EXPORT.'<br/>';
// print 'BBPERM_ADMIN_BILLING='.BBPERM_ADMIN_BILLING.'<br/>';
// print 'BBPERM_ADMIN_BACKUPDB='.BBPERM_ADMIN_BACKUPDB.'<br/>';
// print 'BBPERM_ADMIN_ALL='.BBPERM_ADMIN_ALL.'<br/>';


?>