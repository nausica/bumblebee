<?php
/**
* Edit/create/delete consumables records
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: consume.php,v 1.23 2006/01/06 10:07:20 stuart Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/** ConsumableUse object */
require_once 'inc/bb/consumableuse.php';
/** Consumable object */
require_once 'inc/bb/consumable.php';
/** User object */
require_once 'inc/bb/user.php';
/** DateRange object */
require_once 'inc/bb/daterange.php';
/** date manipulation routines */
require_once 'inc/date.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete consumables records
* @package    Bumblebee
* @subpackage Actions
*/
class ActionConsume extends ActionAction {

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionConsume($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    if (isset($this->PD['list'])) {
      $daterange = new DateRange('daterange', 'Select date range', 
                      'Enter the dates over which you want to report consumable use');
      $daterange->update($this->PD);
      $daterange->checkValid();
      if ($daterange->newObject || !$daterange->isValid) {
        $daterange->setDefaults(DR_PREVIOUS, DR_QUARTER);
        echo $daterange->display($this->PD);
      } elseif (isset($this->PD['consumableid'])) {
        $this->listConsumeConsumable($this->PD['consumableid'], $daterange);
      } elseif (isset($this->PD['user'])) {
        $this->listConsumeUser($this->PD['user'], $daterange);
      }
    } elseif (isset($this->PD['delete'])) {
      $this->delete();
    } elseif (  
                (! isset($this->PD['id'])) && 
                ( 
                  (! isset($this->PD['user'])) || (! isset($this->PD['consumableid'])) 
                )
             ) {
      if (! isset($this->PD['user'])) {
        $this->selectConsumeUser();
      }
      if (! isset($this->PD['consumableid'])) {
        $this->selectConsumeConsumable();
      }
    } else {
      $this->edit();
    }
    echo "<br /><br /><a href='".makeURL('consume')."'>Return to consumable use list</a>";
  }

//   function mungeInputData() {
//     $this->PD = array();
//     foreach ($_POST as $k => $v) {
//       $this->PD[$k] = $v;
//     }
//     $lPDATA = $this->PDATA;
//     array_shift($lPDATA);
//     while (count($lPDATA)) {
//       if (isset($lPDATA[0]) && $lPDATA[0]=='user' && is_numeric($lPDATA[1])) {
//         array_shift($lPDATA);
//         $this->PD['user'] = array_shift($lPDATA);
//       } elseif (isset($lPDATA[0]) && $lPDATA[0]=='consumable' && is_numeric($lPDATA[1])) {
//         array_shift($lPDATA);
//         $this->PD['consumableid'] = array_shift($lPDATA);
//       } elseif (isset($lPDATA[0]) && $lPDATA[0]=='list') {
//         $this->PD['list'] = 1;
//         array_shift($lPDATA);
//       } elseif (isset($lPDATA[0]) && is_numeric($lPDATA[0])) {
//         $this->PD['id'] = array_shift($lPDATA);
//       } else {
//         //this record is unwanted... drop it
//         array_shift($lPDATA);
//       }
//     }
//     #$PD['defaultclass'] = 12;
//     echoData($this->PD, 0);
//   }

  /**
  * Select which user is consuming the item
  */
  function selectConsumeUser() {
    $path = array();
    if (isset($this->PD['consumableid'])) {
      $path['consumableid'] = $this->PD['consumableid'];
    }
    $userselect = new AnchorTableList('Users', 'Select which user is consuming');
    $userselect->deleted = false;  // don't show deleted users
    $userselect->connectDB("users", array('id', 'name', 'username'));
    $userselect->hrefbase = makeURL('consume', array_merge($path, array('user' => '__id__')));
    $userselect->setFormat('id', '%s', array('name'), ' %s', array('username'));

    if (isset($this->PD['consumableid']) && $this->PD['consumableid'] > 0) {
      echo "<p><a href='".makeURL('consume', array_merge($path, array('list'=>1)))."'>View listing</a> "
          ."for selected consumable</p>\n";
    }
    echo $userselect->display();
    echo '<br />';
  }

  /**
  * Select what item is being consumed
  */
  function selectConsumeConsumable() {
    $path = array();
    if (isset($this->PD['user'])) {
      $path['user'] = $this->PD['user'];
    }
    $consumableselect = new AnchorTableList('Consumables', 'Select which Consumables to use');
    $consumableselect->deleted = false;   // don't show deleted consumables
    $consumableselect->connectDB('consumables', array('id', 'name', 'longname'));
    $consumableselect->hrefbase = makeURL('consume', array_merge($path, array('consumableid' => '__id__')));
    $consumableselect->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'));
    
    if (isset($this->PD['user']) && $this->PD['user'] > 0) {
      echo "<p><a href='".makeURL('consume', array_merge($path, array('list'=>1)))."'>View listing</a> "
          .'for selected user</p>'."\n";
    }
    echo $consumableselect->display();
  }

