<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Robert Lemke (robert@typo3.org)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * @author	Robert Lemke <robert@typo3.org>
 */
class object{}
//require_once(t3lib_extMgm::extPath('cby_webdav')."config.php");
require_once(t3lib_extMgm::extPath('cby_webdav')."WEBDAV/Filesystem.php");
require_once(t3lib_extMgm::extPath('cby_webdav')."class.tx_cbywebdav_t3io.php");
//error_reporting (E_ALL ^ E_NOTICE);
set_time_limit(0);

define('T3_FTPD_WWW_ROOT','WEBMOUNTS');
define('T3_FTPD_FILE_ROOT','FILEMOUNTS');





class tx_cbywebdav_testcase extends tx_t3unit_testcase {

	protected $WSDLURI;
	protected $SOAPServiceURI;
	var $CFG;

	public function __construct ($name) {
		$BACK_PATH='../../../typo3/';
		$UPLOADS_PATH='../../../uploads/';
		global $TSFE,$TT,$TYPO3_DB,$EXEC_TIME,$BE_USER,$SIM_EXEC_TIME;
		
		if ($_SERVER['PHP_SELF']) {
			if (!defined('PATH_thisScript')) define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', $_SERVER['PHP_SELF'])));
		} else {
			if (!defined('PATH_thisScript')) define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', $_ENV['_'])));
		}
		
		if (!defined('PATH_site')) define('PATH_site', str_replace('//','/', str_replace('\\','/', $_SERVER['DOCUMENT_ROOT'].realpath(PATH_thisScript).'/')));
		
		
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
		
		
		// *********************
		// Timetracking started
		// *********************
		require_once(PATH_t3lib.'class.t3lib_timetrack.php');
		$TT = new t3lib_timeTrack;
		$TT->start();
		$TT->push('','Script start');
		
		// *********************
		 // Mandatory libraries included
		 // *********************
		 $TT->push('Include class t3lib_db, t3lib_div, t3lib_extmgm','');
		         require_once(PATH_t3lib.'class.t3lib_div.php');
		
		         require_once(PATH_t3lib.'class.t3lib_extmgm.php');
		 $TT->pull();
		
		require_once(PATH_t3lib.'class.t3lib_tcemain.php');
		
		 // **********************
		 // Include configuration
		 // **********************
		 $TT->push('Include config files','');
		//require(PATH_t3lib.'config_default.php');
		
		
		 if (!defined ('TYPO3_db'))      die ('The configuration file was not included.');       // the name of the TYPO3 database is stored in this constant. Here the inclusion of the config-file is verified by checking if this var is set.
		 if (!t3lib_extMgm::isLoaded('cms'))     die('<strong>Error:</strong> The main frontend extension "cms" was not loaded. Enable it in the extension manager in the backend.');
		 
		 if (!defined('PATH_tslib')) {
		         define('PATH_tslib', t3lib_extMgm::extPath('cms').'tslib/');
		 }
		 require_once(PATH_typo3conf.'localconf.php');
		
		 require_once(PATH_t3lib.'class.t3lib_db.php');
		 $TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');
		 $TYPO3_DB->debugOutput = $TYPO3_CONF_VARS['SYS']['sqlDebug'];
		 
		 $CLIENT = t3lib_div::clientInfo();                              // Set to the browser: net / msie if 4+ browsers
		 $TT->pull();
		//require_once(PATH_typo3conf.'localconf.php');
		//require_once(PATH_t3lib.'class.t3lib_db.php');
		
		
		
		
		 
		 // *******************************
		 // Checking environment
		 // *******************************
		 if (t3lib_div::int_from_ver(phpversion())<4001000)      die ('TYPO3 runs with PHP4.1.0+ only');
		 
		 if (isset($_POST['GLOBALS']) || isset($_GET['GLOBALS']))        die('You cannot set the GLOBALS-array from outside the script.');
		 if (!get_magic_quotes_gpc())    {
		         $TT->push('Add slashes to GET/POST arrays','');
		         t3lib_div::addSlashesOnArray($_GET);
		         t3lib_div::addSlashesOnArray($_POST);
		         $HTTP_GET_VARS = $_GET;
		         $HTTP_POST_VARS = $_POST;
		         $TT->pull();
		 }
		
		// *********************
		// Libraries included
		 // *********************
		 $TT->push('Include Frontend libraries','');
		         require_once(PATH_tslib.'class.tslib_fe.php');
		         require_once(PATH_t3lib.'class.t3lib_page.php');
		         require_once(PATH_t3lib.'class.t3lib_userauth.php');
		         require_once(PATH_tslib.'class.tslib_feuserauth.php');
		         require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
		         require_once(PATH_t3lib.'class.t3lib_cs.php');
		 $TT->pull();
		//require_once(PATH_t3lib.'class.t3lib_page.php');	
		//require_once(PATH_tslib.'class.tslib_fe.php');
		//require_once(PATH_t3lib.'class.t3lib_userauth.php');
		//t3lib_div::devlog('FE :'.serialize($GLOBALS['TCA']) ,$ext);
		
		
		// ***********************************
		// Create $TSFE object (TSFE = TypoScript Front End)
		// Connecting to database
		// ***********************************
		$temp_TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
		$TSFE = new $temp_TSFEclassName(
		                $TYPO3_CONF_VARS,
		                 t3lib_div::_GP('id'),
		                 t3lib_div::_GP('type'),
		                 t3lib_div::_GP('no_cache'),
		                 t3lib_div::_GP('cHash'),
		                 t3lib_div::_GP('jumpurl'),
		                 t3lib_div::_GP('MP'),
		                 t3lib_div::_GP('RDCT')
		         );
		
		require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php');
		//require_once(PATH_t3lib.'class.t3lib_befunc.php');
		//require_once(PATH_t3lib.'class.t3lib_userauth.php');
		require_once(PATH_t3lib.'class.t3lib_userauthgroup.php');
		require_once(PATH_t3lib.'class.t3lib_extfilefunc.php');
		//require_once(PATH_t3lib.'class.t3lib_beuserauth.php');
		require_once(PATH_t3lib.'class.t3lib_tcemain.php');
		require_once(PATH_t3lib.'class.t3lib_userauthgroup.php');
		//require_once(PATH_t3lib.'class.t3lib_beuserauth.php');
		
		$TSFE->connectToDB();
		
		// *********
		// FE_USER
		// *********
		$TT->push('Front End user initialized','');
		$TSFE->initFEuser();
		$TT->pull();
		
		 // *****************************************
		 // Proces the ID, type and other parameters
		 // After this point we have an array, $page in TSFE, which is the page-record of the current page, $id
		 // *****************************************
		 $TT->push('Process ID','');
		 //this has been commented because of incompatibilities with URL rewriting.
		 //$TSFE->checkAlternativeIdMethods();
		 $TSFE->clear_preview();
		 $TSFE->determineId();
		
		 // Now, if there is a backend user logged in and he has NO access to this page, then re-evaluate the id shown!
		 if ($TSFE->beUserLogin && (!$BE_USER->extPageReadAccess($TSFE->page) || t3lib_div::_GP('ADMCMD_noBeUser')))     {       // t3lib_div::_GP('ADMCMD_noBeUser') is placed here because workspacePreviewInit() might need to know if a backend user is logged in!
		
		                 // Remove user
		         unset($BE_USER);
		         $TSFE->beUserLogin = 0;
		
		                 // Re-evaluate the page-id.
		         $TSFE->checkAlternativeIdMethods();
		         $TSFE->clear_preview();
		        $TSFE->determineId();
		 }
		 
		 $TSFE->makeCacheHash();
		 $TT->pull();
		
		 //$TSFE->getCompressedTCarray();
		 $TSFE->includeTCA();

		// *********
		// BE_USER
		// *********
		$BE_USER='';
		        $TYPO3_MISC['microtime_BE_USER_start'] = microtime();
		        $TT->push('Back End user initialized','');
		        require_once (PATH_t3lib.'class.t3lib_befunc.php');
		        require_once (PATH_t3lib.'class.t3lib_userauthgroup.php');
		 				require_once (PATH_t3lib.'class.t3lib_beuserauth.php');
		        require_once (PATH_t3lib.'class.t3lib_tsfebeuserauth.php');
		
		    $TT->pull();
				$TYPO3_MISC['microtime_BE_USER_end'] = microtime();
		
		// *********************
		
		if (!defined ('TYPO3_db'))  die ('The configuration file was not included.');
		if (isset($_POST['GLOBALS']) || isset($_GET['GLOBALS']))      die('You cannot set the GLOBALS-array from outside this script.');
		
		
		// ******************************************************
		// The backend language engine is started (ext: "lang")
		// ******************************************************
		require_once(PATH_typo3.'sysext/lang/lang.php');
		$LANG = t3lib_div::makeInstance('language');
		$LANG->init($BE_USER->uc['lang']);
		
		// Connect to the database
		$TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');
		$TYPO3_PAGE=t3lib_div::makeInstance('t3lib_pageSelect');
		//$T3BFILE=t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$TYPO3_TCE=t3lib_div::makeInstance('t3lib_TCEmain');
		$TYPO3_CONF_VARS['BE']['fileExtensions'] = array (
		    'webspace' => array('allow'=>'', 'deny'=>'php3,php'),
		    'ftpspace' => array('allow'=>'*', 'deny'=>'')
		
		);
		
		
		//$T3ROOTDIR=PATH_site.'webdav/';
		//$T3ROOTDIR=PATH_site;
		$T3ROOTDIR=$_SERVER['DOCUMENT_ROOT'];
		
		$result = $TYPO3_DB->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password); 
		if (!$result)	{
			die("Couldn't connect to database at ".TYPO3_db_host);
		}
		$TYPO3_DB->sql_select_db(TYPO3_db); 
		$GLOBALS['LANG']->charSet = 'utf-8'; //????

