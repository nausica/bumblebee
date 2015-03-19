<?php
/**
* Header HTML that is included on every page
*  
* This is only a sample implementation. Feel free to monkey to your heart's delight.
*
*  Note that three CSS files are used:
*    1. bumblebee.css    
*                contains the specific classes that are used for bumblebee markup
*    2. bumblebee-custom-colours.css
*                contains customisations of the default ones (mainly for colour customisation)
*    3. pagelayout.css   
*                contains other classes that are used by your own layout
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: pageheader.php,v 1.7 2006/01/05 02:32:08 stuart Exp $
* @package    Bumblebee
* @subpackage theme
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <title><?php echo $pagetitle?></title>
  <link rel="stylesheet" href="<?php echo $BASEPATH?>/theme/bumblebee.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo $BASEPATH?>/theme/bumblebee-custom-colours.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo $BASEPATH?>/theme/pagelayout.css" type="text/css" />
  <link rel="icon" href="<?php echo $BASEPATH?>/theme/images/favicon.ico" />
  <link rel="shortcut icon" href="<?php echo $BASEPATH?>/theme/images/favicon.ico" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  
<?php
  include 'inc/jsfunctions.php'
?>
</head>
