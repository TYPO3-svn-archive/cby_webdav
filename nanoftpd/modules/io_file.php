<?php

/*
****************************************************
* nanoFTPd for TYPO3 - an FTP daemon written in PHP          *
****************************************************
* this file is licensed under the terms of GPL, v2 *
****************************************************
* developers:                                      *
*  - Arjen <arjenjb@wanadoo.nl>                    *
*  - Phanatic <linux@psoftwares.hu>  
*  - Christoph BALISKY <christophe@balisky.org>     *
****************************************************
* http://sourceforge.net/projects/nanoftpd/        *
****************************************************
*/

class io_file {

	var $parameter;
	var $root;
	var $cwd;
  var $t3;
  var $level;
	var $fp;
	var $CFG;
	var $BEUSER;
	var $T3FILE;

	function io_file($CFG) {
		$this->root = $CFG->T3ROOTDIR;
		$this->cwd = "/";
		$this->t3=0;
		$this->level=0;
		$this->CFG=$CFG;
	}
	
	function getPid($prm) {
		$p=strpos($prm,'[');
		//echo "PID[ $prm ]PID";
	  if ($p) {
	  	return intval(substr($prm,0,$p));
		} else { 
			return $prm;
		}	
  }

		/* test case */
	function TestIsT3() {
		$t[]=''; //Ok
		$t[]='/'; //OK
		$t[]=T3_FTPD_WWW_ROOT; //OK
		$t[]=T3_FTPD_FILE_ROOT; //OK
		$t[]=T3_FTPD_WWW_ROOT.'/'; //OK
		$t[]=T3_FTPD_FILE_ROOT.'/'; //OK
		$t[]='/'.T3_FTPD_WWW_ROOT.'/'; //OK
		$t[]='/'.T3_FTPD_FILE_ROOT.'/'; //OK
		$t[]='/'.T3_FTPD_WWW_ROOT.'/'; //OK
		$t[]='/'.T3_FTPD_FILE_ROOT.'/'; //OK
		$t[]=T3_FTPD_FILE_ROOT.'/Diaporama'; //OK
		$t[]='/'.T3_FTPD_FILE_ROOT.'/Diaporama'; //OK
		$t[]='/'.T3_FTPD_FILE_ROOT.'/Diaporama/'; //OK
		$t[]='KOKOKO'; //KO
		$t[]='KOKOKO/'; //KO
		$t[]='/KOKOKO/'; //KO
		$t[]='/home/'; //KO
		$t[]='/'.T3_FTPD_FILE_ROOT.'/test'; //KO
		$t[]='/'.T3_FTPD_FILE_ROOT.'/test/'; //KO		
		foreach($t as $s) {
			echo $s.":";
			print_r($this->isT3($s));
	  }
	}

	function isT3($prm) {
		$prm=trim($prm);
		$ret=array();
		$ret['prm']=$prm;
		$ret['cwd']=$this->cwd;
		$ret['newcwd']=str_replace('//','/',$prm);		
		$darr=t3lib_div::trimexplode('/',$ret['newcwd']);
		$ret['rootline']=$darr;
		$ret['level']=count($darr)-1;		
		if (!$darr[$ret['level']]) $ret['level']--;
		$ret['pid']=0;	
		$ret['isWebmount']=0;
		$ret['isFilemount']=0;
		$ret['isAuthorized']=0;
		if ($darr[1]==T3_FTPD_WWW_ROOT) {
			$ret['isWebmount']=1;			
			$ret['pid']=$ret['level']?$this->getPid($ret['rootline'][$ret['level']]):0;
			if ($ret['level']==1)	{
				$ret['isAuthorized']=1;
			} else {
				$ret['isAuthorized']=1;
		  }
		} else if ($darr[1]==T3_FTPD_FILE_ROOT) {
			$ret['isFilemount']=1;			
			if ($ret['level']==1) {
					$ret['isAuthorized']=1;
			} else {
		    $ret['testcwd']=str_replace('//','/',$this->replaceMountPointsByPath($ret['newcwd']));
		    $filename=str_replace('//','/',$this->root.$this->replaceMountPointsByPath($ret['newcwd']));
				$ret['isAuthorized']=(file_exists($filename) && filetype($filename) == "dir" && $this->T3FILE->checkPathAgainstMounts($filename));
			}
		} else if ($ret['level']==0) {
			$ret['isAuthorized']=1;
		}
		return $ret;
	}
	