		$CFG = new object();
		$CFG->dbuser 			= TYPO3_db_username;				// user connecting to the database
		$CFG->dbpass 			= TYPO3_db_password;				// password of that user
		$CFG->dbhost 			= TYPO3_db_host;				// host to connect to
		$CFG->dbname 			= TYPO3_db;				// name of the database
		$CFG->dbtype			= "mysql";				// database type - currently available: mysql, pgsql (postgresql) and text
		$CFG->debuglevel=array(3);
		//$CFG->debuglevel=array();
		//$CFG->debugfunction=array('T3filectime','T3filesize','T3MakeFilePath','ServeRequest');
		//$CFG->debugfunction=array('T3filesize','T3MakeFilePath','T3ListDir','T3ReplaceMountPointsByPath');
		//$CFG->debugfunction=array('T3FileSize','T3FileCTime','T3FileMTime','T3IsFile');
		//$CFG->debugfunction=array('MKCOL','MOVE','COPY','T3IsFile','ServeRequest','T3FileExists','T3FileFieldCopy','T3IsFileUpload','T3MakeFilePath','T3ReplaceMountPointsByPath','T3GetFileMount');
		//$CFG->debugfunction=array('T3ListDir','ServeRequest','http_status','PROPFIND','T3FileExists','fileinfo');
		$CFG->debugfunction=array('COPY');
		//$CFG->debugfunction=array('PROPFIND','T3FileMTime','ServeRequest','http_status');
		//$CFG->debugfunction=array();
		//$CFG->debugfunction=array('PROPFIND','PROPPATCH','T3ListDir','http_status','fileinfo','ServeRequest'); //'_check_lock_status',
		//$CFG->debugfunction=array('LOCK',"_check_lock_status",'LOCKRESSOURCE','PUT','http_PUT','ServeRequest','DELETE','http_status');
		
		// Debug Levels :
		// 1 : Function calls (enter && outs)
		// 2 : Errors
		// 3 : SQL
		// 4 : Details
		
		$CFG->T3PAGE	=$TYPO3_PAGE;
		$CFG->T3DB=$TYPO3_DB;
		$CFG->T3TCE=$TYPO3_TCE;
		// Initialize the page-select functions.

		//print_r($GLOBALS['TSFE']);
		//$GLOBALS['TSFE']=$TSFE;
		$CFG->table	= array();
		$CFG->table['name']		= "be_users";				// name of the table which holds user data
		$CFG->table['username']	= "username";				// name of username field
		$CFG->table['password']	= "password";				// name of password field
		$CFG->table['uid']		= "uid";				// name of uid field
		$CFG->table['gid']		= "usergroup";				// name of gid field
		$CFG->text			= array();				// textfile-based user authentication -- see docs/README.text
		$CFG->text['file']		= t3lib_extMgm::extPath('cby_webdav').'nanoftpd/users';		// CBY path to file which holds user data
		$CFG->text['sep']		= ":";					// the character which separates the columns
		$CFG->crypt			= "md5";				// password encryption method ("plain" or "md5")
		$CFG->rootdir 			= t3lib_extMgm::extPath('cby_webdav').'nanoftpd';		//CBY nanoFTPd root directory
		$CFG->WEBDAVPREFIX ='webdav';
		$CFG->T3PHYSICALROOTDIR =$T3ROOTDIR; // CBY GET IT FROM T3
		$CFG->T3ROOTDIR =$T3ROOTDIR; //.$CFG->WEBDAVPREFIX; // CBY GET IT FROM T3
		$CFG->T3UPLOADDIR="/uploads/tx_cbywebdav/";
		$CFG->libdir 			= "$CFG->rootdir/lib";			// nanoFTPd lib/ directory
		$CFG->moddir 			= "$CFG->rootdir/modules";		// nanoFTPd modules/ directory
		$CFG->listen_addr =$TYPO3_CONF_VARS['EXTCONF']['cby_webdav']['listen_addr'];
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
		//require_once "class.tx_cbywebdav_t3io.php";
		
		$CFG->pasv_pool = new pool();
		$CFG->log 		= new log($CFG);
		if ($CFG->dbtype != "text") $CFG->dblink = db_connect($CFG->dbhost, $CFG->dbname, $CFG->dbuser, $CFG->dbpass);
		$t3io=t3lib_div::makeInstance('tx_cbywebdav_t3io');
		$t3io->T3Init($CFG);
		$t3io->T3Authenticate('admin','pr2oo8t7');
		$CFG->t3io=$t3io;

		$this->CFG=$CFG;
		parent::__construct ($name);
	}
	
	public function test_t3io_T3GetFileName() {		
		$t3io=$this->initT3io();
		$test=$t3io->T3GetFileName('/a/b/c/test.doc');
		self::assertTrue ($test== "test.doc", '/a/b/c/test.doc : '.$test); 
		$test=$t3io->T3GetFileName('test.doc');
		self::assertTrue ($test=="test.doc", 'test.doc : '.$test);
		$test=$t3io->T3GetFileName('a/b/c/test.doc');
		self::assertTrue ($test=="test.doc", 'a/b/c/test.doc : '.$test);

	}
