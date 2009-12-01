<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

if (TYPO3_MODE=="BE")	{
		
	t3lib_extMgm::addModule("tools","txcbywebdavM1","",t3lib_extMgm::extPath($_EXTKEY)."mod1/");
}

$tempColumns = Array (
	"tx_cbywebdav_file" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:cby_webdav/locallang_db.php:tt_content.tx_cbywebdav_file",		
		"config" => Array (
			"type" => "group",
			"internal_type" => "file",
			"allowed" => "gif,jpg,jpeg,tif,bmp,pcx,tga,png,xls,doc,ppt,docx,pdf,txt,mp3,ogg,html,htm,php,cpp,avi,flv,xlsx,pptx,pps,zip,pot,dot,msg",
			"max_size" => 25000,	
			"uploadfolder" => "uploads/tx_cbywebdav",
			"size" => 10,	
			"minitems" => 0,
			"maxitems" => 100,
		)
	),
);


t3lib_div::loadTCA("tt_content");
t3lib_extMgm::addTCAcolumns("tt_content",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("tt_content","tx_cbywebdav_file;;;;1-1-1");


t3lib_extMgm::addService($_EXTKEY,  'FTPD' /* sv type */,  'tx_cbywebdav_sv1' /* sv key */,
		array(

			'title' => 'FTPD',
			'description' => 'FTP SERVER FOR TYPO3',

			'subtype' => '',

			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_cbywebdav_sv1.php',
			'className' => 'tx_cbywebdav_sv1',
		)
	);
	
t3lib_extMgm::addStaticFile($_EXTKEY,"static/","Cby WEBDAV Upload Files");						

?>