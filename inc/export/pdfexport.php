<?php
/**
* Construct a PDF from the array representation
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: pdfexport.php,v 1.9.2.1 2006/06/12 09:37:02 stuart Exp $
* @package    Bumblebee
* @subpackage Export
*/

/** constants for defining export formatting and codes */
require_once 'inc/exportcodes.php';
/** FPDF free PDF creation library for PHP: http://fpdf.org/ */
require_once 'fpdf/fpdf.php';


/**
* Construct a PDF from the array representation
*
* @package    Bumblebee
* @subpackage Export
*/
class PDFExport {
  var $ea;
  var $pdf;
  var $export;
  var $filename = '/tmp/test.pdf';
  var $writeToFile = false;
  
  var $orientation = 'L';
  var $size = 'A4';
  var $pageWidth   = 297;   // mm
  var $pageHeight  = 210;   // mm
  var $leftMargin  = 15;    // mm
  var $rightMargin = 15;    // mm
  var $topMargin   = 15;    // mm
  var $bottomMargin= 15;    // mm
  
  var $minAutoMargin = 4;   // mm added to auto calc'd column widths
  var $tableHeaderAlignment = 'L';
  var $rowLines = '';  // use 'T' for lines between rows
  var $headerLines = 'TB';    // Top and Bottom lines on the header rows

  var $normalLineHeight = 5;
  var $headerLineHeight = 6;
  var $footerLineHeight = 4;
  var $doubleLineWidth  = 0.2;
  var $singleLineWidth  = 0.3;
  var $sectionHeaderLineHeight = 8;
  var $singleCellTopMargin    = 1;
  
  var $normalFillColor = array(224,235,255);
  var $normalDrawColor = array(  0,  0,  0);
  var $normalTextColor = array(  0,  0,  0);
  var $normalFont      = array('Arial','',12);
  
  var $sectionHeaderFillColor = array(255,255,255);
  var $sectionHeaderDrawColor = array(  0,  0,  0);
  var $sectionHeaderTextColor = array(  0,  0,  0);
  var $sectionHeaderFont      = array('Arial','B',14);
  
  var $tableHeaderFillColor = array(  0,  0,128);
  var $tableHeaderDrawColor = array(  0,  0,  0);
  var $tableHeaderTextColor = array(255,255,255);
  var $tableHeaderFont      = array('Arial','B',12);
  
  var $tableFooterFillColor = array(  0,  0,128);
  var $tableFooterDrawColor = array(  0,  0,  0);
  var $tableFooterTextColor = array(255,255,255);
  var $tableFooterFont      = array('Arial','',9);
  
  var $tableTotalFillColor = array(224,235,255);
  var $tableTotalDrawColor = array(  0,  0,  0);
  var $tableTotalTextColor = array(  0,  0,  0);
  var $tableTotalFont      = array('Arial','',12);
  
  
    
  var $cols = array(); //=array(50,20,20,20,20,20,20,20);   //column widths
  var $colStart = array();
  var $tableRow = 0;
  
  var $useBigTable = true;
  
  var $DEBUG = 0;
  
  function PDFExport(&$exportArray) {
    $this->ea = $exportArray;
    $this->_readConfig();
  }
  
  function makePDFBuffer() {
    $this->render();
    $this->export = $this->Output();
  }
  
  function render() {
    $this->pdf = new TabularPDF($this->orientation, 'mm', $this->size);
    $this->_setMetaInfo();
    $this->_setPageMargins();
    $this->_setTableAttributes();
    $this->pdf->AliasNbPages();
    #$this->pdf->AddPage();  //should we always include a page to start with?
    $this->_parseArray();
  }

  function Output() {
    if ($this->writeToFile) {
      return $this->pdf->Output($this->filename, 'F');
    } else {
      return $this->pdf->Output('', 'S');
    }
  } 

