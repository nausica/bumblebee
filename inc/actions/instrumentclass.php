<?php
/**
* Edit/create/delete instrument class details
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: instrumentclass.php,v 1.6 2006/01/06 10:07:20 stuart Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/** instrument object */
require_once 'inc/bb/instrumentclass.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete instrument classes
*  
* @package    Bumblebee
* @subpackage Actions
*/
class ActionInstrumentClass extends ActionAction  {

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionInstrumentClass($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['id'])) {
      $this->select();
    } elseif (isset($this->PD['delete'])) {
      $this->delete();
    } else {
      $this->edit();
    }
    echo "<br /><br /><a href='".makeURL('instrumentclass')."'>Return to instrument class list</a>";
  }

  function edit() {
    $class = new InstrumentClass($this->PD['id']);
    $class->update($this->PD);
    $class->checkValid();
    echo $this->reportAction($class->sync(), 
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? 'Instrument class created' : 'Instrument class updated'),
              STATUS_ERR =>  'Instrument class could not be changed: '.$class->errorMessage
          )
        );
        echo $class->display();
    if ($class->id < 0) {
      $submit = 'Create new class';
      $delete = '0';
    } else {
      $submit = 'Update entry';
      $delete = 'Delete entry';
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function select() {
    $select = new AnchorTableList('InstrumentClass', 'Select which instrument class to view');
    $select->connectDB('instrumentclass', array('id', 'name'));
    $select->list->prepend(array('-1','Create new instrument class'));
    $select->hrefbase = makeURL('instrumentclass', array('id'=>'__id__'));
    $select->setFormat('id', '%s', array('name')/*, ' %50.50s', array('longname')*/);
    #echo $groupselect->list->text_dump();
    $select->numcols = 1;
    echo $select->display();
  }

  function delete() {
    $class = new InstrumentClass($this->PD['id']);
    echo $this->reportAction($class->delete(), 
              array(
                  STATUS_OK =>   'Instrument class deleted',
                  STATUS_ERR =>  'Instrument class could not be deleted:<br/><br/>'.$class->errorMessage
              )
            );  
  }
}
?> 
