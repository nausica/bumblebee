<?php
/**
* Booking entry object for viewing booking
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: bookingentryro.php,v 1.5 2006/01/06 10:07:20 stuart Exp $
* @package    Bumblebee
* @subpackage DBObjects
*/

/** uses Booking object */
require_once 'inc/bookings/booking.php';
/** obtain data from database */
require_once 'inc/bookings/bookingdata.php';

/**
* Booking entry object for viewing booking
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class BookingEntryRO {
  /** @var integer   booking id number to display */
  var $id;
  /** @var Booking   booking data object */
  var $data;
  
  /**
  *  Create the new BookingEntryRO object, filling from db
  *
  * @param integer  $id   booking id number to look up
  */
  function BookingEntryRO($id) {
    $this->id = $id;
    $this->_fill();
  }

  /**
  * load data from database
  */
  function _fill() {
    $bookdata = new BookingData(array('id' => $this->id), true);
    $this->data = $bookdata->dataEntry();
  }

  /**
  * display data to user
  *
  * @param boolean $displayAdmin   display admin-only data
  * @param boolean $displayOwner   display booking-owner-only data
  */
  function display($displayAdmin, $displayOwner) {
    return $this->displayAsTable($displayAdmin, $displayOwner);
  }

  /**
  * display data to user in a table
  *
  * @param boolean $displayAdmin   display admin-only data
  * @param boolean $displayOwner   display booking-owner-only data
  */
  function displayAsTable($displayAdmin, $displayOwner) {
    $t = '<table class="tabularobject">';
    $t .= $this->data->displayInTable(2, $displayAdmin, $displayOwner);
    $t .= '</table>';
    return $t;
  }

} //class BookingEntryRO
