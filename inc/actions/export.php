<?php
/**
* Export various views of the booking data in numerous formats
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: export.php,v 1.18 2006/01/06 10:07:20 stuart Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/** CheckBox object */
require_once 'inc/formslib/checkbox.php';
/** CheckBoxTableList object */
require_once 'inc/formslib/checkboxtablelist.php';
/** Data reflector object */
require_once 'inc/formslib/datareflector.php';
/** parent object */
require_once 'inc/actions/bufferedaction.php';
/** Export formatting codes */
require_once 'inc/exportcodes.php';
/** Export configuration and formatting */
require_once 'inc/export/exporttypes.php';
/** Export method object */
require_once 'inc/export/arrayexport.php';
/** Export method object */
require_once 'inc/export/htmlexport.php';
/** database interrogation object */
require_once 'inc/formslib/dblist.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** list of choices */
require_once 'inc/bb/daterange.php';
/** list of choices */
require_once 'inc/formslib/radiolist.php';

/**
* Export various views of the booking data in numerous formats
*  
* A number of different data views can be created (see exporttypes.php)
* and the data can be exported in various formats (see htmlexport.php and arrayexport.php)
*
* This class is inherited by other exporters
* @package    Bumblebee
* @subpackage Actions
*/
class ActionExport extends BufferedAction {
  /**
  * forces SQL errors to be fatal
  * @var    boolean
  */
  var $fatal_sql = 1;
  /**
  * name of the export format to be used
  * @var    string
  */
  var $format;
  /**
  * object containing export SQL and formatting instructions
  * @var    ExportTypeList
  */
  var $typelist;
  /**
  * the specific ExportType to be used in this data export
  * @var    ExportType
  */
  var $_export;  // ExportType format description
  /**
  * The data range to be used for data export
  * @var    DateRange
  */
  var $_daterange;
  /**
  * The original action word (verb!) that instantiated this class (not its descendants)
  * Allows HTML links back to this class to be easily made.
  * @var    string
  */
  var $_verb = 'export';

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionExport($auth, $pdata) {
    parent::BufferedAction($auth, $pdata);
  }

  function go() {
    $reflector = new DataReflector();
    echo $reflector->display($this->PD);

    $this->typelist = new ExportTypeList();
//     preDump($this->typelist);
    if (! isset($this->PD['what'])) {
      $this->unbuffer();
      $this->selectExport();
    } else {
      $allDataOK = true;
      $this->_daterange = new DateRange('daterange', 'Select date range', 
                      'Enter the dates over which you want to export data');
      $this->_daterange->update($this->PD);
      $this->_daterange->checkValid();
      $this->_daterange->reflectData = 0;
      $this->_daterange->includeSubmitButton = 0;
      if ($this->_daterange->newObject || !$this->_daterange->isValid) {
        $allDataOK = false;
        $this->unbuffer();
        $this->_daterange->setDefaults(DR_PREVIOUS, DR_QUARTER);
        echo $this->_daterange->display($this->PD);
      } 
      if (! isset($this->format)) {
        $allDataOK = false;
        $this->unbuffer();
        $this->formatSelect();
      }
      if (! isset($this->PD['limitationselected'])) {
        $allDataOK = false;
        $this->unbuffer();
        $this->outputSelect();
      }
      if ($allDataOK) {
        echo $this->reportAction($this->returnExport(),
              array(STATUS_ERR =>  'Error exporting data: '.$this->errorMessage
                   )
             );
      } else {
        $this->_goButton();
      }
    }
  }
  
  function mungeInputData() {
    parent::mungeInputData();
    if (isset($this->PD['outputformat'])) {
      $this->format = $this->PD['outputformat'];
    }
  }

  /**
  * Generate HTML list for user to select which data export should be used
  *
  * @return void nothing
  */  
  function selectExport() {
    $reportlist = array();
    foreach ($this->typelist->types as $type) {
      $reportlist[$type->name] = $type->description;
    }
    $select = new AnchorTableList('datasource', 'Select which data to export', 1);
    $select->setValuesArray($reportlist, 'id', 'iv');
    $select->hrefbase = makeURL($this->_verb, array('what'=>'__id__'));
    $select->setFormat('id', '%s', array('iv'));
    echo $select->display();
  }

  /**
  * Generate HTML list for user to select which data format should be used
  *
  * @return void nothing
  */  
  function formatSelect() {
    global $CONFIG;
    $formatlist = array(EXPORT_FORMAT_VIEW     => 'View in web browser', 
                        EXPORT_FORMAT_VIEWOPEN => 'View in web browser (new window)', 
                        EXPORT_FORMAT_CSV      => 'Save as comma separated variable (csv)', 
                        EXPORT_FORMAT_TAB      => 'Save as tab separated variable (txt)');
    if ($CONFIG['export']['enablePDF']) {
      $formatlist[EXPORT_FORMAT_PDF] = 'Save as pdf report';
    }
    $select = new RadioList('outputformat', 'Select which data to export', 1);
    $select->setValuesArray($formatlist, 'id', 'iv');
    $select->setFormat('id', '%s', array('iv'));
    if (is_numeric($CONFIG['export']['defaultFormat'])) {
      $select->setDefault($CONFIG['export']['defaultFormat']);
    } else {
      $select->setDefault(exportStringToCode($CONFIG['export']['defaultFormat']));
    }
    echo '<div style="margin: 2em 0 2em 0;">'.$select->display().'</div>';
  }  
  