	function isT3File($prm) {
		$prm=trim($prm);
		$prm='/'.str_replace($this->root,'',$prm);
		$prm=str_replace('//','/',$prm);
		$ret=array();
		$ret['prm']=$prm;
		$ret['cwd']=$this->cwd;
		$ret['newcwd']=$prm;		
		$darr=t3lib_div::trimexplode('/',$prm);
		$ret['rootline']=$darr;
		$ret['level']=count($darr)-1;
		if (!$darr[$ret['level']]) $ret['level']--;
		//if (!$darr[0]) $ret['level']--;
		$ret['pid']=0;	
		$ret['uid']=0;	
		$ret['isWebmount']=0;
		$ret['isFilemount']=0;
		$ret['isAuthorized']=0;
		$ret['isWebcontent']=0;			
		if ($darr[1]==T3_FTPD_WWW_ROOT) {
			$ret['isWebmount']=1;			
			$ret['pid']=$ret['level']?$this->getPid($ret['rootline'][$ret['level']-1]):0;
			$ret['uid']=$ret['level']?$this->getPid($ret['rootline'][$ret['level']]):0;
			// T3 TT_CONTENT
			//$enable=$this->CFG->T3PAGE->enableFields('tt_content'); // does not work ,have to loafd TCA first ..
			$enable=' and deleted=0 and hidden=0 ';
			$res=$this->CFG->T3DB->exec_SELECTquery('uid,ctype,header,bodytext','tt_content','pid='.intval($ret['pid']).' and uid='.$ret['uid'].' '.$enable );
			//echo $this->CFG->T3DB->SELECTquery('uid,CType,header,bodytext','tt_content','pid='.intval($ret['pid']).' and uid='.$ret['uid'].' '.$enable.$orderby );
			while ($row=$this->CFG->T3DB->sql_fetch_assoc($res)) {
				switch ($row['ctype']) {
				case 'html' :
				case 'text' :
					$ret['isT3File']=1;
					$ret['data']=$row['bodytext'];
					$ret['size']=strlen($ret['data']);
					$ret['name']=$row['header'];
					$ret['type']=$row['ctype'];	
					break;
				default:
					$ret['isT3File']=1;
					$ret['name']=$row['header'];
					$ret['type']=$row['ctype'];	
					break;
				}		
			}
			$ret['isAuthorized']=1; // add T3 rights check here !!

		}	else if ($darr[1]==T3_FTPD_FILE_ROOT) {
			$ret['isFilemount']=1;			
			if ($ret['level']==1) {
					$ret['isAuthorized']=0;
			} else {
		    $ret['testcwd']=str_replace('//','/',$this->replaceMountPointsByPath($ret['newcwd']));
				$ret['isAuthorized']=(file_exists($this->root.$ret['testcwd']) && filetype($this->root . $ret['testcwd']) == "dir" && $this->T3FILE->checkPathAgainstMounts($this->root . $ret['testcwd']));
			}
		} else if ($ret['level']==0) {
			$ret['isAuthorized']=0;
		}
		//print_r($ret);
		return $ret;
	}
	
