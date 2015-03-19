<?php
/**
* Simple Bumblebee installer -- creates an SQL and ini files from user input
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: install.php,v 1.7.2.3 2006/05/12 11:56:09 stuart Exp $
* @package    Bumblebee
* @subpackage Installer
*/

$sqlSourceFile = 'setup-tables.sql';
$sqlSetupFilename = 'bumblebee.sql';
$iniSourceFile = 'db.ini';
$iniSetupFilename = 'db.ini';

$defaults['sqlTablePrefix'] = '';
$defaults['sqlDefaultHost'] = 'localhost';
$defaults['sqlHost']        = $defaults['sqlDefaultHost'];
$defaults['sqlDefaultDB']   = 'bumblebeedb';
$defaults['sqlDB']          = $defaults['sqlDefaultDB'];
$defaults['sqlDefaultUser'] = 'bumblebee';
$defaults['sqlUser']        = $defaults['sqlDefaultUser'];
$defaults['sqlDefaultPass'] = 'bumblebeepass';
$defaults['sqlPass']        = $defaults['sqlDefaultPass'];

$defaults['bbDefaultAdmin']     = 'BumblebeeAdmin';
$defaults['bbDefaultAdminName'] = 'Queen Bee';
$defaults['bbDefaultAdminPass'] = 'defaultpassword123';
$defaults['bbAdmin']            = $defaults['bbDefaultAdmin'];
$defaults['bbAdminName']        = $defaults['bbDefaultAdminName'];
$defaults['bbAdminPass']        = $defaults['bbDefaultAdminPass'];





if (! isset($_POST['havedata'])) {
  printUserForm($defaults);
  exit;
}

$userSubmitted = array_merge($defaults, $_POST);
if (isset($_POST['submitrefreshdata'])) {
  printUserForm($userSubmitted);
  exit;
}

// only reach here if we have some config data and we can do something useful with it
if (isset($_POST['submitini'])) {
  $s = constructini($iniSourceFile, $userSubmitted);
  outputTextFile($iniSetupFilename, $s);
} elseif (isset($_POST['submitsql'])) {
  $s = constructSQL($sqlSourceFile, $userSubmitted, $_POST['includeAdmin']);
  outputTextFile($sqlSetupFilename, $s);
} elseif (isset($_POST['submitsqlload'])) {
  $s = constructSQL($sqlSourceFile, $userSubmitted, $_POST['includeAdmin']);
  $results = loadSQL($s, $_POST['sqlHost'], $_POST['sqlAdminUsername'], $_POST['sqlAdminPassword']);
  printUserData($userSubmitted, $results);
} elseif (isset($_POST['submitpostinst'])) {
  $results  = check_preinst($userSubmitted);
  $results .= check_postinst($userSubmitted);
  printUserData($userSubmitted, $results);
} else {
  $results = check_preinst($userSubmitted);
  printUserData($userSubmitted, $results);
} 


