<?php
/**
* User Authorisation, Login and Permissions object
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: auth.php,v 1.29.2.6 2006/05/12 11:58:31 stuart Exp $
* @package    Bumblebee
* @subpackage DBObjects
*/

/** sql manipulation routines */
require_once 'inc/formslib/sql.php';
/** type checking and data manipulation */
require_once 'inc/typeinfo.php';
/** permissions codes */
require_once 'inc/permissions.php';

/**
* User Authorisation, Login and Permissions object
*
* @package    Bumblebee
* @subpackage DBObjects
* @todo Split object into login and permissions objects
* @todo split euid from login
* @todo update permissions system
* @todo documentation
*/
class BumbleBeeAuth {
  var $uid;    //user id from table
  var $username;
  var $name;
  var $email;
  var $isadmin;
  var $euid;            //permit user masquerading like su. Effective UID
  var $ename;           //effective name
  var $eusername;       //effective username
  var $permissions;
  var $system_permissions;
  var $_loggedin=0;
  var $_error = '';
  var $table;
  var $localLogin = 0;
  /** @var integer    debug level (0=off, 10=verbose)  */
  var $DEBUG = 0;

  /**
  *  Create the auth object
  *
  * @param boolean $recheck (optional) ignore session data and check anyway
  * @param string $table  (optional) db table from which login data should be taken
  * @global base path for installation
  */
  function BumbleBeeAuth($recheck = false, $table='users') {
    global $BASEPATH;
    // Only start the session if one has not already been started (e.g. to cope
    // with the situation where session.auto_start=1 in php.ini or where
    // the entire thing is embedded within some other framework.
    // For session.auto_start, the following is enough:
    //      if (! ini_get('session.auto_start')) {
    // But we can check the session_id() (hexadecimal string if session has started
    // empty string "" if it hasn't)
    if (! session_id()) {
      session_name('BumblebeeLogin');
      session_set_cookie_params(ini_get('session.cookie_lifetime'), $BASEPATH.'/');
      session_start();
    }
    $this->table = $table;
    $this->permissions = array();
    if (!$recheck && isset($_SESSION['uid'])) {
      // the we have a session login already done, check it
      $this->_loggedin = $this->_verifyLogin();
      $this->_checkMasq();
    } elseif (isset($_POST['username'])) {
      // then some login info has been provided, so we need to check it
      $this->_loggedin = $this->_login();
    } else {
      // we're not logged in at all
    }
    #FIXME
    if ($this->isadmin) {
      $this->system_permissions = BBPERM_ADMIN_ALL;
    } else {
      if ($this->localLogin) {
        $this->system_permissions = BBPERM_USER_ALL | BBPERM_USER_PASSWD;
      } else {
        $this->system_permissions = BBPERM_USER_ALL;
      }
    }
    if ($this->masqPermitted()) {
      $this->system_permissions |= BBPERM_MASQ;
    }
  }

  function logout() {
    session_destroy();
    $this->_loggedin = 0;
  }

  function isLoggedIn() {
    return $this->_loggedin;
  }

  function loginError() {
    global $CONFIG;
    if ($this->DEBUG || ($CONFIG['auth']['authAdvancedSecurityHole'] && $CONFIG['auth']['verboseFailure'])) {
      return $this->_error;
    } elseif (strpos($this->_error, ':') !== false) {
      // protect any additional info that is in the error string:
      // functions in this class can report the error in the format 'General error: details'
      // Normally, we shouldn't reveal whether it was a bad username or password, 
      // but for debugging purposes, it's nice to have the extra info.
      list($public,$private) = preg_split('/:/', $this->_error);
      return $public;
    } else {
      return $this->_error;
    }
  }

  function _createSession($row) {
    $_SESSION['uid']        = $this->uid        = $row['id'];
    $_SESSION['username']   = $this->username   = $row['username'];
    $_SESSION['name']       = $this->name       = $row['name'];
    $_SESSION['email']      = $this->email      = $row['email'];
    $_SESSION['isadmin']    = $this->isadmin    = $row['isadmin'];
    $_SESSION['localLogin'] = $this->localLogin = $this->localLogin;
  }
  
  function _verifyLogin() {
    // check that the credentials contained in the session are OK
    $uid = $_SESSION['uid'];
    $row = $this->_retrieveUserInfo($uid, 0);
    if ($row['username']  == $_SESSION['username'] && 
        $row['name']      == $_SESSION['name'] && 
        $row['isadmin']   == $_SESSION['isadmin']) {
      $this->uid        = $uid;
      $this->username   = $_SESSION['username'];
      $this->name       = $_SESSION['name'];
      $this->email      = $_SESSION['email'];
      $this->isadmin    = $_SESSION['isadmin'];
      $this->localLogin = $_SESSION['localLogin'];
      return 1;
    } else {
      $this->logout();
      $this->_error = 'Login failed: SESSION INVALID!';
      return 0;
    }
  }