	function isT3FileUpload($prm) {
		$prm=trim($prm);
		$prm=str_replace($this->root,'',$prm);
		$prm=str_replace('//','/',$prm);
		$ret=array();
		$ret['prm']=$prm;
		$ret['cwd']=$this->cwd;
		$ret['newcwd']=$prm;		
		$darr=t3lib_div::trimexplode('/',$prm);
		$ret['rootline']=$darr;
		$ret['level']=count($darr)-1;
		if (!$darr[$ret['level']]) $ret['level']--;
		$ret['pid']=0;	
		$ret['uid']=0;	
		$ret['isT3']=0;
		$ret['isT3File']=0;
		//echo "IST3FILEUPLOAD";
		//print_r($darr);			
		if ($darr[1]==T3_FTPD_WWW_ROOT) {
			$ret['isWebmount']=1;			
			$ret['pid']=$ret['level']?$this->getPid($ret['rootline'][$ret['level']-1]):0;
			$ret['file']=$ret['level']?$ret['rootline'][$ret['level']]:'';
			$ret['filepath']=$this->CFG->T3UPLOADDIR.$ret['file'];
			$filename=t3lib_div::trimexplode('.',$ret['file']);
			$ret['ext']=strtolower($filename[count($filename)-1]);
			$ret['filename']=$filename[0];
			$ret['uid']=$ret['level']?intval($this->getPid($ret['rootline'][$ret['level']+1])):0;
		} else if ($darr[1]==T3_FTPD_FILE_ROOT) {
			$ret['isFilemount']=1;			
			$ret['isT3File']=1;
			if ($ret['level']<=1) {
					$ret['isAuthorized']=0;
			} else {
		    $ret['testcwd']=str_replace('//','/','/'.$this->replaceMountPointsByPath($ret['newcwd']));
		    $ret['filePath']=$this->makeT3FilePath($ret['testcwd']);
				$ret['isAuthorized']=(file_exists($ret['filePath']) && filetype($ret['filePath']) == "dir" && $this->T3FILE->checkPathAgainstMounts($ret['filePath']));
			}
		}
		//print_r($ret);
		return $ret;
	}
	
	function getT3CTypeFile($res) {
	  $ret['pid']=$res['pid'];
	  $conf=t3lib_BEfunc::getPagesTSconfig($ret['pid']);
	  //print_r($conf);
	  if (is_array($conf['plugin.']['tx_cbywebdav.'][$res['ext'].'.']['put.'])) {
	  	$extConf=$conf['plugin.']['tx_cbywebdav.'][$res['ext'].'.']['put.'];
	  	if ($extConf['headerField']) $ret[$extConf['headerField']]=$res['filename'];
	  	//if ($extConf['fileField']) $ret[$extConf['fileField']]=$res['ufile'];
	  	echo $res['ufile'];
	  	if ($extConf['fileField']) $ret[$extConf['fileField']]=$this->getFileName($res['ufile']);
	  	echo $ret[$extConf['fileField']];
	  	//print_r($conf['plugin.']['tx_cbywebdav.'][$res['ext'].'.']['put.']);
			if ($extConf['ctypeField'] && $extConf['ctype']) $ret[$extConf['ctypeField']]=$extConf['ctype'];
			if ($extConf['dataField'] && $extConf['systemTransform']) {
				$cmd=sprintf($extConf['systemTransform'],$this->makeT3FilePath($res['ufile']));
				t3lib_div::devlog($cmd,'cby_webdav');
				t3lib_div::devlog('ufile : '.$this->makeT3FilePath($res['ufile']),'cby_webdav');
				t3lib_div::devlog('file:'.$this->getFileName($res['ufile']),'cby_webdav');
				//echo $cmd;	
			
				//print_r($res);
				$tab=array();
			  $r=exec($cmd,&$tab);
			  $data=implode($extConf['sep']?$extConf['sep']:'',$tab);
			  if (!$r && !$data) {
			  	$data="Erreur commande : $cmd , code retour : $r !";
			  } 
				$ret[$extConf['dataField']]=$data;
				//print_r($ret);
			}
		} else {
			switch($res['ext']) {
			case 'jpg':
			case 'png':
			case 'jpeg':
			case 'tif':
			case 'bmp':
			case 'gif':
				$ret['ctype']='image';
				$ret['image']=$res['file'];
				$ret['header']=$res['filename'];
				break;
			case 'html':
			case 'htm':
			case 'txt':
			case 'sql':
			case 'php':
			case 'c':
			case 'js':
			case 'css':
			case 'csv':
			case 'text':
			case 'xml':
				$ret['ctype']='text';
				$ret['bodytext']=$res['data'];
				$ret['header']=$res['filename'];
				break;
			default :
				$ret['ctype']='uploads';
				$ret['header']=$res['filename'];
				$ret['media']=$res['file'];
				break;
			}
		}
		return $ret;
	}
	
