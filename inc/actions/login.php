<?php
/**
* Print a polite login form
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: login.php,v 1.13.2.1 2006/06/12 09:40:58 stuart Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Print a polite login form
*  
* Authentication is undertaken by the class BumbleBeeAuth
* @package    Bumblebee
* @subpackage Actions
*/
class ActionPrintLoginForm extends ActionAction {
  
  /**
  * Initialising the class 
  * 
  * @return void nothing
  */
  function ActionPrintLoginForm() {
  }

  function go() {
    global $CONFIG;
    if (isset($CONFIG['display']['LoginPage']) && ! empty($CONFIG['display']['LoginPage'])) {
      echo $CONFIG['display']['LoginPage'];
    }
    echo '
      <h2>Login required</h2>
      <p>Please login to view or book instrument usage</p>
      <table>
      <tr>
        <td>Username:</td>
        <td><input name="username" type="text" size="16" /></td>
      </tr>
      <tr>
        <td>Password:</td>
        <td><input name="pass" type="password" size="16" /></td>
      </tr>
      <tr>
        <td></td>
        <td><input name="submit" type="submit" value="login" /></td>
      </tr>
      </table>
    ';
  }
}

?> 