  /**
   * Permit user masquerading -- the admin user can become another user for a period
   * of time to make a bookings etc
  **/
  function _checkMasq() {
    if ($this->masqPermitted() && isset($_SESSION['euid'])) {
      $this->euid      = $_SESSION['euid'];
      $this->ename     = $_SESSION['ename'];
      $this->eusername = $_SESSION['eusername'];
    }
    return 1;
  }
  
  /** 
   * check login details, if OK, set up a PHP SESSION to manage the login
   *
   * @returns boolean credentialsOK
   */
  function _login() {
    global $CONFIG;
    // a login attempt must have a password
    if (! isset($_POST['pass']) ) {
      $this->_error = 'Login failed: no password specified.';
      return false;
    }
    // test the username to make sure it looks valid
    $validUserRegexp = $CONFIG['auth']['validUserRegexp'];
    if (isset($validUserRegexp) && ! empty($validUserRegexp) 
        && ! preg_match($validUserRegexp, $_POST['username'])) {
      $this->_error = 'Login failed: bad username. '
                     .'Either change the username using phpMyAdmin or '
                     .'change how you define a valid username in config/bumblebee.ini '
                     .'(see the value "validUserRegexp")';
      return false;
    }
    // then there is data provided to us in a login form
    // need to verify if it is valid login info
    $PASSWORD = $_POST['pass'];
    $USERNAME = $_POST['username'];
    $row = $this->_retrieveUserInfo($USERNAME);

    // if the admin user has locked themselves out of the system, let them get back in:
    if ($CONFIG['auth']['authAdvancedSecurityHole'] && $CONFIG['auth']['recoverAdminPassword']) {
      $this->_createSession($row);
      return true;
    }

    // the username has to exist in the users table for the login to be valid, so check that first
    if ($row == '0') { 
      $this->_error = 'Login failed: username doesn\'t exist in table';
      return false;
    }

    $authOK = 0;
    if ($CONFIG['auth']['useRadius'] && $CONFIG['auth']['RadiusPassToken'] == $row['passwd']) {
      $authOK = $this->_auth_via_radius($USERNAME, $PASSWORD);
    } elseif ($CONFIG['auth']['useLDAP'] && $CONFIG['auth']['LDAPPassToken'] == $row['passwd']) {
      $authOK = $this->_auth_via_ldap($USERNAME, $PASSWORD);
    } elseif ($CONFIG['auth']['useLocal']) {
      $this->localLogin = 1;
      $authOK = $this->_auth_local($USERNAME, $PASSWORD, $row);
    } else {   //system is misconfigured
      $this->_error = 'System has no login method enabled';
    }
    if (! $authOK) {
      return false;
    }
    if ($row['suspended']) {
      $this->_error = 'Login failed: this account is suspended, please contact us about this.';
      return false;
    }
    // if we got to here, then we're logged in!
    $this->_createSession($row);
    return true;
  }
 
  function _retrieveUserInfo($identifier, $type=1) {
    global $CONFIG;
    $row = quickSQLSelect('users',($type?'username':'id'),$identifier);
    if ($CONFIG['auth']['authAdvancedSecurityHole'] && $CONFIG['auth']['recoverAdminPassword']) {
      if (! is_array($row)) {
        $row = array('id' => -1);
      }
      $row['isadmin'] = 1;
    }
    if (! is_array($row)) {
      $this->_error = "Login failed: unknown username";
      return 0;
    }
    //$row = mysql_fetch_array($sql);
    return $row;
  }
  
  /**
  * RADIUS auth method to login the user against a RADIUS server
  *
  * @global string location of the config file
  */
  function _auth_via_radius($username, $password) {
    global $CONFIGLOCATION;
    require_once 'Auth/Auth.php';
    $RADIUSCONFIG = parse_ini_file($CONFIGLOCATION.DIRECTORY_SEPARATOR.'radius.ini');
    $params = array(
                "servers" => array(array($RADIUSCONFIG['host'], 
                                         0, 
                                         $RADIUSCONFIG['key'],
                                         3, 3)
                                  ),
                "authtype" => $RADIUSCONFIG['authtype']
                );
    // start the PEAR::Auth system using RADIUS authentication with the parameters 
    // we have defined here for this config. Do not display a login box on error.
    $a = new Auth("RADIUS", $params, '', false); 
    $a->username = $username;
    $a->password = $password;
    $a->start();
    $auth = $a->getAuth();
    if (! $auth) {
      $this->_error = 'Login failed: radius auth failed';
    }
    return $auth;
  }