  function edit() {
    $recordid = isset($this->PD['id']) ? $this->PD['id'] : -1;
    $userid   = isset($this->PD['user']) ? $this->PD['user'] : -1;
    $consumableid = isset($this->PD['consumableid']) ? $this->PD['consumableid'] : -1;
    $uid = $this->auth->uid;
    $ip = $this->auth->getRemoteIP();
    $today = new SimpleDate(time());
    $rec = new ConsumableUse($recordid, $userid, $consumableid,
                              $uid, $ip, $today->datestring);
    $rec->update($this->PD);
    $rec->checkValid();
    echo $this->reportAction($rec->sync(), 
          array(
              STATUS_OK =>   ($recordid < 0 ? 'Consumption recorded' : 'Consumption record updated'),
              STATUS_ERR =>  'Consumption record could not be changed: '.$rec->errorMessage
          )
        );
    echo $rec->display();
    if ($rec->id < 0) {
      $submit = 'Record consumable use';
      $delete = '0';
    } else {
      $submit = 'Update entry';
      $delete = 'Delete entry';
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function delete() {
    $rec = new ConsumableUse($this->PD['id']);
    echo $this->reportAction($rec->delete(), 
              array(
                  STATUS_OK =>   'Consumption record deleted',
                  STATUS_ERR =>  'Consumption record could not be deleted:<br/><br/>'.$rec->errorMessage
              )
            );  
  }

  /**
  * list the consumption records for this particular consumable
  *
  * @param integer $consumableID   table ID number of consumable to be displayed
  * @param DateRange $daterange    time period over which consumption records are to be displayed
  * @return void nothing
  */
  function listConsumeConsumable($consumableID, $daterange) {
    $start = $daterange->getStart();
    $stop  = $daterange->getStop();
    $stop->addDays(1);
    $consumable = new Consumable($consumableID);
    echo '<p>Consumption records for '
        .$consumable->fields['name']->value."</p>\n";
    $recselect = new AnchorTableList('Consumption Record', 'Select the consumption record to view',3);
    $recselect->deleted = NULL;
    $recselect->setTableHeadings(array('Date', 'User','Quantity'));
    $recselect->connectDB('consumables_use',
                          array(array('consumables_use.id','conid'), 'consumable', 'usewhen', 'username', 'name', 'quantity'),
                          'consumable='.qw($consumableID)
                              .' AND usewhen >= '.qw($start->datetimestring)
                              .' AND usewhen < '.qw($stop->datetimestring),
                          'usewhen',
                          array('consumables_use.id','conid'),
                          NULL,
                          array('users'=>'userid=users.id'));
    $recselect->hrefbase = makeURL('consume', array('id'=>'__id__'));
    $recselect->setFormat('conid', '%s', array('usewhen'), ' %s (%s)', array('name', 'username'), '%s', array('quantity'));

    echo $recselect->display();
  }

  /**
  * list the consumption records for this particular consumable
  *
  * @param integer $userID         table ID number of user to be displayed
  * @param DateRange $daterange    time period over which consumption records are to be displayed
  * @return void nothing
  */
  function listConsumeUser($userID, $daterange) {
    $start = $daterange->getStart();
    $stop  = $daterange->getStop();
    $stop->addDays(1);
    $user = new User($userID, true);
    echo '<p>Consumption records for '
        .$user->fields['username']->value
        .' ('.$user->fields['name']->value.")</p>\n";
    $recselect = new AnchorTableList('Consumption Record', 'Select the consumption record to view',3);
    $recselect->deleted = NULL;
    $recselect->setTableHeadings(array('Date', 'Item','Quantity'));
    $recselect->connectDB('consumables_use',
                          array(array('consumables_use.id','conid'), 'consumable', 'usewhen', 'name', 'longname', 'quantity'),
                          'userid='.qw($userID)
                              .' AND usewhen >= '.qw($start->datetimestring)
                              .' AND usewhen < '.qw($stop->datetimestring),
                          'usewhen',
                          array('consumables_use.id','conid'),
                          NULL,
                          array('consumables'=>'consumable=consumables.id'));
    $recselect->hrefbase = makeURL('consume', array('id'=>'__id__'));
    $recselect->setFormat('conid', '%s', array('usewhen'), ' %s (%30.30s)', array('name', 'longname'), '%s', array('quantity'));

    echo $recselect->display();
  }
}

?> 