	function getFileName($path) {
		$fa=t3lib_div::trimexplode('/',$path);
		$c=count($fa);
		return $fa[$c-1];
	}
		
	function func_copy($cmds) {
		$theFile = $cmds['data'];
		$theDest = $this->T3FILE->is_directory($cmds['target']);	// Clean up destination directory
		$altName = $cmds['altName'];
		if (!$theDest)	{
		//echo "nodest";
			
			$this->T3FILE->writelog(2,2,100,'Destination "%s" was not a directory',Array($cmds['target']));
			return FALSE;
		}
		if (!$this->T3FILE->isPathValid($theFile) || !$this->T3FILE->isPathValid($theDest))	{
		//echo "notvalid";
			$this->T3FILE->writelog(2,2,101,'Target or destination had invalid path (".." and "//" is not allowed in path). T="%s", D="%s"',Array($theFile,$theDest));
			return FALSE;
		}
		// Processing of file or directory.
		if (@is_file($theFile))	{	// If we are copying a file...
			//echo "filecopy";
			if ($this->T3FILE->actionPerms['copyFile'])	{
			//echo "permsok";
				if (filesize($theFile) < ($this->T3FILE->maxCopyFileSize*1024))	{
					//echo "sizeok";
					$fI = t3lib_div::split_fileref($theFile);
					if ($altName)	{	// If altName is set, we're allowed to create a new filename if the file already existed
						$theDestFile = $this->T3FILE->getUniqueName($fI['file'], $theDest);
						$fI = t3lib_div::split_fileref($theDestFile);
					} else {
						$theDestFile = $theDest.'/'.$fI['file'];
					}
					if ($theDestFile && !@file_exists($theDestFile))	{
						if ($this->T3FILE->checkIfAllowed($fI['fileext'], $theDest, $fI['file'])) {
							if ($this->T3FILE->PHPFileFunctions)	{
								copy ($theFile,$theDestFile);
							} else {
								$cmd = 'cp "'.$theFile.'" "'.$theDestFile.'"';
								exec($cmd);
							}
							clearstatcache();
							if (@is_file($theDestFile))	{
								$this->T3FILE->writelog(2,0,1,'File "%s" copied to "%s"',Array($theFile,$theDestFile));
								return $theDestFile;
							} else $this->T3FILE->writelog(2,2,109,'File "%s" WAS NOT copied to "%s"! Write-permission problem?',Array($theFile,$theDestFile));
							
						}
					}
				}
			}
		}
	}
	
	function T3FileCopy($ret,$fa) {
			$cmds=array();
			$cmds['data']=str_replace('//','/',$this->CFG->T3ROOTDIR.$ret['filepath']);
			$cmds['altName']=1;
			//print_r($fa);
		
		switch ($fa['ctype']) {
			case 'image':
				$cmds['target']=$this->CFG->T3ROOTDIR."uploads/pics/";
				//print_r($this->CFG->T3FILE);
				$this->T3FILE->start($cmds);
				$name=$this->func_copy($cmds);
				if ($name) $fa['image']=$this->getFileName($name);
				//$this->CFG->T3FILE->processData();
				break;
			case 'uploads':
				$cmds['target']=$this->CFG->T3ROOTDIR."uploads/media/";
				//print_r($cmds);
				$this->T3FILE->start($cmds);
				$name=$this->func_copy($cmds);
				if ($name) $fa['media']=$this->getFileName($name);
				//$this->CFG->T3FILE->processData();
				break;
			default:
				break;
	  }		
	}
	
