<?php
/**
* Test of authorisation object logic
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: auth.php,v 1.3 2006/01/05 02:32:07 stuart Exp $
* @package    Bumblebee
* @subpackage Tests
*/

ini_set('error_reporting', E_ALL);

$validusers = array('stuartp', 'stup1', 'stuart_p', 'stu-p', 's1234', '123s',
               'stu@p', 'stup@nano.net', 'stu.p', 'stu+p'
               );
$invalidusers = array('stu/p', 'stu\'p', 'stu"p', 'stu!p', 'stu%p', 'stu#p', 'stu=p', 
               'stu\\p', '-stup', '@stup', '', ' ', ' stup', 'stup ', 'stu p'
              );

$regexp = '/^[a-z0-9][a-z0-9@\-_.+]+$/';

foreach ($validusers as $name) {              
  printf("%-20s: %s\n", $name,
                (preg_match($regexp, $name) ? 'OK, VALID' : 'ERROR, INVALID'));
}

foreach ($invalidusers as $name) {              
  printf("%-20s: %s\n", $name,
                (preg_match($regexp, $name) ? 'ERROR, VALID' : 'OK, INVALID'));
}

?> 
