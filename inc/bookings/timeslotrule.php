<?php
/**
* Timeslot validation based on rules passed to us (presumably from an SQL entry)
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: timeslotrule.php,v 1.21.2.2 2006/05/16 15:05:14 stuart Exp $
* @package    Bumblebee
* @subpackage Bookings
*/

/** date manipulation routines */
require_once 'inc/date.php';
/** timeslot object for bookings/vacancies */
require_once 'timeslot.php';

/** date-time operation control: look for match with start of slot */
define('TSSTART',  'tstart');
/** date-time operation control: look for match with end of slot */
define('TSSTOP',   'tstop');
/** date-time operation control: look for slot where this time is within the slot */
define('TSWITHIN', 2);
/** date-time operation control: look for slot following this time */
define('TSNEXT',   3);
// define('TSSLOT',   4);
/** number of extra elements that will be in the array created from the slot rules */
define('TSARRAYMIN', 1);
/** flag that slot was not found by the slot finding routines */
define('TS_SLOT_NOT_FOUND', -10000);

  
/**
* Timeslot validation based on rules passed to us (presumably from an SQL entry)
*
* @package    Bumblebee
* @subpackage Bookings
* @todo       more documentation
*/
class TimeSlotRule {
  var $picture = '';
  var $slots;
  
  var $DEBUG = 0;
  
  function TimeSlotRule($pic) {
    $this->picture = $pic;
    $this->_interpret();
  }
  
  /** 
   * timeslot picture syntax (concatinate onto one line, no spaces)
   * [x] or [x-y] 
   *         specify the day of week (or range of days). 0=Sunday, 6=Saturday
   * <slot;slot;slot> 
   *         around the comma-separated slot specifications for that day. All times from 00:00 to 24:00
   *         must be included in the list
   * starttime-stoptime/num_slots-discount%,comment
   *         HH:MM time slots with the length of each slot.
   *         e.g. 09:00-17:00/1 specifies a slot starting at 9am and finishing 5pm.
   *              both 00:00 and 24:00 are valid, also 32:00 for 8am the next day (o'night)
   *         num_slots is integer, or wildcard of * for freely adjustable time
   *         e.g. 09:00-12:00/3 specifies 3 slots 9-10, 10-11 and 11-12.
   *         discount (optional) is the percentage discount applied to bookings in that slot
   *         comment  (optional) is displayed in vacant or unavailable slots
   *
   * EXAMPLES:
   *
   * [0-6]<00:00-08:00/*;08:00-13:00/1;13:00-18:00/1;18:00-24:00/*>
   *    Available all days, free booking until 8am and after 6pm, other slots as defined
   * [0]<00:00-24:00/0,Unavailable>[1-5]<09:00-17:00/01:00>[6]<00:00-24:00/0,Unavailable>
   *    Not available Sunday and Saturday. Only available weekdays 9am till 5pm with 1hr booking
   *    granularity. Calendar views of Sat & Sun will see message "Unavailable"
   * [0]<>[1-5]<00:00-09:00/*;09:00-17:00/8;17:00-24:00/*-50%>[6]<>
   *    Not available Sunday and Saturday. Available weekdays 9am till 5pm with 1hr booking
   *    granularity, before 9am and after 5pm with no granularity and at a 50% discount.
   * [0]<>[1-5]<09:00-13:00/4;13:00-17:00/2;17:00-33:00/1>[6]<>
   *    Not available Sunday and Saturday. Available weekdays 9am till 5pm with 1hr booking
   *    granularity, before 9am and after 5pm with no granularity
   *
   */
  function _interpret() {
    $this->slots = array();
    for ($dow=0; $dow<=6; $dow++) {
      $this->slots[$dow] = array();
    }
    $this->_findDayLines();
    for ($dow=0; $dow<=6; $dow++) {
      $this->_fillDaySlots($dow);
    }
  } 
  
  function _findDayLines() {  
    $daylines = array();
    preg_match_all('/\[.+?\>/', $this->picture, $daylines);
    foreach ($daylines[0] as $d) {
      $day = array();
      #echo "considering $d\n";
      if (preg_match('/\[(\d)\]/', $d, $day)) {
        #echo "found match: ".$day[1]."\n";
        $this->slots[$day[1]]['picture']=$d;
      }
      if (preg_match('/\[(\d)-(\d)\]/', $d, $day)) {
        #echo "found multimatch: ".$day[1].'.'.$day[2]."\n";
        for ($i=$day[1]; $i<=$day[2]; $i++) {
          $this->slots[$i]['picture']=$d;
        }
      }
    }
  }
  
