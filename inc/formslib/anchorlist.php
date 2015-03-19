<?php
/**
* anchor list (<li><a href="$href">$name</a></li>) for a ChoiceList
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: anchorlist.php,v 1.10 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** choicelist parent object */
require_once 'choicelist.php';

/**
* anchor list (<li><a href="$href">$name</a></li>) for a ChoiceList
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class AnchorList extends ChoiceList {
  /** @var string  prepended to all hrefs genereated here  */
  var $hrefbase;
  /** @var string  html/css class used for the entire unordered list  */
  var $ulclass = 'selectlist';
  /** @var string  html/css class used for each list item  */
  var $liclass = 'item';
  /** @var string  html/css class used for each anchor  */
  var $aclass  = 'itemanchor';

  /**
  *  Create a new AnchorList
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $description  used in the html title of the list
  */
  function AnchorList($name, $description='') {
    $this->ChoiceList($name, $description);
  }

  /**
  * format an individual element in the list
  *
  * @param array $data the data to format using the OutputFormatter
  * @return string  formatted entry
  */
  function format($data) {
    $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : '');
    $t .= "<a href='$this->hrefbase".$data[$this->formatid]."'$aclass>"
         .$this->formatter[0]->format($data)
         .'</a>'
         .$this->formatter[1]->format($data);
    return $t;
  }

  /**
  * create the html list
  *
  * @return string  formatted list
  */
  function display() {
    $ulclass = (isset($this->ulclass) ? " class='$this->ulclass'" : '');
    $liclass = (isset($this->liclass) ? " class='$this->liclass'" : '');
    $t  = "<ul title='$this->description'$ulclass>\n";
    if (is_array($this->list->choicelist)) {
      foreach ($this->list->choicelist as $v) {
        $t .= "<li$liclass>";
        #$t .= print_r($v, true);
        $t .= $this->format($v);
        $t .= "</li>\n";
      }
    }
    $t .= "</ul>\n";
    return $t;
  }

} // class AnchorList


?> 
