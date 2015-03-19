<?php
/**
* User object (extends dbo), with extra customisations for other links
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: user.php,v 1.20.2.1 2006/03/30 23:29:36 stuart Exp $
* @package    Bumblebee
* @subpackage DBObjects
*/

/** parent object */
require_once 'inc/formslib/dbrow.php';
require_once 'inc/formslib/idfield.php';
require_once 'inc/formslib/textfield.php';
require_once 'inc/formslib/radiolist.php';
require_once 'inc/formslib/checkbox.php';
require_once 'inc/formslib/passwdfield.php';
require_once 'inc/formslib/droplist.php';
require_once 'inc/formslib/joindata.php';

/**
* User object (extends dbo), with extra customisations for other links
*
* @package    Bumblebee
* @subpackage DBObjects
* @todo       Editing method for new permissions model
* @todo       Double password entry and require them to be the same
*/
class User extends DBRow {
  
  var $_localAuthPermitted;
  var $_authList;
  var $_magicPassList;
  var $_authMethod;

  function User($id, $passwdOnly=false) {
    $this->DBRow('users', $id);
    $this->editable = ! $passwdOnly;
    $this->use2StepSync = 1;
    $this->deleteFromTable = 0;
    $f = new IdField('id', 'UserID');
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('username', 'Username');
    $attrs = array('size' => '48');
    $f->required = 1;
    $f->requiredTwoStage = 1;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('name', 'Name');
    $f->required = ! $passwdOnly;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('email', 'Email');
    $f->required = ! $passwdOnly;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('phone', 'Phone');
    $f->required = ! $passwdOnly;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    
    if (! $passwdOnly) {
      $f = new CheckBox('suspended', 'Suspended');
      $this->addElement($f);
      $f = new CheckBox('isadmin', 'System Administrator');
      $this->addElement($f);
    }
    
    // association of user with an authentication method
    $this->_findAuthMethods();
    $f = new RadioList('auth_method', 'User authentication method');
    $f->sqlHidden = 1;
    $f->setValuesArray($this->_authList, 'id', 'iv');
    $f->setFormat('id', '%s', array('iv'));
    $f->setAttr($attrs);
    $f->required = 1;
    $f->hidden = $passwdOnly;
    $this->addElement($f);
    if ($this->_localAuthPermitted) {
      $password = new PasswdField('passwd','Password (for local login)');
      $password->setAttr(array('size' => 24));
      //$password->isValidTest = 'is_nonempty_string';
      $password->suppressValidation = 0;
      $password->editable = 1;
      //$f->list->append(array('local','Local login: '), $password);
      $this->addElement($password);
    }
    
    if (! $passwdOnly) {
      // association of users to projects
      $f = new JoinData('userprojects',
                        'userid', $this->id,
                        'projects', 'Project membership');
      $projectfield = new DropList('projectid', 'Project');
      $projectfield->connectDB('projects', array('id', 'name', 'longname'));
      $projectfield->prepend(array('0','(none)', 'no selection'));
      $projectfield->setDefault(0);
      $projectfield->setFormat('id', '%s', array('name'), ' (%25.25s)', array('longname'));
      $f->addElement($projectfield);
      $f->joinSetup('projectid', array('minspare' => 2));
      $f->colspan = 2;
      $this->addElement($f);
  
      // association of users with instrumental permissions
      $f = new JoinData('permissions',
                        'userid', $this->id,
                        'instruments', 'Instrument permissions');
      $instrfield = new DropList('instrid', 'Instrument');
      $instrfield->connectDB('instruments', array('id', 'name', 'longname'));
      $instrfield->prepend(array('0','(none)', 'no selection'));
      $instrfield->setDefault(0);
      $instrfield->setFormat('id', '%s', array('name'), ' (%25.25s)', array('longname'));
      $f->addElement($instrfield);
      $subscribeAnnounce = new CheckBox('announce', 'Subscribe: announce');
      $subscribeAnnounce->defaultValue = 1;
      $f->addElement($subscribeAnnounce);
      $unbookAnnounce = new CheckBox('unbook', 'Subscribe: unbook');
      $f->addElement($unbookAnnounce);
      $instradmin = new CheckBox('isadmin', 'Instrument admin');
      $f->addElement($instradmin);
      /*  
      //Add these fields in once we need this functinality
      $hasPriority = new CheckBox('haspriority', 'Booking priority');
      $f->addElement($hasPriority);
      $bookPoints = new TextField('points', 'Booking points');
      $f->addElement($bookPoints);
      $bookPointsRecharge = new TextField('pointsrecharge', 'Booking points recharge');
      $f->addElement($bookPointsRecharge);
      */
      $f->joinSetup('instrid', array('minspare' => 2));
      $f->colspan = 2;
      $this->addElement($f);
    }
    
    $this->fill($id);
    $this->dumpheader = 'User object';
  }