  /**
  * Generate HTML form widget for user to control output.
  *
  * User can select which sub-view of the data to use
  * and which specific parts of the data should be included (i.e. restrict by group)
  *
  * @return void nothing
  */  
  function outputSelect() {
    $export = $this->typelist->types[$this->PD['what']];
    for ($lim = 0; $lim < count($export->limitation); $lim++) {
      $select = new CheckBoxTableList('limitation-'.$lim, 'Select which detail to view');
      $hidden = new TextField($export->limitation[$lim]);
      $select->addFollowHidden($hidden);
      $chosen = new CheckBox('selected', 'Selected');
      $select->addCheckBox($chosen);
      //$select->numSpareCols = 1;
      if ($export->limitation[$lim] == 'users') {
        $select->connectDB($export->limitation[$lim], array('id', 'name', 'username'));
        $select->setFormat('id', '%s', array('name'), " (%s)", array('username'));
      } else {
        $select->connectDB($export->limitation[$lim], array('id', 'name', 'longname'));
        $select->setFormat('id', '%s', array('name'), " %50.50s", array('longname'));
      }
      $select->addSelectAllFooter(true);
      echo $select->display().'<br/>';
    }
    if (is_array($export->pivot) && count($export->pivot) > 1) {
      $views = array();
      foreach ($export->pivot as $k => $v) {
        $views[$k] = $v['description'];
      }
      $viewselect = new RadioList('pivot', 'Select which data view to export', 1);
      $viewselect->setValuesArray($views, 'id', 'iv');
      $viewselect->setFormat('id', '%s', array('iv'));
      reset($views);
      $viewselect->setDefault(key($views));
      echo '<div style="margin: 0em 0 2em 0;">'.$viewselect->display().'</div>';
    }
    echo '<input type="hidden" name="limitationselected" value="1" />';
  }

  /**
  * Common submit button for this class
  *
  * @return void nothing
  */  
  function _goButton() {
    echo '<input type="submit" name="submit" value="Select" />';
  }
      
  /**
  * Generate the data export and then send it to the user in the appropriate format
  *
  * @return void nothing
  */  
  function returnExport() {
    $list = $this->_getDataList($this->PD['what']);
    $list->fill();
    if (count($list->data) == 0) {
      return $this->unbufferForError('<p>No data found for those criteria</p>');
    }      
    // start rendering the data
    $list->outputFormat = $this->format;
    $list->formatList();   
    if ($this->format & EXPORT_FORMAT_USEARRAY) {
      $exportArray = new ArrayExport($list, $list->breakfield);
      $exportArray->header = $this->_reportHeader();
      $exportArray->author = $this->auth->name;
      $exportArray->makeExportArray();
      //preDump($exportArray->export);
    }
    if ($this->format & EXPORT_FORMAT_USEHTML) {
      $htmlExport = new HTMLExport($exportArray);
      $htmlExport->makeHTMLBuffer();
    }
    
    //finally, direct the data towards its output
    if ($this->format == EXPORT_FORMAT_PDF){ 
      // construct the PDF from $htmlbuffer
      $pdfExport = $this->_preparePDFExport($exportArray);
      $pdfExport->makePDFBuffer();
      
      if ($pdfExport->writeToFile) {
        $this->unbuffer();
      } else {
        $this->_getFilename();
        $this->bufferedStream =& $pdfExport->export;
        // the data itself will be dumped later by the action driver (index.php)
      }
    } elseif ($this->format & EXPORT_FORMAT_DELIMITED) {
      $this->_getFilename();
      $this->bufferedStream = '"'.$this->_reportHeader().'"'."\n"
                               .$list->outputHeader()."\n"
                               .join($list->formatdata, "\n");
      // the data itself will be dumped later by the action driver (index.php)
    } elseif ($this->format == EXPORT_FORMAT_VIEWOPEN) {
      $this->unbuffer();
      echo $htmlExport->wrapHTMLBuffer();
    } else {
      $this->unbuffer();
      echo $htmlExport->export;
    }
  }
  
  /**
  * Sets up the DBlist object so that it can query the db
  *
  * @return DBList query ready to run
  */  
  function _getDataList($report) {
    $this->_export = $this->typelist->types[$report];
    $start = $this->_daterange->getStart();
    $stop  = $this->_daterange->getStop();
    $stop->addDays(1);

    if (is_array($this->_export->union) && count($this->_export->union)) {
      $union = array();
      $limitsOffset = 0;
      foreach ($this->_export->union as $export) {
        $union[] = $this->_getDBListFromExport($export, $start, $stop, $limitsOffset);
        $limitsOffset += count($export->limitation);
      }
      $list = $this->_getDBListFromExport($this->_export, $start, $stop);
      $list->union = $union;
    } else {
      $list = $this->_getDBListFromExport($this->_export, $start, $stop);
    }
    return $list;
  }
  
