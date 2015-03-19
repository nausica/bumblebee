<?php
/**
* Create a pair of forward/back javascript links to allow the user to move forward and back through a sequence of date periods
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: jsquickwalk.php,v 1.6 2006/01/06 10:07:20 stuart Exp $
* @package    Bumblebee
* @subpackage Misc
*/

/** date manipulation routines */
require_once 'inc/date.php';

/**
* Create a pair of forward/back javascript links to allow the user to move forward and back through a sequence of date periods
*
* @package    Bumblebee
* @subpackage Misc
*/
class JSQuickWalk {
  /**
  * unqiue name in the javascript namespace for these functions (prepended to everything)
  * @var string
  */
  var $namebase;
  /**
  * name for the "back" link
  * @var string
  */
  var $back;
  /**
  * name for the "forward" link
  * @var string
  */
  var $fwd;
  /**
  * html text-input id's for the two date elements
  * @var array
  */
  var $keys;
  /**
  * dates to use in the sequence
  * @var array
  */
  var $values;
  /**
  * initial position in sequence
  * @var integer
  */
  var $counter;

  /**
  * Constructor
  * @param string $namebase added to javascript namespace for uniqueness
  * @param string $back     label for 'previous' button
  * @param string $fwd      label for 'next' button
  * @param array  $keys     list of js id tags for the text boxes to alter
  * @param array  $values   list of dates to use in the sequence
  * @param integer $counter  initial position in sequence
  */
  function JSQuickWalk($namebase, $back, $fwd, $keys, $values, $counter) {
    $this->namebase = $namebase;
    $this->back = $back;
    $this->fwd  = $fwd;
    $this->keys = $keys;
    $this->values = $values;
    $this->counter = $counter;
  }
  
  /**
  * generate the javascript for the +/- links
  * @return string javascript embedded in html for necessary functions and data
  */
  function displayJS() {
    $eol="\n";
    $t = '<script type="text/javascript">'.$eol
        .'<!--'.$eol;
    $t .= $this->namebase.'walkarray = new Array();'.$eol;
    for ($i=0; $i<count($this->values); $i++) {
      $t .= $this->namebase.'walkarray['.$i.']= new Array();'.$eol;
      foreach ($this->keys as $k) {
        $t .= $this->namebase.'walkarray['.$i.']["'.$k.'"]="'
                                    .$this->values[$i][$k]->datestring.'";'.$eol;
      }
    }
    $c = $this->namebase.'walkcounter';
    $t .= 'var '.$c.'='.$this->counter.';'.$eol;
    $t .= 'function '.$this->namebase.'walkfwd() {'.$eol
         .'  rightForm = "bumblebeeform";'
         //.'  alert("FOO"+'.$c.')'.$eol
         .'  ('.$c.' < '.(count($this->values)-1).' && '.$c.'++);'.$eol;
    foreach ($this->keys as $k) {
      $t .= '  document.forms[rightForm].'.$this->namebase.$k.'.value='
                              .$this->namebase.'walkarray['.$c.']["'.$k.'"];'.$eol;
    }
    
//         .'  alert('.$c.'['..'])'.$eol
         //.'  return false;'
    $t .= '}'.$eol;

    $t .= 'function '.$this->namebase.'walkback() {'.$eol
         .'  rightForm = "bumblebeeform";'
         .'  ('.$c.' > 0 && '.$c.'--);'.$eol;
    foreach ($this->keys as $k) {
      $t .= '  document.forms[rightForm].'.$this->namebase.$k.'.value='
                              .$this->namebase.'walkarray['.$c.']["'.$k.'"];'.$eol;
    }
    $t .= '}'.$eol;
    $t .= '-->'.$eol
         .'</script>'.$eol;
    return $t;
  }
  
  /**
  * generate the forward link
  * @return string html link to fire the forwards event
  */
  function displayFwd() {
    return '<a href="javascript:'.$this->namebase.'walkfwd()">'
            .$this->fwd.'</a>';
  }
  
  /**
  * generate the back link
  * @return string html link to fire the previous event
  */
  function displayBack() {
    return '<a href="javascript:'.$this->namebase.'walkback()">'
            .$this->back.'</a>';
  }

}
