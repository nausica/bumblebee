<?php
/**
* Instrument object (extends dbo), with extra customisations for other links
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: instrument.php,v 1.23 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage DBObjects
*/

/** parent object */
require_once 'inc/formslib/dbrow.php';
require_once 'inc/formslib/idfield.php';
require_once 'inc/formslib/textfield.php';
require_once 'inc/formslib/textarea.php';
require_once 'inc/formslib/commentfield.php';
require_once 'inc/formslib/checkbox.php';
require_once 'inc/formslib/radiolist.php';
require_once 'inc/formslib/exampleentries.php';
require_once 'inc/bookings/timeslotrule.php';

/**
* Instrument object (extends dbo), with extra customisations for other links
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class Instrument extends DBRow {

  var $_slotrule;  

  function Instrument($id) {
    global $CONFIG;
    //$this->DEBUG=10;
    $this->DBRow('instruments', $id);
    $this->editable = 1;
    $this->deleteFromTable = 0;
    $f = new IdField('id', 'Instrument ID');
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', 'Name');
    $attrs = array('size' => '48');
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('longname', 'Description');
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('location', 'Location');
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('usualopen', 'Calendar start time (HH:MM)');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualopen'];
    $f->isValidTest = 'is_valid_time';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('usualclose', 'Calendar end time (HH:MM)');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualclose'];
    $f->isValidTest = 'is_valid_time';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('calprecision', 'Precision of calendar display (seconds)');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualprecision'];
    $f->isValidTest = 'is_number';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('caltimemarks', 'Time-periods per HH:MM displayed');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualtimemarks'];
    $f->isValidTest = 'is_number';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('callength', 'Number of weeks displayed in calendar');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualcallength'];
    $f->isValidTest = 'is_number';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('calhistory', 'Number of weeks history shown');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualcalhistory'];
    $f->isValidTest = 'is_number';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('calfuture', 'Number of days into the future');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualcalfuture'];
    $f->isValidTest = 'is_number';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextArea('calendarcomment', 'Advisory text', 'Displayed at the bottom of instrument calendar and on booking form. HTML permitted.');
    $f->setAttr(array('rows' =>5, 'cols' => 30));
    $f->required = 0;
    $this->addElement($f);
    
    // associate with a charging class
    $f = new RadioList('class', 'Charging class');
    $f->connectDB('instrumentclass', array('id', 'name'));
    $classexample = new ExampleEntries('id','instruments','class','name',3);
    $classexample->separator = '; ';
    $f->setFormat('id', '%s', array('name'), ' (%40.40s)', $classexample);
    $newclassname = new TextField('name','');
    $newclassname->namebase = 'newclass-';
    $newclassname->setAttr(array('size' => 24));
    $newclassname->isValidTest = 'is_nonempty_string';
    $newclassname->suppressValidation = 0;
    $f->list->append(array('-1','Create new: '), $newclassname);
    $f->setAttr($attrs);
    $f->extendable = 1;
    $f->required = 1;
    $f->isValidTest = 'is_valid_radiochoice';
    $this->addElement($f);
    $f = new TextField('halfdaylength', 'Hours in a half-day');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualhalfdaylength'];
    $f->isValidTest = 'is_number';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('fulldaylength', 'Hours in a full-day');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualfulldaylength'];
    $f->isValidTest = 'is_number';
    $f->setAttr($attrs);
    $this->addElement($f);

    // create the timeslot rule information required
    $f = new CommentField('timeslotpicturecomment', 'Instrument timeslots',
                              'These fields describe when the instrument is available. ');
    $f->value = 'Format: HH:MM-HH:MM/n-x%,comment';
    $this->addElement($f);
    $f = new TextField('timeslotpicture', 'Time slot picture');
    $f->required = 1;
    $f->hidden = 1;
    $f->defaultValue = $CONFIG['instruments']['usualtimeslotpicture'];
    $f->isValidTest = 'is_set';
    $f->setAttr($attrs);
    $this->addElement($f);
    $weekstart = new SimpleDate(time());
    $weekstart->weekRound();
    for ($day=0; $day<7; $day++) {
      $today = $weekstart;
      $today->addDays($day);
      $f = new TextArea('tsr-'.$day, $today->dowStr(), 'Slots in day, one per line');
      $f->sqlHidden = 1;
      $f->setAttr(array('rows' =>3, 'cols' => 30));
      $f->required = 1;
      $this->addElement($f);
    }
    
    $f = new TextField('mindatechange', 'Minimum notice for booking change (hours)');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualmindatechange'];
    $f->isValidTest = 'is_number';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('supervisors', 'Instrument supervisors', 'comma separated list of usernames');
    $f->required = 0;
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new CheckBox('emailonbooking', 'Email supervisors when booking');
    $f->defaultValue = 0;
    $f->setAttr($attrs);
    $this->addElement($f);
    
    $this->fill();
    $this->dumpheader = 'Instrument object';
  }

  function fill() {
    parent::fill();
    //now edit the time slot representation fields
    $this->_calcSlotRepresentation();
  }
  
  function sync() {
    //first construct a timeslot field from the submitted data, then do the sync
    $newslotrule = $this->_calcNewSlotRule();
    if ($this->fields['timeslotpicture']->value != $newslotrule /*&& $this->id > -1*/) {
      $this->log('Instrument::sync(): indulging in timeslotrule munging: <br />'. 
                    $newslotrule .'<br/>'.$this->fields['timeslotpicture']->value);
      $this->fields['timeslotpicture']->set($newslotrule);
      $this->fields['timeslotpicture']->changed = 1;
      $this->changed = $this->changed || ($this->id > -1);
      // reflect back the data, this is good for checking that it's right, as TimeSlotRule
      // will drop bits it doesn't understand or doesn't like.
      //$this->_calcSlotRepresentation();
    } else {
      //?
    }
   //$this->DEBUG=10;
   return parent::sync();
  }

  function _calcSlotRepresentation() {  
    $this->_slotrule = new TimeSlotRule($this->fields['timeslotpicture']->getValue());
    for ($day=0; $day<7; $day++) {
      $this->fields['tsr-'.$day]->value = '';
      //preDump($this->_slotrule->slots[$day]);
      $prevpicture = '';
      foreach ($this->_slotrule->slots[$day] as $key => $slot) {
        if (is_numeric($key) && $slot->picture != $prevpicture) {
          $prevpicture = $slot->picture;
          //preDump($slot);
          $this->log('Added picture '. $slot->picture);
          $this->fields['tsr-'.$day]->value .= $slot->picture."\n";
        }
      }
    }
  }
  
  function _calcNewSlotRule() {
    $newslot = '';
    for ($day=0; $day<7; $day++) {
      //preDump($this->fields['tsr-'.$day]->value);
      $lines = preg_split('/[\n\r]+/', $this->fields['tsr-'.$day]->value);
      // get rid of blanks
      $lines = preg_grep('/^\s*$/', $lines,PREG_GREP_INVERT);
      $rejects = preg_grep('{^\d\d:\d\d\-\d\d:\d\d/(\d+|\*)(\-\d+%)?(,.+)?$}', $lines,PREG_GREP_INVERT);
      if (count($rejects) > 0) {
        //then this input is invalid
        $this->fields['tsr-'.$day]->isValid = 0;
        $this->isValid = 0;
        //preDump($rejects);
      }
      $newslot .= '['.$day.']<'.join($lines,';').'>';
      $this->log('Calculated picture '. $newslot);
    }
    return $newslot;    
  }
  
  function display() {
    return $this->displayAsTable();
  }

} //class Instrument