  function _setMetaInfo() {
    $metaData = $this->ea->export['metadata'];
    $this->pdf->SetCreator($metaData['creator']);
    $this->pdf->SetAuthor($metaData['author']);
    $this->pdf->SetKeywords($metaData['keywords']);
    $this->pdf->SetSubject($metaData['subject']);
    $this->pdf->SetTitle($metaData['title']);
    $this->pdf->title = $metaData['title'];
  }
  
  function _readConfig() {
    global $CONFIG;
    $simplevars = array(
      'orientation', 'size', 'pageWidth', 'pageHeight', 
      'leftMargin', 'rightMargin', 'topMargin', 'bottomMargin', 
      'minAutoMargin', 'tableHeaderAlignment', 'rowLines', 'headerLines',
      'normalLineHeight', 'headerLineHeight', 'footerLineHeight', 'sectionHeaderLineHeight',
      'doubleLineWidth', 'singleLineWidth', 'singleCellTopMargin'
      );
    $cxvars = array(
      'normalFillColor', 'normalDrawColor', 'normalTextColor', 'normalFont',
      'sectionHeaderFillColor', 'sectionHeaderDrawColor', 'sectionHeaderTextColor',  'sectionHeaderFont',
      'tableHeaderFillColor', 'tableHeaderDrawColor', 'tableHeaderTextColor', 'tableHeaderFont',
      'tableFooterFillColor', 'tableFooterDrawColor', 'tableFooterTextColor', 'tableFooterFont',
      'tableTotalFillColor', 'tableTotalDrawColor', 'tableTotalTextColor', 'tableTotalFont'
      );
    foreach ($simplevars as $v) {
      $this->$v = $CONFIG['pdfexport'][$v];
    }
    foreach ($cxvars as $v) {
      $this->$v = $this->_confSplit($CONFIG['pdfexport'][$v]);
    }
  }
  
  function _confSplit($c) {
    return explode(',', $c);
  }
  
  function _setPageMargins() {
    $this->pdf->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
    $this->pdf->SetAutoPageBreak(true, $this->bottomMargin);
    $vars = array('pageWidth', 'pageHeight', 'leftMargin', 'rightMargin', 'topMargin', 'bottomMargin', 'minAutoMargin', 'tableHeaderAlignment', 'rowLines', 'headerLines');
    foreach ($vars as $v) {
      $this->pdf->$v = $this->$v;
    }
  }
  
  function _setTableAttributes() {
    $vars = array(
      'normalLineHeight', 'headerLineHeight', 'footerLineHeight', 'sectionHeaderLineHeight',
      'doubleLineWidth', 'singleLineWidth', 'singleCellTopMargin',
      'normalFillColor', 'normalDrawColor', 'normalTextColor', 'normalFont',
      'sectionHeaderFillColor', 'sectionHeaderDrawColor', 'sectionHeaderTextColor',  'sectionHeaderFont',
      'tableHeaderFillColor', 'tableHeaderDrawColor', 'tableHeaderTextColor', 'tableHeaderFont',
      'tableFooterFillColor', 'tableFooterDrawColor', 'tableFooterTextColor', 'tableFooterFont',
      'tableTotalFillColor', 'tableTotalDrawColor', 'tableTotalTextColor', 'tableTotalFont'
      );
    foreach ($vars as $v) {
      $this->pdf->$v = $this->$v;
    }
  }
  
  /** 
  * calculate column widths
  * 
  * @todo make sure PDF columns don't go over right side of page
  */
  function _calcColWidths($widths, $entry) {
    if ($this->useBigTable && count($this->cols)) return;
    $this->log('Calculating column widths');
    //preDump($widths);
    //preDump($entry);
    $sum = 0;
    $this->cols = array();
    for ($col = 0; $col<count($widths); $col++) {
      if ($widths[$col] == '*') {
        $this->cols[$col] = $this->_getColWidth($col, $entry);
      } else {
        $sum += $widths[$col];
      }
    }
    $taken = array_sum($this->cols);
    //if ($taken > ($this->pageWidth-$this->leftMargin-$this->rightMargin)) {
      //FIXME: must not go over page right
    for ($col = 0; $col<count($widths); $col++) {
      if (! isset($this->cols[$col])) {
        $this->cols[$col] = $widths[$col] 
                  / $sum*($this->pageWidth-$this->leftMargin-$this->rightMargin-$taken);
      }
    }
    $this->pdf->cols = $this->cols;
  }
  
