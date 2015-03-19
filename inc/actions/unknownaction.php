<?php
/**
* Error handling class for unknown actions
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: unknownaction.php,v 1.11 2006/01/06 10:07:20 stuart Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Error handling class for unknown actions
* @package    Bumblebee
* @subpackage Actions
*/
class ActionUnknown extends ActionAction {
  var $action;
  var $forbiden;

  /**
  * Initialising the class 
  * 
  * @param  string  $action requested action ('verb')
  * @param  boolean $forbidden  (optional) 
  * @return void nothing
  */
  function ActionUnknown($action, $forbidden=0) {
    parent::ActionAction('','');
    $this->action = $action;
    $this->forbidden = $forbidden;
  }

  function go() {
    global $ADMINEMAIL;
    echo '<h2>Error</h2><div class="msgerror">';
    if ($this->forbidden) {
      echo '<p>Sorry, you don\'t have permission to perform the '
          .'action "'.$this->action.'".</p>';
    } else {
      echo '<p>An unknown error occurred. I was asked to perform the '
          .'action "'.$this->action.'" but I don\'t know how to do that.</p>';
    }
    echo '<p>Please contact <a href="mailto:'.$ADMINEMAIL.'">the system '
        .'administrator</a> for more information.</p></div>';
  }
  
  
  
} //ActionUnknown
?> 
