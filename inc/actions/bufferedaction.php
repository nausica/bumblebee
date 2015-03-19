<?php
/**
* Primitive class that allows output to be suppressed 
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: bufferedaction.php,v 1.6 2006/01/06 10:07:20 stuart Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/** status codes for success/failure of database actions */
require_once 'inc/statuscodes.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Primitive class that allows output to be suppressed 
* @package    Bumblebee
* @subpackage Actions
*/
class BufferedAction extends ActionAction  {
  /** 
  * data stream to be buffered then output
  * @var string
  */
  var $bufferedStream;
  /** 
  * filename to suggest to the browser for saving the stream
  * @var string
  */
  var $filename;
  /** 
  * error message (if any) for display instead of the stream
  * @var string
  */
  var $errorMessage;
  /** 
  * mime type for instructing the browser what to do with the stream
  * @var string
  */
  var $mimetype = 'text/plain';
  
  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function BufferedAction($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
    $this->ob_flush_ok = 0;
  }

  /** 
  * Unbuffer the stream and allow the data to be output
  */
  function unbuffer() {
    // only run this once.
    if (! $this->ob_flush_ok) {
      $this->ob_flush_ok = 1;
      ob_end_flush();
    }
  }
  
  /** 
  * Unbuffer the stream and get ready to drop the error message to the user
  * @param string $err error message to be output
  */
  function unbufferForError($err) {
    $this->errorMessage = '<p>'.$err.'</p>';
    $this->unbuffer();
    return STATUS_ERR;
  }
  
  /** 
  * Send the data back to the user now
  */
  function sendBufferedStream() {
    $this->outputTextFile($this->filename, $this->bufferedStream);
  }
  
  /** 
  * send headers to the browser with the filename and the mimetype
  * @param string $filename the suggested filename to give to the browser
  */
  function startOutputTextFile($filename) {
    header('Content-type: '.$this->mimetype); 
    header("Content-Disposition: attachment; filename=$filename");                    
  }
  
  /** 
  * Dump a data stream to the user
  * @param string $filename the suggested filename to give to the browser
  * @param string $stream the data stream to be sent
  */
  function outputTextFile($filename, $stream) {
    $this->startOutputTextFile($filename);
    echo $stream;
  }

  /** 
  * Save the datastream to a local file
  * @param string $filename the filename to save the data to on the server
  * @param string $stream the data stream to be saved
  */
  function saveTextFile($filename, $stream) {
    $fp = fopen($filename, 'w');
    fputs($fp, $stream);
    fclose($fp);
  }

  /** 
  * Work out an appropriate (and hopefully unique) filename for the data
  * Uses the Config option in bumblebee.ini [export]::filename
  * The following parameters are replaced: __date__ __action__ __what__
  *
  * @param string $action the action verb
  * @param string $what the nature of the export
  * @param string $ext  the file extension (pdf, csv etc) without the dot.
  */
  function getFilename($action, $what, $ext) {
    global $CONFIG;
    $name = $CONFIG['export']['filename'];
    $name = preg_replace('/__date__/', strftime('%Y%m%d-%H%M%S', time()), $name);
    $name = preg_replace('/__action__/', $action, $name);
    $name = preg_replace('/__what__/', $what, $name);
    return $name.'.'.$ext;
  }

} //class BufferedAction
 
?>
