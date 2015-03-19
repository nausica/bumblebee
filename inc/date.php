<?php
/**
* Simple date and time classes to perform basic date calculations
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: date.php,v 1.21 2006/01/05 02:32:07 stuart Exp $
* @package    Bumblebee
* @subpackage Misc
*/

/**
* Simple date class to perform basic date calculations
* 
* WARNING USING TICKS DIRECTLY IS DANGEROUS
* 
* The ticks variable here is a little problematic... daylight saving transitions tend
* to make it rather frought. For example, for the purposes of this system, 
* if you want to know what 4 days after 9am 20th March
* is, you expect to get 9am 23rd March regardless of a timezone change from daylight saving.
* 
* For example, this might not give you what you want:<code>
* $date = new SimpleDate('2005-03-20');
* $date->addSecs(4*24*60*60);   // add 4 days
* </code>
* But this will:<code>
* $date = new SimpleDate('2005-03-20');
* $date->addDays(4);   // add 4 days
* </code>
*
* @package    Bumblebee
* @subpackage Misc
*/
class SimpleDate {
  /**
  * date in string format (YYYY-MM-DD)
  * @var string
  */
  var $datestring = '';
  /**
  * date and time in string format (YYYY-MM-DD HH:MM:SS)
  * @var string
  */
  var $datetimestring = '';
  /**
  * date in seconds since epoch
  * @var integer
  */
  var $ticks  = '';
  /**
  * is a valid date-time
  * @var boolean
  */
  var $isValid = 1;

  /**
  * construct a Date-Time object
  * 
  * constructor will work with $time in the following formats:
  * - YYYY-MM-DD            (assumes 00:00:00 for time part)
  * - YYYY-MM-DD HH:MM      (assumes :00 for seconds part)
  * - YYYY-MM-DD HH:MM:SS
  * - seconds since epoch
  * - SimpleDate object
  * @param mixed $time  initial time to use
  */
  function SimpleDate($time) {
    ($time == NULL) && $time = 0;
    if (is_numeric($time)) {
      $this->setTicks($time);
    } elseif (is_a($time, 'SimpleDate')) {
      $this->setTicks($time->ticks);
    } else {
      $this->setStr($time);
    } 
  }

  /**
  * set the date and time from a string
  * 
  * - YYYY-MM-DD            (assumes 00:00:00)
  * - YYYY-MM-DD HH:MM      (assumes :00)
  * - YYYY-MM-DD HH:MM:SS
  * @param mixed $s date time string
  */
  function setStr($s) {
    $this->isValid = 1;
    $this->_setTicks($s);
    $this->_setStr();
  }

  function _setStr() {
    #echo "SimpleDate::Str $this->ticks<br />";
    $this->datestring = strftime('%Y-%m-%d', $this->ticks);
    $this->datetimestring = strftime('%Y-%m-%d %H:%M:%S', $this->ticks);
    $this->isValid = $this->isValid && ($this->datestring != '' && $this->datetimestring != '' 
                   && $this->datestring != -1 && $this->datetimestring != -1);
  }

  /**
  * set the date and time from seconds since epoch
  * 
  * @param mixed $t ticks
  */
  function setTicks($t) {
    $this->isValid = 1;
    $this->ticks = $t;
    $this->_setStr();
  }

  function _setTicks($s) {
    #echo "SimpleDate::Ticks $s<br />";
    #preDump(debug_backtrace());
    $this->ticks = strtotime($s);
    $this->isValid = $this->isValid && ($this->ticks != '' && $this->ticks != -1);
  }

  /**
  * add a whole number of days to the current date-time
  * @param integer $d days to add
  */
  function addDays($d) {
    $this->addTimeParts(0,0,0,$d,0,0);
  }

  /**
  * add a time (i.e. a number of seconds) to the current date-time
  *
  * - SimpleTime object
  * - seconds
  * @param mixed $d days to add
  */
  function addTime($t) {
    if (is_a($t, 'SimpleTime')) {
      $this->ticks += $t->seconds();
    } else {
      $this->ticks += $t;
    }
    $this->_setStr();
  }
  
  /**
  * add a whole number of seconds to the current date-time
  * @param integer $s seconds to add
  */
  function addSecs($s) {
    $this->ticks+=$s;
    $this->_setStr();
  }

  /**
  * round (down) the date-time to the current day
  *
  * sets the current YYY-MM-DD HH:MM:SS to YYYY-MM-DD 00:00:00
  */
  function dayRound() {
    $this->setStr($this->datestring);
  }

