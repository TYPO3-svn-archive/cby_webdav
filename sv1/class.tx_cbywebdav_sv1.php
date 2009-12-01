<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2004 Christophe BALISKY (<christophe@balisky.org>)
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
 * Service 'FTPD' for the 'cby_webdav' extension.
 *
 * @author	Christophe BALISKY <christophe@balisky.org>
 */


require_once(PATH_t3lib.'class.t3lib_svbase.php');

class tx_cbywebdav_sv1 extends t3lib_svbase {
	var $prefixId = 'tx_cbywebdav_sv1';		// Same as class name
	var $scriptRelPath = 'sv1/class.tx_cbywebdav_sv1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'cby_webdav';	// The extension key.
	
	/**
	 * [Put your description here]
	 */
	function init()	{
		$available = parent::init();
	
		// Here you can initialize your class.
	
		// The class have to do a strict check if the service is available.
		// The needed external programs are already checked in the parent class.
	
		// If there's no reason for initialization you can remove this function.
	
		return $available;
	}
	
	/**
	 * [Put your description here]
	 * performs the service processing
	 *
	 * @param	string 	Content which should be processed.
	 * @param	string 	Content type
	 * @param	array 	Configuration array
	 * @return	boolean
	 */
	function process($content='', $type='', $conf=array())	{
	
		// Depending on the service type there's not a process() function.
		// You have to implement the API of that service type.
	
		return FALSE;
	}


	function render_ftpUploads($content,$conf)	{
		echo '####################################################àààààààààààààààààààààààààààà";
			$out = '';

			// Set layout type:
			//$type = intval($this->cObj->data['layout']);

			// Get the list of files (using stdWrap function since that is easiest)
			//$lConf=array();
			//$lConf['override.']['filelist.']['field'] = 'select_key';
			$fileList = $this->cObj->stdWrap($this->cObj->data['tx_cbywebdav_ftpfile'],$lConf);

				// Explode into an array:
			$fileArray = t3lib_div::trimExplode(',',$fileList,1);

			// If there were files to list...:
			if (count($fileArray))	{

				// Get the path from which the images came:
				$selectKeyValues = explode('|',$this->cObj->data['select_key']);
				$path = trim($selectKeyValues[0]) ? trim($selectKeyValues[0]) : 'uploads/tx_cbywebdav/';

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

					// Now, lets render the list!
				$tRows = array();
				foreach($filesData as $key => $fileD)	{

						// Setting class of table row for odd/even rows:
					$oddEven = $key%2 ? 'tr-odd' : 'tr-even';

						// Render row, based on the "layout" setting
					$tRows[]='
					<tr class="'.$oddEven.'">'.($type>0 ? '
						<td class="csc-uploads-icon">
							'.$fileD['linkedFilenameParts'][0].'
						</td>' : '').'
						<td class="csc-uploads-fileName">
							<p>'.$fileD['linkedFilenameParts'][1].'</p>'.
							($fileD['description'] ? '
							<p class="csc-uploads-description">'.htmlspecialchars($fileD['description']).'</p>' : '').'
						</td>'.($this->cObj->data['filelink_size'] ? '
						<td class="csc-uploads-fileSize">
							<p>'.t3lib_div::formatSize($fileD['filesize']).'</p>
						</td>' : '').'
					</tr>';
				}

					// Table tag params.
				$tableTagParams = $this->getTableAttributes($conf,$type);
				$tableTagParams['class'] = 'csc-uploads csc-uploads-'.$type;


					// Compile it all into table tags:
				$out = '
				<table '.t3lib_div::implodeAttributes($tableTagParams).'>
					'.implode('',$tRows).'
				</table>';
			}

				// Calling stdWrap:
			if ($conf['stdWrap.']) {
				$out = $this->cObj->stdWrap($out, $conf['stdWrap.']);
			}

				// Return value
			return $out;
		}
	}

}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/cby_webdav/sv1/class.tx_cbywebdav_sv1.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/cby_webdav/sv1/class.tx_cbywebdav_sv1.php"]);
}

?>