<?php
/**
* Collate some stats on the current usage of the system (number of bookings etc)
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: systemstats.php,v 1.6 2006/01/05 02:32:07 stuart Exp $
* @package    Bumblebee
* @subpackage Misc
*/

/**
* Collate some stats on the current usage of the system (number of bookings etc)
*
* @package    Bumblebee
* @subpackage Misc
*/
class SystemStats {
  /**
  * numbers of rows in each table ($table => $num_rows)
  * @var array
  */
  var $stats;
  /**
  * tables on which stats should be compiled
  * @var array
  */
  var $tables;

  /** 
  * Constructor: load up the stats
  */
  function SystemStats() {
    $tables = array('users', 'projects', 'instruments', 'bookings');
    foreach ($tables as $t) {
      $this->stats[$t]       = $this->countEntries($t);
    }
  }

  /**
  * Runs the SQL count(*) query to find out how many rows in the table
  * @param string $table the table to query
  */
  function countEntries($table) {
    $row = quickSQLSelect($table, '', '', 0, 1);
    return $row[0];
  }

  /**
  * Return the stats for the designated table
  * @param string $table the table to query
  */
  function get($table) {
    return $this->stats[$table];
  }
}
?>