  /**
  * round (down) the date-time to the start of the current week (Sunday)
  */
  function weekRound() {
    $this->dayRound();
    $this->addDays(-1 * $this->dow());
  }
  
  /**
  * round (down) the date-time to the start of the current month (the 1st)
  */
  function monthRound() {
    $this->dayRound();
    $this->addDays(-1*$this->dom()+1);
  }

  /**
  * round (down) the date-time to the start of the current quarter (1st Jan, 1st Apr, 1st Jul, 1st Oct)
  */
  function quarterRound() {
    $month = $this->moy();
    $quarter = floor(($month-1)/3)*3+1;
    $this->setTimeParts(0,0,0,1,$quarter,$this->year());
  }
  
  /**
  * round (down) the date-time to the start of the current year (1st Jan)
  */
  function yearRound() {
    $this->dayRound();
    $this->addDays(-1*$this->doy());
  }
  
  /** 
  * returns the number of days between two dates ($this - $date) 
  * note that it will return fractional days across daylight saving boundaries
  * @param SimpleDate $date date to subtract from this date
  */
  function daysBetween($date) {
    return $this->subtract($date) / (24 * 60 * 60);
  }
  
  /** 
  * returns the number of days between two dates ($this - $date) accounting for daylight saving
  * @param SimpleDate $date date to subtract from this date
  */
  function dsDaysBetween($date) {
    //Calculate the number of days as a fraction, removing fractions due to daylight saving
    $numdays = $this->daysBetween($date);
    
    //We don't want to count an extra day (or part thereof) just because the day range 
    //includes going from summertime to wintertime so the date range includes an extra hour!

    $tz1 = date('Z', $this->ticks);
    $tz2 = date('Z', $date->ticks);
    if ($tz1 == $tz2) {
      // same timezone, so return the computed amount 
      #echo "Using numdays $tz1 $tz2 ";
      return $numdays;
    } else {
      // subtract the difference in the timezones to fix this
      #echo "Using tzinfo: $tz1 $tz2 ";
      return $numdays - ($tz2-$tz1) / (24*60*60);
    }
  }

  /** 
  * returns the number of days (or part thereof) between two dates ($this - $d) 
  * @param SimpleDate $date date to subtract from this date
  */
  function partDaysBetween($date) {
    //we want this to be an integer and since we want "part thereof" we'd normally round up
    //but daylight saving might cause problems....  We also have to include the part day at 
    //the beginning and the end
    
    $startwhole = $date;
    $startwhole->dayRound();
    $stopwhole = $this;
    $stopwhole->ticks += 24*60*60-1;
    $stopwhole->_setStr();
    $stopwhole->dayRound();
    
    return $stopwhole->dsDaysBetween($startwhole);
  }
   
  /** 
  * returns the number of seconds between two times
  * NB this does not specially account for daylight saving changes, so will not always give
  * the 24*60*60 for two datetimes that are 1 day apart on the calendar...!
  * @param SimpleDate $date date to subtract from this date
  */
  function subtract($date) {
    #echo "$this->ticks - $date->ticks ";
    return $this->ticks - $date->ticks;
  }

  /** 
  * returns a SimpleTime object for just the time component of this date time
  *
  * @return SimpleTime object of just the HH:MM:SS component of this date
  */
  function timePart() {
    $timestring = strftime('%H:%M:%S', $this->ticks);
    return new SimpleTime($timestring,1);
  }

  /** 
  * Sets the time component of this date-time to the specified time but with the same date as currently set
  *
  * The specified time can be HH:MM HH:MM:SS, seconds since midnight or a SimpleTime object
  * @param mixed time to use
  */
  function setTime($s) {
    //echo $this->dump().$s.'<br/>';
    $this->dayRound();
    $time = new SimpleTime($s);
    $this->addTimeParts($time->part('s'), $time->part('i'), $time->part('H'), 0,0,0);
    return $this;
  }  
    

  /** 
  * Sets this SimpleDate to the earlier of $this and $t
  *
  * @param SimpleDate $t
  */
  function min($t) {
    $this->setTicks(min($t->ticks, $this->ticks));
  }

  /** 
  * Sets this SimpleDate to the later of $this and $t
  *
  * @param SimpleDate $t
  */
  function max($t) {
    $this->setTicks(max($t->ticks, $this->ticks));
  }
  