  /**
  * From the export definition in the ExportType generate a DBList query
  *
  * @return void DBList ready for query
  */  
  function _getDBListFromExport(&$export, $start, $stop, $limitsOffset=0) {
    $where = $export->where;
    $where[] = $export->timewhere[0].qw($start->datetimestring);
    $where[] = $export->timewhere[1].qw($stop->datetimestring);
    $where = array_merge($where, $this->_limitationSet($export->limitation, $limitsOffset));
    // work out what view/pivot of the data we want to see
    if (count($export->limitation) > 1 && is_array($export->pivot)) {
      $pivot = $export->pivot[$this->PD['pivot']];
      $export->group      = $pivot['group'];
      $export->omitFields = array_flip($pivot['omitFields']);
      $export->breakField = $pivot['breakField'];
      if (isset($pivot['fieldOrder']) && is_array($pivot['fieldOrder'])) {
        $export->fieldOrder = $pivot['fieldOrder'];
      }
      if (isset($pivot['extraFields']) && is_array($pivot['extraFields'])) {
        $export->fields = array_merge($export->fields, $pivot['extraFields']);
      }
    }
    $list = new DBList($export->basetable, $export->fields, join($where, ' AND '));
    $list->join        = array_merge($list->join, $export->join);
    $list->group       = $export->group;
    $list->manualGroup = $export->manualGroup;
    $list->manualSum   = $export->manualSum;
    $list->order       = $export->order;
    $list->distinct    = $export->distinct;
    $list->fieldOrder  = $export->fieldOrder;
    $list->breakfield  = $export->breakField;
    if ($this->format & EXPORT_FORMAT_USEARRAY) {
      $list->omitFields = $export->omitFields;
    }
    return $list;
  }

  /**
  * Determine what limitation should be applied to the broad query
  *
  * @todo   there are limitations in the IN() syntax.... replace this with lots of booleans?
  *
  * @return array SQL field IN (list...) syntax
  */  
  function _limitationSet($fields, $limitsOffset, $makeSQL=true) {
    $sets = array();
    for ($lim = 0; $lim < count($fields); $lim++) {
      $limitation = array();
      $fieldpattern = '/^limitation\-(\d+)\-(\d+)\-'.$fields[$lim].'$/';
      $selected = array_values(preg_grep($fieldpattern, array_keys($this->PD)));
      #preDump($selected);
      for ($j=0; $j < count($selected); $j++) {
        $ids = array();
        preg_match($fieldpattern, $selected[$j], $ids);
        $item = issetSet($this->PD,$selected[$j]);
        if (issetSet($this->PD,'limitation-'.$ids[1].'-'.$ids[2].'-selected') && $item !== NULL) {
          $limitation[] = /*$export->limitation[$lim].'.id='.*/qw($item);
        }
        //echo $namebase.':'.$j.':'.$lim.':'.$fields[$lim].':'.$item.'<br/>';
      }
      if (count($limitation)) {
        if ($makeSQL) {
          $sets[] = $fields[$lim].'.id IN ('.join($limitation, ', ').')';
        } else {
          $sets[$fields[$lim]] = $limitation;
        }
      }
      //preDump($limitation);
    }
    return $sets;
  }
  
  /**
  * Set the filename and mimetype for the data export
  *
  * @return void nothing
  */  
  function _getFilename() {
    switch ($this->format & EXPORT_FORMAT_MASK) {
      case EXPORT_FORMAT_CSV:
        $ext = 'csv';
        $type = 'text/csv';
        break;
      case EXPORT_FORMAT_TAB:
        $ext = 'txt';
        $type = 'text/tab-separated-values';
        //$type = 'application/vnd-excel';
        break;
      case EXPORT_FORMAT_PDF:
        $ext = 'pdf';
        $type = 'application/pdf';
        break;
      default:
        $ext = '';
        $type = 'application/octet-stream';
    }
    $this->filename = parent::getFilename('export', $this->PD['what'], $ext);
    $this->mimetype = $type;
  }
  
  /**
  * Generate the PDF for return to the user
  *
  * @uses PDFExport but only loaded here so that optional library can be absent without compile errors
  * @return string $pdf containing the PDF
  */  
  function _preparePDFExport(&$exportArray) {
    require_once('inc/export/pdfexport.php');
    $pdf = new PDFExport($exportArray);
    return $pdf;
  }

  /**
  * Generate the standard report header from the date range and the description in ExportType
  *
  * @return string the title to be used for this report
  */  
  function _reportHeader() {
    $start = $this->_daterange->getStart();
    $stop  = $this->_daterange->getStop();
    $s = $this->_export->description .' for '. $start->datestring .' - '. $stop->datestring;
    return $s;
  }  

}  //ActionExport
?> 
