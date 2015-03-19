<?php
/**
* Footer HTML that is included on every page
*  
* this is only a sample implementation giving credit to the BumbleBee project and some
* feedback on what BumbleBee has been managing.
*
* This is GPL'd software, so it is *not* a requirement that you give credit to BumbleBee,
* link to the site etc. In fact, this is in the theme/ directory to allow you to customise
* it easily, without having to delve into the rest of the code.
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: contentfooter.php,v 1.11 2006/01/05 02:32:08 stuart Exp $
* @package    Bumblebee
* @subpackage theme
*/

  /** use the SystemStats object to create some nice info about what the system does */
  include 'inc/systemstats.php';
  $stat = new SystemStats;
?>

<div id='bumblebeefooter'>
  <p>
    System managed by 
    <a href="http://bumblebeeman.sf.net/">BumbleBee</a> version
    <?php echo $BUMBLEBEEVERSION ?>,
    released under the 
    <a href="http://www.gnu.org/licenses/gpl.html">GNU GPL</a>.
  <br />
    This installation of BumbleBee currently manages
    <?php
      echo $stat->get('users') . ' users, ';
      echo $stat->get('projects') . ' projects, ';
      echo $stat->get('instruments') . ' instruments and ';
      echo $stat->get('bookings') . ' bookings. ';
    ?>
  <br />
    Email the <a href="mailto:<?php echo $ADMINEMAIL ?>">system administrator</a>
    for help.
  </p>
  <p class='bumblebeecopyright'>
    Booking information Copyright &copy; <?php echo date('Y').' '.$COPYRIGHTOWNER ?>.
  </p>
</div>
