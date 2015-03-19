<?php
/**
* Test of authorisation object logic
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: timeslotrule.php,v 1.7 2006/01/05 02:32:08 stuart Exp $
* @package    Bumblebee
* @subpackage Tests
*/


$INC = ini_get('include_path');
ini_set('include_path', $INC.':../../../');

/** include the class we have to test! */
include_once '../timeslotrule.php';
include_once 'inc/typeinfo.php';

$p = array (
    #'[0-6]<00:00-08:00/*;08:00-13:00/1;13:00-18:00/1;18:00-24:00/*>',
    #'[0]<00:00-24:00/0>[1-5]<00:00-09:00/0;09:00-17:00/8;17:00-24:00/0>[6]<>',
    #'[0]<>[1-5]<00:00-09:00/*;09:00-17:00/8;17:00-24:00/*>[6]<>',
    #'[0]<>[1-5]<00:00-09:00/0;09:00-13:00/4;13:00-17:00/2;17:00-33:00/1>[6]<>',
    #'[0]<>[1-5]<09:00-13:00/4;13:00-17:00/2;17:00-33:00/1,Overnight bookings>[6]<>'
    '[0]<00:00-24:00/0,Unavailable >[1]<00:00-09:00/0,Unavailable ;09:00-12:00/6;12:00-17:00/3;17:00-24:00/0,Unavailable >[2]<00:00-09:00/0,Unavailable;09:00-17:00/16;17:00-24:00/0,Unavailable>[3]<00:00-09:00/0,Unavailable ;09:00-17:00/8;17:00-24:00/0,Unavailable>[4]<00:00-09:00/0,Unavailable ;09:00-17:00/8;17:00-24:00/0,Unavailable>[5]<00:00-09:00/0,Unavailable ;09:00-17:00/2,Honours Priority;17:00-24:00/0,Unavailable>[6]<00:00-24:00/0,Unavailable>'
    );

foreach ($p as $pic) { 
  $at = new TimeSlotRule($pic);
  echo $at->dump(0);
}

#$findtest = new TimeSlotRule($p[3]);
#$findtest->findNextSlot(new SimpleDate(