  function _fillDaySlots($dow) {
    $times = array();
    #preDump($this->slots[$dow]['picture']);
    preg_match('/\<(.*)\>/', $this->slots[$dow]['picture'], $times);
    #preDump($times);
    if ($times[0] == '<>') {
      $times[1]='00:00-24:00/0';
    }
    
    $i=0;
    foreach(preg_split('/;/', $times[1]) as $slot) {
      #echo "found slot=$slot\n";
      #$this->slots[$dow][$i] = array();
      $tmp = array();
      if (preg_match('/(\d\d:\d\d)-(\d\d:\d\d)\/(\d+|\*)(-\d+%)?(?:,(.+))?/', $slot, $tmp)) {
        /* regexp:       HH:MM    -  HH:MM     /n         -x%       ,comment
           group:         1            2        3          4          5
           notes:  
              n can be digit or *
              x is optional and can be one or more digits 
              ,comment is optional and the comma is not included in the captured pattern
              ?: means don't capture the contents of that subpattern () to $tmp
        */
        $start    = $tmp[1];
        $stop     = $tmp[2];
        $tstart   = new SimpleTime($start);
        $tstop    = new SimpleTime($stop);
        $numslots = $tmp[3];
        $optional=4;
        $discount = 0; $comment = '';
        while (isset($tmp[$optional])) {
          if (substr($tmp[$optional], 0, 1) == '-' && substr($tmp[$optional], -1, 1) == '%') {
            // then it's a discouint
            $discount = substr($tmp[$optional],1,-1);
          } else {
            // then it's the comment at the end
            $comment  = $tmp[$optional];
          }
          $optional++;
        }
        //echo "(start,stop,slots) = ($start,$stop,$numslots)\n";
        //echo "(discount,comment) = ($discount,$comment)<br />\n";
        if ($numslots != '*' && $numslots != '0') {
          #echo "Trying to interpolate timeslots\n";
          $tgran = new SimpleTime($tstop->subtract($tstart)/$numslots);
          $siblingSlot=1;
          for ($time = $tstart; $time->ticks < $tstop->ticks; $time->addTime($tgran)) {
            #echo $time->timestring.' '. $tstop->timestring.' '.$tgran->timestring."\n";
            $sstop = $time;
            $sstop->addTime($tgran);
            $this->slots[$dow][$i] = new RuleSlot($slot, $start, $stop, $time, $sstop, $tgran);
            $this->slots[$dow][$i]->numslotsInGroup = 1;
            $this->slots[$dow][$i]->numslotsFollowing = $numslots - $siblingSlot;
            $this->slots[$dow][$i]->comment = $comment;
            $this->slots[$dow][$i]->discount = $discount;
            if ($time->ticks != $tstart->ticks) {
              $this->slots[$dow][$i-1]->nextSlot = &$this->slots[$dow][$i];
            }
            $i++;
            $siblingSlot++;
          }
        } else {
          #echo "No need to interpolate\n";
          $this->slots[$dow][$i] = new RuleSlot($slot, $start, $stop, $tstart, $tstop);
          $this->slots[$dow][$i]->numslotsInGroup = 1;
          $this->slots[$dow][$i]->numslotsFollowing = 0;
          $this->slots[$dow][$i]->isFreeForm = $numslots == '*';
          $this->slots[$dow][$i]->isAvailable = $numslots != '0';
          $this->slots[$dow][$i]->comment = $comment;
          $this->slots[$dow][$i]->discount = $discount;
          $i++;
        }
      }
    }
    #var_dump($this->slots);
  }
  
/*  function allSlotStart() {
    echo "STUB: ". __FILE__ .' '. __LINE__;
    return array('09:00','10:00','15:00');
  }*/
  
  /**
   * return true if the specified date & time correspond to a valid starting time according
   * to this object's slot rules.
   */
  function isValidStart($date) {
    return $this->_isValidStartStop($date, TSSTART);
  }

  /**
   * return true if the specified date & time correspond to a valid stopping time according
   * to this object's slot rules.
   */
  function isValidStop($date) {
    $startday = $date;
    $startday->dayRound();
    //echo "$startday->datetimestring =? $date->datetimestring\n";
    if ($startday->ticks == $date->ticks) {
      $time = new SimpleTime('24:00');
      $startday->addDays(-1);
      //echo "$startday->datetimestring + $time->timestring\n";
      return $this->_isValidStartStop($time, TSSTOP, $startday);
    }
    return $this->_isValidStartStop($date, TSSTOP);
  }
  
  /**
   * perform the above operations with no code duplication
   */
  function _isValidStartStop($date, $type, $daysdate=0) {
    return $this->_findSlot($date, $type, $daysdate) != 0;
  }
  
  /**
   * return true if the specified dates & times are valid start/stop times
   * to this object's slot rules.
   */
  function isValidSlot($startdate, $stopdate) {
    return $this->isValidStart($startdate) && $this->isValidStop($stopdate);
  }
  
