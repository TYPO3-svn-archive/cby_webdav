<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2004 Christophe BALISKY (christophe@balisky.org)
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
 * Module 'FTPD' for the 'cby_webdav' extension.
 *
 * @author	Christophe BALISKY <christophe@balisky.org>
 */



	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);	
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
$LANG->includeLLFile("EXT:cby_webdav/mod1/locallang.php");
#include ("locallang.php");
require_once (PATH_t3lib."class.t3lib_scbase.php");
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

class tx_cbywebdav_module1 extends t3lib_SCbase {
	var $pageinfo;

	/**
	 * 
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		
		parent::init();

		/*
		if (t3lib_div::_GP("clear_all_cache"))	{
			$this->include_once[]=PATH_t3lib."class.t3lib_tcemain.php";
		}
		*/
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			"function" => Array (
				"1" => $LANG->getLL("function1"),
				"2" => $LANG->getLL("function2"),
				"3" => $LANG->getLL("function3"),
				"4" => $LANG->getLL("function4"),
				"5" => $LANG->getLL("function5"),
			)
		);
		parent::menuConfig();
	}

		// If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	/**
	 * Main function of the module. Write the content to $this->content
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		
		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		
		if (($this->id && $access) || ($BE_USER->user["admin"] && !$this->id))	{
	
				// Draw the header.
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
				</script>
			';

			$headerSection = $this->doc->getHeader("pages",$this->pageinfo,$this->pageinfo["_thePath"])."<br>".$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.path").": ".t3lib_div::fixed_lgd_pre($this->pageinfo["_thePath"],50);

			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section("",$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"])));
			$this->content.=$this->doc->divider(5);

			
			// Render content:
			$this->moduleContent();

			
			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section("",$this->doc->makeShortcutIcon("id",implode(",",array_keys($this->MOD_MENU)),$this->MCONF["name"]));
			}
		
			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero
		
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;
		
			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Prints out the module HTML
	 */
	function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
	
	/**
	 * Generates the module content
	 */
	function moduleContent()	{
		global $TYPO3_CONF_VARS;
		$lport=$TYPO3_CONF_VARS['EXTCONF']['cby_webdav']['listen_port'];
		switch((string)$this->MOD_SETTINGS["function"])	{
			case 1:
				$content="<div align=center><strong>FTPD Configuration</strong></div><BR>
					Welcome to the administration of the FTPD server. Configuration directives (ports, ip adresses and so forth) are accessible from the extension manager";
				$this->content.=$this->doc->section("Configuration:",$content,0,1);
			break;
			case 2:
				$content="<div align=center><strong>FTPD Control...</strong></div>";
				$this->content.=$this->doc->section("Start of FTPD:",$content,0,1);
				$tab=array();
			  exec("ps -ew | grep nanoftpd | grep -v grep",&$tab);
			  foreach($tab as $line) {
			  	$tarr= t3lib_div::trimexplode(' ',trim($line));
			  	$pid=$tarr[0];
					$this->content.="T3 FTPD is running under pid : $pid<br>";
				};
				if (count($tab)===0) $this->content.="T3 FTPD is not running.";
					
					
					proc_close(proc_open (t3lib_extMgm::extPath('cby_webdav').'nanoftpd/nanoftpd.php &', array(),$foo));

					$tab=array();
			  	exec("ps -ew | grep nanoftpd | grep -v grep",&$tab);
					if ($tab[0]) {
			  		$tarr= t3lib_div::trimexplode(' ',trim($tab[0]));
			  		$pid=$tarr[0];
						$this->content.="T3 FTPD has started under pid : $pid !.";
					} else {
						$this->content.="T3 FTPD has not started check log file and execution rights of daemon.";
				}
				break;
			case 3:
				$content="<div align=center><strong>FTPD Control...</strong></div>";
				$this->content.=$this->doc->section("Stop of FTPD:",$content,0,1);
				exec("ps -ew | grep nanoftpd | grep -v grep",&$tab);
				if ($tab[0]) {
			  	$tarr= t3lib_div::trimexplode(' ',trim($tab[0]));
			  	$pid=$tarr[0];
					if ($pid) {
							exec("kill -9 $pid");
							$tab=array();
			  			exec("ps -ew | grep nanoftpd | grep -v grep",&$tab);
							if ($tab[0]) {
			  				$tarr= t3lib_div::trimexplode(' ',trim($tab[0]));
			  				$pid=$tarr[0];
								$this->content.="Problem T3 FTPD is still running  under pid : $pid, stop it manually !!!.";
							} else {
								$this->content.="T3 FTPD has stopped.";
							}							
					}
				} else {
					$this->content.="T3 FTPD is already stopped !.";
				}


			break;
			case 4:
				$content="<div align=center><strong>FTPD Control...</strong></div>";
				$this->content.=$this->doc->section("Status of FTPD:",$content,0,1);
				$tab=array();
			  exec("ps -ew | grep nanoftpd | grep -v grep",&$tab);
			  foreach($tab as $line) {
			  	$tarr= t3lib_div::trimexplode(' ',trim($line));
			  	$pid=$tarr[0];
					$this->content.="T3 FTPD is running under pid : $pid<br>";
				};
				if (count($tab)===0) $this->content.="T3 FTPD is not running.";
			break;

			case 5:
				$content="<div align=center><strong>FTPD Control...</strong></div><pre>";
				$this->content.=$this->doc->section("Log of FTPD:",$content,0,1);
				$this->content.= "<pre>";
				$tab=array();
			  exec("tail --lines 40 ".t3lib_extMgm::extPath('cby_webdav').'nanoftpd/log/nanoftpd.log',&$tab);
			  $this->content.= implode('<br>',$tab);
			  $this->content.= "</pre>";
			 // $this->content.="</pre>";
			  

			break;

		} 
	}
}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/cby_webdav/mod1/index.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/cby_webdav/mod1/index.php"]);
}




// Make instance:
$SOBE = t3lib_div::makeInstance("tx_cbywebdav_module1");
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>