/**
* Check installation to see if required and optional components are installed.
* Also check to see if entered data is valid
*/
function check_preinst($data) {
  $s = array();
  $error = $warn = false;
  ini_set('track_errors', true);
  // check kit: check that a Bumblebee installation can be found
  $REBASE_INSTALL = '..'.DIRECTORY_SEPARATOR;
  set_include_path($REBASE_INSTALL.PATH_SEPARATOR.get_include_path());
  $NON_FATAL_CONFIG = true;
  $php_errormsg = '';
  if (@ include 'config/config.php') {   // FIXME file moved for v1.2
    $s[] = "GOOD: Found installation of Bumblebee version $BUMBLEBEEVERSION.";
  } else {
    $s[] = "ERROR: I couldn't find any evidence of a Bumblebee installation here. PHP said:<blockquote>\n$php_errormsg</blockquote>";
    $error = true;
  }
  if ($php_errormsg !== '') {
    $s[] = "ERROR: Configuration didn't load properly. "
           ."Bumblebee said:<blockquote>\n$php_errormsg</blockquote>";
    $error = true;
  } else {
    $s[] = "GOOD: Configuration loaded successfully";
  }
  // check kit: check that php-gettext can be found  // FIXME not needed for 1.0
//   if (! @ include 'php-gettext/gettext.inc') {
//     $s[] = "WARNING: <a href='https://savannah.nongnu.org/projects/php-gettext/'>php-gettext</a> internationali[sz]ation layer not found. Translations will not be available. "
//            ."PHP said:<blockquote>\n$php_errormsg</blockquote>";
//     $warn = true;
//   } else {
//     $s[] = "GOOD: php-gettext found for generating translated content.";
//   }
  // check kit: LDAP and RADIUS modules
  if (! (@ include 'Auth/Auth.php') || ! (@ include_once 'PEAR.php')) {
    $s[] = "WARNING: <a href='http://pear.php.net/'>PEAR::Auth</a> modules not found. LDAP and RADIUS authentication unavailable. "
           ."PHP said:<blockquote>\n$php_errormsg</blockquote>";
    $warn = true;
  } else {
    // check individually for LDAP and RADIUS here? but will that just cause a PHP crash if they are not installed?
    if (! extension_loaded('ldap')) {
      //$b = new Auth("LDAP", array(), '', false); 
      $s[] = "WARNING: PHP's <a href='http://php.net/ldap'>LDAP extension</a> was not found. LDAP authentication unavailable.";
      $warn = true;
    } else {
      $s[] = "GOOD: LDAP extension found for LDAP authentication.";
    }
    if (! PEAR::loadExtension('radius')) {
      //$b = new Auth("RADIUS", array("servers" => array()), "", false);    // hangs if radius module not installed
      $s[] = "WARNING: PHP's <a href='http://pecl.php.net/package/radius'>RADIUS extension</a> was not found. RADIUS authentication unavailable.";
      $warn = true;
    } else {
      $s[] = "GOOD: PECL RADIUS extension found for RADIUS authentication.";
    }
  }
  // check kit: see if FPDF is installed
  if (! (@ include 'fpdf/fpdf.php')) {
    $s[] = "WARNING: Free PDF library <a href='http://www.fpdf.org/'>FPDF</a> not found. Will not be able to generate PDF reports.";
    $warn = true;
  } else {
    $s[] = "GOOD: FPDF library found for generating PDF reports.";
  }

  // check username: make sure admin username meets Bumblebee requirements
  if (! preg_match($CONFIG['auth']['validUserRegexp'], $data['bbAdmin'])) {
    $s[] = "ERROR: The username you have chosen for your Admin user ('".$data['bbAdmin']."') "
          ."will not be able to log into Bumblebee due to restrictions on valid usernames in "
          ."<code>config/bumblebee.ini</code>. Either change the username you have chosen or "
          ."relax the restrictions specficied by <code>[auth].validUserRegexp</code> in that file.";
    $error = true;
  } else {
    $s[] = "GOOD: Admin username is valid.";
  }

  // check password strength of admin password
  list ($strength, $message) = passwordStrength($data['bbAdminPass']);
  if ($strength == 2) {
    $s[] = "ERROR: Admin user's password is poor. $message";
    $error = true;
  } elseif ($strength == 1) {
    $s[] = "WARNING: Admin user's password is poor. $message";
    $warn = true;
  } else {
    $s[] = "GOOD: Admin user's password seems ok. $message";
  }

  // check password strength of database password
  list ($strength, $message) = passwordStrength($data['sqlPass']);
  if ($strength == 2) {
    $s[] = "ERROR: Database user's password is poor. $message";
    $error = true;
  } elseif ($strength == 1) {
    $s[] = "WARNING: Database user's password is poor. $message";
    $warn = true;
  } else {
    $s[] = "GOOD: Database user's password seems ok. $message";
  }

  if ($error) {
    $s[] = "<b>Errors were detected. Please fix them and reload this page to perform these tests again.</b>";
  }
  if ($warn) {
    $s[] = "<b>Warnings were emitted. Please check to see if they are important to your setup and correct them if necessary. Reload this page to perform these tests again.</b>";
  }
  if (! $error && ! $warn) {
    $s[] = "<b>Excellent! Your setup looks fine.</b>";
  }
  return "<hr /><h2>Pre-install check</h2>"
        ."Checking to see if your kit looks good...<br />\n".parseTests($s).'<br /><br />';
}

