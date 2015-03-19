<?php
/**
* Allow the admin user to masquerade as another user to make some bookings. A bit like "su".
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: masquerade.php,v 1.11 2006/01/06 10:07:20 stuart Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/** user object */
require_once 'inc/bb/user.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Allow the admin user to masquerade as another user to make some bookings. A bit like "su".
*  
* @package    Bumblebee
* @subpackage Actions
*/
class ActionMasquerade extends ActionAction {

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionMasquerade($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['id'])) {
      $this->selectUser();
    } elseif ($this->PD['id'] == -1) {
      $this->removeMasquerade();
    } else {
      $this->assumeMasquerade();
    }
    echo "<br /><br /><a href='".makeURL('masquerade')."'>Return to user list</a>";
  }

  /**
  * Print an HTML list of users to allow the user to masquerade as for making bookings
  *  
  */
  function selectUser() {
    $select = new AnchorTableList('Users', 'Select which user to masquerade as');
    $select->connectDB('users', array('id', 'name', 'username'),'id!='.qw($this->auth->uid));
    $select->hrefbase = makeURL('masquerade', array('id'=>'__id__'));
    $select->setFormat('id', '%s', array('name'), ' %s', array('username'));
    if ($this->auth->amMasqed()) {
      $select->list->prepend(array('-1','End current Masquerade'));
      echo 'Currently wearing the mask of:'
        .'<blockquote class="highlight">'
        .$this->auth->ename.' ('.$this->auth->eusername.')</blockquote>';
    }
    echo $select->display();
  }

  /**
  * Put on the selected mask 
  *  
  */
  function assumeMasquerade() {
    if ($row = $this->auth->assumeMasq($this->PD['id'])) {
      echo '<h3>Masquerade started</h3>'
            .'<p>The music has started and you are now wearing the mask that looks like:</p>'
            .'<blockquote class="highlight">'.$row['name'].' ('.$row['username'].')</blockquote>'
            .'<p>Is that a scary thought?</p>'
            .'<p>When you are tired of wearing your mask, remove it by returning to the '
            .'Masquerade menu once more.</p>';
      echo '<p>Note that even with your mask on, you can only edit/create bookings on instruments '
            .'for which you have administrative rights.</p>';
    } else {
      echo '<div class="msgerror"><h3>Masquerade Error!</h3>'
          .'<p>Sorry, but if you\'re comming to a masquerade ball, '
          .'you really should wear a decent mask!</p>'
          .'<p>Masquerade didn\'t start properly: mask failed to apply and music didn\'t start.</p>'
          .'<p>Are you sure you\'re allowed to do this?</p></div>';
    }
  }
  
  /**
  * Remove the mask 
  *  
  */
  function removeMasquerade() {
    $this->auth->removeMasq();
    echo '<h3>Masquerade finished</h3>'
          .'<p>Oh well. All good things have to come to an end. '
          .'The music has stopped and you have taken your mask off. </p>'
          .'<p>Hope you didn\'t get too much of a surprise when eveyrone else took their masks off too!</p>';
  }
  
} //ActionMasquerade
?>