/*
	public function test_t3io_T3CleanFilePath() {
		$t3io=$this->initT3io();
		$test=$t3io->T3CleanFilePath('/');
		self::assertTrue ($test=="/", 'test "/": '.$test); 
		$test=$t3io->T3CleanFilePath('//');
		self::assertTrue ($test=="/", 'test "//": '.$test); 
		$test=$t3io->T3CleanFilePath(' a/b/c /d/j ');
		self::assertTrue ($test=="a/b/c /d/j", 'test "a/b/c /d/j": '.$test); 
		$test=$t3io->T3CleanFilePath('a/////b///c../d////.....g///h///j...k../f');
		self::assertTrue ($test=="a/b/c/d/.g/h/j.k/f", 'test "a/b/c/d/.g/h/j.k/f": '.$test); 
		$test=$t3io->T3CleanFilePath('..');
		self::assertTrue ($test=="", 'test "..": '.$test); 
		$test=$t3io->T3CleanFilePath('');
		self::assertTrue ($test=="", 'test "": '.$test); 
		$test=$t3io->T3CleanFilePath('//');
		self::assertTrue ($test=="/", 'test "//": '.$test); 
		$test=$t3io->T3CleanFilePath(' a/a/a ');
		self::assertTrue ($test=="a/a/a", '" a/a/a ": '.$test); 

	}
	
public function test_t3io_T3ReplaceMountPointsByPath() {
		$t3io=$this->initT3io();
		$test=$t3io->T3ReplaceMountPointsByPath('/');
		self::assertTrue ($test=="/", 'test / ko: '.$test); 
		$test=$t3io->T3ReplaceMountPointsByPath('/FILEMOUNTS');
		self::assertTrue ($test=="/", 'test /FILEMOUNTS ko: '.$test); 
		$test=$t3io->T3ReplaceMountPointsByPath('/FILEMOUNTS/');
		self::assertTrue ($test=="/", 'test /FILEMOUNTS/ ko: '.$test); 
		$test=$t3io->T3ReplaceMountPointsByPath('/home/test/www/FILEMOUNTS');
		self::assertTrue ($test=="/home/test/www/", 'test /home/test/www/FILEMOUNTS ko: '.$test); 
		$test=$t3io->T3ReplaceMountPointsByPath('/home/test/www/webdav/FILEMOUNTS');
		self::assertTrue ($test=="/home/test/www/", 'test /home/test/www/webdav/FILEMOUNTS ko: '.$test); 
		$test=$t3io->T3ReplaceMountPointsByPath('/home/test/www/webdav/FILEMOUNTS/toto');
		self::assertTrue ($test=="/home/test/www/toto", 'test /home/test/www/FILEMOUNTS ko: '.$test); 
	}
	
	public function test_t3io_T3MakeVirtualPathFromPid() {
		$t3io=$this->initT3io();
		//$test=$t3io->T3MakeVirtualPathFromPid(116);
		self::assertTrue (true, 'test ko: '.$test); 
	}
	*/
	public function test_t3io_T3MakeFilePath() {
		$root=$_SERVER['DOCUMENT_ROOT'];		
		$t3io=$this->initT3io();
	  $test=$t3io->T3MakeFilePath("/home");
		self::assertTrue ($test== $root."home", '/home : '.$test);
	  $test=$t3io->T3MakeFilePath("$root");
		self::assertTrue ($test== "$root", $root.' : '.$test);
	  $test=$t3io->T3MakeFilePath($t3io->_unslashify($root));
		self::assertTrue ($test== "$root", $root.' : '.$test);
	  $test=$t3io->T3MakeFilePath($root."FILEMOUNTS");
		self::assertTrue ($test== $root."fileadmin", $root.'FILEMOUNTS : '.$test);
	  $test=$t3io->T3MakeFilePath($root."FILEMOUNTS/");
		self::assertTrue ($test== $root."fileadmin/",$root.'FILEMOUNTS/ : '.$test);
		$test=$t3io->T3MakeFilePath($root."FILEMOUNTS/fileadmin");
		self::assertTrue ($test== $root."fileadmin",$root.'FILEMOUNTS/fileadmin : '.$test);
	  $test=$t3io->T3MakeFilePath($root."FILEMOUNTS/test");
		self::assertTrue ($test== $root."fileadmin/test", $root.'FILEMOUNTS/test : '.$test);
	  $test=$t3io->T3MakeFilePath($root."fileadmin");
		self::assertTrue ($test== $root."fileadmin", $root.'fileadmin : '.$test);

   	/*	
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath /home/testdir/www ".$t3io->T3MakeFilePath("/home/testdir/www"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath /home/testdir/www/ ".$t3io->T3MakeFilePath("/home/testdir/www/"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath /home/testdir/www/fileadmin ".$t3io->T3MakeFilePath("/home/testdir/www/fileadmin"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath /home/testdir/www/fileadmin/test ".$t3io->T3MakeFilePath("/home/testdir/www/fileadmin/test"),"cby_webdav","TESTs");
   		//$this->cbywebdav_devlog(1,"=== T3MakeFilePath /home/testdir/www/FILEMOUNTS ".$t3io->T3MakeFilePath("/home/testdir/www/FILEMOUNTS"),"cby_webdav","TESTs");
   		//$this->cbywebdav_devlog(1,"=== T3MakeFilePath /home/testdir/www/FILEMOUNTS/fileadmin/test ".$t3io->T3MakeFilePath("/home/testdir/www/FILEMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
   		//$this->cbywebdav_devlog(1,"=== T3MakeFilePath /home/testdir/www/FILEMOUNTS/fileadmin ".$t3io->T3MakeFilePath("/home/testdir/www/FILEMOUNTS/fileadmin"),"cby_webdav","TESTs");
   		//$this->cbywebdav_devlog(1,"=== T3MakeFilePath /home/testdir/www/FILEMOUNTS/fileadmin/test ".$t3io->T3MakeFilePath("/home/testdir/www/FILEMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath /home/testdir/www/WEBMOUNTS ".$t3io->T3MakeFilePath("/home/testdir/www/WEBMOUNTS"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath /home/testdir/www/WEBMOUNTS/fileadmin/test ".$t3io->T3MakeFilePath("/home/testdir/www/WEBMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath /home/testdir/www/WEBMOUNTS/litmus ".$t3io->T3MakeFilePath("/home/testdir/www/WEBMOUNTS/litmus"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath /home/testdir/www/WEBMOUNTS/fileadmin/test ".$t3io->T3MakeFilePath("/home/testdir/www/WEBMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
    	$this->cbywebdav_devlog(1,"=== T3MakeFilePath FILEMOUNTS ".$t3io->T3MakeFilePath("FILEMOUNTS"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath FILEMOUNTS/fileadmin/test ".$t3io->T3MakeFilePath("FILEMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath FILEMOUNTS/fileadmin ".$t3io->T3MakeFilePath("FILEMOUNTS/fileadmin"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath FILEMOUNTS/fileadmin/test ".$t3io->T3MakeFilePath("FILEMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath WEBMOUNTS ".$t3io->T3MakeFilePath("WEBMOUNTS"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath WEBMOUNTS/fileadmin/test ".$t3io->T3MakeFilePath("WEBMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath WEBMOUNTS/litmus ".$t3io->T3MakeFilePath("WEBMOUNTS/litmus"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3MakeFilePath WEBMOUNTS/fileadmin/test ".$t3io->T3MakeFilePath("WEBMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
		*/
	}
	/*
	public function test_t3io_T3Authenticate() {
	}
	

	public function test_t3io_T3CheckFilePathRename() {
	}
	public function test_t3io_T3MakePageTitle() {
	}
	public function test_t3io_T3MakeContentTitle() {
	}
	public function test_t3io_T3ExtractPageTitle() {
	}
	public function test_t3io_T3IsFileUpload() {
	}
	public function test_t3io_T3GetUidFromFileName() {
	}
	public function test_t3io_T3GetCTypeFile() {
	}
	public function test_t3io_T3MakeNewFlexFormArray() {
	}
	public function test_t3io_T3ClearPageCache() {
	}
	public function test_t3io_T3ClearAllCache() {
	}
	public function test_t3io_cbywebdav_devlog() {
	}

	public function test_t3io_T3FuncCopy() {
	}
	
	public function test_t3io_T3FileFieldCopy() {
	}
	public function test_t3io_T3FlexFileCopy() {
	}
	public function test_t3io_T3LinkFileUpload() {
	}
	public function test_t3io_T3GetFileMount() {
	}

	public function test_t3io_T3ReplacePathByMountPoints() {
	}
	public function test_t3io_T3WMFileExists() {
	}
	public function test_t3io_T3FMFileExists() {
	}
	public function test_t3io_T3FileCTime() {
	}
	public function test_t3io_T3FileCTimeI() {
	}
	public function test_t3io_T3FileMTime() {
	}
	public function test_t3io_T3FileMTimeI() {
	}
	public function test_t3io_T3FileSize() {
	}
	public function test_t3io_T3IsDir() {
	}
	public function test_t3io_T3_strip_selected_tags() {
	}
	*/
	public function test_t3io_T3GetFileUid() {
		$t3io=$this->initT3io();
    $wd=t3lib_div::makeInstance('HTTP_WebDAV_Server_Filesystem');
	 	$wd->init($this->CFG);
	  $wd->base=$this->CFG->T3ROOTDIR;
	  unset($wd->_SERVER["CONTENT_LENGTH"]);
	  $root=$_SERVER['DOCUMENT_ROOT'];


	  $test=$t3io->T3GetFileUid("/home");
		self::assertTrue ($test==0, '/home : '.$test);
	  $test=$t3io->T3GetFileUid("/WEBMOUNTS");
		self::assertTrue ($test== 0, 'WEBMOUNTS : '.$test);
	  $test=$t3io->T3GetFileUid("/WEBMOUNTS");
		self::assertTrue ($test== 0, '/WEBMOUNTS : '.$test);
		
		$options["path"]="/WEBMOUNTS/foo.meta";
		$test=$wd->DELETE($options);				
	  $test=$wd->MKCOL($options,true);
		$uid0=$wd->CFG->T3DB->sql_insert_id();
		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta : '.$test);
		$options["path"]="/WEBMOUNTS/foo.meta/1";	  

	  $test=$wd->MKCOL($options,true);
		$uid1=$this->CFG->T3DB->sql_insert_id();	
		self::assertTrue ($test=="201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1 : '.$test);	
			
		$options["path"]="/WEBMOUNTS/foo.meta/1/2";	  
	  $test=$wd->MKCOL($options,true);
		$uid2=$this->CFG->T3DB->sql_insert_id();	
		self::assertTrue ($test=="201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1/2 : '.$test);		
		
		$options["path"]="/WEBMOUNTS/foo.meta/1/2/3";		
		$test=$wd->MKCOL($options,true);
		$uid3=$this->CFG->T3DB->sql_insert_id();	
		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1/2/3 : '.$test);

	  $test=$t3io->T3GetFileUid("/WEBMOUNTS/foo.meta");
		self::assertTrue ($test==$uid0, '/WEBMOUNTS/foo.meta : '.$test." , uid : $uid0");

	  $test=$t3io->T3GetFileUid("/WEBMOUNTS/foo.meta/");
		self::assertTrue ($test==$uid0, '/WEBMOUNTS/foo.meta/ : '.$test." , uid : $uid0");

	  $test=$t3io->T3GetFileUid("/WEBMOUNTS/foo.meta/1");
		self::assertTrue ($test==$uid1, '/WEBMOUNTS/foo.meta/1 : '.$test." , uid : $uid1");

	  $test=$t3io->T3GetFileUid("/WEBMOUNTS/foo.meta/1/");
		self::assertTrue ($test==$uid1, '/WEBMOUNTS/foo.meta/1/ : '.$test." , uid : $uid1");

	  $test=$t3io->T3GetFileUid("/WEBMOUNTS/foo.meta/1/2");
		self::assertTrue ($test==$uid2, '/WEBMOUNTS/foo.meta/1/2 : '.$test." , uid : $uid2");

	  $test=$t3io->T3GetFileUid("/WEBMOUNTS/foo.meta/1/2/");
		self::assertTrue ($test==$uid2, '/WEBMOUNTS/foo.meta/1/2/ : '.$test." , uid : $uid2");

	  $test=$t3io->T3GetFileUid("/WEBMOUNTS/foo.meta/1/2/3");
		self::assertTrue ($test==$uid3, '/WEBMOUNTS/foo.meta/1/2/3 : '.$test." , uid : $uid3");

	  $test=$t3io->T3GetFileUid("/WEBMOUNTS/foo.meta/1/2/3/");
		self::assertTrue ($test==$uid3, '/WEBMOUNTS/foo.meta/1/2/3/ : '.$test." , uid : $uid3");

		$options["path"]="/WEBMOUNTS/foo.meta";
		$test=$wd->DELETE($options);				
	}			
	
	public function test_t3io_T3FileExists() {		
		$t3io=$this->initT3io();
	  $test=$t3io->T3FileExists("/home");
		self::assertTrue ($test==0, '/home : '.$test);
	  $test=$t3io->T3FileExists("/home/testdir/www");
		self::assertTrue ($test==1, '/home/testdir/www : '.$test);
	  $test=$t3io->T3FileExists("/home/testdir/www/ZZZZZZ");
		self::assertTrue ($test==0, '/home/testdir/ZZZZZZ : '.$test);
	  $test=$t3io->T3FileExists("/home/testdir/www/FILEMOUNTS/");
		self::assertTrue ($test==1, '/home/testdir/FILEMOUNTS/fileadmin : '.$test);
	  $test=$t3io->T3FileExists("/home/testdir/www/fileadmin");
		self::assertTrue ($test==1, '/home/testdir/fileadmin : '.$test);
	  $test=$t3io->T3FileExists("/home/testdir/www/WEBMOUNTS");
		self::assertTrue ($test==1, '/home/testdir/WEBMOUNTS : '.$test);
	  $test=$t3io->T3FileExists("/home/testdir/www/FILEMOUNTS");
		self::assertTrue ($test==1, '/home/testdir/FILEMOUNTS : '.$test);
		/*
		  // sanity check
   		$this->cbywebdav_devlog(1,"=== T3FileExists /home/testdir/www/ ".$t3io->T3FileExists("/home/testdir/www/"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists /home/testdir/www/fileadmin ".$t3io->T3FileExists("/home/testdir/www/fileadmin"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists /home/testdir/www/fileadmin/test ".$t3io->T3FileExists("/home/testdir/www/fileadmin/test"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists /home/testdir/www/FILEMOUNTS ".$t3io->T3FileExists("/home/testdir/www/FILEMOUNTS"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists /home/testdir/www/FILEMOUNTS/fileadmin/test ".$t3io->T3FileExists("/home/testdir/www/FILEMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists /home/testdir/www/FILEMOUNTS/fileadmin ".$t3io->T3FileExists("/home/testdir/www/FILEMOUNTS/fileadmin"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists /home/testdir/www/FILEMOUNTS/fileadmin/test ".$t3io->T3FileExists("/home/testdir/www/FILEMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists /home/testdir/www/WEBMOUNTS ".$t3io->T3FileExists("/home/testdir/www/WEBMOUNTS"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists /home/testdir/www/WEBMOUNTS/fileadmin/test ".$t3io->T3FileExists("/home/testdir/www/WEBMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists /home/testdir/www/WEBMOUNTS/litmus ".$t3io->T3FileExists("/home/testdir/www/WEBMOUNTS/litmus"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists /home/testdir/www/WEBMOUNTS/fileadmin/test ".$t3io->T3FileExists("/home/testdir/www/WEBMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
    	$this->cbywebdav_devlog(1,"=== T3FileExists FILEMOUNTS ".$t3io->T3FileExists("FILEMOUNTS"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists FILEMOUNTS/fileadmin/test ".$t3io->T3FileExists("FILEMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists FILEMOUNTS/fileadmin ".$t3io->T3FileExists("FILEMOUNTS/fileadmin"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists FILEMOUNTS/fileadmin/test ".$t3io->T3FileExists("FILEMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists WEBMOUNTS ".$t3io->T3FileExists("WEBMOUNTS"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists WEBMOUNTS/fileadmin/test ".$t3io->T3FileExists("WEBMOUNTS/fileadmin/test"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists WEBMOUNTS/Accueil ".$t3io->T3FileExists("WEBMOUNTS/Accueil"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists WEBMOUNTS/Accueil/wap ".$t3io->T3FileExists("WEBMOUNTS/Accueil/wap"),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists WEBMOUNTS/Accueil/ ".$t3io->T3FileExists(utf8_encode("WEBMOUNTS/Accueil/")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3FileExists WEBMOUNTS/Accueil/clients/Gitd ".$t3io->T3FileExists("WEBMOUNTS/Accueil/wap"),"cby_webdav","TESTs");
		*/
	}		
	
	public function test_t3io_T3GetPid() {		
		$t3io=$this->initT3io();
    $wd=t3lib_div::makeInstance('HTTP_WebDAV_Server_Filesystem');
	 	$wd->init($this->CFG);
	  $wd->base=$this->CFG->T3ROOTDIR;
	  unset($wd->_SERVER["CONTENT_LENGTH"]);
	  $root=$_SERVER['DOCUMENT_ROOT'];


	  $test=$t3io->T3GetPid("/home");
		self::assertTrue ($test==0, '/home : '.$test);
	  $test=$t3io->T3GetPid("/WEBMOUNTS");
		self::assertTrue ($test== 0, 'WEBMOUNTS : '.$test);
	  $test=$t3io->T3GetPid("/WEBMOUNTS");
		self::assertTrue ($test== 0, '/WEBMOUNTS : '.$test);
		
		$options["path"]="/WEBMOUNTS/foo.meta";
		$test=$wd->DELETE($options);				
	  $test=$wd->MKCOL($options,true);
		$uid0=$this->CFG->T3DB->sql_insert_id();	

		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta : '.$test);
		
		$options["path"]="/WEBMOUNTS/foo.meta/1";	  
	  $test=$wd->MKCOL($options,true);
		$uid1=$this->CFG->T3DB->sql_insert_id();	
		self::assertTrue ($test=="201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1 : '.$test);	
			
		$options["path"]="/WEBMOUNTS/foo.meta/1/2";	  
	  $test=$wd->MKCOL($options,true);
		$uid2=$this->CFG->T3DB->sql_insert_id();	
		self::assertTrue ($test=="201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1/2 : '.$test);		
		
		$options["path"]="/WEBMOUNTS/foo.meta/1/2/3";		
		$test=$wd->MKCOL($options,true);
		$uid3=$this->CFG->T3DB->sql_insert_id();	
		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1/2/3 : '.$test);

	  $test=$t3io->T3GetPid("/WEBMOUNTS/foo.meta");
		self::assertTrue ($test==0, '/WEBMOUNTS/foo.meta : '.$test.", pid : 0");

	  $test=$t3io->T3GetPid("/WEBMOUNTS/foo.meta/");
		self::assertTrue ($test==0, '/WEBMOUNTS/foo.meta/ : '.$test.", pid : 0");

	  $test=$t3io->T3GetPid("/WEBMOUNTS/foo.meta/1");
		self::assertTrue ($test==$uid0, '/WEBMOUNTS/foo.meta/1 : '.$test.", pid : $uid0");

	  $test=$t3io->T3GetPid("/WEBMOUNTS/foo.meta/1/");
		self::assertTrue ($test==$uid0, '/WEBMOUNTS/foo.meta/1/ : '.$test.", pid : $uid0");

	  $test=$t3io->T3GetPid("/WEBMOUNTS/foo.meta/1/2");
		self::assertTrue ($test==$uid1, '/WEBMOUNTS/foo.meta/1/2 : '.$test.", pid : $uid1");

	  $test=$t3io->T3GetPid("/WEBMOUNTS/foo.meta/1/2/");
		self::assertTrue ($test==$uid1, '/WEBMOUNTS/foo.meta/1/2/ : '.$test.", pid : $uid1");

	  $test=$t3io->T3GetPid("/WEBMOUNTS/foo.meta/1/2/3");
		self::assertTrue ($test==$uid2, '/WEBMOUNTS/foo.meta/1/2/3 : '.$test.", pid : $uid2");

	  $test=$t3io->T3GetPid("/WEBMOUNTS/foo.meta/1/2/3/");
		self::assertTrue ($test==$uid2, '/WEBMOUNTS/foo.meta/1/2/3 : '.$test.", pid : $uid2");

		$options["path"]="/WEBMOUNTS/foo.meta";
		$test=$wd->DELETE($options);				


		
  	/*
  	  // T3GetPid
   		$this->cbywebdav_devlog(1,"=== T3GetPid WEBMOUNTS/Accueil ".serialize($t3io->T3GetPid("WEBMOUNTS/Accueil")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3GetPid WEBMOUNTS/Accueil/wap ".serialize($t3io->T3GetPid("WEBMOUNTS/Accueil/wap")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3GetPid WEBMOUNTS/Accueil/cbywebdav ".serialize($t3io->T3GetPid(utf8_encode("WEBMOUNTS/Accueil/cbywebdav"))),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3GetPid WEBMOUNTS/Accueil/clients ".serialize($t3io->T3GetPid("WEBMOUNTS/Accueil/clients")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3GetPid WEBMOUNTS/Accueil/clients/Gitd ".serialize($t3io->T3GetPid("WEBMOUNTS/Accueil/clients/Gitd")),"cby_webdav","TESTs");
		*/
	}
	
	public function test_t3io_T3IsFile() {		
		$t3io=$this->initT3io();
    $wd=t3lib_div::makeInstance('HTTP_WebDAV_Server_Filesystem');
	 	$wd->init($this->CFG);
	  $wd->base=$this->CFG->T3ROOTDIR;
	  unset($wd->_SERVER["CONTENT_LENGTH"]);
	  $root=$_SERVER['DOCUMENT_ROOT'];	
	  	
	  $test=$t3io->T3IsFile("/home");
		self::assertTrue ($test['isDir']==0, '/home isDir : '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/home isFile : '.serialize($test));
		self::assertTrue ($test['isWebmount']==0, '/home isWebmount : '.serialize($test));
		self::assertTrue ($test['isFilemount']==0, '/home isFilemount : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==0, '/home isAuthorized : '.serialize($test));
		self::assertTrue ($test['exists']==0, '/home exists : '.serialize($test));

	  $test=$t3io->T3IsFile("$root");
		self::assertTrue ($test['isDir']==1, $root.' isDir : '.serialize($test));
		self::assertTrue ($test['isFile']==0, $root.' isFile : '.serialize($test));
		self::assertTrue ($test['isWebmount']==0, $root.' isWebmount : '.serialize($test));
		self::assertTrue ($test['isFilemount']==1, $root.' isFileMount : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, $root.' isAuthorized : '.serialize($test));
		self::assertTrue ($test['exists']==1, $root.' exists : '.serialize($test));


	  $test=$t3io->T3IsFile("/WEBMOUNTS");
		self::assertTrue ($test['isDir']==1, '/home : '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/home : '.serialize($test));
		self::assertTrue ($test['isWebmount']==1, '/home : '.serialize($test));
		self::assertTrue ($test['isFilemount']==0, '/home : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/home : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/home exists: '.serialize($test));

	  $test=$t3io->T3IsFile("/WEBMOUNTS/");
		self::assertTrue ($test['isDir']==1, '/home : '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/home : '.serialize($test));
		self::assertTrue ($test['isWebmount']==1, '/home : '.serialize($test));
		self::assertTrue ($test['isFilemount']==0, '/home : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/home : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/home exists: '.serialize($test));

		$options["path"]="/WEBMOUNTS/foo.meta";
		$test=$wd->DELETE($options);				
	  $test=$wd->MKCOL($options,true);
		$uid0=$this->CFG->T3DB->sql_insert_id();	

		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta : '.$test);
		
		$options["path"]="/WEBMOUNTS/foo.meta/1";	  
	  $test=$wd->MKCOL($options,true);
		$uid1=$this->CFG->T3DB->sql_insert_id();	
		self::assertTrue ($test=="201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1 : '.$test);	
			
		$options["path"]="/WEBMOUNTS/foo.meta/1/2";	  
	  $test=$wd->MKCOL($options,true);
		$uid2=$this->CFG->T3DB->sql_insert_id();	
		self::assertTrue ($test=="201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1/2 : '.$test);		
		
		$options["path"]="/WEBMOUNTS/foo.meta/1/2/3";		
		$test=$wd->MKCOL($options,true);
		$uid3=$this->CFG->T3DB->sql_insert_id();	
		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1/2/3 : '.$test);

	  $test=$t3io->T3IsFile("/WEBMOUNTS/foo.meta");
		self::assertTrue ($test['isDir']==1, '/WEBMOUNTS/foo.meta/ isDir: '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/WEBMOUNTS/foo.meta/ isFile: '.serialize($test));
		self::assertTrue ($test['isWebmount']==1, '/WEBMOUNTS/foo.meta/ isWebmount: '.serialize($test));
		self::assertTrue ($test['isFilemount']==0, '/WEBMOUNTS/foo.meta/ isFilemount : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/WEBMOUNTS/foo.meta/ isAuthorized : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/WEBMOUNTS/foo.meta/ exists: '.serialize($test));

	  $test=$t3io->T3IsFile("/WEBMOUNTS/foo.meta/");
		self::assertTrue ($test['isDir']==1, '/WEBMOUNTS/foo.meta/ isDir: '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/WEBMOUNTS/foo.meta/ isFile: '.serialize($test));
		self::assertTrue ($test['isWebmount']==1, '/WEBMOUNTS/foo.meta/ isWebmount: '.serialize($test));
		self::assertTrue ($test['isFilemount']==0, '/WEBMOUNTS/foo.meta/ isFilemount : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/WEBMOUNTS/foo.meta/ isAuthorized : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/WEBMOUNTS/foo.meta/ exists: '.serialize($test));

	  $test=$t3io->T3IsFile("/WEBMOUNTS/foo.meta/1");
		self::assertTrue ($test['isDir']==1, '/WEBMOUNTS/foo.meta/ isDir: '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/WEBMOUNTS/foo.meta/ isFile: '.serialize($test));
		self::assertTrue ($test['isWebmount']==1, '/WEBMOUNTS/foo.meta/ isWebmount: '.serialize($test));
		self::assertTrue ($test['isFilemount']==0, '/WEBMOUNTS/foo.meta/ isFilemount : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/WEBMOUNTS/foo.meta/ isAuthorized : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/WEBMOUNTS/foo.meta/ exists: '.serialize($test));

	  $test=$t3io->T3IsFile("/WEBMOUNTS/foo.meta/1/");
		self::assertTrue ($test['isDir']==1, '/WEBMOUNTS/foo.meta/ isDir: '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/WEBMOUNTS/foo.meta/ isFile: '.serialize($test));
		self::assertTrue ($test['isWebmount']==1, '/WEBMOUNTS/foo.meta/ isWebmount: '.serialize($test));
		self::assertTrue ($test['isFilemount']==0, '/WEBMOUNTS/foo.meta/ isFilemount : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/WEBMOUNTS/foo.meta/ isAuthorized : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/WEBMOUNTS/foo.meta/ exists: '.serialize($test));

	  $test=$t3io->T3IsFile("/WEBMOUNTS/foo.meta/1/2");
		self::assertTrue ($test['isDir']==1, '/WEBMOUNTS/foo.meta/ isDir: '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/WEBMOUNTS/foo.meta/ isFile: '.serialize($test));
		self::assertTrue ($test['isWebmount']==1, '/WEBMOUNTS/foo.meta/ isWebmount: '.serialize($test));
		self::assertTrue ($test['isFilemount']==0, '/WEBMOUNTS/foo.meta/ isFilemount : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/WEBMOUNTS/foo.meta/ isAuthorized : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/WEBMOUNTS/foo.meta/ exists: '.serialize($test));

	  $test=$t3io->T3IsFile("/WEBMOUNTS/foo.meta/1/2/");
		self::assertTrue ($test['isDir']==1, '/WEBMOUNTS/foo.meta/ isDir: '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/WEBMOUNTS/foo.meta/ isFile: '.serialize($test));
		self::assertTrue ($test['isWebmount']==1, '/WEBMOUNTS/foo.meta/ isWebmount: '.serialize($test));
		self::assertTrue ($test['isFilemount']==0, '/WEBMOUNTS/foo.meta/ isFilemount : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/WEBMOUNTS/foo.meta/ isAuthorized : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/WEBMOUNTS/foo.meta/ exists: '.serialize($test));

	  $test=$t3io->T3IsFile("/WEBMOUNTS/foo.meta/1/2/3");
		self::assertTrue ($test['isDir']==1, '/WEBMOUNTS/foo.meta/ isDir: '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/WEBMOUNTS/foo.meta/ isFile: '.serialize($test));
		self::assertTrue ($test['isWebmount']==1, '/WEBMOUNTS/foo.meta/ isWebmount: '.serialize($test));
		self::assertTrue ($test['isFilemount']==0, '/WEBMOUNTS/foo.meta/ isFilemount : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/WEBMOUNTS/foo.meta/ isAuthorized : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/WEBMOUNTS/foo.meta/ exists: '.serialize($test));

	  $test=$t3io->T3IsFile("/WEBMOUNTS/foo.meta/1/2/3/");
		self::assertTrue ($test['isDir']==1, '/WEBMOUNTS/foo.meta/ isDir: '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/WEBMOUNTS/foo.meta/ isFile: '.serialize($test));
		self::assertTrue ($test['isWebmount']==1, '/WEBMOUNTS/foo.meta/ isWebmount: '.serialize($test));
		self::assertTrue ($test['isFilemount']==0, '/WEBMOUNTS/foo.meta/ isFilemount : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/WEBMOUNTS/foo.meta/ isAuthorized : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/WEBMOUNTS/foo.meta/ exists: '.serialize($test));


		$options["path"]="/WEBMOUNTS/foo.meta";
		$test=$wd->DELETE($options);				


	  $test=$t3io->T3IsFile("/FILEMOUNTS");
		self::assertTrue ($test['isDir']==1, '/FILEMOUNTS : '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/FILEMOUNTS : '.serialize($test));
		self::assertTrue ($test['isWebmount']==0, '/FILEMOUNTS : '.serialize($test));
		self::assertTrue ($test['isFilemount']==1, '/FILEMOUNTS : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/FILEMOUNTS : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/FILEMOUNTS exists: '.serialize($test));

	  $test=$t3io->T3IsFile("/FILEMOUNTS/");
		self::assertTrue ($test['isDir']==1, '/FILEMOUNTS/ : '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/FILEMOUNTS/ : '.serialize($test));
		self::assertTrue ($test['isWebmount']==0, '/FILEMOUNTS/ : '.serialize($test));
		self::assertTrue ($test['isFilemount']==1, '/FILEMOUNTS/ : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/FILEMOUNTS/ : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/FILEMOUNTS/ exists: '.serialize($test));

	  $test=$t3io->T3IsFile("/FILEMOUNTS/fileadmin");
		self::assertTrue ($test['isDir']==1, '/FILEMOUNTS/fileadmin isDir : '.serialize($test));
		self::assertTrue ($test['isFile']==0, '/FILEMOUNTS/fileadmin isFile : '.serialize($test));
		self::assertTrue ($test['isWebmount']==0, '/FILEMOUNTS/fileadmin isWebmount : '.serialize($test));
		self::assertTrue ($test['isFilemount']==1, '/FILEMOUNTS/fileadmin isFilemount : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, '/FILEMOUNTS/fileadmin isAuthorized : '.serialize($test));
		self::assertTrue ($test['exists']==1, '/FILEMOUNTS/ exists: '.serialize($test));


	  $test=$t3io->T3IsFile($root."FILEMOUNTS");
		self::assertTrue ($test['isDir']==1, $root.'FILEMOUNTS : '.serialize($test));
		self::assertTrue ($test['isFile']==0, $root.'FILEMOUNTS : '.serialize($test));
		self::assertTrue ($test['isWebmount']==0, $root.'FILEMOUNTS : '.serialize($test));
		self::assertTrue ($test['isFilemount']==1, $root.'FILEMOUNTS : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, $root.'FILEMOUNTS : '.serialize($test));
		self::assertTrue ($test['exists']==1, $root.'FILEMOUNTS exists : '.serialize($test));

		$test=$t3io->T3IsFile($root."fileadmin");
		self::assertTrue ($test['isDir']==1, $root.'fileadmin isDir :'.serialize($test));
		self::assertTrue ($test['isFile']==0, $root.'fileadmin isFile : '.serialize($test));
		self::assertTrue ($test['isWebmount']==0, $root.'fileadmin isWebmount : '.serialize($test));
		self::assertTrue ($test['isFilemount']==1, $root.'fileadmin isFilemount : '.serialize($test));
		self::assertTrue ($test['isAuthorized']==1, $root.'fileadmin isAuthorized : '.serialize($test));
		self::assertTrue ($test['exists']==1, $root.'fileadmin  exists: '.serialize($test));
		/*
		  // T3IsFile
   		$this->cbywebdav_devlog(1,"=== T3IsFile /home ".serialize($t3io->T3IsFile("/home")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3IsFile FILEMOUNTS/fileadmin/montest/1".serialize($t3io->T3IsFile("FILEMOUNTS/fileadmin/montest/1")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3IsFile WEBMOUNTS ".serialize($t3io->T3IsFile("WEBMOUNTS")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3IsFile WEBMOUNTS/litmus ".serialize($t3io->T3IsFile("WEBMOUNTS/litmus")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3IsFile WEBMOUNTS/litmus/frag ".serialize($t3io->T3IsFile("WEBMOUNTS/litmus/frag")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3IsFile WEBMOUNTS/litmus/frag/ ".serialize($t3io->T3IsFile("WEBMOUNTS/litmus/frag/")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3IsFile WEBMOUNTS/Accueil ".serialize($t3io->T3IsFile("WEBMOUNTS/Accueil")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3IsFile WEBMOUNTS/Accueil/wap ".serialize($t3io->T3IsFile("WEBMOUNTS/Accueil/wap")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3IsFile WEBMOUNTS/Accueil/cbywebdav ".serialize($t3io->T3IsFile(utf8_encode("WEBMOUNTS/Accueil/cbywebdav"))),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3IsFile WEBMOUNTS/Accueil/clients ".serialize($t3io->T3IsFile("WEBMOUNTS/Accueil/clients")),"cby_webdav","TESTs");
   		$this->cbywebdav_devlog(1,"=== T3IsFile WEBMOUNTS/Accueil/clients/Gitd ".serialize($t3io->T3IsFile("WEBMOUNTS/Accueil/clients/Gitd")),"cby_webdav","TESTs");
		*/
	}
	
	public function test_t3io_T3ListDir() {
		$root=$_SERVER['DOCUMENT_ROOT'];		
		$t3io=$this->initT3io();
		$list=$t3io->T3ListDir('/FILEMOUNTS');
		//print_r($list);
		self::assertTrue (count($list)>0, '/FILEMOUNTS : '.serialize($list));
		$list=$t3io->T3ListDir('/FILEMOUNTS/');
		//echo "<br/>";
		//print_r($list);
		self::assertTrue (count($list)>0, '/FILEMOUNTS : '.serialize($list));
	}

	// Web dav functions
	
	public function test_webdav_check_auth() {
	}

	public function test_webdav_MKCOL() {	
		$t3io=$this->initT3io();
    $wd=t3lib_div::makeInstance('HTTP_WebDAV_Server_Filesystem');
	 	$wd->init($this->CFG);
	  $wd->base=$this->CFG->T3ROOTDIR;
	  $root=$_SERVER['DOCUMENT_ROOT'];
	  
	  unset($wd->_SERVER["CONTENT_LENGTH"]);
		$options["path"]='/';
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "409 Conflict", 'MKCOL / : '.$test);
	  $options["path"]=$root;
	  $test=$wd->MKCOL($root);
		self::assertTrue ($test== "409 Conflict", "MKCOL $root : ".$test);
		
		$options["path"]="/FILEMOUNTS/";
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "409 Conflict", 'MKCOL /WEBMOUNTS/ : '.$test);
		
		$options["path"]="/FILEMOUNTS/foo.meta";				
	  $test=$wd->DELETE($options);
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /FILEMOUNTS/foo.meta : '.$test);		

		$options["path"]="/FILEMOUNTS/foo.meta/1";				
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /FILEMOUNTS/foo.meta/1 : '.$test);		

		$options["path"]="/FILEMOUNTS/foo.meta/1/2";				
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /FILEMOUNTS/foo.meta/1/2 : '.$test);		

		$options["path"]="/FILEMOUNTS/foo.meta/1/2/3";				
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /FILEMOUNTS/foo.meta/1/2/3 : '.$test);		

		$options["path"]="/WEBMOUNTS/foo.meta";				
	  $test=$wd->DELETE($options);
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta : '.$test);
		
		$options["path"]="/WEBMOUNTS/foo.meta/1";	  
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test=="201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1 : '.$test);	
			
		$options["path"]="/WEBMOUNTS/foo.meta/1/2";	  
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test=="201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1/2 : '.$test);		
		
		$options["path"]="/WEBMOUNTS/foo.meta/1/2/3";		
		$test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1/2/3 : '.$test);

		$options["path"]="/FILEMOUNTS/foo.meta";				
	  $test=$wd->DELETE($options);
		$options["path"]="/WEBMOUNTS/foo.meta";				
	  $test=$wd->DELETE($options);
	}
	
	public function test_webdav_COPY() {
		$t3io=$this->initT3io();
    $wd=t3lib_div::makeInstance('HTTP_WebDAV_Server_Filesystem');
	 	$wd->init($this->CFG);
	  $wd->base=$this->CFG->T3ROOTDIR;
	  unset($wd->_SERVER["CONTENT_LENGTH"]);
	  $root=$_SERVER['DOCUMENT_ROOT'];
		$options["path"]="/FILEMOUNTS/foo.meta";				
		
		// we clean up before (just in case) ...
		
		$options["path"]="/FILEMOUNTS/foo.meta";				
	  $test=$wd->DELETE($options);
	  $options["path"]="/FILEMOUNTS/foo.meta.dest";				
	  $test=$wd->DELETE($options);
		$options["path"]="/WEBMOUNTS/foo.meta";				
	  $test=$wd->DELETE($options);
	  $options["path"]="/WEBMOUNTS/foo.meta.dest";				
	  $test=$wd->DELETE($options);

		$options["path"]="/FILEMOUNTS/foo.meta";			
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /FILEMOUNTS/foo.meta : '.$test);		

		$options["path"]="/FILEMOUNTS/foo.meta/1";				
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /FILEMOUNTS/foo.meta/1 : '.$test);		

		$options["path"]="/FILEMOUNTS/foo.meta/1/2";				
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /FILEMOUNTS/foo.meta/1/2 : '.$test);		

		$options["path"]="/FILEMOUNTS/foo.meta/1/2/3";				
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /FILEMOUNTS/foo.meta/1/2/3 : '.$test);		
		$options["path"]="/FILEMOUNTS/foo.meta";				
    $options["dest"]="/FILEMOUNTS/foo.meta.dest";
		$options["depth"]="infinity";
    $test=$wd->COPY($options, false);
		self::assertTrue ($test== "201 Created", 'COPY /FILEMOUNTS/foo.meta >CopySimple> /FILEMOUNTS/foo.meta.dest : '.$test);		
		//$options["overwrite"]="F";
    $test=$wd->COPY($options, false);
 		self::assertTrue ($test== "412 precondition failed", 'COPY /FILEMOUNTS/foo.meta >Overwrite F> /FILEMOUNTS/foo.meta.dest : '.$test);		
		$options["overwrite"]="T";
    $test=$wd->COPY($options, false);
 		self::assertTrue ($test== "204 No Content", 'COPY /FILEMOUNTS/foo.meta >Overwrite T> /FILEMOUNTS/foo.meta.dest : '.$test);		
		unset($options["overwrite"]);
		
		$options["path"]="/WEBMOUNTS/foo.meta";				
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta : '.$test);
		
		$options["path"]="/WEBMOUNTS/foo.meta/1";	  
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test=="201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1 : '.$test);	
			
		$options["path"]="/WEBMOUNTS/foo.meta/1/2";	  
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test=="201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1/2 : '.$test);		
		
		$options["path"]="/WEBMOUNTS/foo.meta/1/2/3";		
		$test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta/1/2/3 : '.$test);

		$options["path"]="/WEBMOUNTS/foo.meta";				
    $options["dest"]="/WEBMOUNTS/foo.meta.dest";
    $test=$wd->COPY($options, false);
		self::assertTrue ($test== "201 Created", 'COPY /WEBMOUNTS/foo.meta >> /WEBMOUNTS/foo.meta.dest : '.$test);		
	
		$options["path"]="/WEBMOUNTS/foo.meta/ccsrc/";				
	  $test=$wd->MKCOL($options,true);
		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta/ccsrc/ : '.$test);
	  $uid=$wd->CFG->T3DB->sql_insert_id();
		$options["path"]="/WEBMOUNTS/foo.meta/ccsrc/subcoll/";				
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta/ccsrc/subcoll : '.$test);
		for ($n = 0; $n < 10; $n++) {
				$content['pid']=$uid;
				$content['header']=basename("foo.$n"); 
				$wd->CFG->T3DB->exec_INSERTquery('tt_content',$content); 	
    }
		$options["path"]="/WEBMOUNTS/foo.meta/ccsrc/";				
    $options["dest"]="/WEBMOUNTS/foo.meta/ccdest/";
	  $test=$wd->COPY($options);
		self::assertTrue ($test== "201 Created", 'COPY /WEBMOUNTS/foo.meta/ccdest : '.$test);
    $options["dest"]="/WEBMOUNTS/foo.meta/ccdest2/";
	  $test=$wd->COPY($options);
		self::assertTrue ($test== "201 Created", 'COPY /WEBMOUNTS/foo.meta/ccdest2 : '.$test);

		$options["path"]="/WEBMOUNTS/foo.meta/mvsrc/";				
	  $test=$wd->MKCOL($options,true);
		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta/mvsrc/ : '.$test);
	  $uid=$wd->CFG->T3DB->sql_insert_id();
		$options["path"]="/WEBMOUNTS/foo.meta/mvsrc/subcoll/";				
	  $test=$wd->MKCOL($options);
		self::assertTrue ($test== "201 Created", 'MKCOL /WEBMOUNTS/foo.meta/mvsrc/subcoll : '.$test);
		for ($n = 0; $n < 10; $n++) {
				$content['pid']=$uid;
				$content['header']=basename("foo.$n"); 
				$wd->CFG->T3DB->exec_INSERTquery('tt_content',$content); 	
    }
		$content['header']=basename("mvnoncoll"); 
		$wd->CFG->T3DB->exec_INSERTquery('tt_content',$content); 	

		$options["path"]="/WEBMOUNTS/foo.meta/mvsrc/";				
    $options["dest"]="/WEBMOUNTS/foo.meta/mvdest2/";
	  $test=$wd->COPY($options);
		self::assertTrue ($test== "201 Created", 'COPY /WEBMOUNTS/foo.meta/mvdest2 : '.$test);
    $options["dest"]="/WEBMOUNTS/foo.meta/mvdest/";
	  $test=$wd->MOVE($options);
		self::assertTrue ($test== "201 Created", 'MOVE /WEBMOUNTS/foo.meta/mvdest : '.$test);
		$options["path"]="/WEBMOUNTS/foo.meta/mvdest/";				
    $options["dest"]="/WEBMOUNTS/foo.meta/mvdest2/";
	  $test=$wd->MOVE($options);
		self::assertTrue ($test== "412 precondition failed", 'MOVE /WEBMOUNTS/foo.meta/mvdest /WEBMOUNTS/foo.meta/mvdest2 : '.$test);
		$options["overwrite"]="T";
 	  $test=$wd->MOVE($options);
		unset($options["overwrite"]);
		self::assertTrue ($test== "204 No Content", 'MOVE /WEBMOUNTS/foo.meta/mvdest overwrite /WEBMOUNTS/foo.meta/mvdest2 : '.$test);

		// we clean up after ...
		
		$options["path"]="/FILEMOUNTS/foo.meta";				
	  $test=$wd->DELETE($options);
	  $options["path"]="/FILEMOUNTS/foo.meta.dest";				
	  $test=$wd->DELETE($options);
		$options["path"]="/WEBMOUNTS/foo.meta";				
	  $test=$wd->DELETE($options);
	  $options["path"]="/WEBMOUNTS/foo.meta.dest";				
	  $test=$wd->DELETE($options);

	}

/*
	public function test_webdav_PROPFIND() {
	}
	public function test_webdav_urlencode() {
	}
	public function test_webdav_urldecode() {
	}
	public function test_webdav_fileinfo() {
	}
	public function test_webdav_can_execute() {
	}
	public function test_webdav_mimetype() {
	}
	public function test_webdav_GET() {
	}
	public function test_webdav_GetDir() {
	}
	public function test_webdav_PUT() {
	}
	public function test_webdav_AFTERPUT() {
	}
	public function test_webdav_DELETE() {
	}
	public function test_webdav_MOVE() {
	}
	public function test_webdav_T3COPY() {
	}
	public function test_webdav_PROPPATCH() {
	}
	public function test_webdav_LOCK() {
	}
	public function test_webdav_LOCKDIR() {
	}
	public function test_webdav_LOCKRESSOURCE() {
	}
	public function test_webdav_UNLOCK() {
	}
	
	public function test_webdav_checkLock() {
	}
	
	public function test_webdav_create_database() {
	}
	
	public function test_webdav_ServeRequest() {
	}
	public function test_webdav_init() {
	}
	public function test_webdav_cbywebdav_devlog() {
	}
	*/

	public function initT3io() {
	 		return $this->CFG->t3io;
  } 
		
}

?>