/**
* Check installation to see if required and optional components are installed.
* post-inst auto-test of db, environment, auth modules etc.
* post-inst test that .ini files are protected by .htaccess
* check that admin can log in ok using auth.php
*/
function check_postinst($data) {
  $s = array();
  $error = $warn = false;
  ini_set('track_errors', true);
  // check that we can load the config correctly
  $REBASE_INSTALL = '..'.DIRECTORY_SEPARATOR;
  $NON_FATAL_CONFIG = true;
  $php_errormsg = '';
  if ((! @ require 'config/config.php') || $php_errormsg !== '') {   // FIXME file moved for v1.2
    $s[] = "ERROR: Configuration didn't load properly. "
           ."Bumblebee said:<blockquote>\n$php_errormsg</blockquote>";
    $error = true;
  } else {
    $s[] = "GOOD: Configuration loaded successfully";
  }
  // check that we can login to the db
  $NON_FATAL_DB = true;
  $DB_CONNECT_DEBUG = true;
  $php_errormsg = '';
  if ((! @ require_once 'inc/db.php') || $php_errormsg !== '') {
    $s[] = "ERROR: Unable to connect to database. "
           ."PHP said:<blockquote>\n$php_errormsg</blockquote>";
    $error = true;
  } else {
    $s[] = "GOOD: Successfully connected to database";
  }
  if (! $error) {
    // check that the admin user can log into the system
    require_once 'inc/bb/auth.php';
    $_POST['username'] = $data['bbAdmin'];
    $_POST['pass']     = $data['bbAdminPass'];
    $auth = @ new BumbleBeeAuth(true);
    if (! $auth->isLoggedIn()) {
      $auth->DEBUG=10;
      $s[] = "ERROR: Admin user cannot log in to Bumblebee with username and password supplied. Bumblebee said:"
            . "<blockquote>".$auth->loginError()."</blockquote>";
      $error = true;
    } else {
      $s[] .= "GOOD: Admin can log in to Bumblebee with this username and password.";
    }
  }

  // check to see if ini files are accessible to outsiders using HTTP
  $htdbini    = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$BASEPATH.'/config/db.ini';
  $localdbini = '..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'db.ini';
  if (! ini_get('allow_url_fopen')) {
    $s[] = "WARNING: The accessibility of your ini files (and passwords!) to outsiders cannot be checked."
          ."You can enable the PHP option <code>allow_url_fopen</code> in the <code>php.ini</code> file and rerun this test,"
          ."or you can see if you are able to download the file yourself through your browser: "
          ."<a href='$htdbini' target='_blank'>config/db.ini</a>.";
    $warn = true;
  } else {
    if ($dbinidata = @ file_get_contents($htdbini)) {
      // then something was downloaded
      if ($dbinidata == file_get_contents($localdbini)) {
        $s[] = "ERROR: it appears that your db.ini file can be downloaded from your webserver, exposing your "
              ."database passwords. Try it for yourself: "
              ."<a href='$htdbini' target='_blank'>config/db.ini</a>. "
              ."You really want to correct that either in your webserver's configuration or in a local .htaccess file.";
        $error = true;
      } else {
          $s[] = "WARNING: it appears something can be downloaded from your webserver from the config/db.ini file, "
                ."however, I was unable to verify what it was. Please try it for yourself: "
                ."<a href='$htdbini' target='_blank'>config/db.ini</a>. "
                ."You really want to make sure that the db.ini (and ldap.ini etc) file cannot be accessed as it "
                ."contains your password information.";
          $warn = true;
      }
    } elseif (preg_match('/\s403 Forbidden/', $php_errormsg)) {
      $s[] = "GOOD: db.ini file is protected against downloading (gives 403 Forbidden).";
    } elseif (preg_match('/\s404 Not Found/', $php_errormsg)) {
      $s[] = "WARNING: db.ini file gave a 404 Not Found error. If you have manually moved the config files "
            ."out of the webserver's file tree then that's fine, but if you haven't done this then your "
            ."setup in <code>bumblebee.ini</code> is specifying an incorrect location for your Bumblebee installation.";
    } else {
        $s[] = "WARNING: db.ini file appears to be protected against downloading, "
              ."but I didn't get a 403 Forbidden error. "
              ."Please try it for yourself: "
              ."<a href='$htdbini' target='_blank'>config/db.ini</a>. "
              ."You really want to make sure that the db.ini (and ldap.ini etc) file cannot be accessed as it "
              ."contains your password information.";
        $warn = true;
    }
  }
  
  // check to see if bumblebee.ini has the right place for the installation
  $htbb[]  = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$BASEURL;
  $htbb[]  = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$BASEPATH.'/';
  if (! ini_get('allow_url_fopen')) {
    $s[] = "WARNING: I can't test to see that your bumblebee.ini file points to the right URL.."
          ."You can enable the PHP option <code>allow_url_fopen</code> in the <code>php.ini</code> file and rerun this test,"
          ."or you can see if you are able to download the file yourself through your browser: "
          ."<a href='$htbb[0]' target='_blank'>check 1</a> "
          ."<a href='$htbb[1]' target='_blank'>check 2</a>.";
    $warn = true;
  } else {
    $htbbdata[] = @ file_get_contents($htbb[0]);
    $htbbdata[] = @ file_get_contents($htbb[1]);
    if ($htbbdata[0] || $htbbdata[1]) {
      // then something was downloaded
      if ($htbbdata[0] != $htbbdata[1]) {
        $s[] = "ERROR: I got different results when I tried to go to <a href='$htbb[0]' target='_blank'>check 1</a> "
              ."and <a href='$htbb[1]' target='_blank'>check 2</a>.";
        $error = true;
      } elseif (! preg_match('/Bumblebee/', $htbbdata[0])) {
        $s[] = "WARNING: I was able to find a webpage at your <a href='$htbb[0]' target='_blank'>configured location</a>, "
              ."but I couldn't find any evidence that it was a Bumblebee installation.";
        $warn = true;
      } else {
        $s[] = "GOOD: I could find your installation using http.";
      }
    } else {
      $s[] = "ERROR: I couldn't find a web page at your  <a href='$htbb[0]' target='_blank'>configured location</a>.";
      $error = true;
    }
  }
  
  if ($error) {
    $s[] = "<b>Errors were detected. Please fix them and reload this page.</b>";
  }
  if ($warn) {
    $s[] = "<b>Warnings were emitted. Please check to see if they are important to your setup and correct them if necessary.</b>";
  }
  if (! $error && ! $warn) {
    $s[] = "<b>Excellent! Your setup looks fine.</b><p><a href='$BASEURL'>Go to Bumblebee installation</a></p>";
  }
  return "<hr /><h2>Post-install check</h2>"
        ."Checking your setup works now you've installed the db.ini file and created the database...<br />\n"
        .parseTests($s).'<br /><br />';
}