	// Creation of uploaded content
	function linkT3FileUpload($ret) {
		$fa=$this->getT3CTypeFile($ret);
		$this->T3FileCopy($ret,&$fa);
		//$fa[1000]='linkT3FileUpload';
		//print_r($ret);
		//print_r($fa);
		//if ($fa['ctype']=='text') $fa['bodytext']=$ret['data'];
		$this->CFG->T3DB->exec_INSERTquery('tt_content',$fa);
		return;
	}
	
	function getFileMount($name) {
		$ret=array();
		//echo "getFileMount[ $name ]getFM";
		$filemounts=$this->BEUSER->groupData['filemounts'];
		foreach($filemounts as $fm) {
			if ($fm['name']==$name) {
				$path=substr($fm['path'],strlen($this->root));
				$fm['relPath']=$path;
				return $fm;
			}
		}
		return $ret;
	}
	
	// PATH is composed of siteroot/T3_FTPD_FILE_ROOT/mountpoint/ ...

	function replaceMountPointsByPath($path) {
		//echo "PATH MP: $path";
		// we take out site root
		$l=strlen($path);
		$path='/'.str_replace($this->root,'',$path);
		$path=str_replace('//','/',$path);
		$l2=strlen($path);
		$rootflag=0;
		if ($l2!=$l) $rootflag=1;
		//echo "PATH MP2: $path";
		$parr=t3lib_div::trimexplode('/',$path);
		$c=count($parr);
		//print_r($parr);
		if (substr($path, 0, 1) == "/" && $c >=3 ) {
			//echo "A1A1A1";
			if ($parr[1]==T3_FTPD_FILE_ROOT) {
			//echo "A2A2A2";
				$fm=$this->getFileMount($parr[2]);
				$rp=$fm['relPath'];
				//echo "RP PATH : $rp";
				$ret=str_replace('/'.T3_FTPD_FILE_ROOT.'/'.$parr[2],$rp,$path);
				//echo "RET : $ret";
			} else {
				$ret=$path;
			}
		} else {
			//echo "A3A3A3";
			if ($parr[1]==T3_FTPD_FILE_ROOT && $c >=2) {
			//echo "A4A4A4";
				$fm=$this->getFileMount($parr[1]);
				$rp=$fm['relPath'];
				//echo "RP PATH 2 : $rp";
				$ret=str_replace(T3_FTPD_FILE_ROOT.'/'.$parr[1],$rp,$path);
			} else {
				$ret=$path;
			}
		}
		//echo "RET1 : $ret";
		if ($rootflag) $ret=$this->root.$ret;
				//echo "RET2 : $ret";
		$ret=str_replace('//','/',$ret);
		//echo "PATH MP3: $ret";
		
		return $ret;
	}
	
	function replacePathByMountPoints($path) {
		//echo "PATH : $path";
				$parr=t3lib_div::trimexplode('/',$path);
				$c=count($parr);
				//print_r($parr);
				if (substr($path, 0, 1) == "/" && $c >=3 ) {
					if ($parr[1]==T3_FTPD_FILE_ROOT) {
						$fm=$this->getFileMount($parr[2]);
						$rp=$fm['relPath'];
						//echo "RP PATH : $rp";
						$ret=str_replace($path,'/'.T3_FTPD_FILE_ROOT.'/'.$parr[2],$rp);
					}
				} else {
					if ($parr[0]==T3_FTPD_FILE_ROOT && $c >=2) {
						$fm=$this->getFileMount($parr[1]);
						$rp=$fm['relPath'];
						//echo "RP PATH : $rp";
						$ret=str_replace($path,'/'.T3_FTPD_FILE_ROOT.'/'.$parr[1],$rp);
					}
				}
				return $ret;		
	}
	