  function _findAuthMethods() {
    global $CONFIG;
    $this->_localAuthPermitted = isset($CONFIG['auth']['useLocal']) 
                                        && $CONFIG['auth']['useLocal'];
    $this->_authList = array();
    foreach ($CONFIG['auth'] as $key => $val) {
      if (strpos($key, 'use') === 0 && $val) {
        $method = substr($key,3);
        $this->_authList[$method] = $method;
        $this->_magicPassList[$method] = $CONFIG['auth'][$method.'PassToken'];
      }
    }  
  }

  function fill() {
    parent::fill();
    //now edit the passwd/auth fields
    $this->_authMethod = 'Local';
    foreach($this->_magicPassList as $meth => $passtok) {
      if ($this->fields['passwd']->value == $passtok) {
        $this->_authMethod = $meth;
      }
    }
    if ($this->_authMethod != 'Local') {
     $this->fields['passwd']->crypt_method = '';
    } else {
     $this->fields['passwd']->crypt_method = $this->_magicPassList['Local'];
    }
    $this->fields['auth_method']->set($this->_authMethod);
    //echo $this->fields['passwd']->value;
  }
  
  function sync() {
    //$this->DEBUG = 10;
    //monkey the passwd/auth fields
    //echo $this->_authMethod. '-';
    //preDump($this->fields['passwd']);
    //echo $this->fields['passwd']->value;
    //echo $this->fields['auth_method']->changed.'/'.$this->fields['passwd']->value;
    $this->_authMethod = $this->fields['auth_method']->getValue();
    if ($this->_authMethod == 'Local') {
      $this->fields['passwd']->crypt_method = $this->_magicPassList['Local'];
      if (in_array($this->fields['passwd']->value, $this->_magicPassList)) {
        $this->fields['passwd']->value = '';
      }
    }
    if ($this->fields['auth_method']->changed || $this->fields['passwd']->changed) {
      if ($this->_authMethod != 'Local' 
            /*&& $this->fields['passwd']->value != ''*/
            && $this->fields['passwd']->value != $this->_magicPassList[$this->_authMethod]) {
        $this->log('User::sync(): indulging in password munging, '. $this->_authMethod);
        $this->fields['passwd']->set($this->_magicPassList[$this->_authMethod]);
        $this->fields['passwd']->crypt_method = '';
        $this->fields['passwd']->changed = 1;
        $this->changed = 1;
      } elseif ($this->_authMethod == 'Local' && $this->fields['passwd']->value == '' 
                        && $this->fields['username']->value != '')  {
        $this->fields['passwd']->changed = 1;
        $this->fields['passwd']->isValid = 0;
        $this->errorMessage .= 'password must be set for local login.<br/>';
        $this->isValid = 0;
      } else {
      }
    }
    return parent::sync();
  }
  
  /**
   *  Suspend the user as well as deleting it.
   *
   *  Returns from statuscodes
   */
  function delete() {
    return parent::delete("suspended='1'");
  }
  
  function display() {
    return $this->displayAsTable();
  }

} //class User