  /**
  * Calculate the width of an actual column
  *
  * @todo calculate width of header from actual header data (bold) rather than 1.1 * non-bold 
  */
  function _getColWidth($col, $entry) {
    //why are we doing lots of calls into the pdf here? is it bad encapsulation?
    //We have to have a font chosen within FPDF to perform these length calculations
    $this->pdf->_setTableFont();
    $ea =& $this->ea->export;
    $i=0;
    $width = 0;
    for ($key=$entry; 
        $key<count($ea)-1 && ($this->useBigTable || $ea[$key]['type'] != EXPORT_REPORT_TABLE_END);
        $key++) {
      //$this->log('key='.$key);
      //preDump($ea[$key]);
      $newWidth = $this->pdf->GetStringWidth($ea[$key]['data'][$col]['value']);
      if ($ea[$key]['type'] == EXPORT_REPORT_TABLE_HEADER)
        $newWidth *= 1.1;     //FIXME: we should do this calculation properly!
      //echo "VAL=".$ea[$key]['data'][$col]['value'].", WIDTH=$newWidth/$width.<br/>";
      $width = max($width, $newWidth);
    }
    //echo "WIDTH=$width.<br/>";
    return $width + $this->minAutoMargin;
  }

  function _getColWidthRand($col) {
    //FIXME: is this random thing good enough? what about fitting in the header?
    //why are we doing lots of calls into the pdf here? is it bad encapsulation?
    //We have to have a font chosen within FPDF to perform these length calculations
    $this->pdf->_setTableFont();
    $ea =& $this->ea->export;
    $i=0;
    $width = 0; $header = 0;
    while ($rows<10 || $header<1) {
      $key = array_rand($ea, 1);
      //echo $key;
      if (is_numeric($key) && $ea[$key]['type'] == EXPORT_REPORT_TABLE_ROW) {
        $rows++;
        $newWidth = $this->pdf->GetStringWidth($ea[$key]['data'][$col]['value']);
        //echo "VAL=".$ea[$key]['data'][$col]['value'].", WIDTH=$newWidth/$width.<br/>";
        $width = max($width, $newWidth);
     } elseif (is_numeric($key) && $ea[$key]['type'] == EXPORT_REPORT_TABLE_HEADER) {
        $header++;
        $newWidth = 1.1*$this->pdf->GetStringWidth($ea[$key]['data'][$col]['value']);
        //echo "VAL=".$ea[$key]['data'][$col]['value'].", WIDTH=$newWidth/$width.<br/>";
        $width = max($width, $newWidth);
     }
    }
    //echo "WIDTH=$width.<br/>";
    return $width + $this->minAutoMargin;
  }
  