/**
* Parse the test results and do some pretty printing of them
* @param array $results
* @return string pretty printed results
*/
function parseTests($r) {
  $replace = array(
              '/^GOOD:/'    => '<span class="good">GOOD:</span>',
              '/^WARNING:/' => '<span class="warn">WARNING:</span>',
              '/^ERROR:/'   => '<span class="error">ERROR:</span>'
             );
  $s = preg_replace(array_keys($replace), array_values($replace), $r);
  return join($s, "<br />\n");
}


/**
* Check the strength of the password
* @param string $password
* @return array list(integer (0=ok, 1=warn, 2=err), string description)
*/
function passwordStrength($password) {
  $advice = "Use at least 8 characters and include numbers, upper and lower case letters and some punctuation.";
  if (preg_match("/^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$/", $password)) {
    // password at least 8 chars and contains uppercase, lowercase, digits and punctuation
    return array(0, "password seems strong enough");
  }
  if (preg_match("/^(?=.{7,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$/", $password)) {
    // password at least 7 chars and contains two out of uppercase, lowercase, digits and punctuation
    return array(1, "This password is relatively weak. $advice");
  }
  if (strlen($password) > 8) {
    // password doesn't have lots of letters and numbers but at least it has 8 characters...
    return array(1, "This password is quite weak. $advice");
  }
  // password is exceedingly poor
  return array(1, "This password is very weak. $advice");
} 

