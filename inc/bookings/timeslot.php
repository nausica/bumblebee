<?php
/**
* Booking/Vacancy base object -- designed to be inherited by Vacancy and Booking
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: timeslot.php,v 1.11 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage Bookings
*/

/** date manipulation routines */
require_once 'inc/date.php';

/**
* Booking/Vacancy base object -- designed to be inherited by Vacancy and Booking
*
* @package    Bumblebee
* @subpackage Bookings
*/
class TimeSlot {
  /** @var SimpleDate   start of the slot as used for calculating graphical representation   */
  var $start;             
  /** @var SimpleDate   end of the slot as used for calculating graphical representation  */
  var $stop;              
  /** @var SimpleTime   unused?  */
  var $duration;      
  /** @var string       href to current page */
  var $href = '';
  /** @var string       html/css class to use for display */
  var $baseclass;
  /** @var boolean      time slot is disabled (instrument is unavailable)  */
  var $isDisabled=0;
  /** @var boolean      instrument is vacant  */
  var $isVacant = 0;
  /** @var boolean      timeslot is the start of a booking (bookings can go fro mone day to another)  */
  var $isStart = 0;
  /** @var SimpleDate   start of the slot for when time should be displayed   */
  var $displayStart;     
  /** @var SimpleDate   end of the slot for when time should be displayed  */
  var $displayStop; 
  /** @var boolean      start time is arbitrary from truncation due to db lookup */
  var $arb_start = false; 
  /** @var boolean      stop time is arbitrary from truncation due to db lookup */
  var $arb_stop  = false; 
  /** @var TimeSlotRule timeslot definitions */
  var $slotRule;
  
  /**
  *  Create a new timeslot to be superclassed by Booking or Vacancy object
  *
  * @param mixed  $start    start time and date (SimpleDate or string or ticks)
  * @param mixed  $stop     stop time and date (SimpleDate or string or ticks)
  * @param mixed  $duration duration of the slot (SimpleTime or string or ticks, 0 to autocalc)
  */
  function TimeSlot($start, $stop, $duration=0) {
    $this->start = new SimpleDate($start);
    $this->stop = new SimpleDate($stop);
    if ($duration==0) {
      $this->duration = new SimpleTime($this->stop->ticks - $this->start->ticks);
    } else {
      $this->duration = new SimpleTime($duration);
    }
  }

  /**
  *  Set the start/stop times of the slot
  *
  * @param SimpleDate  $start    start time and date 
  * @param SimpleDate  $stop     stop time and date 
  * @param SimpleTime  $duration duration of the slot 
  */
  function _TimeSlot_SimpleDate($start, $stop, $duration) {
    $this->start = $start;
    $this->stop = $stop;
    $this->duration = $duration;
  }

  /**
  * display the timeslot as a short table row
  */
  function displayShort() {
    return '<tr><td>'.get_class($this)
            .'</td><td>'.$this->start->datetimestring
            .'</td><td>'.$this->stop->datetimestring
            .'</td><td>'.$this->displayStart->datetimestring
            .'</td><td>'.$this->displayStop->datetimestring
            .'</td><td>'.$this->isStart
            .'</td></tr>'."\n";
  }

} //class TimeSlot