  function _parseArray() {
    //$this->log('Making HTML representation of data');
    $ea =& $this->ea->export;
    $metaData = $ea['metadata'];
    unset($ea['metadata']);
    $this->log('Found '.count($ea).' rows in this ExportArray');
    for ($i=0; $i<count($ea); $i++) {
      #echo $i.': '.$ea[$i]['type'].'<br/>';
      #preDump($ea[$i]);
      switch ($ea[$i]['type']) {
        case EXPORT_REPORT_START:
          $this->pdf->reportStart();
          break;
        case EXPORT_REPORT_END:
          $this->pdf->reportEnd();
          //$i = count($ea);
          break;
        case EXPORT_REPORT_HEADER:
          $this->pdf->reportHeader($ea[$i]['data']);
          break;
        case EXPORT_REPORT_SECTION_HEADER:
          $this->_calcColWidths($ea[$i]['metadata']['colwidths'], $i);
          $this->pdf->sectionHeader($ea[$i]['data']);
          break;
        case EXPORT_REPORT_TABLE_START:
          $this->tableRow=0;
          $this->pdf->tableStart();
          break;
        case EXPORT_REPORT_TABLE_END:
          $this->pdf->tableEnd();
          break;
        case EXPORT_REPORT_TABLE_HEADER:
          $this->pdf->tableHeader($this->_formatRow($ea[$i]['data'], true));
          break;
        case EXPORT_REPORT_TABLE_TOTAL:
          $this->pdf->tableTotal($this->_formatRow($ea[$i]['data'], false, 'TT'));
          break;
        case EXPORT_REPORT_TABLE_FOOTER:
          $this->pdf->tableFooter($this->_formatRow($ea[$i]['data']));
          break;
        case EXPORT_REPORT_TABLE_ROW:
          $this->pdf->tableRow($this->_formatRow($ea[$i]['data']));
          $this->tableRow++;
          break;
      }
    }      
  }
   
  function _formatRow($row, $isHeader=false, $border=NULL) {
    $rowpdf = array();
    for ($j=0; $j<count($row); $j++) {
      $rowpdf[] = $this->_formatCell($row[$j], $j, $isHeader, $border);
    }
    return $rowpdf;
  }

  function _formatCell($d, $col, $isHeader, $setborder) {
    $val = $d['value'];
    if (! $isHeader) {
      switch($d['format'] & EXPORT_HTML_ALIGN_MASK) {
        case EXPORT_HTML_LEFT:
          $align='L';
          break;
        case EXPORT_HTML_RIGHT:
          $align='R';
          break;
        case EXPORT_HTML_CENTRE:
        default:
          $align='C';
      }
      $fill = $this->tableRow % 2;
      $border = $this->rowLines;
    } else {
      $align = $this->tableHeaderAlignment;
      $fill = 1;
      $border = $this->headerLines;
    }
    if (isset($setborder)) {
      $border = $setborder;
    }
    return array('align'=>$align, 'value'=>$val, 'fill'=>$fill, 'border'=>$border);
  }

  function log ($string, $prio=10) {
    if ($prio <= $this->DEBUG) {
      echo $string."<br />\n";
    }
  }

}  //  class PDFExport


/**
* PDF class that extends FPDF by putting the logo in the top corner
*
* @package    Bumblebee
* @subpackage Export
*/
class BrandedPDF extends FPDF {
  var $title = 'BumbleBee Report';
  var $pageWidth   = 297;   // mm
  var $pageHeight  = 210;   // mm
  var $leftMargin  = 15;    // mm
  var $rightMargin = 15;    // mm
  var $topMargin   = 15;    // mm
  var $bottomMargin= 15;    // mm
  
  var $DEBUG = 0;
  
  function BrandedPDF($orientation, $measure, $format) {
    parent::FPDF($orientation, $measure, $format);
  }
    
  function Header() {
    //Logo
    $this->Image('theme/export/logo.png',10,8,33);
    //Arial bold 15
    $this->SetFont('Arial','B',15);
    //Move to the right
    $this->Cell(40);
    //Title
    $this->Cell(200,30,$this->title,0,0,'C');
    //Line break
    $this->Ln(30);
  }
  
  //Page footer
  function Footer() {
    //Position at 1.5 cm from bottom
    $this->SetY(-15);
    //Arial italic 8
    $this->SetFont('Arial','I',8);
    //Page number
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
  }

  function SetFillColor($r=0, $g=0, $b=0) {
    if (is_array($r)) {
      return parent::SetFillColor($r[0], $r[1], $r[2]);
    } else {
      return parent::SetFillColor($r, $g, $b);
    }
  }
  
