<?php

/*
****************************************************
* nanoFTPd - an FTP daemon written in PHP          *
****************************************************
* this file is licensed under the terms of GPL, v2 *
****************************************************
* developers:                                      *
*  - Arjen <arjenjb@wanadoo.nl>                    *
*  - Phanatic <linux@psoftwares.hu>                *
****************************************************
* http://sourceforge.net/projects/nanoftpd/        *
****************************************************
*/

error_reporting (E_ALL ^ E_NOTICE);
#error_reporting(E_ALL);
set_time_limit(0);

//phpinfo();

#define('TYPO3_cliMode', TRUE);
#define('TYPO3_MOD_PATH', '../typo3conf/ext/rss2_import/cron/');
define('T3_FTPD_WWW_ROOT','WEBMOUNTS');
define('T3_FTPD_FILE_ROOT','FILEMOUNTS');

$BACK_PATH='../../../../typo3/';
$UPLOADS_PATH='../../../../uploads/';

//$MCONF["name"]="_CLI_rss2_import";

if ($_SERVER['PHP_SELF']) {
	if (!defined('PATH_thisScript')) define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', $_SERVER['PHP_SELF'])));
} else {
	if (!defined('PATH_thisScript')) define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', $_ENV['_'])));
}
if (!defined('PATH_site')) define('PATH_site', dirname(dirname(dirname(dirname(dirname(PATH_thisScript))))).'/');

//echo PATH_site."@@@";
if (!defined('PATH_t3lib')) if (!defined('PATH_t3lib')) define('PATH_t3lib', PATH_site.'t3lib/');
define('PATH_typo3conf', PATH_site.'typo3conf/');
define('TYPO3_mainDir', 'typo3/');
if (!defined('PATH_typo3')) define('PATH_typo3', PATH_site.TYPO3_mainDir);
if (!defined('PATH_tslib')) {
	if (@is_dir(PATH_site.'typo3/sysext/cms/tslib/')) {
		define('PATH_tslib', PATH_site.'typo3/sysext/cms/tslib/');
	} elseif (@is_dir(PATH_site.'tslib/')) {
		define('PATH_tslib', PATH_site.'tslib/');
	}
}

define('TYPO3_OS', stristr(PHP_OS,'win')&&!stristr(PHP_OS,'darwin')?'WIN':'');
define('TYPO3_MODE', 'BE');


//print_r
require_once(PATH_t3lib.'class.t3lib_div.php');
require_once(PATH_t3lib.'class.t3lib_extmgm.php');
require_once(PATH_t3lib.'config_default.php');
require_once(PATH_typo3conf.'localconf.php');
require_once(PATH_t3lib.'class.t3lib_page.php');	
require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php');
require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_userauth.php');
require_once(PATH_t3lib.'class.t3lib_userauthgroup.php');
require_once(PATH_t3lib.'class.t3lib_beuserauth.php');
require_once(PATH_t3lib.'class.t3lib_extfilefunc.php');

if (!defined ('TYPO3_db'))  die ('The configuration file was not included.');
if (isset($_POST['GLOBALS']) || isset($_GET['GLOBALS']))      die('You cannot set the GLOBALS-array from outside this script.');
	
	// Connect to the database
require_once(PATH_t3lib.'class.t3lib_db.php');
$TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');
$TYPO3_PAGE=t3lib_div::makeInstance('t3lib_pageSelect');
//$T3BFILE=t3lib_div::makeInstance('t3lib_basicFileFunctions');

$TYPO3_CONF_VARS['BE']['fileExtensions'] = array (

    'webspace' => array('allow'=>'', 'deny'=>'php3,php'),

    'ftpspace' => array('allow'=>'*', 'deny'=>'')

);

$T3ROOTDIR=PATH_site;
$result = $TYPO3_DB->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password); 
if (!$result)	{
	die("Couldn't connect to database at ".TYPO3_db_host);
}
$TYPO3_DB->sql_select_db(TYPO3_db); 

