<?php
/**
* Permit a local user to change their password
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: password.php,v 1.8 2006/01/06 10:07:20 stuart Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/** parent object */
require_once 'inc/actions/actionaction.php';
/** user editing object */
require_once 'inc/bb/user.php';

/**
* Permit a local user to change their password
*
* @package    Bumblebee
* @subpackage Actions
* @todo extend form to include current password and duplicate password
*/
class ActionPassword extends ActionAction {

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionPassword($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    $this->edit();
    echo "<br /><br /><a href='".makeURL('')."'>Return to main menu</a>";
  }

  function edit() {
    $user = new User($this->auth->uid, true);
    $user->update($this->PD);
    #$project->fields['defaultclass']->invalid = 1;
    $user->checkValid();
    echo $this->reportAction($user->sync(), 
          array(
              STATUS_OK =>   'Password changed successfully.',
              STATUS_ERR =>  'Password could not be changed: '.$user->errorMessage
          )
        );
    echo $user->display();
    echo "<input type='submit' name='submit' value='Change password' />";
  }
}

?> 
