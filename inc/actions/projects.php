<?php
/**
* Edit/create/delete projects
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: projects.php,v 1.25 2006/01/06 10:07:20 stuart Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/** project object */
require_once 'inc/bb/project.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete projects
*
* @package    Bumblebee
* @subpackage Actions
*/
class ActionProjects extends ActionAction {

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionProjects($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['id'])) {
      $this->select(issetSet($this->PD, 'showdeleted', false));
    } elseif (isset($this->PD['delete'])) {
      $this->delete();
    } else {
      $this->edit();
    }
    echo "<br /><br /><a href='".makeURL('projects')."'>Return to project list</a>";
  }

  function select($deleted=false) {
    $select = new AnchorTableList('Projects', 'Select which project to view');
    $select->deleted = $deleted;
    $select->connectDB('projects', array('id', 'name', 'longname'));
    $select->list->prepend(array('-1','Create new project'));
    $select->list->append(array('showdeleted','Show deleted projects'));
    $select->hrefbase = makeURL('projects', array('id'=>'__id__'));
    $select->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'));
    echo $select->display();
  }

  function edit() {
    $project = new Project($this->PD['id']);
    $project->update($this->PD);
    $project->checkValid();
    echo $this->reportAction($project->sync(), 
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? 'Project created' : 'Project updated'),
              STATUS_ERR =>  'Project could not be changed: '.$project->errorMessage
          )
        );
    echo $project->display();
    if ($project->id < 0) {
      $submit = 'Create new project';
      $delete = '0';
    } else {
      $submit = 'Update entry';
      $delete = $project->isDeleted ? 'Undelete entry' : 'Delete entry';
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function delete() {
    $project = new Project($this->PD['id']);
    echo $this->reportAction($project->delete(), 
              array(
                  STATUS_OK =>   $project->isDeleted ? 'Project undeleted' : 'Project deleted',
                  STATUS_ERR =>  'Project could not be deleted:<br/><br/>'.$project->errorMessage
              )
            );  
  }

}

?>