	function makeT3FilePath($filename) {
		$filename=trim($filename);
		if (substr($filename, 0, 1) == "/") {
			$filename= $this->root . $filename;
	  } else {
			$filename= $this->root . $this->cwd . $filename;
		}
		$filename=str_replace('//','/','/'.$filename);
		//echo "FileName[$filename]filename";
		$filename=$this->replaceMountPointsByPath($filename);	
		//echo "FileName2[$filename]filename2";
		return $filename;
	}


	function cwd() {
//		echo "PRM=$this->parameter";
		$dir = trim($this->parameter);
		$cwd_path = preg_split("/\//", $this->cwd, -1, PREG_SPLIT_NO_EMPTY);
		$new_cwd = "";		
		switch (TRUE) {
			case (!strlen($dir)):
				return $this->cwd;

			case ($dir == ".."):
				if (count($cwd_path)) {
					array_pop($cwd_path);
					$terminate = (count($cwd_path) > 0) ? "/" : "";
					$new_cwd = "/" . implode("/", $cwd_path) . $terminate;
				} else {
					return false;
				}
				break;
			// full path given ?
			case (substr($dir, 0, 1) == "/"):
				if (strlen($dir) == 1) {
					$new_cwd = "/";
				} else {
				    $new_cwd = rtrim($dir, "/") . "/";
				}
				break;
			// most cases
			default:				
					$new_cwd = $this->cwd . rtrim($dir, "/") . "/";
				break;
		}
		
		if (strpos($new_cwd, "..") !== false) return false;

		//echo 'NEWCWD['.$new_cwd."]NEWCWD|";

		$p=$this->isT3(str_replace('//','/',$new_cwd));
		//print_r($p);		
		if ($p['isAuthorized']) {
			$this->cwd = $new_cwd;
			return $this->cwd;
		} else {
			return false;
		}
	}

	function pwd() {
		return $this->cwd;
	}

