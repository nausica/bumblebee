<?php
/**
* Obtains booking data from the database
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: bookingdata.php,v 1.11.2.1 2006/07/13 20:17:30 themill Exp $
* @package    Bumblebee
* @subpackage Bookings
*/

/** loads data into Booking objects */
require_once 'booking.php';

/**
* Obtains booking data from the database
*
* Can be used either to find all bookings that are within a time period
* or to look for a specific booking id. Note that when using time periods
* to define the query, a booking is within the query time if *any* part of 
* the booking period overlaps with *any* part of the query period.
*
* Can optionally exclude deleted bookings from the list.
*
* @package    Bumblebee
* @subpackage Bookings
*/
class BookingData {
  /** @var string   start of the query (SQL date-time string)  */
  var $start;
  /** @var string   stop of the query (SQL date-time string)  */
  var $stop;
  /** @var integer  instrument id number of interest  */
  var $instrument;
  /** @var integer  specific booking that is of interest */
  var $id;
  /** @var array    list of Booking objects returned for time period */
  var $bookinglist;
  /** @var Booking  single Booking object returned for id match */
  var $booking;
  /** @var boolean  sql errors are fatal */
  var $fatal_sql = 1;
  /** @var boolean  include deleted bookings in listing */
  var $includeDeleted = 0;
  
  /**
  * Obtain the booking listing within defined parameters
  *
  * @param array  $arr    data => value pairs where data can be
  *                       start, stop, instrument, id
  */
  function BookingData($arr, $includeDeleted=false) {
    $this->start = issetSet($arr,'start');
    $this->stop  = issetSet($arr,'stop');
    $this->instrument  = issetSet($arr,'instrument');
    $this->id  = issetSet($arr,'id');
    $this->includeDeleted = $includeDeleted;
    $this->_fill();
  }

  /**
  * Interrogate the database to get the bookings
  *
  * @global string  prefix prepended to all table names in database
  8 @todo mysql specific function
  */
  function _fill() {
    global $TABLEPREFIX;
    $q = 'SELECT bookings.id AS bookid,bookwhen,duration,'
        .'DATE_ADD(bookwhen, INTERVAL duration HOUR_SECOND) AS stoptime,'
        .'discount,log,comments,projectid,'
        .'userid,'
        .'users.name AS name, '
        .'users.username AS username, '
        .'users.email AS email, '
        .'users.phone AS phone, '
        .'bookedby AS masquserid, '
        .'masq.name AS masquser, '
        .'masq.username AS masqusername, '
        .'masq.email AS masqemail, '
        .'projects.name AS project '
        .'FROM '.$TABLEPREFIX.'bookings AS bookings '
        .'LEFT JOIN '.$TABLEPREFIX.'users AS users ON '
            .'bookings.userid=users.id '
        .'LEFT JOIN '.$TABLEPREFIX.'users AS masq ON '
            .'bookings.bookedby=masq.id '
        .'LEFT JOIN '.$TABLEPREFIX.'projects AS projects ON '
            .'bookings.projectid=projects.id '
        .'WHERE '.($this->includeDeleted ? '' : 'bookings.deleted<>1 AND ');
    if ($this->id) {
      $q .= 'bookings.id='.qw($this->id);
    } else {
      $q .= 'bookings.userid<>0 AND bookings.instrument='.qw($this->instrument).' '
            .'HAVING (bookwhen <= '.qw($this->start).' AND stoptime > '.qw($this->start).') '
            .'OR (bookwhen < '.qw($this->stop).' AND stoptime >= '.qw($this->stop).') '
            .'OR (bookwhen >= '.qw($this->start).' AND stoptime <= '.qw($this->stop).')'
           .' ORDER BY bookwhen';
    }
    
    if ($this->id) {
      $g = db_get_single($q, $this->fatal_sql);
      $this->booking = new Booking($g); 
    } else {
      $this->bookinglist = array();
      $sql = db_get($q, $this->fatal_sql);
      //FIXME: mysql specific function
      while ($g = mysql_fetch_array($sql)) {
        $this->bookinglist[] = new Booking($g); 
      }
    }
  }

  /**
  * obtain the list of bookings
  * @return array  list of Booking objects
  */
  function dataArray() {
    return $this->bookinglist;
  }

  /**
  * obtain the bookings
  * @return Booking booking object
  */
  function dataEntry() {
    return $this->booking;
  }

} //class BookingData