  /**
  * round time down to the nearest $g time-granularity measure
  *
  * Example: if this date time is set to 2005-11-21 17:48 and 
  * $g represents 00:15:00 (i.e. 15 minutes) then this date-time would
  * be set to 2005-11-21 17:45 by rounding to the nearest 15 minutes.
  *
  * @param SimpleTime $g
  */
  function floorTime($g) {
    $tp = $this->timePart();
    $tp->floorTime($g);
    $this->setTime($tp->timestring);
  }
  
  /**
  * Add components to the current date-time
  *
  * Note that this function will take account of daylight saving in unusual (but quite sensible)
  * ways... for example, if $today is a SimpleDate object representing midday the day before
  * daylight saving ends, then $today->addTimeParts(24*60*60) will give a different result
  * to $today->addTimeParts(0,0,0,1). The former will be exactly 24 hours later than the original
  * value of $today (11:00), but the latter will 1 calendar day later (12:00).
  *
  * @param integer $sec  (optional) number of seconds to add to this date-time
  * @param integer $min  (optional) number of minutes to add to this date-time
  * @param integer $hour (optional) number of hours to add to this date-time
  * @param integer $day  (optional) number of days to add to this date-time
  * @param integer $month  (optional) number of months to add to this date-time
  * @param integer $year  (optional) number of years to add to this date-time
  */
  function addTimeParts($sec=0, $min=0, $hour=0, $day=0, $month=0, $year=0) {
    $this->ticks = mktime(
                            date('H',$this->ticks) + $hour,
                            date('i',$this->ticks) + $min,
                            date('s',$this->ticks) + $sec,
                            date('m',$this->ticks) + $month,
                            date('d',$this->ticks) + $day,
                            date('y',$this->ticks) + $year
                        );
    $this->_setStr();
  }
  
  /**
  * Set current date-time by components
  *
  * @param integer $sec  (optional) seconds to set
  * @param integer $min  (optional) minutes to set
  * @param integer $hour (optional) hours to add set
  * @param integer $day  (optional) days to add set
  * @param integer $month  (optional) months to set
  * @param integer $year  (optional) years to set
  */
  function setTimeParts($sec=0, $min=0, $hour=0, $day=0, $month=0, $year=0) {
    $this->ticks = mktime(
                            $hour,
                            $min,
                            $sec,
                            $month,
                            $day,
                            $year
                        );
    $this->_setStr();
  }
  
  /**
  * return the day of week of the current date. 
  * @return integer day of week (0 == Sunday, 6 == Saturday)
  */
  function dow() {
    return date('w', $this->ticks);
  }
  
  /**
  * return the day of week of the current date as a string (always in English)
  * @return string day of week (Sunday, Monday, etc)
  */
  function dowStr() {
    return date('l', $this->ticks);
  }
  
  /**
  * return the day of month
  * @return integer day of month (1..31)
  */
  function dom() {
    return date('d', $this->ticks);
  }
  
  /**
  * return integer month of year (1..12)
  * @return integer month of year (1..12)
  */
  function moy() {
    return date('m', $this->ticks);
  }
  
  /**
  * day of year (0..365)
  * returns 365 only in leap years
  * @return integer day of year (0..365)
  */
  function doy() {
    return date('z', $this->ticks);
  }

  /**
  * four-digit year (YYYY)
  * @return integer year (e.g. 2005)
  */
  function year() {
    return date('Y', $this->ticks);
  }
  
  /**
  * dump the datetimestring and ticks in a readable format
  * @param boolean $html (optional) use html line endings
  * @return string datetimestring and ticks
  */
  function dump($html=1) {
    $s = 'ticks = ' . $this->ticks . ', ' . $this->datetimestring;
    $s .= ($html ? '<br />' : '') . "\n";
    return $s;
  }
  
} // class SimpleDate


/**
* Simple time class to perform basic time calculations
*
* @package    Bumblebee
* @subpackage Misc
*/
class SimpleTime {
  /**
  * current time in string format (HH:MM:SS)
  * @var string
  */
  var $timestring = '';
  /**
  * current time in integer seconds since midnight
  * @var integer
  */
  var $ticks = '';
  /**
  * is set to a valid value
  * @var boolean
  */
  var $isValid = 1;

  /** 
  * Constructor for class
  * 
  * Accepts the following for the initial time:
  * - HH:MM:SS
  * - HH:MM  (assumes :00 for seconds part)
  * - SimpleTime object
  */
  function SimpleTime($time) {
    #echo "New SimpleTime: $time, $type<br />";
    if (is_numeric($time)) {
      $this->setTicks($time);
    } elseif (is_a($time, 'SimpleTime')) {
      $this->setTicks($time->ticks);
    } else {
      $this->setStr($time);
    }
  }