/**
* Work out an SQL load file from the defaults and the user input
*/
function constructSQL($source, $replacements, $includeAdmin) {
  $sqlSourceFile = $source;
    
  $sqlTablePrefix       = $replacements['sqlTablePrefix'];
  $sqlDefaultHost       = $replacements['sqlDefaultHost'];
  $sqlHost              = $replacements['sqlHost'];
  $sqlDefaultDB         = $replacements['sqlDefaultDB'];
  $sqlDB                = $replacements['sqlDB'];
  $sqlDefaultUser       = $replacements['sqlDefaultUser'];
  $sqlUser              = $replacements['sqlUser'];
  $sqlDefaultPass       = $replacements['sqlDefaultPass'];
  $sqlPass              = $replacements['sqlPass'];
  $bbDefaultAdmin       = $replacements['bbDefaultAdmin'];
  $bbDefaultAdminName   = $replacements['bbDefaultAdminName'];
  $bbDefaultAdminPass   = $replacements['bbDefaultAdminPass'];
  $bbAdmin              = $replacements['bbAdmin'];
  $bbAdminName          = $replacements['bbAdminName'];
  $bbAdminPass          = $replacements['bbAdminPass'];

  $sql = file($sqlSourceFile);
  
  $sql = preg_replace("/(DELETE .+ WHERE User=')$sqlDefaultUser';/",
                      "$1$sqlUser';", $sql);
  $sql = preg_replace("/(INSERT INTO user .+)'$sqlDefaultHost','$sqlDefaultUser',\s*PASS.+\)(.+);/",
                      "$1'$sqlHost','$sqlUser',PASSWORD('$sqlPass')\$2;", $sql);
  // GRANT OR REVOKE PRIVS
  $sql = preg_replace("/(.+ ON) $sqlDefaultDB\.\* (TO|FROM) $sqlDefaultUser@$sqlDefaultHost;/",
                      "\$1 $sqlDB.* \$2 $sqlUser@$sqlHost;", $sql);
  // REVOKE ALL PRIVILEGES ON *.* FROM bumblebee;
  // REVOKE GRANT OPTION ON *.* FROM bumblebee;
  $sql = preg_replace("/(REVOKE .+ FROM) $sqlDefaultUser;/",
                      "\$1 $sqlUser;", $sql);
  // CREATE OR DROP DATABASE                     
  $sql = preg_replace("/^(.+) DATABASE(.*) $sqlDefaultDB;/",
                      "\$1 DATABASE\$2 $sqlDB;", $sql);
  $sql = preg_replace("/USE $sqlDefaultDB;/",
                      "USE $sqlDB;", $sql);
  $sql = preg_replace("/DROP TABLE IF EXISTS (.+)?;/",
                      "DROP TABLE IF EXISTS $sqlTablePrefix\$1;", $sql);
  $sql = preg_replace("/CREATE TABLE (.+)? /",
                      "CREATE TABLE $sqlTablePrefix\$1 ", $sql);
  // make the admin user
  $sql = preg_replace("/INSERT INTO (users)/",
                      "INSERT INTO $sqlTablePrefix\$1", $sql);
  
  $sql = preg_replace("/\('$bbDefaultAdmin','$bbDefaultAdminName',MD5\('$bbDefaultAdminPass'\),1\)/",
                      "('$bbAdmin','$bbAdminName','".md5($bbAdminPass)."',1);", $sql);
  $sql = preg_replace('/^(.*?)--.*$/',
                      '$1', $sql);
  $sql = preg_grep('/^\s*$/', $sql, PREG_GREP_INVERT);
  
  $stream = join($sql,'');
  if (! $includeAdmin) {
    $stream = substr($stream, strpos($stream, "USE $sqlDB"));
    $stream = "-- SQL user and database creation code removed as per user request.\n".$stream;
  }
  
  $settingComment = "-- Bumblebee SQL load file for ".$_SERVER['SERVER_NAME']."\n"
                   ."-- date: ".date('r', time())."\n"
                   ."-- sourced from $sqlSourceFile\n"
                   ."-- database: $sqlDefaultDB => $sqlDB\n"
                   ."-- table prefix: $sqlTablePrefix\n"
                   ."--\n"
                   ."-- Load this file using phpMyAdmin or on the MySQL command line tools:\n"
                   ."--     mysql -p --user someuser < tables.sql\n"
                   ."--\n";

  return $settingComment.$stream;
}

