<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Christophe BALISKY (christophe@balisky.org)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*f
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
 * This is a API for rendering uploaded files in tt_content.
 * See documentation or extensions 'cby_webdav' for examples how to use this plugin
 *
 * @author	Christophe BALISKY <christophe@balisky.org>
 */
define('T3_FTPD_WWW_ROOT','WEBMOUNTS');
define('T3_FTPD_FILE_ROOT','FILEMOUNTS');
define('t3prefix','T3-');
define('t3pidsep','-');
define('t3ctypesep','-');
define('t3ctypetitlesep','-');
require_once(PATH_t3lib.'class.t3lib_div.php');
class tx_cbywebdav_renderFile extends tslib_pibase	{

	// External, static:
	var $cObj;
	
	
	function T3MakeVirtualPathFromPid($pid) {
	$rootline=t3lib_BEfunc::BEgetRootLine($pid);
	foreach($rootline as $num =>$pages){
		//foreach($pages as $key =>$val)
		 if ($pages['uid']!==0) $virtualpath="/".t3prefix.$pages['uid'].t3pidsep.$pages['title'].$virtualpath;
	}
	$virtualpath=T3_FTPD_WWW_ROOT.$virtualpath;;

	t3lib_div::devlog('T3MakeVirtualPathFromPid :'.$virtualpath,'cby_webdav');
	return $virtualpath;
}
	function _urlencode($path)
{
	
  $c = explode('/', $path);
   for ($i = 0; $i < count($c); $i++)
   {
     $c[$i] =  str_replace('+','%20',urlencode($c[$i]));
   }
   //$this->tx_cbywebdav_devlog(1,"### urlencode : $path |".implode('/', $c),"cby_webdav");
   return implode('/', $c);
}

function _urldecode($path)
{
   $c = explode('/', $path);
   for ($i = 0; $i < count($c); $i++)
   {
     $c[$i] =  str_replace('?','%3f', str_replace('+','%20',urldecode($c[$i])));
   }
   return implode('/', $c);
}

