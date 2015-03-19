<?php
/**
* Content heading, branding etc
*  
* This is only a sample header file. 
* You can customise the menu system here or in pageheader.php
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: contentheader.php,v 1.5 2006/01/05 02:32:08 stuart Exp $
* @package    Bumblebee
* @subpackage theme
*/
  
  $MENUCONTENTS = $usermenu->getMenu();
  
?>
<body>

<div id="header">
  <div id="headerLeft">
    <a href='http://bumblebeeman.sf.net/' title="Bumblebee">
      <img src='<?php echo $BASEPATH ?>/theme/images/logo.png' alt="Bumblebee logo" />
    </a>
  </div>
  <div id="headerRight">
    <h1>Bumblebee Instrument Bookings</h1>
  </div>
</div>

<div id='fmenu'>
  <?php echo $MENUCONTENTS ?>
</div>