  function SetDrawColor($r=0, $g=0, $b=0) {
    if (is_array($r)) {
      return parent::SetDrawColor($r[0], $r[1], $r[2]);
    } else {
      return parent::SetDrawColor($r, $g, $b);
    }
  }
  
  function SetTextColor($r=0, $g=0, $b=0) {
    if (is_array($r)) {
      return parent::SetTextColor($r[0], $r[1], $r[2]);
    } else {
      return parent::SetTextColor($r, $g, $b);
    }
  }
    
  function SetFont($font=0, $style=0, $size=0) {
    if (is_array($font)) {
      return parent::SetFont($font[0], $font[1], $font[2]);
    } else {
      return parent::SetFont($font, $style, $size);
    }
  }
  
  function log ($string, $prio=10) {
    if ($prio <= $this->DEBUG) {
      echo $string."<br />\n";
    }
  }

  
  // overload the error function to give us some better output
  function Error($msg) {
    echo '<div class="error"><b>Error generating PDF output:</b><br/> '.$msg
        .'<br/><br/>Sorry things didn\'t work out for you.</div';
    if ($this->DEBUG) {
      preDump(debug_backtrace());
    }
    die('Exiting with error');
  }
  
} // class BrandedPDF


/**
* PDF class that extends BrandedPDF by providing functions for representing row data.
*
* Table-managing code adapted from Olivier's FPDF examples:
*      http://www.fpdf.org/en/script/script3.php
*
* @package    Bumblebee
* @subpackage Export
*/
class TabularPDF extends BrandedPDF {

  var $_last_sectionHeader;
  var $_last_tableHeader;
  var $_preventNewPage;
  
  var $continuedHeader = ' (continued)';
  
  var $lineHeight;
  var $normalLineHeight = 5;
  var $headerLineHeight = 6;
  var $footerLineHeight = 4;
  var $doubleLineWidth  = 0.2;
  var $singleLineWidth  = 0.3;
  var $sectionHeaderLineHeight = 8;
  var $cellTopMargin;
  var $singleCellTopMargin    = 1;
  
  var $normalFillColor = array(224,235,255);
  var $normalDrawColor = array(  0,  0,  0);
  var $normalTextColor = array(  0,  0,  0);
  var $normalFont      = array('Arial','',12);
  
  var $sectionHeaderFillColor = array(255,255,255);
  var $sectionHeaderDrawColor = array(  0,  0,  0);
  var $sectionHeaderTextColor = array(  0,  0,  0);
  var $sectionHeaderFont      = array('Arial','B',14);
  
  var $tableHeaderFillColor = array(  0,  0,128);
  var $tableHeaderDrawColor = array(  0,  0,  0);
  var $tableHeaderTextColor = array(255,255,255);
  var $tableHeaderFont      = array('Arial','B',12);
  
  var $tableFooterFillColor = array(  0,  0,128);
  var $tableFooterDrawColor = array(  0,  0,  0);
  var $tableFooterTextColor = array(255,255,255);
  var $tableFooterFont      = array('Arial','',9);
  
  var $tableTotalFillColor = array(224,235,255);
  var $tableTotalDrawColor = array(  0,  0,  0);
  var $tableTotalTextColor = array(  0,  0,  0);
  var $tableTotalFont      = array('Arial','',12);
  
  function TabularPDF($orientation, $measure, $format) {
    parent::BrandedPDF($orientation, $measure, $format);
  }
    

  function _setTableFont() {
    $this->SetFillColor($this->normalFillColor);
    $this->SetDrawColor($this->normalDrawColor);
    $this->SetLineWidth($this->singleLineWidth);
    $this->SetTextColor($this->normalTextColor);
    $this->SetFont($this->normalFont);
    $this->lineHeight = $this->normalLineHeight;
    $this->cellTopMargin = $this->singleCellTopMargin;
  }
  