class object{}
$CFG = new object();
$CFG->dbuser 			= TYPO3_db_username;				// user connecting to the database
$CFG->dbpass 			= TYPO3_db_password;				// password of that user
$CFG->dbhost 			= TYPO3_db_host;				// host to connect to
$CFG->dbname 			= TYPO3_db;				// name of the database
$CFG->dbtype			= "mysql";				// database type - currently available: mysql, pgsql (postgresql) and text
$CFG->T3PAGE	=$TYPO3_PAGE;
$CFG->T3DB=$TYPO3_DB;
//$CFG->T3FILE=$T3EXTFILE;
$CFG->table	= array();
$CFG->table['name']		= "be_users";				// name of the table which holds user data
$CFG->table['username']		= "username";				// name of username field
$CFG->table['password']		= "password";				// name of password field
$CFG->table['uid']		= "uid";				// name of uid field
$CFG->table['gid']		= "usergroup";				// name of gid field
$CFG->text			= array();				// textfile-based user authentication -- see docs/README.text
$CFG->text['file']		= t3lib_extMgm::extPath('cby_webdav').'nanoftpd/users';		// CBY path to file which holds user data
$CFG->text['sep']		= ":";					// the character which separates the columns
$CFG->crypt			= "md5";				// password encryption method ("plain" or "md5")
$CFG->rootdir 			= t3lib_extMgm::extPath('cby_webdav').'nanoftpd';		//CBY nanoFTPd root directory
$CFG->T3ROOTDIR =$T3ROOTDIR; // CBY GET IT FROM T3
$CFG->T3UPLOADDIR="/uploads/tx_cbywebdav/";
$CFG->libdir 			= "$CFG->rootdir/lib";			// nanoFTPd lib/ directory
$CFG->moddir 			= "$CFG->rootdir/modules";		// nanoFTPd modules/ directory
$CFG->listen_addr =$TYPO3_CONF_VARS['EXTCONF']['cby_webdav']['listen_addr'];
//$CFG->listen_addr 		= "213.251.162.171";			// IP address where nanoFTPd should listen
$CFG->listen_port 		= $TYPO3_CONF_VARS['EXTCONF']['cby_webdav']['listen_port'];					// port where nanoFTPd should listen // CBY GET IT FROM T3
$CFG->low_port			= $TYPO3_CONF_VARS['EXTCONF']['cby_webdav']['low_port'];
$CFG->high_port			= $TYPO3_CONF_VARS['EXTCONF']['cby_webdav']['high_port'];
$CFG->max_conn			= $TYPO3_CONF_VARS['EXTCONF']['cby_webdav']['max_conn'];					// max number of connections allowed
$CFG->max_conn_per_ip	= $TYPO3_CONF_VARS['EXTCONF']['cby_webdav']['max_conn_per_ip'];					// max number of connections per ip allowed
$CFG->io			= "file";				// io module (default: file) -- note: ips doesn't work
$CFG->server_name 		= "TYPO3 FTPd server [BALISKY Services]";		// nanoFTPd server name

$CFG->dynip			= array();				// dynamic ip support -- see docs/REAME.dynip
$CFG->dynip['on']		= 0;					// 0 = off (use listen_addr directive) 1 = on (override listen_addr directive)
$CFG->dynip['iface']	= "ppp0";					// interface connecting to the internet

$CFG->logging = new object;
$CFG->logging->mode		= 1;					// 0 = no logging, 1 = to file (see directive below), 2 = to console, 3 = both
$CFG->logging->file		= "$CFG->rootdir/log/nanoftpd.log";	// the file where nanoFTPd should log the accesses & errors


require($CFG->libdir."/db_".$CFG->dbtype.".php");
require("$CFG->libdir/pool.php");
require("$CFG->libdir/auth.php");
require("$CFG->libdir/log.php");

$CFG->pasv_pool = new pool();
$CFG->log 		= new log($CFG);
if ($CFG->dbtype != "text") $CFG->dblink = db_connect($CFG->dbhost, $CFG->dbname, $CFG->dbuser, $CFG->dbpass);

?>
