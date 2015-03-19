<?php
/**
* DateRange display/data reflection class
*
* A class to handle a simple date range from a form and reflect all other entered data
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: daterange.php,v 1.5 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage DBObjects
*/

/** date manipulation routines */
require_once 'inc/date.php';
/** quick select javascript object */
require_once 'inc/jsquickwalk.php';
/** parent object */
require_once 'inc/formslib/nondbrow.php';
require_once 'inc/formslib/datefield.php';

/** default is current time period */
define('DR_CURRENT',  1);
/** default is next time period */
define('DR_NEXT',     2);
/** default is previous time period */
define('DR_PREVIOUS', 4);

/** default time period is one day */
define('DR_DAY',      8);
/** default time period is one week */
define('DR_WEEK',    16);
/** default time period is one month */
define('DR_MONTH',   32);
/** default time period is one quarter (3 months) (alternative) */
define('DR_QTR',     64);
/** default time period is one quarter (3 months) */
define('DR_QUARTER', 64);
/** default time period is one year */
define('DR_YEAR',   128);

/**
* DateRange display/data reflection class
*
* A class to handle a simple date range from a form and reflect all other entered data
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: daterange.php,v 1.5 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage DBObjects
*/
class DateRange extends nonDBRow { 
  /** @var boolean   include data in hidden fields if not shown  */
  var $reflectData = 1;
  /** @var boolean   include a submit button in the output  */
  var $includeSubmitButton = 1;

  function dateRange($name, $longname, $description) {
    parent::nonDBRow($name, $longname, $description);
    //$this->DEBUG=10;
    $startdate = new DateField('startdate','Start period beginning of',
                          'Period starts at the beginning of this day');
    $startdate->isValidTest = 'is_valid_date';
    $this->addElement($startdate);
    $stopdate  = new DateField('stopdate','Finish period end of', 
                          'Period finishes at the end of this day' );
    $stopdate->isValidTest = 'is_valid_date';
    $this->addElement($stopdate);
  }

  /**
   * Calculate a sensible range
   *
   * this is calculated from the 'DR_*' data that we have above
   * 
   * @param integer $which     DR_NEXT, DR_CURRENT, DR_PREVIOUS
   * @param integer $range     DR_DAY, DR_WEEK, etc...
   * @param integer $basetime  what date is "current" date
   * @return array  (SimpleDate startdate, SimpleDate stopdate) 
   */
  function _calcRange($which, $range, $basetime) {
    $start = $basetime;
    $stop = $start;
    switch ($range) {
      case DR_YEAR:
        $start->yearRound();
        $stop = $start;
        switch ($which) {
          case DR_NEXT:
            $start->addTimeParts(0,0,0,0,0,1);
            $stop->addTimeParts(0,0,0,0,0,2);
            break;
          case DR_PREVIOUS:
            $start->addTimeParts(0,0,0,0,0,-1);
            break;
          case DR_CURRENT:
            $stop->addTimeParts(0,0,0,0,0,1);
        }
        break;
      case DR_QUARTER:
        $start->quarterRound();
        $stop = $start;
        switch ($which) {
          case DR_NEXT:
            $start->addTimeParts(0,0,0,0,3);
            $stop->addTimeParts(0,0,0,0,6);
            break;
          case DR_PREVIOUS:
            $start->addTimeParts(0,0,0,0,-3);
            break;
          case DR_CURRENT:
            $stop->addTimeParts(0,0,0,0,3);
        }
        break;
      case DR_MONTH:
        $start->monthRound();
        $stop = $start;
        switch ($which) {
          case DR_NEXT:
            $start->addTimeParts(0,0,0,0,1);
            $stop->addTimeParts(0,0,0,0,2);
            break;
          case DR_PREVIOUS:
            $start->addTimeParts(0,0,0,0,-1);
            break;
          case DR_CURRENT:
            $stop->addTimeParts(0,0,0,0,1);
        }
        break;
      case DR_WEEK:
        $start->weekRound();
        $stop = $start;
        switch ($which) {
          case DR_NEXT:
            $start->addTimeParts(0,0,0,7);
            $stop->addTimeParts(0,0,0,14);
            break;
          case DR_PREVIOUS:
            $start->addTimeParts(0,0,0,-7);
            break;
          case DR_CURRENT:
            $stop->addTimeParts(0,0,0,0,7);
        }
        break;
      case DR_DAY:
        $start->dayRound();
        $stop = $start;
        switch ($which) {
          case DR_NEXT:
            $start->addTimeParts(0,0,0,1);
            $stop->addTimeParts(0,0,0,2);
            break;
          case DR_PREVIOUS:
            $start->addTimeParts(0,0,0,-1);
            break;
          case DR_CURRENT:
            $stop->addTimeParts(0,0,0,0,1);
        }
        break;
    }
    $stop->addDays(-1);
    return array('startdate'=>$start, 'stopdate'=>$stop);
  }

  /**
   * what default values should be in the boxes
   *
   * this is calculated from the 'DR_*' data that we have above
   *
   * @param integer $which     DR_NEXT, DR_CURRENT, DR_PREVIOUS
   * @param integer $range     DR_DAY, DR_WEEK, etc...
   * @param integer $basetime  what date is "current" date
   * @return array  (html javascript, forwards-backwards button html)
   */
  function setDefaults($which, $range, $basetime=0) {
    $start = new SimpleDate($basetime ? $basetime : time());
    $daterange = $this->_calcRange($which, $range, $start);    
    $this->fields['startdate']->setDate($daterange['startdate']->datestring);
    $this->fields['stopdate']->setDate($daterange['stopdate']->datestring);
    $this->extrarows = array();
    $rangePile = array($daterange);
    $nextrange = $daterange;
    $prevrange = $daterange;
    $maxRanges  = 10;
    for ($i=0; $i<$maxRanges; $i++) {
      $nextrange = $this->_calcRange(DR_NEXT,     $range, $nextrange['startdate']);
      $prevrange = $this->_calcRange(DR_PREVIOUS, $range, $prevrange['startdate']);
      array_unshift($rangePile, $prevrange);
      array_push   ($rangePile, $nextrange);
    }
    //preDump($rangePile);
    $jswalk = new JSQuickWalk($this->namebase,'&laquo; Previous', 'Next &raquo;', array('startdate', 'stopdate'), $rangePile, $maxRanges);
    //preDump($jswalk);
    //$this->extrarows[] = array('', $prevRange[0]->datestring.$prevRange[1]->datestring);
    //$this->extrarows[] = array('', $nextRange[0]->datestring.$nextRange[1]->datestring);
    $this->extrarows[] = array($jswalk->displayJS(),
                        $jswalk->displayBack().' | '.$jswalk->displayFwd());  
  }
  
  
  function display($PD) {
    $t = '';
    if ($this->reflectData) {
      foreach ($PD as $key => $val) {
        $t .= '<input type="hidden" name="'.$key.'" value="'.xssqw($val).'" />';
      }
    }
    $t .= $this->displayInTable(2);
    if ($this->includeSubmitButton) {
      $t .= '<input type="submit" name="submit" value="Go" />';
    }
    return $t;
  }
  
  function getStart() {
    return $this->fields['startdate']->date;
  }
  
  function getStop() {
    return $this->fields['stopdate']->date;
  }
} //dateRange

?>
