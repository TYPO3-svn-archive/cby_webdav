<?php
error_reporting (E_ALL ^ E_NOTICE);
set_time_limit(0);

define('T3_FTPD_WWW_ROOT','WEBMOUNTS');
define('T3_FTPD_FILE_ROOT','FILEMOUNTS');

$BACK_PATH='../../../typo3/';
$UPLOADS_PATH='../../../uploads/';


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
;
require_once(PATH_t3lib.'class.t3lib_tcemain.php');

 // **********************
 // Include configuration
 // **********************
 $TT->push('Include config files','');
require(PATH_t3lib.'config_default.php');

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
 require_once(PATH_t3lib.'class.t3lib_page.php');	
 require_once(PATH_tslib.'class.tslib_fe.php');
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
         
//$TSFE->fetch_the_id();
// Initialize the page-select functions.

		
		
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
 
        // the value this->formfield_status is set to empty in order to disable login-attempts to the backend account through this script
/*        $BE_USER = t3lib_div::makeInstance('t3lib_tsfeBeUserAuth');     // New backend user object
        $BE_USER->OS = TYPO3_OS;
        $BE_USER->lockIP = $TYPO3_CONF_VARS['BE']['lockIP'];
        $BE_USER->start();                      // Object is initialized
        $BE_USER->unpack_uc('');
        if ($BE_USER->user['uid'])      {
             $BE_USER->fetchGroupData();
             $TSFE->beUserLogin = 1;
        }
00233                 if ($BE_USER->checkLockToIP() && $BE_USER->checkBackendAccessSettingsFromInitPhp())     {
00234                         $BE_USER->extInitFeAdmin();
00235                         if ($BE_USER->extAdmEnabled)    {
00236                                 require_once(t3lib_extMgm::extPath('lang').'lang.php');
00237                                 $LANG = t3lib_div::makeInstance('language');
00238                                 $LANG->init($BE_USER->uc['lang']);
00239 
00240                                 $BE_USER->extSaveFeAdminConfig();
00241                                         // Setting some values based on the admin panel
00242                                 $TSFE->forceTemplateParsing = $BE_USER->extGetFeAdminValue('tsdebug', 'forceTemplateParsing');
00243                                 $TSFE->displayEditIcons = $BE_USER->extGetFeAdminValue('edit', 'displayIcons');
00244                                 $TSFE->displayFieldEditIcons = $BE_USER->extGetFeAdminValue('edit', 'displayFieldIcons');
00245 
00246                                 if (t3lib_div::_GP('ADMCMD_editIcons')) {
00247                                         $TSFE->displayFieldEditIcons=1;
00248                                         $BE_USER->uc['TSFE_adminConfig']['edit_editNoPopup']=1;
00249                                 }
00250                                 if (t3lib_div::_GP('ADMCMD_simUser'))   {
00251                                         $BE_USER->uc['TSFE_adminConfig']['preview_simulateUserGroup']=intval(t3lib_div::_GP('ADMCMD_simUser'));
00252                                         $BE_USER->ext_forcePreview=1;
00253                                 }
00254                                 if (t3lib_div::_GP('ADMCMD_simTime'))   {
00255                                         $BE_USER->uc['TSFE_adminConfig']['preview_simulateDate']=intval(t3lib_div::_GP('ADMCMD_simTime'));
00256                                         $BE_USER->ext_forcePreview=1;
00257                                 }
00258 
00259                                         // Include classes for editing IF editing module in Admin Panel is open
00260                                 if (($BE_USER->extAdmModuleEnabled('edit') && $BE_USER->extIsAdmMenuOpen('edit')) || $TSFE->displayEditIcons == 1)      {
00261                                         $TSFE->includeTCA();
00262                                         if ($BE_USER->extIsEditAction())        {
00263                                                 require_once (PATH_t3lib.'class.t3lib_tcemain.php');
00264                                                 $BE_USER->extEditAction();
00265                                         }
00266                                         if ($BE_USER->extIsFormShown()) {
00267                                                 require_once(PATH_t3lib.'class.t3lib_tceforms.php');
00268                                                 require_once(PATH_t3lib.'class.t3lib_iconworks.php');
00269                                                 require_once(PATH_t3lib.'class.t3lib_loaddbgroup.php');
00270                                                 require_once(PATH_t3lib.'class.t3lib_transferdata.php');
00271                                         }
00272                                 }
00273 
00274                                 if ($TSFE->forceTemplateParsing || $TSFE->displayEditIcons || $TSFE->displayFieldEditIcons)     { $TSFE->set_no_cache(); }
00275                         }
00276 
00277         //              $WEBMOUNTS = (string)($BE_USER->groupData['webmounts'])!='' ? explode(',',$BE_USER->groupData['webmounts']) : Array();
00278         //              $FILEMOUNTS = $BE_USER->groupData['filemounts'];
00279                 } else {        // Unset the user initialization.
00280                         $BE_USER='';
00281                         $TSFE->beUserLogin=0;
00282                 }
*/
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

class object{}
$CFG = new object();
$CFG->dbuser 			= TYPO3_db_username;				// user connecting to the database
$CFG->dbpass 			= TYPO3_db_password;				// password of that user
$CFG->dbhost 			= TYPO3_db_host;				// host to connect to
$CFG->dbname 			= TYPO3_db;				// name of the database
$CFG->dbtype			= "mysql";				// database type - currently available: mysql, pgsql (postgresql) and text
$CFG->debuglevel=array(1,2);
//$CFG->debuglevel=array();
//$CFG->debugfunction=array('T3filectime','T3filesize','T3MakeFilePath','ServeRequest');
//$CFG->debugfunction=array('T3filesize','T3MakeFilePath','T3ListDir','T3ReplaceMountPointsByPath');
//$CFG->debugfunction=array('T3FileSize','T3FileCTime','T3FileMTime','T3IsFile');
//$CFG->debugfunction=array('MKCOL','MOVE','COPY','T3IsFile','ServeRequest','T3FileExists','T3FileFieldCopy','T3IsFileUpload','T3MakeFilePath','T3ReplaceMountPointsByPath','T3GetFileMount');
//$CFG->debugfunction=array('T3ListDir','ServeRequest','http_status','PROPFIND','T3FileExists','fileinfo');
$CFG->debugfunction=array('fileinfo');
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
$CFG->TSFE=$TSFE;
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
$CFG->io= "file";				// io module (default: file) -- note: ips doesn't work
$CFG->server_name 		= "TYPO3 FTPd server [Metaphore Multimedia]";		// nanoFTPd server name
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
require_once "class.tx_cbywebdav_t3io.php";

$CFG->pasv_pool = new pool();
$CFG->log 		= new log($CFG);
if ($CFG->dbtype != "text") $CFG->dblink = db_connect($CFG->dbhost, $CFG->dbname, $CFG->dbuser, $CFG->dbpass);
$t3io=t3lib_div::makeInstance('tx_cbywebdav_t3io');
$t3io->T3Init($CFG);
$CFG->t3io=$t3io;
?>