	function render_Uploads($content,$conf)	{
			$out = '';
			// Set layout type:
			//$type = intval($this->cObj->data['layout']);
			$type=2;
			// Get the list of files (using stdWrap function since that is easiest)
			//$lConf=array();
			//$lConf['override.']['filelist.']['field'] = 'select_key';
			$fileList = $this->cObj->stdWrap($this->cObj->data['tx_cbywebdav_file'],$lConf);
			$uid=$this->cObj->data['uid'];
			// Explode into an array:
			$fileArray = t3lib_div::trimExplode(',',$fileList,1);
			// If there were files to list...:
			if (count($fileArray))	{

				// Get the path from which the images came:
				//$selectKeyValues = explode('|',$this->cObj->data['select_key']);
				//$path = trim($selectKeyValues[0]) ? trim($selectKeyValues[0]) : 'uploads/tx_cbywebdav/';
				$pid=$GLOBALS['TSFE']->id;
				$path = 'uploads/tx_cbywebdav/'.$pid.'/'; //.$uid.'/';
				if (!@is_dir($path)) $path = 'uploads/tx_cbywebdav/';
				$virtualpath=$this->_urlencode(utf8_decode($this->T3MakeVirtualPathFromPid($pid)));				
				$PROTOCOL='http';
				$SERVER=$_SERVER['SERVER_NAME'];
				$PORT=$_SERVER['SERVER_PORT'];				
				$WEBDAVPATH=$PROTOCOL.'://'.$SERVER.':'.$PORT.'/webdav/'.$virtualpath;
				// Get the descriptions for the files (if any):
				$descriptions = t3lib_div::trimExplode(chr(10),$this->cObj->data['imagecaption']);

				// Adding hardcoded TS to linkProc configuration:
				$conf['linkProc.']['path.']['current'] = 1;
				$conf['linkProc.']['icon'] = 1;	// Always render icon - is inserted by PHP if needed.
				$conf['linkProc.']['icon.']['wrap'] = ' | //**//';	// Temporary, internal split-token!
				$conf['linkProc.']['icon_link'] = 1;	// ALways link the icon
				//$conf['linkProc.']['icon_image_ext_list'] = ($type==2 || $type==3) ? $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] : '';	// If the layout is type 2 or 3 we will render an image based icon if possible.
				$conf['linkProc.']['icon_image_ext_list'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];	// If the layout is type 2 or 3 we will render an image based icon if possible.
				// Traverse the files found:
				$filesData = array();
				foreach($fileArray as $key => $fileName)	{
					$absPath = t3lib_div::getFileAbsFileName($path.$fileName);
					//echo $absPath;
					if (@is_file($absPath))	{
						$fI = pathinfo($fileName);
						$filesData[$key] = array();

						$filesData[$key]['filename'] = $fileName;
						$filesData[$key]['path'] = $path;
						$filesData[$key]['filesize'] = filesize($absPath);
						$filesData[$key]['fileextension'] = strtolower($fI['extension']);
						$filesData[$key]['description'] = trim($descriptions[$key]);

						$this->cObj->setCurrentVal($path);
						$GLOBALS['TSFE']->register['ICON_REL_PATH'] = $path.$fileName;
						$filesData[$key]['linkedFilenameParts'] = explode('//**//',$this->cObj->filelink($fileName, $conf['linkProc.']));
					}
				}
				//print_r($filesData);
					// Now, lets render the list!
				$tRows = array();
				foreach($filesData as $key => $fileD)	{

						// Setting class of table row for odd/even rows:
					$oddEven = $key%2 ? 'tr-odd' : 'tr-even';

						// Render row, based on the "layout" setting
					$tRows[]='
					<tr class="'.$oddEven.'">
						<td class="csc-uploads-icon">
							'.$fileD['linkedFilenameParts'][0].'
						</td>
						<td class="csc-uploads-fileName">
							<p>'.$fileD['linkedFilenameParts'][1].'</p>'.
							($fileD['description'] ? '
							<p class="csc-uploads-description">'.htmlspecialchars($fileD['description']).'</p>' : '').'
						</td>'.($fileD['filesize'] ? '
						<td class="csc-uploads-fileSize">
							<p>'.t3lib_div::formatSize($fileD['filesize']).'</p></td>' : '').
						'<td>&nbsp;</td></tr>';  //'<a STYLE="behavior:url(\'#default#AnchorClick\')" ID="'.$fileName.'" href="'.$WEBDAVPATH.'" folder="'.$WEBDAVPATH.'#" id="">edit<a>
						//'<td><a STYLE="behavior:url(\'#default#AnchorClick\')" ID="'.$fileName.'" href="'.$WEBDAVPATH.'" folder="'.$WEBDAVPATH.'/'.$fileName.'#" id="">edit<a></td></tr>';
				}
					// Table tag params.
				
				$tableTagParams = $this->getTableAttributes($conf,$type);
				$tableTagParams['class'] = 'csc-uploads csc-uploads-'.$type;


					// Compile it all into table tags:
				$out = '
				<table '.t3lib_div::implodeAttributes($tableTagParams).' style="border:1px solid;"><tr width="100%"><td width="100%"><table width="100%">
					'.implode('',$tRows).'
				</table></tr></td></table>';
			}

				// Calling stdWrap:
				//print_r($conf);
			if ($conf['stdWrap.']) {
				$out = $this->cObj->stdWrap($out, $conf['stdWrap.']);
			}

				// Return value
			return $out;
		}
		
			/************************************
	 *
	 * Helper functions
	 *
	 ************************************/

	/**
	 * Returns table attributes for uploads / tables.
	 *
	 * @param	array		TypoScript configuration array
	 * @param	integer		The "layout" type
	 * @return	array		Array with attributes inside.
	 */
	function getTableAttributes($conf,$type)	{

			// Initializing:
			//print_r($conf);
		$tableTagParams_conf = $conf['tableParams_'.$type.'.'];

		$conf['color.'][200] = '';
		$conf['color.'][240] = 'black';
		$conf['color.'][241] = 'white';
		$conf['color.'][242] = '#333333';
		$conf['color.'][243] = 'gray';
		$conf['color.'][244] = 'silver';

			// Create table attributes array:
		$tableTagParams = array();
		$tableTagParams['width']='100%';
		$tableTagParams['bordercolor']='black';
		//print_r($this->cObj->data);
		$tableTagParams['border'] =  $this->cObj->data['table_border'] ? intval($this->cObj->data['table_border']) : $tableTagParams_conf['border'];
		$tableTagParams['cellspacing'] =  $this->cObj->data['table_cellspacing'] ? intval($this->cObj->data['table_cellspacing']) : $tableTagParams_conf['cellspacing'];
		$tableTagParams['cellpadding'] =  $this->cObj->data['table_cellpadding'] ? intval($this->cObj->data['table_cellpadding']) : $tableTagParams_conf['cellpadding'];
		$tableTagParams['bgcolor'] =  isset($conf['color.'][$this->cObj->data['table_bgColor']]) ? $conf['color.'][$this->cObj->data['table_bgColor']] : $conf['color.']['default'];

			// Return result:
		return $tableTagParams;
	}

	}
	
	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cby_webdav/class.tx_cbywebdav_renderFile.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cby_webdav/class.tx_cbywebdav_renderFile.php']);
}
	?>