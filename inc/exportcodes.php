<?php
/**
* Numeric codes for defining data export types and formats
*
* Export types include TAB and Comma delimited, formats include the number of decimal places to use.
* The numeric codes for report sections aren't designed to be bitshifted (they 
* are mutually exclusive options) so they are not powers of 2.
* Formatting options may be use with bitwise addition etc.
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: exportcodes.php,v 1.10 2006/01/05 02:32:07 stuart Exp $
* @package    Bumblebee
* @subpackage Export
*/

/** use a user-defined function to create report */
define('EXPORT_FORMAT_CUSTOM',         1);
/** output format is a delimited format like CSV */
define('EXPORT_FORMAT_DELIMITED',      2);
/** comma separated variable format */
define('EXPORT_FORMAT_CSV',            EXPORT_FORMAT_DELIMITED|4);
/** tab separated variable format */
define('EXPORT_FORMAT_TAB',            EXPORT_FORMAT_DELIMITED|8);
/** delimited output bitmask */
define('EXPORT_FORMAT_DELIMITED_MASK', EXPORT_FORMAT_CSV|EXPORT_FORMAT_TAB);

/** create a complex data array using {@link ArrayExport } */
define('EXPORT_FORMAT_USEARRAY',  64);
/** create an HTML representation of the report using {@link HTMLExport } */
define('EXPORT_FORMAT_USEHTML',   128);
/** Open the HTML in a new window */
define('EXPORT_FORMAT_VIEWOPEN',  EXPORT_FORMAT_USEARRAY|EXPORT_FORMAT_USEHTML|256);
/** Open the HTML in the existing window */
define('EXPORT_FORMAT_VIEW',      EXPORT_FORMAT_USEARRAY|EXPORT_FORMAT_USEHTML|512);
/** Export using a PDF */
define('EXPORT_FORMAT_PDF',       EXPORT_FORMAT_USEARRAY|1024);
/** bitmask for all exports using {@link ArrayExport } */
define('EXPORT_FORMAT_USEARRAY_MASK', EXPORT_FORMAT_VIEWOPEN|EXPORT_FORMAT_PDF|EXPORT_FORMAT_VIEW);
/** bitmask for all exports using {@link HTMLExport } */
define('EXPORT_FORMAT_USEHTML_MASK',  EXPORT_FORMAT_VIEWOPEN|EXPORT_FORMAT_VIEW);

/** bitmask for all export formats */
define('EXPORT_FORMAT_MASK',      EXPORT_FORMAT_USEARRAY_MASK|EXPORT_FORMAT_DELIMITED_MASK
                                            |EXPORT_FORMAT_CUSTOM);

/** Start of the report (HTML and PDF reports) */
define('EXPORT_REPORT_START',              1);
/** End of the report (HTML and PDF reports) */
define('EXPORT_REPORT_END',                2);
/** Data for the report page header area */
define('EXPORT_REPORT_HEADER',             3);
/** Start of the section header for this part of the report */
define('EXPORT_REPORT_SECTION_HEADER',     4);
/** Start a data table in the report (HTML and PDF reports) */
define('EXPORT_REPORT_TABLE_START',        5);
/** Header row for the data table (HTML and PDF reports) */
define('EXPORT_REPORT_TABLE_HEADER',       6);
/** Data row in a data table (HTML and PDF reports) */
define('EXPORT_REPORT_TABLE_ROW',          7);
/** Totals row in a data table (HTML and PDF reports) */
define('EXPORT_REPORT_TABLE_TOTAL',        8);
/** Footer row in a data table (HTML and PDF reports) 
* @todo EXPORT_REPORT_TABLE_FOOTER not implemented in styling */
define('EXPORT_REPORT_TABLE_FOOTER',       9);
/** End of data table (HTML and PDF reports) */
define('EXPORT_REPORT_TABLE_END',         10);
  
                                          
/** Formatting code: alignment descriptions */
define('EXPORT_HTML_ALIGN',      1);
/** Formatting code: centre output */
define('EXPORT_HTML_CENTRE',     EXPORT_HTML_ALIGN|2);
/** Formatting code: right align output */
define('EXPORT_HTML_RIGHT',      EXPORT_HTML_ALIGN|4);
/** Formatting code: left-align output */
define('EXPORT_HTML_LEFT',       EXPORT_HTML_ALIGN|8);
/** Formatting code: alignment bitmask */
define('EXPORT_HTML_ALIGN_MASK', EXPORT_HTML_CENTRE|EXPORT_HTML_RIGHT|EXPORT_HTML_LEFT);

/** Formatting code: format as a number */
define('EXPORT_HTML_NUMBER',       32);
/** Formatting code: format as money (use defined currency symbol and 2 decimal places)
 * @todo 2d.p. isn't a good choice for all currencies
 */
define('EXPORT_HTML_MONEY',        EXPORT_HTML_NUMBER|64);
/** Formatting code: format to 1 decimal place, rounding appropriately */
define('EXPORT_HTML_DECIMAL_1',    EXPORT_HTML_NUMBER|128);  // round to 1 sig figs
/** Formatting code: format to 2 decimal places, rounding appropriately */
define('EXPORT_HTML_DECIMAL_2',    EXPORT_HTML_NUMBER|256);  // round to 2 sig figs
/** Formatting code: format to x decimal places  bitmask */
define('EXPORT_HTML_DECIMAL_MASK', EXPORT_HTML_DECIMAL_1|EXPORT_HTML_DECIMAL_2);
/** Formatting code: format as a number bitmask */
define('EXPORT_HTML_NUMBER_MASK',  EXPORT_HTML_MONEY|EXPORT_HTML_DECIMAL_MASK);

/** Formatting code: create an automatic total of the data for this column in the table */
define('EXPORT_CALC_TOTAL',        2048);

/**
* convert a string name for an export into the defined numeric code
*
* @param string $export_name  name of the exported variable
* @return integer numeric code for the export name
*/
function exportStringToCode($export_name) {
  switch ($export_name) {
    case 'EXPORT_FORMAT_CUSTOM':
      return EXPORT_FORMAT_CUSTOM;
    case 'EXPORT_FORMAT_VIEW':
      return EXPORT_FORMAT_VIEW;
    case 'EXPORT_FORMAT_VIEWOPEN':
      return EXPORT_FORMAT_VIEWOPEN;
    case 'EXPORT_FORMAT_CSV':
      return EXPORT_FORMAT_CSV;
    case 'EXPORT_FORMAT_TAB':
      return EXPORT_FORMAT_TAB;
    case 'EXPORT_FORMAT_PDF':
      return EXPORT_FORMAT_PDF;
  }
}

?>