	function ls() {
		$list = array();
		$dir = trim($this->cwd);
		$p=$this->isT3($dir);
		//echo "CW $dir";
		// T3 Pages			
		if ($p['isWebmount']) {
			if ($p['level']==1) {
				$webmounts=$this->BEUSER->groupData['webmounts'];
				$pids=t3lib_div::trimexplode(',',$webmounts);
				foreach($pids as $pid) {
					$parr[]=$this->CFG->T3PAGE->getPage($pid);
				}
			} else {
				$pid=$p['rootline'][$p['level']];
				$parr=$this->CFG->T3PAGE->getMenu($pid);
			}
			// T3 Pages
			foreach($parr as $pid=>$row) {
				
				$info = array(
					"name" => $row['uid'].'['.str_replace('/','',$row['title']).']'
					,"size" => 0
					,"owner" => 'arcsisor' //    getT3Own
					,"group" => 'nobody' // .. getT3Group
					,"time" => date("M d H:i", $row['tstamp']) // CBY get real time $row['tstamp'];
					,"perms" => 'drwxrwxrwx' //  getT3Perms...
				);
				$list[] = $info;
			}
			// T3 TT_CONTENT
			//$enable=$this->CFG->T3PAGE->enableFields('tt_content'); // does not work ,have to loafd TCA first ..
			$enable=' and deleted=0 and hidden=0 ';
			$orderby=' ORDER BY sorting ';
			$res=$this->CFG->T3DB->exec_SELECTquery('uid,ctype,header,bodytext','tt_content','pid='.intval($p['pid']).' '.$enable.$orderby );
			//echo $this->CFG->T3DB->SELECTquery('uid,CType,header,bodytext','tt_content','pid='.intval($p['pid']).' '.$enable.$orderby );
			while ($row=$this->CFG->T3DB->sql_fetch_assoc($res)) {
				//print_r($row);
				$info = array(
					"name" => $row['uid'].'['.str_replace('/','',$row['header']).'].'.str_replace('/','',$row['ctype'])
					,"size" => strlen($row['bodytext'])
					,"owner" => 'arcsisor' // getT3Own
					,"group" => 'nobody' // getT3Group ..
					,"time" => date("M d H:i", $row['tstamp'])  // CBY get real time $row['tstamp']
					,"perms" => '-rwxrwxrwx' // get getT3Perms...
				);
				$list[] = $info;				
			}
			
			
		} else {
			if ($this->cwd=='/') { // We are at root of FTPD server
				$info = array(
					"name" => T3_FTPD_FILE_ROOT
					,"size" => 0
					,"owner" => 'arcsisor' // get apache user
					,"group" => 'nobody' // get apache group ..
					,"time" => 'Oct 31 22:02'// CBY get real time
					,"perms" => 'drwxrwxrwx' // get T3 rights
				);
				$list[] = $info;
				$info = array(
					"name" => T3_FTPD_WWW_ROOT
					,"size" => 0
					,"owner" => 'arcsisor' // get apache user
					,"group" => 'nobody' // get apache group ..
					,"time" => 'Oct 31 22:02'// CBY get real time
					,"perms" => 'drwxrwxrwx' // get T3 rights
				);
				$list[] = $info;
			} else if ($this->cwd=='/'.T3_FTPD_FILE_ROOT.'/') {
								$filemounts=$this->BEUSER->groupData['filemounts'];
								foreach($filemounts as $fm) {
									$filename=$this->root.substr($fm['path'],strlen($this->root));
									$owner = posix_getpwuid(fileowner($filename));
									$fileowner = $owner['name'];
									$group = posix_getgrgid(filegroup($filename));
									$filegroup = $group['name'];
									$perms=$this->perms(fileperms($filename));
									$mtime = filemtime($filename);
									$filemod = date("M d H:i", $mtime);
									//echo " $filemod l  $fileowner l  $filename l  $filegroup l $perms";
									$info = array(
										"name" => $fm['name']
										,"size" => 0
										,"owner" => $fileowner // get apache user
										,"group" => $filegroup // get apache group ..
										,"time" => $filemod // CBY get real time
										,"perms" => $perms // get T3 rights
									);
									$list[] = $info;

							}

			} else {
				$test_cwd=$this->replaceMountPointsByPath($this->cwd);
				if ($handle = opendir($this->root . $test_cwd)){
					while (false !== ($file = readdir($handle))) {
						if ($file == "." || $file == "..") continue;
						$filename = $this->root . $test_cwd . $file;
						$filetype = filetype($filename);
						// Fileadmin patch ...
						//if ($this->cwd=='/' && $file !='fileadmin' && $file !='uploads') continue;
						if ($filetype != "dir" && $filetype != "file") continue;
						$filesize = ($filetype == "file") ? filesize($filename) : 0;
						/* owner, group, last modification and access info added by Phanatic */				
						$owner = posix_getpwuid(fileowner($filename));
						$fileowner = $owner['name'];
						$group = posix_getgrgid(filegroup($filename));
						$filegroup = $group['name'];
						$mtime = filemtime($filename);
						$filemod = date("M d H:i", $mtime);
						$fileperms = $this->perms(fileperms($filename));
						clearstatcache();
						$info = array(
							"name" => $file
							,"size" => $filesize
							,"owner" => $fileowner
							,"group" => $filegroup
							,"time" => $filemod
							,"perms" => $fileperms
						);
						$list[] = $info;
					}

					closedir($handle);
				} else {
					return false;
				}
			}			
		}
		return $list;
	}

	function rm($filename) {
		$filename=$this->makeT3FilePath($filename);
		$p=$this->isT3($filename);
		if ($p['isWebmount']) {
			return FALSE;
		}
		return unlink($filename);
	}

	function size($filename) {
		$filename=$this->makeT3FilePath($filename);
		return filesize($filename);
	}

	function exists($filename) {
		$filename=$this->makeT3FilePath($filename);
		return (@file_exists($filename));
	}