  /** 
  * Set time by a string
  */
  function setStr($s) {
    $this->_setTicks($s);
    $this->_setStr();
  }

  function _setStr() {
    $this->timestring = sprintf('%02d:%02d', $this->ticks/3600, ($this->ticks%3600)/60);
  }

  /** 
  * Set time by seconds since midnight
  */
  function setTicks($t) {
    $this->ticks = $t;
    $this->_setStr();
  }

  function _setTicks($s) {
    if (preg_match('/^(\d\d):(\d\d):(\d\d)$/', $s, $t)) {
      #preDump($t);
      $this->ticks = $t[1]*3600+$t[2]*60+$t[3];
    } elseif (preg_match('/^(\d\d):(\d\d)$/', $s, $t) || preg_match('/^(\d):(\d\d)$/', $s, $t)) {
      #preDump($t);
      $this->ticks = $t[1]*3600+$t[2]*60;
    } else {
      $this->ticks = 0;
      $this->inValid = 0;
    }
  }

  /** 
  * subtract seconds $this -  $other
  * @param SimpleTime $other 
  * @return integer seconds difference
  */
  function subtract($other) {
    return $this->ticks - $other->ticks;
  }

  /** 
  * add seconds to this time
  * @param integer $s
  */
  function addSecs($s) {
    $this->ticks += $s;
    $this->_setStr();
  }

  /** 
  * return current seconds
  * @return integer current seconds since midnight
  */
  function seconds() {
    return $this->ticks;
  }
  
  /** 
  * set this value to the earlier of $this and $other
  * @param SimpleTime $other
  */
  function min($other) {
    $this->setTicks(min($other->ticks, $this->ticks));
  }

  /** 
  * set this value to the later of $this and $other
  * @param SimpleTime $other
  */
  function max($t) {
    $this->setTicks(max($t->ticks, $this->ticks));
  }

  /** 
  * get a string representation that includes the number of seconds
  * @return string time value in HH:MM:SS format
  */
  function getHMSstring() {
    return sprintf('%02d:%02d:%02d', $this->ticks/3600, ($this->ticks%3600)/60, $this->ticks%60);
  }

  /**
  * round time down to the nearest $g time-granularity measure
  * 
  * @see SimpleDate::floorTime()
  * @param SimpleTime $g time granularity
  */
  function floorTime($g) {
    $gt = $g->seconds();
    $this->setTicks(floor(($this->ticks+1)/$gt)*$gt);
  }
  
  /**
  * round time up to the nearest $g time-granularity measure
  * 
  * @see SimpleTime::floorTime()
  * @param SimpleTime $g time granularity
  */
  function ceilTime($g) {
    $gt = $g->seconds();
    $this->setTicks(ceil(($this->ticks-1)/$gt)*$gt);
  }
  
  /** 
  * Obtain hour, minute or seconds parts of the time
  *
  * return hour, minute or seconds parts of the time, emulating the date('H', $ticks) etc
  * functions, but not using them as they get too badly confused with timezones to be useful
  * in many situations
  * 
  * @param char $s time part to obtain (valid parts: h, i, s for hours, mins, secs)
  * @return integer part of the time
  */
  function part($s) {
    switch ($s) {
      //we don't actually care about zero padding in this case.
      case 'H':
      case 'h':
        return floor($this->ticks/(60*60));
      //let's just allow 'm' to give minutes as well, as it's easier
      case 'i':
      case 'm':
        return floor(($this->ticks%3600) / 60);
      case 's':
        return floor($this->ticks % 60);
    }
    //we can't use this as we're not actually using the underlying date-time types here.
    //return date($s, $this->ticks);
  }
  
  /** 
  * Add another time to this time
  * 
  * @param SimpleTime $t time to add to this one
  */
  function addTime($t) {
    $this->ticks += $t->ticks;
    $this->_setStr();
  }
  
  /**
  * dump the timestring and ticks in a readable format
  * @param boolean $html (optional) use html line endings
  * @return string timestring and ticks
  */
  function dump($html=1) {
    $s = 'ticks = ' . $this->ticks . ', ' . $this->timestring;
    $s .= ($html ? '<br />' : '') . "\n";
    return $s;
  }
} // class SimpleTime

?> 