  function _setSectionHeaderFont() {
    $this->SetFillColor($this->sectionHeaderFillColor);
    $this->SetDrawColor($this->sectionHeaderDrawColor);
    $this->SetLineWidth($this->singleLineWidth);
    $this->SetTextColor($this->sectionHeaderTextColor);
    $this->SetFont($this->sectionHeaderFont);
    $this->lineHeight = $this->normalLineHeight;
    $this->cellTopMargin = $this->singleCellTopMargin;
  }
  
  function _setTableHeaderFont() {
    $this->SetFillColor($this->tableHeaderFillColor);
    $this->SetDrawColor($this->tableHeaderDrawColor);
    $this->SetLineWidth($this->singleLineWidth);
    $this->SetTextColor($this->tableHeaderTextColor);
    $this->SetFont($this->tableHeaderFont);
    $this->lineHeight = $this->headerLineHeight;
    $this->cellTopMargin = $this->singleCellTopMargin;
  }
  
  function _setTableFooterFont() {
    $this->SetFillColor($this->tableFooterFillColor);
    $this->SetDrawColor($this->tableFooterDrawColor);
    $this->SetLineWidth($this->singleLineWidth);
    $this->SetTextColor($this->tableFooterTextColor);
    $this->SetFont($this->tableFooterFont);
    $this->lineHeight = $this->footerLineHeight;
    $this->cellTopMargin = $this->singleCellTopMargin;
  }
  
  function _setTableTotalFont() {
    $this->SetFillColor($this->tableTotalFillColor);
    $this->SetDrawColor($this->tableTotalDrawColor);
    $this->SetLineWidth($this->doubleLineWidth);
    $this->SetTextColor($this->tableTotalTextColor);
    $this->SetFont($this->tableTotalFont);
    $this->lineHeight = $this->normalLineHeight; //+ 4*$this->doubleLineWidth;
    $this->cellTopMargin = $this->singleCellTopMargin + 4*$this->doubleLineWidth;
  }
  
  function sectionHeader($header, $skipAddPage=false) {
    if (!$skipAddPage) {
      $this->AddPage();                  // FIXME: doesn't add a page if no section header??
      $this->_last_sectionHeader = $header;
    }
    //Colors, line width and bold font
    $this->_setSectionHeaderFont();
    $this->_row(array(array('value'=>$header,'border'=>'','align'=>'L', 'fill'=>true, 'fullWidth'=>1)));
    $this->_setTableFont();
    $this->lineHeight = $this->sectionHeaderLineHeight;
}  
  
  function repeatSectionHeader() {
    $this->sectionHeader($this->_last_sectionHeader.$this->continuedHeader, true);
  }
  
  function repeatTableHeader() {
    $this->tableHeader($this->_last_tableHeader);
  }
  
  function reportStart() {
  }
  
  function reportHeader() {
  }
  
  function reportEnd() {
  }
  
  function tableStart() {
  }
  
  function tableHeader($data) {
    $this->_setTableHeaderFont();
    //$data[0]['fullWidth'] = 1;
    $this->_row($data);
    $this->_last_tableHeader = $data;
    $this->_setTableFont();
  }
  
  function tableFooter($data) {
    $this->_setTableFooterFont();
    $this->_row($data);
    $this->_setTableFont();
  }
  
  function tableTotal($data) {
    $this->_setTableTotalFont();
    $this->_row($data);
    $this->_setTableFont();
  }
  
  function tableEnd() {
    $this->_preventNewPage = true;
    $currHeight = $this->lineHeight;
    $this->lineHeight = 0.1;
    $this->_row(array(array('value'=>'','border'=>'T','fill'=>false,'fullWidth'=>0)));
    $this->lineHeight = $currHeight;
    $this->_preventNewPage = false;
  } 
  
  function tableRow($data) {
    $this->_row($data);
  } 