/**
* Work out a db.ini from the defaults and the user input
*/
function constructini($source, $defaults) {
  $eol = "\n";
  $s = '[database]'.$eol
      .'host = "'.$defaults['sqlHost'].'"'.$eol
      .'username = "'.$defaults['sqlUser'].'"'.$eol
      .'passwd = "'.$defaults['sqlPass'].'"'.$eol
      .'database = "'.$defaults['sqlDB'].'"'.$eol
      .'tableprefix = "'.$defaults['sqlTablePrefix'].'"'.$eol;
  return $s;
}

/**
* Dump the generated file to the user to save and upload to the server
*/
function outputTextFile($filename, $stream) {
  // Output a text file
  header("Content-type: text/plain"); 
  header("Content-Disposition: attachment; filename=$filename");
  echo $stream;
}


/**
* Load the generated SQL into the database one command at a time
*/
function loadSQL($sql, $host, $username, $passwd) {
  #echo "Loading SQL";
  if ($connection = @ mysql_pconnect($host, $username, $passwd)) {
    // then we successfully logged on to the database
    $sqllist = preg_split('/;/', $sql);
    foreach ($sqllist as $q) {
      #echo "$q\n";
      if (preg_match('/^\s*$/', $q)) continue;
      $handle = mysql_query($q);
      if (! $handle) {
        return "<hr />ERROR: I had trouble executing SQL statement:"
              ."<blockquote>$q</blockquote>"
              ."MySQL said:<blockquote>"
              .mysql_error()
              ."</blockquote>";
      }
    }
    return "<hr />SQL file loaded correctly";
  } else {
    return "<hr />ERROR: Could not log on to database to load SQL file: ".mysql_error() ;
  }
}

/**
* Find out from the user what username and passwords to use for connecting to the database etc
*/
function printUserForm($values) {
  ?>
<html>
  <head>
    <title>Bumblebee setup</title>
  </head>
  <body>
  <h1>Bumblebee Setup Script</h1>
  <p>Please use this script in conjunction with the 
  <a href='http://bumblebeeman.sourceforge.net/documentation/install'>installation instructions</a>
  and delete install.php after verifying that Bumblebee is working properly for you.</p>

  <form action='install.php' method='POST'>
  <fieldset>
    <legend>Input data</legend>
    <table>

    <?php printFormFields($values, false);?>
     <tr><td><input type='submit' name='submitdata' value='Check data' /></td></tr>

    </table>
  </fieldset>
  </form>
  </body>
</html>

  <?php

}

