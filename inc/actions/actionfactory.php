<?php
/**
* Create Action object that will do whatever category of work is required in this invocation 
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: actionfactory.php,v 1.22.2.2 2006/05/16 15:05:14 stuart Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/**  permissions definitions */
// require_once 'inc/permissions.php';

/**  basic functions (user functions) */
// require_once 'login.php';
// require_once 'logout.php';
// require_once 'view.php';
// require_once 'password.php';

// only some users can masquerade, but include this by default, 
// checking permissions is done by the Masquerade class itself
// require_once 'masquerade.php';

//admin functions: only include these files if they are necessary (security + efficiency)
//these classes do not check permissions of the user -- this must be done by the instantiating code
// if ($auth->isadmin) {
  /**  admin functions (restricted inclusion) */
//   require_once 'groups.php';
//   require_once 'projects.php';
//   require_once 'users.php';
//   require_once 'instruments.php';
//   require_once 'consumables.php';
//   require_once 'consume.php';
//   require_once 'deletedbookings.php';
//   require_once 'costs.php';
//   require_once 'specialcosts.php';
//   require_once 'userclass.php';
//   require_once 'instrumentclass.php';
//   //require_once 'adminconfirm.php';
//   require_once 'emaillist.php';
//   // require_once 'report.php';
//   require_once 'export.php';
//   require_once 'billing.php';
//   require_once 'backupdatabase.php';
// }

// require_once 'unknownaction.php';
require_once 'inc/typeinfo.php';
require_once 'actions.php';

/**
* Factory class for creating Action objects
*  
* An Action is a single operation requested by the user. What action is to be performed
* is determined by the action-triage mechanism in the class ActionFactory.
*
* Everything done by an application suite (e.g. edit/create a user) can be reduced to 
* broad categories of actions (e.g. edituser) which can be encapsulated within an 
* object framework in which every object has the same interface.
*
* The invoking code is thus boiled down to something quite simple:
* <code>
* $action = new ActionFactory($params);
* $action->go();
* </code>
* where ActionFactory does the work of deciding what is to be done on this
* invocation and instantiates the appropriate action object.
* 
* The action object then actually performs the tasks desired by the user.
*
* Here, we use the data from the browser (a PATH_INFO variable from the URL in the form
* index.php/user) to triage the transaction.
*
* @package    Bumblebee
* @subpackage Actions
*/
class ActionFactory {
  /** @var string          the user-supplied "verb" (name of the action) e.g. "edituser" */
  var $_verb; 
  /** @var string          the actual user-supplied verb... we may pretend to do something else due to permissions  */
  var $_original_verb;
  /**  @var string         Each action has a description associated with it that we will put into the HTML title tag  */
  var $title;
  /**  @var ActionAction   The action object (some descendent of the ActionAction class)   */
  var $_action;
  /**  @var BumbleBeeAuth  The user's login credentials object  */
  var $_auth;
  /**  @var array          user-supplied data from the PATH_INFO and GET sections of the URL  */
  var $PDATA;
  /**  @var string         The 'verb' that should follow this current action were a standard workflow being followed   */
  var $nextaction;
  /**  @var array          ActionListing object  */
  var $actionListing;
  /**  @var ActionData     The action data object for this action  */
  var $_actionData;
  
  /** 
  * Constructor for the class
  *
  * - Parse the submitted data
  * - work out what action we are supposed to be performing
  * - set up the title tag for the browser
  * - create the ActionAction descendent object that will perform the task
  *
  * @param BumbleBeeAuth $auth  user login credentials object
  */
  function ActionFactory($auth) {
    $this->_auth = $auth;
    #$this->PDATA = $this->_eatPathInfo();
    $this->PDATA = $this->_eatGPCInfo();
    $this->actionListing = new ActionListing();
    $this->_verb = $this->_checkActions();
    $this->_actionData = $this->actionListing->actions[$this->_verb];
    $this->nextaction = $this->_actionData->next_action();
    $this->title = $this->_actionData->title();
    $this->_action = $this->_makeAction();
  }
  
  /** 
  * Fire the action: make things actually happen now
  */
  function go() {
    $this->_action->go();
  }

  /** 
  * Determine what action should be performed.
  *
  * This is done by:
  * - checking that the user is logged in... if not, they *must* login
  * - looking for hints in the user-supplied data for what the correct action is
  * - checking that the user is an admin user if admin functions were requested
  *
  * @return string the name of the action (verb) to be undertaken
  */
  function _checkActions() {
    $action = '';
  
    # first, we need to determine if we are actually logged in or not
    # if we are not logged in, the the action *has* to be 'login'
    if (! $this->_auth->isLoggedIn()) return 'login';
  
    #We can have action verbs past to us in three different ways.
    # 1. first PATH_INFO
    # 2. explicit PATH_INFO action=
    # 3. action= form fields 
    # Later specifications are used in preference to earlier ones
  
    $explicitaction = issetSet($this->PDATA, 'forceaction');
    $pathaction = issetSet($this->PDATA, 'action');
    $formaction = issetSet($_POST, 'action');
    #$pathaction = $PDATA['action'];
    #$formaction = $_POST['action'];
  
    if ($explicitaction) $action = $explicitaction;
    if ($pathaction) $action = $pathaction;
    if ($formaction) $action = $formaction;
  
    $this->_original_verb = $action;
    
    // dump if unknown action
    if (! $this->actionListing->action_exists($action)) {
      return 'unknown';
    }
    // protect admin functions
    if (! $this->_auth->permitted($this->actionListing->actions[$action]->permissions)) {
      return 'forbidden!';
    }
  
    # We also need to check to see if we are trying to change privileges
    #if (isset($_POST['changemasq']) && $_POST['changemasq']) return 'masquerade';
    
    return $action;
  }

