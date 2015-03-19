<?php
/**
* Object for an individual booking
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: booking.php,v 1.13.2.1 2006/07/13 20:17:17 themill Exp $
* @package    Bumblebee
* @subpackage Bookings
*/

/** date manipulation routines */
require_once 'inc/date.php';
/** parent object */
require_once 'timeslot.php';

/**
* Object for an individual booking
*
* @package    Bumblebee
* @subpackage Bookings
*/
class Booking extends TimeSlot {
  /** @var integer    booking id number  */
  var $id;
  /** @var integer    percentage discount to be applied to the booking  */
  var $discount;
  /** @var string     log message for the instrument log book  */
  var $log;
  /** @var string     log message for the booking calendar  */
  var $comments;
  /** @var integer    project id number  */
  var $project;
  /** @var integer    user id number for user that will use instrument */
  var $userid;
  /** @var string     username of user for user that will use instrument */
  var $username;
  /** @var string     full name of user for user that will use instrument */
  var $name;
  /** @var string     email address for user that will use instrument */
  var $useremail;
  /** @var string     phone number for user that will use instrument */
  var $userphone;
  /** @var integer    user id number for user that booked the instrument */
  var $masquserid;
  /** @var integer    full name of user for user that booked the instrument */
  var $masquser;
  /** @var string     username of user for user that booked the instrument */
  var $masqusername;
  /** @var string     email address for user that booked the instrument */
  var $masqemail;
  
  /**
  *  Create a booking object
  *
  * @param array  $arr  key => value paids
  */
  function Booking($arr) {
    $this->TimeSlot($arr['bookwhen'], $arr['stoptime'], $arr['duration']);
    $isVacant = false;
    $this->id = $arr['bookid'];
    $this->discount = $arr['discount'];
    $this->log = $arr['log'];
    $this->comments = $arr['comments'];
    $this->project = $arr['project'];
    $this->userid = $arr['userid'];
    $this->username = $arr['username'];
    $this->name = $arr['name'];
    $this->useremail = $arr['email'];
    $this->userphone = $arr['phone'];
    $this->masquserid = $arr['masquserid'];
    $this->masquser = $arr['masquser'];
    $this->masqusername = $arr['masqusername'];
    $this->masqemail = $arr['masqemail'];

    #echo "Booking from ".$this->start->datetimestring." to ".$this->stop->datetimestring."<br />\n";
    $this->baseclass='booking';
  }

  /**
  * display the booking as a list of settings
  *
  * @param boolean   $displayAdmin   show admin-only information (discount etc)
  * @param boolean   $displayOwner   show owner-only information (project etc)
  * @return string html representation of booking
  */
  function display($displayAdmin, $displayOwner) {
    return $this->displayInTable(2, $displayAdmin, $displayOwner);
  }
  
  /**
  * display the booking as a list of settings
  *
  * @param boolean   $displayAdmin   show admin-only information (discount etc)
  * @param boolean   $displayOwner   show owner-only information (project etc)
  * @return string html representation of booking
  */
  function displayInTable($cols, $displayAdmin, $displayOwner) {
    $t = '<tr><td>Booking ID</td><td>'.$this->id.'</td></tr>'."\n"
       . '<tr><td>Start</td><td>'.$this->start->datetimestring.'</td></tr>'."\n"
       . '<tr><td>Stop</td><td>'.$this->stop->datetimestring.'</td></tr>'."\n"
       . '<tr><td>Duration</td><td>'.$this->duration->timestring/*.$bookinglength*/.'</td></tr>'."\n"
       . '<tr><td>User</td><td><a href="mailto:'.$this->useremail.'">'.$this->name.'</a> ('.$this->username.')</td></tr>'."\n"
       . '<tr><td>Comments</td><td>'.$this->comments.'</td></tr>'."\n"
       . '<tr><td>Log</td><td>'.$this->log.'</td></tr>'."\n";
    if ($displayAdmin) {
      if ($this->masquser) {
        $t .= '<tr><td>Booked by</td><td><a href="mailto:'.$this->masqemail.'">'.$this->masquser.'</a> ('.$this->masqusername.')</td></tr>'."\n";
      }
    }
    if ($displayAdmin || $displayOwner) {
      $t .= '<tr><td>Project</td><td>'.$this->project.'</td></tr>'."\n";
      if ($this->discount) {
        $t .= '<tr><td>Discount</td><td>'.$this->discount.'</td></tr>'."\n";
      }
    }
    return $t;
  }

  /**
  * display the booking as a single cell in a calendar
  *
  * @global string base path to the installation
  * @global array  system config
  *
  * @return string html representation of booking
  */
  function displayInCell(/*$isadmin=0*/) {
    global $BASEPATH, $CONFIG;
    $start = isset($this->displayStart) ? $this->displayStart : $this->start;
    $stop  = isset($this->displayStop)  ? $this->displayStop  : $this->stop;
    $timedescription = $start->datetimestring.' - '.$stop->datetimestring;
    //$timedescription = $this->start->timestring.' - '.$this->stop->timestring;
    $isodate = $start->datestring;
    $t = '';
    $t .= "<div style='float:right;'><a href='$this->href&amp;isodate=$isodate&amp;bookid=$this->id' title='View or edit booking $timedescription' class='but'><img src='$BASEPATH/theme/images/editbooking.png' alt='View/edit booking $timedescription' class='calicon' /></a></div>";
    // Finally include details of the booking:
    $t .= '<div class="calbookperson">'
         .'<a href="mailto:'.$this->useremail.'">'
         .$this->name.'</a></div>';
    if (isset($CONFIG['calendar']['showphone']) && $CONFIG['calendar']['showphone']) {
      $t .= '<div class="calphone">'
          .xssqw($this->userphone)
          .'</div>';
    }
    if ($this->comments) {
      $t .= '<div class="calcomment">'
          .xssqw($this->comments)
          .'</div>';
    }
    return $t;
  }

  /**
  * work out the title (start and stop times) for the booking for display
  *
  * @return string title
  */
  function generateBookingTitle() {
    $start = isset($this->displayStart) ? $this->displayStart : $this->start;
    $stop  = isset($this->displayStop)  ? $this->displayStop  : $this->stop;
    return 'Booking from '. $start->datetimestring
         .' - '. $stop->datetimestring;
  }

} //class Booking