/**
* Show the user what data they have given and give options for what to do next
*/
function printUserData($values, $extradata) {
  ?>
<html>
  <head>
    <title>Bumblebee setup</title>
    <style>
      .good  { color: green;  font-weight: bolder; }
      .warn  { color: orange; font-weight: bolder; }
      .error { color: red;    font-weight: bolder; }
    </style>
  </head>
  <body>
  <h1>Bumblebee Setup Script</h1>

  <form action='install.php' method='POST'>
  <fieldset>
    <legend>Input data</legend>
    <table>
      <?php printFormFields($values, true);?>
    </table>
    <input type='submit' name='submitrefreshdata' value='Change data' />
  </fieldset>
  <fieldset id='dbsetup'>
    <legend>Database setup</legend>
    <label>
      <input type='checkbox' name='includeAdmin' value='1' checked='checked' />
      include commands to create the database and MySQL user
    </label><br />
    You can either download the database setup script and load it manually into the database
    or you can enter the username and password of a database user who is permitted
    to add database users, grant them permissions and create databases (<i>e.g.</i> root)
    and I'll try to setup database for you.
    <table>
    <tr><td width='50%' valign='top'>
    <fieldset>
      <legend>Manual setup</legend>
      <input type='submit' name='submitsql' value='Download database script' />
            <br/>
            Save the SQL file and then load it into the database using either phpMyAdmin or
            the mysql command line tools, <i>e.g.</i>: 
            <code style="white-space: nowrap;">mysql -p --user root &lt; bumbelebee.sql</code>
    </fieldset>
    </td><td width='50%' valign='top'>
    <fieldset>
      <legend>Automated setup</legend>
      <input type='submit' name='submitsqlload' value='Automated setup' />
            (needs username and password)
      <table>
        <tr>
          <td>MySQL admin username</td>
          <td><?php printField('sqlAdminUsername', 'root', false);?></td>
        </tr>
        <tr>
          <td>MySQL admin password</td>
          <td><?php printField('sqlAdminPassword', '', false, 'password');?></td>
        </tr>
      </table>
    </fieldset>
    </td></tr>
    </table>
  </fieldset>
  <fieldset id='dbini'>
    <legend>Config file generation</legend>
    Bumblebee needs to know what username and password to use for connecting to your database.
    Download the <code>db.ini</code> file (which will contain the values specified above)
    and save it into your Bumblebee installation on the webserver as <code>config/db.ini</code>.<br />
    <input type='submit' name='submitini' value='Generate db.ini file' />
  </fieldset>
  <fieldset id='test'>
    <legend>Test installation</legend>
    Once you have setup the database, given Bumblebee its <code>db.ini</code> file and customised your
    <code>bumblebee.ini</code> file, you might
    like to test your installion to make sure that it's all looking good.<br />
    <input type='submit' name='submitpostinst' value='Run post-install check' />
  </fieldset>
  
  <?php print $extradata; ?>

  </form>
  </body>
</html>

  <?php

}


/**
* Find out from the user what username and passwords to use for connecting to the database etc
*/
function printFormFields($values, $hidden) {
  ?>
  <tr>
    <td>MySQL host</td>
    <td><?php printField('sqlHost', $values['sqlHost'], $hidden);?></td>
  </tr>
  <tr>
    <td>MySQL database</td>
    <td><?php printField('sqlDB', $values['sqlDB'], $hidden);?></td>
  </tr>
  <tr>
    <td>MySQL table prefix</td>
    <td><?php printField('sqlTablePrefix', $values['sqlTablePrefix'], $hidden);?></td>
  </tr>
  <tr>
    <td>MySQL username</td>
    <td><?php printField('sqlUser', $values['sqlUser'], $hidden);?></td>
  </tr>
  <tr>
    <td>MySQL user password</td>
    <td><?php printField('sqlPass', $values['sqlPass'], $hidden);?></td>
  </tr>
  <tr>
    <td>Bumblebee admin username</td>
    <td><?php printField('bbAdmin', $values['bbAdmin'], $hidden);?></td>
  </tr>
  <tr>
    <td>Bumblebee admin password</td>
    <td><?php printField('bbAdminPass', $values['bbAdminPass'], $hidden);?></td>
  </tr>
  <tr>
    <td>Bumblebee admin user's real name</td>
    <td><?php printField('bbAdminName', $values['bbAdminName'], $hidden);?></td>
  </tr>
  <input type='hidden' name='havedata' value='1' />

  <?php
}

/**
* Display an individual field
*/
function printField($name, $value, $hidden, $type='text') {
  if ($hidden) {
    print "<input type='hidden' name='$name' value='$value' />$value";
  } else {
    print "<input type='$type' name='$name' value='$value' />";
  }
}

?>
