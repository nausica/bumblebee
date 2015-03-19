<?php
/**
* a non-SQL active field (for the information of the user, not the database)
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: commentfield.php,v 1.4 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
require_once 'field.php';

/**
* a non-SQL active field (for the information of the user, not the database)
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class CommentField extends Field {

  /**
  *  Create a new commet field object
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
  function CommentField($name, $longname='', $description='') {
    parent::Field($name, $longname, $description);
    $this->sqlHidden = 1;
    $this->suppressValidation = 1;
    $this->editable = 0;
  }

  function displayInTable($cols) {
    $t = '';
    if (! $this->hidden) {
      $t .= '<tr><td>'.$this->longname.'</td>'."\n"
          .'<td title="'.$this->description.'">';
      $t .= $this->selectable();
      $t .= '</td>'."\n";
      for ($i=0; $i<$cols-2; $i++) {
        $t .= '<td></td>';
      }
      $t .= '</tr>';
    }
    return $t;
  }

  function selectable() {
    return xssqw($this->getValue());
  }
  
  function hidden() {
    return '';
  }

} // class CommentField


?> 
