<?php
/**
* Thank the user for using the system.
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: logout.php,v 1.7 2006/01/06 10:07:20 stuart Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Thank the user for using the system.
*  
* Destruction of login credentials is undertaken by the class BumbleBeeAuth
* @package    Bumblebee
* @subpackage Actions
*/
class ActionLogout extends ActionAction {

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionLogout($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->auth->logout();
  }

  function go() {
    $url = makeURL('');
    echo "
      <h2>Successfully logged out</h2>
      <p>Thank you for using Bumblebee!</p>
      <p>(<a href='$url'>login</a>)</p>
    ";
  }
}

?> 