  /**
   * return true if the specified dates & times are valid as above, but only occupy one slot
   */
  function isValidSingleSlot($startdate, $stopdate) {
    $slot = $this->_findSlot($startdate, TSSTART);
    return $slot->stop->ticks == $stopdate->ticks;
  }
      
  /**
   * return the corresponding the slot specified by the given start date.
   * ASSUMES that the specified date is a valid start, else behaviour is undefined.
   *
   * @param mixed SimpleDate $date the date to match or SimpleTime to match
   * @param mixed optional SimpleDate iff $date was a SimpleTime. Provide the date component
   */
  function findSlotByStart($date, $datetime=0) {
    return $this->_findSlot($date, TSSTART, $datetime);
  }
  
  /**
   * return the 
   * as per findSlotByStart
   */
  function findSlotByStop($date, $datetime=0) {
    return $this->_findSlot($date, TSSTOP, $datetime);
  }
  
  /**
   * return the corresponding startdate/time to a time that is possibly within a slot
   */
  function findSlotFromWithin($date, $datetime=0) {
    return $this->_findSlot($date, TSWITHIN, $datetime);
  }

  /**
   * returns the slot that starts >= the date specified.
   */
  function findNextSlot($date, $datetime=0) {
    return $this->_findSlot($date, TSNEXT, $datetime);
  }
  
  
  /**
   * return the corresponding slot number of the starting or stopping date-time $date
   * returns -1 if no matching slot found.
   * $match is TSSTART, TSSTOP, TSWITHIN, TSNEXT depending on what matching is queried.
   * $return is TSSTART or TSSTOP depending on the required return value
   *
   * this function will return a slot from the previous day if the slot spans days and
   * that is the appropriate slot.
   */
  function _findSlot($date, $match, $datetime=0) {
    #$this->DEBUG=10;
    //$this->log("TimeSlotRule::_findSlot:($date->datetimestring, $match, $datetime)", 10);
    //preDump(debug_backtrace());
    if ($datetime == 0) {
      $time = $date->timePart();
      $dow = $date->dow();
      $day = $date;
    } else {
      $time = $date;
      $dow = $datetime->dow();
      $day = $datetime;
      //echo "Found $time->timestring, $dow, $day->datetimestring\n";
    } 
    $slot=0;
    $timecmp = $match;
    if ($match == TSWITHIN) $timecmp = TSSTOP;
    if ($match == TSNEXT)   $timecmp = TSSTART;
    $startvar = TSSTART;
    $stopvar  = TSSTOP;
    #preDump($this->slots[$dow]);
    $this->log("TimeSlotRule::_findSlot:($time->timestring, ".$this->slots[$dow][0]->$startvar->ticks.", $time->ticks, $dow)", 10);
    if ($time->ticks < $this->slots[$dow][0]->$startvar->ticks 
        || $match ==  TSSTOP && $time->ticks <= $this->slots[$dow][0]->$startvar->ticks) {
      $dow = ($dow+6)%7;
      $day->addDays(-1);
      $time->addSecs(24*60*60);
      $this->log("Stepped back a day to do the comparison");
    }
/*    $this->log("TimeSlotRule::_findSlot:($time->timestring, ".$this->slots[$dow][0]->$stopvar->ticks.", $time->ticks, $dow)", 10);
    if ($time->ticks > $this->slots[$dow][count($this->slots[$dow])-1-TSARRAYMIN]->$stopvar->ticks) {
      $dow = ($dow+1)%7;
      $day->addDays(1);
      $time->addSecs(-24*60*60);
      $this->log("Stepped forward a day to do the comparison");
    }*/
    $this->log("Asking for ($dow, $slot, $timecmp, $match)", 10);
    //preDump($this->slots[$dow]);
    while(
            //print_r("Asking for ($dow, $slot, $match)<br />") && 
            $slot < count($this->slots[$dow])-TSARRAYMIN 
            && $time->ticks >= $this->slots[$dow][$slot]->$timecmp->ticks) {
      //echo $time->ticks .'#'. $this->slots[$dow][$slot]->$timecmp->ticks."\n";
      //echo $slot .'#'.(count($this->slots[$dow])-TSARRAYMIN)."\n";
      $slot++;
    }
    #echo count($this->slots[$dow])-TSARRAYMIN."/";
    #echo $time->ticks .', '. $this->slots[$dow][$slot-1]->$timecmp->ticks."\n";
    $this->log("Final ($dow, $slot, $match)",10);
    if ($match == TSSTART || $match == TSSTOP) {
      $slot--;
      $finalslot = ($slot < count($this->slots[$dow])-TSARRAYMIN
                    && $slot >= 0
                    && $time->ticks == $this->slots[$dow][$slot]->$timecmp->ticks) 
                    ? $slot : TS_SLOT_NOT_FOUND ;
    } elseif ($match == TSWITHIN) {
      $a=TSSTART;
      $b=TSSTOP;
      $finalslot =  ($slot < count($this->slots[$dow])-TSARRAYMIN
                      && $time->ticks >= $this->slots[$dow][$slot]->$a->ticks
                      && $time->ticks <  $this->slots[$dow][$slot]->$b->ticks) 
                      ? $slot : TS_SLOT_NOT_FOUND ;
      //echo "Found containing slot: $finalslot\n";
    } else { //TSNEXT
      if ($slot >= count($this->slots[$dow])-TSARRAYMIN) {
        //echo "Looking for next slot in overflow:\n";
        do {
          //echo "($dow, $day->datetimestring,".count($this->slots[$dow]).")\n";
          $dow = ($dow+1) % 7;
          $day->addDays(1);
          $finalslot=0;
        } while (count($this->slots[$dow]) <= TSARRAYMIN);
        //echo "($dow, $day->datetimestring,".TSARRAYMIN.")\n".count($this->slots[$dow]);
      } else {
        $finalslot = $slot;
      }
    }
    $this->log("ReallyFinal ($dow, $finalslot, $match)",10);
    if ($finalslot == TS_SLOT_NOT_FOUND) {
      //trigger_error('Could not find a match to this time slot.', E_USER_NOTICE);
      return 0;
      $finalslot==0;
    }
    $returnSlot = $this->slots[$dow][$finalslot];
    //preDump($returnSlot);
    $returnSlot->setDate($day);
//    preDump($returnSlot->dump());
    return $returnSlot;
  }
  