  /** 
  * Trigger a restart of the action or a new action
  *
  * Sometimes, an action may need to be restarted or the action changed (e.g. logout => login)
  *
  * @param string $newaction  new verb for the new action
  */
  function _actionRestart($newaction) {
    $this->_verb=$newaction;
    $this->_action = $this->_makeAction();
    $this->go();
  }
  
  /** 
  * Parse the user-supplied data in PATH_INFO part of URL
  *
  * @returns array  (key => $data)
  */
  function _eatPathInfo() {
    $pd = array();
    $pathinfo = issetSet($_SERVER, 'PATH_INFO');
    if ($pathinfo) {
      $path = explode('/', $pathinfo);
      $pd['action'] = $path[1];
      $actions = preg_grep("/^action=/", $path);
      $forceaction = array_keys($actions);
      if (isset($forceaction[0])) {
        preg_match("/^action=(.+)/",$path[$forceaction[0]],$m);
        $pd['forceaction'] = $m[1];
        $max = $forceaction[0];
      } else {
        $max = count($path);
      }
      for($i=2; $i<$max; $i++) {
        $pd[$i-1] = $path[$i];
      }
    }
    return $pd;
  }
  
  /** 
  * Parse the user-supplied data from either the GET or POST data
  *
  * @returns array  (key => $data)
  */
  function _eatGPCInfo() {
    //$pd = $this->_eatPathInfo();
    //return array_merge($pd, $_GET, $_POST);
    return array_merge($_GET, $_POST);
  }
  
  /** 
  * Is it ok to allow the HTML template to dump to the browser from the output buffer?
  *
  * (see BufferedAction descendents)
  *
  * @returns boolean  ok to dump to browser
  */
  function ob_flush_ok() {
    return $this->_action->ob_flush_ok;
  }
  
  /** 
  * Cause buffered actions to output their data to the browser
  *
  * (see BufferedAction descendents)
  */
  function returnBufferedStream() {
    if (method_exists($this->_action, 'sendBufferedStream')) {
      $this->_action->sendBufferedStream();
    }
  }

  /** 
  * create the action object (a descendent of ActionAction) for the user-defined verb
  */
  function _makeaction() {
    /** to reduce PHP processing overhead, include only the file that is required for this action */
    require_once $this->_actionData->include_file();
    switch ($this->_verb) {
      case 'forbidden!':
        return new ActionUnknown($this->_original_verb, 1);
      case 'unknown':
        return new ActionUnknown($this->_original_verb);
      default:
        $class = $this->_actionData->action_class();
        return new $class ($this->_auth, $this->PDATA);
    }
  }
//     switch ($act[$this->_verb]) {
//       case $act['login']:
//         $this->nextaction = 'view';
//         return new ActionPrintLoginForm($this->_auth, $this->PDATA);
//       case $act['logout']:
//         $this->_auth->logout();
//         return new ActionLogout($this->_auth, $this->PDATA);
//       case $act['view']:
//         return new ActionView($this->_auth, $this->PDATA);
//       case $act['passwd']:
//         return new ActionPassword($this->_auth, $this->PDATA);
//       // instrument-admin only
//       case $act['masquerade']:
//         return new ActionMasquerade($this->_auth, $this->PDATA);
//       // admin only
//       case $act['groups']:
//         return new ActionGroup($this->_auth, $this->PDATA);
//       case $act['projects']:
//         return new ActionProjects($this->_auth, $this->PDATA);
//       case $act['users']:
//         return new ActionUsers($this->_auth, $this->PDATA);
//       case $act['instruments']:
//         return new ActionInstruments($this->_auth, $this->PDATA);
//       case $act['consumables']:
//         return new ActionConsumables($this->_auth, $this->PDATA);
//       case $act['consume']:
//         return new ActionConsume($this->_auth, $this->PDATA);
//       case $act['deletedbookings']:
//         return new ActionDeletedBookings($this->_auth, $this->PDATA);
//       case $act['costs']:
//         return new ActionCosts($this->_auth, $this->PDATA);
//       case $act['specialcosts']:
//         return new ActionSpecialCosts($this->_auth, $this->PDATA);
//       case $act['instrumentclass']:
//         return new ActionInstrumentClass($this->_auth, $this->PDATA);
//       case $act['userclass']:
//         return new ActionUserClass($this->_auth, $this->PDATA);
//       /*case $act['bookmeta']:
//         return new ActionBookmeta();
//       case $act['adminconfirm']:
//         return new ActionAdminconfirm();*/
//       case $act['emaillist']:
//         return new ActionEmaillist($this->_auth, $this->PDATA);
//       case $act['backupdb']:
//         return new ActionBackupDB($this->_auth, $this->PDATA);
//       case $act['report']:
//         return new ActionReport($this->_auth, $this->PDATA);
//       case $act['export']:
//         return new ActionExport($this->_auth, $this->PDATA);
//       case $act['billing']:
//         return new ActionBilling($this->_auth, $this->PDATA);
//       case $act['forbidden!']:
//         return new ActionUnknown($this->_original_verb, 1);
//       default:
//         return new ActionUnknown($this->_original_verb);
//     }
     
}
 //class ActionFactory
 
?>