	function type($filename) {
			$filename=$this->makeT3FilePath($filename);
			return (filetype($filename));
	}
	
	function md($dir) {
			$dir=$this->makeT3FilePath($dir);
			return (@mkdir($dir));
	}
	
	function rd($dir) {
		$dir=$this->makeT3FilePath($dir);
		return (@rmdir($dir));
	}
	
	function rn($from, $to) {
		$dir = trim($this->cwd);
		$p=$this->isT3($dir);
		if ($p['isWebmount']) {
			return FALSE;
		}
	  $ff=$this->makeT3FilePath($from);
	  $ft=$this->makeT3FilePath($to);	    
	  return (rename($ff, $ft));
	}

	function read($size) {
		return fread($this->fp, $size);
	}

	function write($str) {
		fwrite($this->fp, $str);
	}

	function open($filename, $create = false, $append = false) {
	    clearstatcache();	
	    $type = ($create) ? "w" : "r";
	    $type = ($append) ? "a" : "w";
	    $filename=$this->makeT3FilePath($filename);
			return ($this->fp = fopen($filename, $type));
	}

	function close() {
		fclose($this->fp);
	}
	
	/* permission output added by Phanatic */
	function perms($mode) {
	    /* Determine Type */
	    if( $mode & 0x1000 )
			$type='p'; /* FIFO pipe */
	    elseif( $mode & 0x2000 )
			$type='c'; /* Character special */
	    elseif( $mode & 0x4000 )
			$type='d'; /* Directory */
	    elseif( $mode & 0x6000 )
			$type='b'; /* Block special */
	    elseif( $mode & 0x8000 )
			$type='-'; /* Regular */
	    elseif( $mode & 0xA000 )
			$type='l'; /* Symbolic Link */
	    elseif( $mode & 0xC000 )
			$type='s'; /* Socket */
	    else
			$type='u'; /* UNKNOWN */
			
	    /* Determine permissions */
	    $owner['read']    = ($mode & 00400) ? 'r' : '-';
	    $owner['write']   = ($mode & 00200) ? 'w' : '-';
	    $owner['execute'] = ($mode & 00100) ? 'x' : '-';
	    $group['read']    = ($mode & 00040) ? 'r' : '-';
	    $group['write']   = ($mode & 00020) ? 'w' : '-';
	    $group['execute'] = ($mode & 00010) ? 'x' : '-';
	    $world['read']    = ($mode & 00004) ? 'r' : '-';
	    $world['write']   = ($mode & 00002) ? 'w' : '-';
	    $world['execute'] = ($mode & 00001) ? 'x' : '-';
	    
	    /* Adjust for SUID, SGID and sticky bit */
	    if( $mode & 0x800 )
			$owner['execute'] = ($owner['execute']=='x') ? 's' : 'S';
	    if( $mode & 0x400 )
			$group['execute'] = ($group['execute']=='x') ? 's' : 'S';
	    if( $mode & 0x200 )
			$world['execute'] = ($world['execute']=='x') ? 't' : 'T';
			
	    $permstr = sprintf("%1s", $type);
	    $permstr = $permstr . sprintf("%1s%1s%1s", $owner['read'], $owner['write'], $owner['execute']);
	    $permstr = $permstr . sprintf("%1s%1s%1s", $group['read'], $group['write'], $group['execute']);
	    $permstr = $permstr . sprintf("%1s%1s%1s", $world['read'], $world['write'], $world['execute']);
	    
	    return $permstr;
	}
   function strip_selected_tags($str, $tags = "", $stripContent = false)
   {
       preg_match_all("/<([^>]+)>/i",$tags,$allTags,PREG_PATTERN_ORDER);
       foreach ($allTags[1] as $tag){
           if ($stripContent) {
               $str = preg_replace("/<".$tag."[^>]*>.*<\/".$tag.">/iU","",$str);
           }
           $str = preg_replace("/<\/?".$tag."[^>]*>/iU","",$str);
       }
       return $str;
   }

}
?>
