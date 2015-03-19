<?php
/**
* View a bookings calendar and make bookings
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: view.php,v 1.50.2.2 2006/07/13 20:21:28 themill Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/** calendar object */
require_once 'inc/bb/calendar.php';
/** generic booking entry object */
require_once 'inc/bb/bookingentry.php';
/** read-only booking entry object */
require_once 'inc/bb/bookingentryro.php';
/** list of choices object */
require_once 'inc/formslib/anchortablelist.php';
/** date maniuplation objects */
require_once 'inc/date.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* View a bookings calendar and make bookings
* @package    Bumblebee
* @subpackage Actions
*/
class ActionView extends ActionAction {
  /**
  * booking is for the logged in user 
  * @var boolean
  */
  var $_isOwnBooking    = false;
  /**
  * logged in user has admin view of booking/calendar
  * @var boolean
  */
  var $_isAdminView     = false;
  /**
  * logged in user can modify booking
  * @var boolean
  */
  var $_haveWriteAccess = false;


  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionView($auth, $PDATA) {
    parent::ActionAction($auth, $PDATA);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['instrid'])
          || $this->PD['instrid'] < 1
          || $this->PD['instrid'] == '') {
      $this->selectInstrument();
      return;
    }
    $instrument = $this->PD['instrid'];
    if (isset($this->PD['delete']) && isset($this->PD['bookid'])) {
      $this->deleteBooking();
      echo $this->_calendarViewLink($instrument);
    } elseif (isset($this->PD['isodate']) && 
                    ! isset($this->PD['bookid']) && ! isset($this->PD['startticks']) ) {
      $this->instrumentDay();
      echo $this->_calendarViewLink($instrument);
    } elseif (isset($this->PD['bookid']) && isset($this->PD['edit'])) {
      $this->editBooking();
      echo $this->_calendarViewLink($instrument);
    } elseif (isset($this->PD['bookid'])) {
      $this->booking();
      echo $this->_calendarViewLink($instrument);
    } elseif ( (isset($this->PD['startticks']) && isset($this->PD['stopticks']))
               || (isset($this->PD['bookwhen-time']) && isset($this->PD['bookwhen-date']) && isset($this->PD['duration']) ) ) {
      $this->createBooking();
      echo $this->_calendarViewLink($instrument);
    } elseif (isset($this->PD['instrid'])) {
      $this->instrumentMonth();
      echo "<br /><br /><a href='".makeURL('view')."'>Return to instrument list</a>";
    } else {
      # shouldn't get here
      $err = 'Invalid action specification in action/view.php::go()';
      $this->log($err);
      trigger_error($err, E_USER_WARNING);
      $this->selectInstrument();
    }
  }

  function mungeInputData() {
    parent::mungeInputData();
    if (isset($this->PD['caloffset']) && preg_match("/\d\d\d\d-\d\d-\d\d/", $this->PD['caloffset'])) {
      $then = new SimpleDate($this->PD['caloffset']);
      $now = new SimpleDate(time());
      $this->PD['caloffset'] = floor($then->dsDaysBetween($now));
    }
    echoData($this->PD, 0);
  }
  
  /**
  * Calculate calendar offset in days
  *
  * calculates the number of days between the current date and the last date that was selected
  * by the user (in making a booking etc)
  * @return string URL string for inclusion in href (examples: '28' '-14')
  */
  function _offset() {
    $now = new SimpleDate(time());
    if (isset($this->PD['isodate'])) {
      $then = new SimpleDate($this->PD['isodate']);
      return floor($then->dsDaysBetween($now));
    } elseif (isset($this->PD['startticks'])) {
      $then = new SimpleDate($this->PD['startticks']);
      return $then->datestring;
    } elseif (isset($this->PD['bookwhen-date'])) {
      $then = new SimpleDate($this->PD['bookwhen-date']);
      return floor($then->dsDaysBetween($now));
    }
  }
    
  /**
  * Makes a link back to the current calendar
  *
  * @return string URL string for link back to calendar view
  */
  function _calendarViewLink($instrument) {
    return '<br /><br /><a href="'.
        makeURL('view', array('instrid'=>$instrument, 'caloffset'=>$this->_offset()))
      .'">Return to calendar view</a>';
  }

  /**
  * Select which instrument for which the calendar should be displayed
  */
  function selectInstrument() {
    $instrselect = new AnchorTableList('Instrument', 'Select which instrument to view', 3);
    if ($this->auth->isSystemAdmin()) {
      $instrselect->connectDB('instruments', 
                            array('id', 'name', 'longname', 'location')
                            );
    } else {
      $instrselect->connectDB('instruments', 
                            array('id', 'name', 'longname', 'location'),
                            'userid='.qw($this->auth->getEUID()),
                            'name', 
                            'id', 
                            NULL, 
                            array('permissions'=>'instrid=id'));
    }
    $instrselect->hrefbase = makeURL('view', array('instrid'=>'__id__'));
    $instrselect->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'), ' %20.20s', array('location'));
    echo $instrselect->display();
  }

  /**
  * Display the monthly calendar for the selected instrument
  */
  function instrumentMonth() {
    global $CONFIG;
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    // Show a window $row['calendarlength'] weeks long starting $row['calendarhistory'] weeks 
    // before the current date. Displayed week always starts on Monday
    $offset = issetSet($this->PD, 'caloffset');
    $now = new SimpleDate(time());
    $now->dayRound();
    $start = $now;
    $start->addDays($offset);
    
    // check to see if this is an allowable calendar view (not too far into the future)
    $callength = 7*$row['callength'];
    $totaloffset = $offset + $callength - 7*$row['calhistory'] - $start->dow();
    $this->log("Found total offset of $totaloffset, ".$row['calfuture']);
    
    //admin users are allowed to see further into the future.
    $this->_checkBookingAuth(-1);
    if ($totaloffset > $row['calfuture'] && !$this->_isAdminView) {
      #echo "Found total offset of $totaloffset, but only ".$row['calfuture']." is permitted. ";
      $start = $now;
      $offset = $row['calfuture'] - $callength + 7*$row['calhistory'] + 7;
      $start->addDays($offset);
      #echo $start->datetimestring;
    }
    
    // jump backwards to the start of that week.
    $day = $start->dow(); // the day of the week, 0=Sun, 6=Sat
    $start->addDays(1-7*$row['calhistory']-$day);
        
    $stop = $start;
    $stop->addDays($callength);
    
    $cal = new Calendar($start, $stop, $this->PD['instrid']);

    $daystart    = new SimpleTime($row['usualopen']);
    $daystop     = new SimpleTime($row['usualclose']);
    //configure the calendar view granularity (not the same as booking granularity)
    $granularity = $row['calprecision'];
    $timelines   = $row['caltimemarks'];
    $cal->setTimeSlotPicture($row['timeslotpicture']);
    #$granularity = 60*60;
//     echo $cal->display();
    $cal->href=makeURL('view', array('instrid'=>$this->PD['instrid']));
    $cal->isAdminView = $this->_isAdminView;
    $cal->setOutputStyles('', $CONFIG['calendar']['todaystyle'], 
                preg_split('{/}',$CONFIG['calendar']['monthstyle']), 'm');
    echo $this->displayInstrumentHeader($row);
    echo $this->_linksForwardBack(($offset-$callength),
                                  0,($offset+$callength),
                                  $totaloffset <= $row['calfuture'] || $this->_isAdminView);
    echo $cal->displayMonthAsTable($daystart,$daystop,$granularity,$timelines);
    echo $this->displayInstrumentFooter($row);
  }

  /**
  * Generate back | today | forward links for the calendar
  * @return string html for links
  */
  function _linksForwardBack($back, $today, $forward, $showForward=true, $extra=array()) {
    return '<div style="text-align:center">'
        .'<a href="'.makeURL('view', array_merge(array('instrid'=>$this->PD['instrid'], 'caloffset'=>$back), $extra)).'">&laquo; earlier</a> | '
        .'<a href="'.makeURL('view', array_merge(array('instrid'=>$this->PD['instrid'], 'caloffset'=>$today), $extra)).'">today</a> '
        .($showForward ? 
                ' | '
                .'<a href="'.makeURL('view', array_merge(array('instrid'=>$this->PD['instrid'], 'caloffset'=>$forward), $extra)).'">later &raquo;</a>'
                : ''
          )
        .'</div>';
  }

  /**
  * Display a single day's calendar for the selected instrument
  */
  function instrumentDay() {
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    $granularity = $row['calprecision'];
    $timelines   = $row['caltimemarks'];
    
    $today = new SimpleDate(time());
    $today->dayRound();
    $start = new SimpleDate($this->PD['isodate']);
    $start->dayRound();
    $offset = issetSet($this->PD, 'caloffset');
    $start->addDays($offset);
    $totaloffset = $start->daysBetween($today);
    $maxfuture = $row['calfuture'] + 7 - $today->dow();
    //admin users are allowed to see further into the future.
    $this->_checkBookingAuth(-1);
    $this->log("Found total offset of $totaloffset, $maxfuture");
    if ($totaloffset > $maxfuture && !$this->_isAdminView) {
      #echo "Found total offset of $totaloffset, but only ".$row['calfuture']." is permitted. ";
      $delta = $maxfuture-$totaloffset;
      #echo "Changing offset by $delta\n";
      $start->addDays($delta);
    }
    $stop = $start;
    $stop->addDays(1);
    $cal = new Calendar($start, $stop, $this->PD['instrid']);
    $cal->setTimeSlotPicture($row['timeslotpicture']);

    # FIXME: get this from the instrument table?
    $daystart    = new SimpleTime('00:00:00');
    $daystop     = new SimpleTime('23:59:59');
//     echo $cal->display();
    $cal->href = makeURL('view', array('instrid'=>$this->PD['instrid']));
    $cal->isAdminView = $this->_isAdminView;
    $cal->setOutputStyles('', 'caltoday', array('monodd', 'moneven'), 'm');
    echo $this->displayInstrumentHeader($row);
    echo $this->_linksForwardBack('-1', -1*$totaloffset, '+1', 
                                $maxfuture > $totaloffset || $this->_isAdminView, 
                                array('isodate'=>$start->datestring));
    echo $cal->displayDayAsTable($daystart,$daystop,$granularity,$timelines);
    echo $this->displayInstrumentFooter($row);
  }

  /**
  * Make a new booking
  */
  function createBooking() {
    $start = new SimpleDate(issetSet($this->PD, 'startticks'));
    $stop  = new SimpleDate(issetSet($this->PD, 'stopticks'));
    $duration = new SimpleTime($stop->subtract($start));
    $this->log($start->datetimestring.', '.$duration->timestring.', '.$start->dow());
    $this->_editCreateBooking(-1, $start->datetimestring, $duration->timestring);
  }

  /**
  * Editing an existing booking
  */
  function editBooking() {
    $start = new SimpleDate(issetSet($this->PD, 'startticks'));
    $this->_editCreateBooking($this->PD['bookid'], $start->datetimestring, -1);
  }

  /**
  * Do the hard work to edit or create the booking
  */
  function _editCreateBooking($bookid, $start, $duration) {
    $ip = $this->auth->getRemoteIP();
    //echo $ip;
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    $booking = new BookingEntry($bookid,$this->auth,$this->PD['instrid'], $row['mindatechange'],$ip, 
                                $start, $duration, $row['timeslotpicture']);
    $this->_checkBookingAuth($booking->fields['userid']->getValue());
    if (! $this->_haveWriteAccess) {
      return $this->_forbiddenError('Edit booking');
    }
    $booking->update($this->PD);
    $booking->checkValid();
    echo $this->displayInstrumentHeader($row);
    echo $this->reportAction($booking->sync(), 
              array(
                  STATUS_OK =>   ($bookid < 0 ? 'Booking made' : 'Booking updated'),
                  STATUS_ERR =>  'Booking could not be made:<br/><br/>'.$booking->errorMessage
              )
            );
    echo $booking->display();
    $submit = ($booking->id < 0) ? 'Make booking' : 'Update booking';
    $delete = ($booking->id >= 0 && $booking->deletable) ? 'Delete booking' : '';
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
    echo $this->displayInstrumentFooter($row);
  }

  /**
  * Display a booking in read-only format (i.e. not in a form to allow it to be edited)
  */
  function booking() {
    $booking = new BookingEntryRO($this->PD['bookid']);
    $this->_checkBookingAuth($booking->data->userid);
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    echo $this->displayInstrumentHeader($row);
    echo $booking->display($this->_isAdminView, $this->_isOwnBooking);
    if ($this->_isOwnBooking || $this->_isAdminView) {
      echo "<p><a href='"
            .makeURL('view', 
                array('instrid' => $this->PD['instrid'], 
                      'bookid'  => $this->PD['bookid'], 
                      'edit'    => 1,
                      'isodate' => $this->PD['isodate']))
            ."'>Edit booking</a></p>\n";
    }
  }
  
  /**
  * Delete a booking
  */
  function deleteBooking() {
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    $booking = new BookingEntry($this->PD['bookid'], $this->auth, $this->PD['instrid'], $row['mindatechange']);
    $this->_checkBookingAuth($booking->fields['userid']->getValue());
    if (! $this->_haveWriteAccess) {
      return $this->_forbiddenError('Delete booking');
    }
    echo $this->displayInstrumentHeader($row);
    echo $this->reportAction($booking->delete(), 
              array(
                  STATUS_OK =>   'Booking deleted',
                  STATUS_ERR =>  'Booking could not be deleted:<br/><br/>'.$booking->errorMessage
              )
            );  
  }

  /**
  * Set flags according to the permissions of the logged in user
  * @todo replace with new permissions system
  */
  function _checkBookingAuth($userid) {
    $this->_isOwnBooking = $this->auth->isMe($userid);
    $this->_isAdminView = $this->auth->isSystemAdmin() 
                  || $this->auth->isInstrumentAdmin($this->PD['instrid']);
    $this->_haveWriteAccess = $this->_isOwnBooking || $this->_isAdminView;
  }

  /**
  * Polite "go away" message if someone tries to delete a booking that they can't
  */
  function _forbiddenError($msg) {
    $this->log('Action forbidden: '.$msg);
    echo $this->reportAction(STATUS_FORBIDDEN, 
              array(
                  STATUS_FORBIDDEN => $msg.': <br/>Sorry, you do not have permission to do this.',
              )
            );
    return STATUS_FORBIDDEN;
  }
    
  /**
  * Display a heading on the page with the instrument name and location
  */
  function displayInstrumentHeader($row) {
    $t = '<h2 class="instrumentname">'
        .$row['longname']
        .'</h2>'
       .'<p class="instrumentlocation">'
       .$row['location'].'</p>'."\n";
    $t .= $this->_instrumentNotes($row, false);
    return $t;
  }
  
  /**
  * Display a footer for the page with the instrument comments and who looks after the instrument
  */
  function displayInstrumentFooter($row) {
    $t = '';
    $t .= $this->_instrumentNotes($row, true);
    if ($row['supervisors']) {
      $t .= '<h3>Instrument supervisors</h3>';
      $t .= '<ul>';
      foreach(preg_split('/,\s*/', $row['supervisors']) as $username) {
        $user = quickSQLSelect('users', 'username', $username);
        $t .= '<li><a href="mailto:'. $user['email'] .'">'. $user['name'] .'</a></li>';
      }
      $t .= '</ul>';
    }
    return $t;
  }

  /**
  * Display the instrument comment in either header or footer as configured
  *
  * @param array $row        instrument db row
  * @param boolean $footer   called in the footer
  * @returns string          header/footer to display for notes section
  *
  * @global array system config 
  */
  function _instrumentNotes($row, $footer=true) {
    global $CONFIG;
    $t = '';
    $notesbottom = issetSet($CONFIG['calendar'], 'notesbottom', true);
    if ($notesbottom == $footer && $row['calendarcomment']) {
      $t = '<div class="calendarcomment">'
          .'<p>'.preg_replace("/\n+/", '</p><p>', $row['calendarcomment']).'</p></div>';
    }
    return $t;
  }


} // class ActionView
?> 