  function _row($data) {
    $widths = array();
    if (count($data) == 1 && isset($data[0]['fullWidth']) && $data[0]['fullWidth'] == 1) {
      $widths[0] = $this->pageWidth - $this->leftMargin - $this->rightMargin;
    } elseif (count($data) == 1) {
      $widths[0] = array_sum($this->cols);
    } else {
      $widths = $this->cols;
    }
    //Calculate the height of the row
    $nb=0;
    for($i=0; $i<count($data); $i++)
        $nb=max($nb, $this->NbLines($widths[$i], $data[$i]['value']));
    $rowHeight = $this->lineHeight*$nb + $this->cellTopMargin; 
    //Issue a page break first if needed
    $this->CheckPageBreak($rowHeight);
    $y0bg  = $this->GetY();
    $y0txt = $y0bg + $this->cellTopMargin;
    //Draw the cells of the row
    $this->SetY($y0txt);
    for($i=0; $i<count($data); $i++) {
      $align=isset($data[$i]['align']) ? $data[$i]['align'] : 'L';
      //Save the current position
      $x = $this->GetX();
      //$y = $this->GetY();
      //Draw the background of the cell if appropriate
      if ($data[$i]['fill'])
        $this->Rect($x, $y0bg, $widths[$i], $rowHeight+$this->cellTopMargin, 'F');
      //Draw the borders requested
      if ($data[$i]['border']) {
        if (strpos($data[$i]['border'], 'B') !== false) {
          //echo 'B';
          $this->line($x,             $y0txt+$rowHeight, $x+$widths[$i], $y0txt+$rowHeight);
        }
        if (strpos($data[$i]['border'], 'T') !== false) {
          //echo 'T';
          $this->line($x,             $y0bg,            $x+$widths[$i], $y0bg);
        }
        if (strpos($data[$i]['border'], 'TT') !== false) {
          //double line on the top of the cell
          $dy=$this->doubleLineWidth*3; //mm
          $this->line($x,             $y0bg+$dy,        $x+$widths[$i], $y0bg+$dy);
        }
        if (strpos($data[$i]['border'], 'L') !== false) {
          //echo 'L';
          $this->line($x,             $y0bg,            $x,             $y0txt+$rowHeight);
        }
        if (strpos($data[$i]['border'], 'R') !== false) {
          //echo 'R';
          $this->line($x+$widths[$i], $y0bg,            $x+$widths[$i], $y0txt+$rowHeight);
        }
      }
      //Print the text
      $this->MultiCell($widths[$i], $this->lineHeight, $data[$i]['value'], '', $align, 0);
      //Put the position to the right of the cell
      $this->SetXY($x+$widths[$i],$y0txt);
    }
    //Go to the next line
    $this->Ln($rowHeight);
  }

  function CheckPageBreak($h) {
    //If the height h would cause an overflow, add a new page immediately
    if(! $this->_preventNewPage && $this->GetY()+$h>$this->PageBreakTrigger) {
      $this->tableEnd();
      $this->AddPage($this->CurOrientation);
      $this->repeatSectionHeader();
      $this->repeatTableHeader();
    }
  }

  function NbLines($w,$txt) {
    //Computes the number of lines a MultiCell of width w will take
    $cw=&$this->CurrentFont['cw'];
    if($w==0)
        $w=$this->w-$this->rMargin-$this->x;
    $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
    $s=str_replace("\r",'',$txt);
    $nb=strlen($s);
    if($nb>0 and $s[$nb-1]=="\n")
        $nb--;
    $sep=-1;
    $i=0;
    $j=0;
    $l=0;
    $nl=1;
    while($i<$nb)  {
        $c=$s[$i];
        if($c=="\n") {
            $i++;
            $sep=-1;
            $j=$i;
            $l=0;
            $nl++;
            continue;
        }
        if($c==' ')
            $sep=$i;
        $l+=$cw[$c];
        if($l>$wmax) {
            if($sep==-1)
            {
                if($i==$j)
                    $i++;
            }
            else
                $i=$sep+1;
            $sep=-1;
            $j=$i;
            $l=0;
            $nl++;
        }
        else
            $i++;
    }
    return $nl;
  }
  
        
} // class TabularPDF

?> 