  function dump($html=1) {
    #preDump($this->slots);
    #return;
    $eol = $html ? "<br />\n" : "\n";
    $s = '';
    $s .= "Initial Pattern = '". $this->picture ."'".$eol;
    for ($day=0; $day<=6; $day++) {
      $s .= "Day Pattern[$day] = '". $this->slots[$day]['picture'] ."'".$eol;
      //for ($j=0; $j<count($this->slots[$day]) && isset($this->slots[$day][$j]); $j++) {
      foreach ($this->slots[$day] as $k => $v) {
        if (is_numeric($k)) {
          $s .= $v->dump($html);
//           $s .= "\t" . $v->tstart->timestring 
//                 ." - ". $v->tstop->timestring .$eol;
        }
      }
    }
    return $s;
  }

  function log($logstring, $prio=10) {
    if ($prio <= $this->DEBUG) {
      echo $logstring."<br />\n";
    }
  }
  

} //class TimeSlotRule



/**
* Data object for an individual time slot defined by the time slot picture
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: timeslotrule.php,v 1.21.2.2 2006/05/16 15:05:14 stuart Exp $
* @package    Bumblebee
* @subpackage Bookings
* @todo       more documentation
*/
class RuleSlot {
  var $tstart;
  var $tstop;
  var $tgran;
  var $start;
  var $stop;
  var $startStr;
  var $stopStr;
  var $numslotsFollowing = 0;
  var $numslotsInGroup = 1;
  var $isFreeForm = 0;
  var $isAvailable = 1;
  var $picture = '';
  var $nextSlot;
  var $comment = '';
  var $discount = 0;
  
  function RuleSlot($picture, $startStr, $stopStr, $tstart, $tstop, $tgran=NULL) {
    $this->picture = $picture;
    $this->startStr = $startStr;
    $this->stopStr = $stopStr;
    $this->tstart = $tstart;
    $this->tstop = $tstop;
    $this->tgran = is_a($tgran, 'SimpleTime') ? $tgran : new SimpleTime(0);
  }

  function setDate($date) {
    $this->start = $date;
    $this->start->setTime($this->tstart);
    $this->stop = $date;
    $this->stop->setTime($this->tstop);
  }
  
  function dump($html=1) {
    $eol = $html ? "<br />\n" : "\n";
    return 'Slot:'."\t"
          .$this->tstart->timestring.' - '
          .$this->tstop->timestring.' : '
          .($this->isAvailable? 'Available' : 'Not available')
          .($this->isFreeForm ? ' Freeform' : '')
          .($this->comment !== '' && $this->comment !== NULL ? ' Comment = '.$this->comment : '')
          .$eol
    ;
  }

  function allSlotDurations() {
    $duration = array();
    $cslot = $this;
    $cdur = $this->tgran;
    for ($i=0; $i<=$this->numslotsFollowing; $i++) {
      #echo $i.': length='.$cslot->tgran->timestring.', sum='.$cdur->timestring."<br />\n";
      $duration[$cdur->timestring] = $cdur->timestring;
      $cdur->addTime($cslot->tgran);
      $cslot = $cslot->nextSlot;
    }
    return $duration;
  }
  
}  // RuleSlot