  /**
  * LDAP auth method to login the user against an LDAP server
  *
  * @global string location of the config file
  */
  function _auth_via_ldap($username, $password) {
    global $CONFIGLOCATION;
    require_once 'Auth/Auth.php';
    $LDAPCONFIG = parse_ini_file($CONFIGLOCATION.DIRECTORY_SEPARATOR.'ldap.ini');
    $params = array(
                'url'        => $LDAPCONFIG['url'],
                'basedn'     => $LDAPCONFIG['basedn'],
                'userattr'   => $LDAPCONFIG['userattr'],
                'useroc'     => $LDAPCONFIG['userobjectclass'],          // for v 1.2
                'userfilter' => $LDAPCONFIG['userfilter'],               // for v 1.3
                'debug'      => $LDAPCONFIG['debug'] ? true : false,
                'version'    => intval($LDAPCONFIG['version']),          // for v 1.3
                'start_tls'  => $LDAPCONFIG['start_tls'] ? true : false  // requires patched version of LDAP auth
                );
    // start the PEAR::Auth system using LDAP authentication with the parameters 
    // we have defined here for this config. Do not display a login box on error.
    $a = new Auth("LDAP", $params, '', false); 
    $a->username = $username;
    $a->password = $password;
    $a->start();
    $auth = $a->getAuth();
    if (! $auth) {
      $this->_error = 'Login failed: ldap auth failed';
    }
    return $auth;
  }

  function _auth_local($username, $password, $row) {
    global $CONFIG;
    $crypt_method = $CONFIG['auth']['LocalPassToken'];
    if ($crypt_method != '' && is_callable($crypt_method)) {
      $passOK = ($row['passwd'] == $crypt_method($password));
      if (! $passOK) {
        $this->_error = 'Login failed: bad password';
      }
      return $passOK;
    } else {
      $this->_error = 'Login failed: no crypt method';
      return false;
    }
  }
        
  function isSystemAdmin() {
    return $this->isadmin;
  }
  
  function isInstrumentAdmin($instr) {
    if (isset($this->permissions[$instr])) {
      return $this->permissions[$instr];
    }
    $permission = 0;
    if ($instr==0) {
      // we can use cached queries for this too
      if (in_array(1, $this->permissions)) {
        return 1;
      }
      // then we look at *any* instrument that we have this permission for
       $row = quickSQLSelect('permissions',
                                array('userid',  'isadmin'), 
                                array($this->uid, 1)
                            );
      if (is_array($row)) {
        $this->permissions[$instr] = 1;
        $instr = $row['instrid'];
        $permission = 1;
      }
    } else {
      $row = quickSQLSelect('permissions',
                              array('userid',   'instrid'), 
                              array($this->uid, $instr)
                           );
      $permission = (is_array($row) && $row['isadmin']);
    }
    //save the permissions to speed this up later
    $this->permissions[$instr] = $permission;
    return $this->permissions[$instr];
  }
  
  function getEUID() {
    return (isset($this->euid) ? $this->euid : $this->uid);
  }
   
  function masqPermitted($instr=0) {
    return $this->isadmin || $this->isInstrumentAdmin($instr);
  }

  function amMasqed() {
    return (isset($this->euid) && $this->euid != $this->uid);
  }
  
  /** 
   * start masquerading as another user
  **/
  function assumeMasq($id) {
    if ($this->masqPermitted()) {
      //masquerade permitted
      $row = $this->_retrieveUserInfo($id, 0);
      $_SESSION['euid']      = $this->euid      = $row['id'];
      $_SESSION['eusername'] = $this->eusername = $row['username'];
      $_SESSION['ename']     = $this->ename     = $row['name'];
      return $row;
    } else {
      // masquerade not permitted
      return 0;
    }
  }
   
  /** 
   * stop masquerading as another user
  **/
  function removeMasq() {
    $_SESSION['euid']      = $this->euid      = null;
    $_SESSION['eusername'] = $this->eusername = null;
    $_SESSION['ename']     = $this->ename     = null;
  }
  
  function isMe($id) {
    return $id == $this->uid;
  }

  function getRemoteIP() {
    return (getenv('HTTP_X_FORWARDED_FOR')
           ?  getenv('HTTP_X_FORWARDED_FOR')
           :  getenv('REMOTE_ADDR'));
  }

  function permitted($operation, $instrument=NULL) {
    // print "Requested: $operation and have permissions $this->system_permissions<br/>";
    if ($instrument===NULL) {
      // looking for system permissions
      return $operation & $this->system_permissions;
    } else {
      return $operation & $this->instrument_permission($instrument);
    }
  }
  
} //BumbleBeeAuth

?> 
