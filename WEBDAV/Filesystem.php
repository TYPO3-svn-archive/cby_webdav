<?php
require_once "Server.php";
require_once "System.php";
require_once "Var.php";
stream_wrapper_register( "var", "Stream_Var" );
$data="test";

    
/**
 * Filesystem access using WebDAV
 *
 * @access  public
 * @author  Hartmut Holzgraefe <hartmut@php.net>
 * @version @package-version@
 */
class HTTP_WebDAV_Server_Filesystem extends HTTP_WebDAV_Server 
{
	/**
	* Root directory for WebDAV access
	*
	* Defaults to webserver document root (set by ServeRequest)
	*
	* @access private
	* @var    string
	*/
	var $base = "";
	var $CFG;
	var $debugLevel;
	/** 
	* MySQL Host where property and locking information is stored
	*
	* @access private
	* @var    string
	*/
	
	/**
	* MySQL table name prefix 
	*
	* @access private
	* @var    string
	*/
	var $db_prefix = "tx_cbywebdav_webdav_";
	
	/**
	* MySQL user for property/locking db access
	*
	* @access private
	* @var    string
	*/
	var $db_user = "root";
	
	/**
	* MySQL password for property/locking db access
	*
	* @access private
	* @var    string
	*/
	var $db_passwd = "";
	
	/**
	* Serve a webdav request
	*
	* @access public
	* @param  string  
	*/
	
	function tx_cbywebdav_devlog($level,$message,$ext,$func=array()) 
	{
		if (in_array($level,$this->CFG->debuglevel) && (count($func)===0 || in_array($func,$this->CFG->debugfunction)))  t3lib_div::devlog($level.':'.$message ,$ext.($func?':'.$func:''));
	}
	
	function init(&$CFG) {
		$this->CFG=$CFG;
		$this->base=$CFG->T3ROOTDIR;
	}
	
	/**
	* Serve a webdav request
	*
	* @access public
	* @param  string  
	*/
	
	function ServeRequest($base = false,&$CFG) 
	{
		$this->init($CFG);
		//$this->base=$CFG->T3ROOTDIR;
	  // special treatment for litmus compliance test
	  // reply on its identifier header
	  // not needed for the test itself but eases debugging
	  foreach (apache_request_headers() as $key => $value) {
	      if (stristr($key, "litmus")) {
	          error_log("Litmus test $value");
	          header("X-Litmus-reply: ".$value);
	      }
	  }
	
	  // set root directory, defaults to webserver document root if not set
	  if ($base) {
	      $this->base = realpath($base); // TODO throw if not a directory
	  } else if (!$this->base) {
	      $this->base = $this->_SERVER['DOCUMENT_ROOT'];
	  }
		$this->tx_cbywebdav_devlog(1,"=================    START   REQUEST : $this->base  =========================" ,"cby_webdav",'ServeRequest');			
	          
	  // TODO throw on connection problems
	  // let the base class do all the work
	  parent::ServeRequest();
		$this->tx_cbywebdav_devlog(1,"===========       END  REQUEST    =============" ,"cby_webdav",'ServeRequest');
	}
	
	/**
	* No authentication is needed here
	*
	* @access private
	* @param  string  HTTP Authentication type (Basic, Digest, ...)
	* @param  string  Username
	* @param  string  Password
	* @return bool    true on successful authentication
	*/
	
	function check_auth($type, $user, $pass) 
	{
		$this->tx_cbywebdav_devlog(1,"=== check_auth start; type : $type, user: $user, pass: **** ","cby_webdav","check_auth");
		$t3io=$this->CFG->t3io;
		$auth=$t3io->T3Authenticate($user,$pass);
		$this->tx_cbywebdav_devlog(1,"=== check_auth end : $auth","cby_webdav","check_auth");
		return $auth;
	}


/**
* PROPFIND method handler
*
* @param  array  general parameter passing array
* @param  array  return array for file properties
* @return bool   true on success
*/

function PROPFIND(&$options, &$files) 
{
	$this->tx_cbywebdav_devlog(1,"=== PROPFIND start options : ".serialize($options)." files : ".serialize($files),"cby_webdav","PROPFIND");
	$t3io=$this->CFG->t3io;

  // get absolute fs path to requested resource

  $virtualpath = $t3io->T3CleanFilePath($this->base . $options["path"]);
  
  $fileinfo=$t3io->T3IsFile($virtualpath);
        
  // sanity check
  if (!$fileinfo['exists']) {
		$this->tx_cbywebdav_devlog(2,"=== PROPFIND $virtualpath file does not exist end : ".serialize($fileinfo),"cby_webdav","PROPFIND");
    return false;
  }

	// prepare property array
	$files["files"] = array();
	
	// store information for the requested path itself id depth=0
	
	if ($options["depth"]==0) $files["files"][] = $this->fileinfo($options["path"]);
	
	// information for contained resources requested?
	$this->tx_cbywebdav_devlog(1,"propfind DEPTH: ".$options["depth"],"cby_webdav","PROPFIND");
	
	//if (!empty($options["depth"])) { // TODO check for is_dir() first?
	        
	// make sure path ends with '/'
	$options["path"] = $this->_slashify($options["path"]);
	
	// TODO use DEPTH value in T3ListDir
	
	$fileList=$t3io->T3ListDir($options["path"]);
	
	if (is_array($fileList)) {
		foreach ($fileList as $fileName) {
			$this->tx_cbywebdav_devlog(1,"file:".$fileName,'cby_webdav',"PROPFIND");
	    if ($fileName != "." && $fileName != "..") {
	    	// another hack 
				$fileName=str_replace('?','%3F',$fileName);
	      $files["files"][] = $this->fileinfo($options["path"].$fileName);
		    $this->tx_cbywebdav_devlog(1,"file2:".serialize($this->fileinfo($options["path"].$fileName)),'cby_webdav',"PROPFIND");
			}
	 	}			
	}
	
	return true;
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


		/**
		* Get properties for a single file/resource
		*
		* @param  string  resource path
		* @return array   resource properties
		*/
		
    function fileinfo($path) 
    {
   		$this->tx_cbywebdav_devlog(1,"=== Fileinfo : ".$path,"cby_webdav","fileinfo");
			//$upath=$this->_urlencode($path);
			$upath=$path;
			$t3io=$this->CFG->t3io;

     // map URI path to filesystem path
     $virtualpath = $t3io->T3CleanFilePath($this->base . $path);
		 $fsupath = $t3io->T3CleanFilePath($this->base . $upath);

     // create result array
     $info = array();
     // TODO remove slash append code when base clase is able to do it itself
     $info["path"]  = $t3io->T3IsDir($virtualpath) ? $this->_slashify($upath) : $upath; 
     $info["props"] = array();
     // no special beautified displayname here ...
     $info["props"][] = $this->mkprop("displayname", strtoupper($upath));
          
     // creation and modification time
			if ($virtualpath== '/'.T3_FTPD_FILE_ROOT || $virtualpath== '/'.T3_FTPD_WWW_ROOT) {	
		        	$info["props"][] = $this->mkprop("creationdate",   0);
		        	$info["props"][] = $this->mkprop("getlastmodified", 0);
			} else {
		        	$info["props"][] = $this->mkprop("creationdate",    $t3io->T3FileCTime($virtualpath));
		        	$info["props"][] = $this->mkprop("getlastmOdified", $t3io->T3FileMTime($virtualpath));
			}

      // type and size (caller already made sure that path exists)

      if ($t3io->T3IsDir($virtualpath)) {
          // directory (WebDAV collection)
          $info["props"][] = $this->mkprop("resourcetype", "collection");
          $info["props"][] = $this->mkprop("getcontenttype", "httpd/unix-directory");             
      } else {
          // plain file (WebDAV resource)
          $info["props"][] = $this->mkprop("resourcetype", "");
          if (is_readable($virtualpath)) {
             $info["props"][] = $this->mkprop("getcontenttype", $this->_mimetype($virtualpath));
          } else {
              $info["props"][] = $this->mkprop("getcontenttype", "application/x-non-readable");
          }               
          $info["props"][] = $this->mkprop("getcontentlength", $t3io->T3FileSize($virtualpath));
      }

      // get additional properties from database
      $res=$this->CFG->T3DB->exec_SELECTquery('ns, name, value','tx_cbywebdav_webdav_properties',"path = '$path'");
			$this->tx_cbywebdav_devlog(1,"=== fileinfo query: ".$this->CFG->T3DB->SELECTquery('ns, name, value','tx_cbywebdav_webdav_properties',"path = '$path'"),"cby_webdav","fileinfo");
     if($res) {
  			while ($row=$this->CFG->T3DB->sql_fetch_assoc($res)) {
          $info["props"][] = $this->mkprop($row["ns"], $row["name"], $row["value"]);
      	}
     } else {
 		$this->tx_cbywebdav_devlog(1,"=== fileinfo : pb db","cby_webdav","fileinfo");

	}

    	 $this->tx_cbywebdav_devlog(1,"=== fileinfo end:".serialize($info),"cby_webdav","fileinfo");
       return $info;
    }

    /**
     * detect if a given program is found in the search PATH
     *
     * helper function used by _mimetype() to detect if the 
     * external 'file' utility is available
     *
     * @param  string  program name
     * @param  string  optional search path, defaults to $PATH
     * @return bool    true if executable program found in path
     */
    function _can_execute($name, $path = false) 
    {
 			$this->tx_cbywebdav_devlog(1,"_can_execute: $path ","cby_webdav","_can_execute");
  		$t3io=$this->CFG->t3io;

        // path defaults to PATH from environment if not set
        if ($path === false) {
            $path = getenv("PATH");
        }
            
        // check method depends on operating system
        if (!strncmp(PHP_OS, "WIN", 3)) {
            // on Windows an appropriate COM or EXE file needs to exist
            $exts     = array(".exe", ".com");
            $check_fn = "file_exists";
        } else {
            // anywhere else we look for an executable file of that name
            $exts     = array("");
            $check_fn = "is_executable";
        }
            
        // now check the directories in the path for the program
        foreach (explode(PATH_SEPARATOR, $path) as $dir) {
            // skip invalid path entries
            if (!$t3io->T3FileExists($dir)) continue;
            if (!$t3io->T3IsDir($dir)) continue;

            // and now look for the file
            foreach ($exts as $ext) {
                if ($check_fn("$dir/$name".$ext)) return true;
            }
        }

        return false;
    }

        
    /**
     * try to detect the mime type of a file
     *
     * @param  string  file path
     * @return string  guessed mime type
     */
    function _mimetype($virtualpath) 
    {
			$this->tx_cbywebdav_devlog(1,"_mimetype: $virtualpath ","cby_webdav","_mimetype");
			$t3io=$this->CFG->t3io;

        if (@$t3io->T3IsDir($virtualpath)) {
            // directories are easy
            return "httpd/unix-directory"; 
        } else if (function_exists("mime_content_type")) {
            // use mime magic extension if available
            $mime_type = mime_content_type($virtualpath);
        } else if ($this->_can_execute("file")) {
            // it looks like we have a 'file' command, 
            // lets see it it does have mime support
            $fp    = popen("file -i '$virtualpath' 2>/dev/null", "r");
            $reply = fgets($fp);
            pclose($fp);
                
            // popen will not return an error if the binary was not found
            // and find may not have mime support using "-i"
            // so we test the format of the returned string 
                
            // the reply begins with the requested filename
            if (!strncmp($reply, "$virtualpath: ", strlen($virtualpath)+2)) {                     
                $reply = substr($reply, strlen($virtualpath)+2);
                // followed by the mime type (maybe including options)
                if (preg_match('|^[[:alnum:]_-]+/[[:alnum:]_-]+;?.*|', $reply, $matches)) {
                    $mime_type = $matches[0];
                }
            }
        } 
            
        if (empty($mime_type)) {
            // Fallback solution: try to guess the type by the file extension
            // TODO: add more ...
            // TODO: it has been suggested to delegate mimetype detection 
            //       to apache but this has at least three issues:
            //       - works only with apache
            //       - needs file to be within the document tree
            //       - requires apache mod_magic 
            // TODO: can we use the registry for this on Windows?
            //       OTOH if the server is Windos the clients are likely to 
            //       be Windows, too, and tend do ignore the Content-Type
            //       anyway (overriding it with information taken from
            //       the registry)
            // TODO: have a seperate PEAR class for mimetype detection?
            switch (strtolower(strrchr(basename($virtualpath), "."))) {
            case ".html":
            case ".textpic":
            case ".txt":
            case ".[unknown]":
               $mime_type = "text/html";
                break;
            case ".gif":
                $mime_type = "image/gif";
                break;
            case ".jpg":
                $mime_type = "image/jpeg";
                break;
            default: 
                $mime_type = "application/octet-stream";
                break;
            }
        }
				$this->tx_cbywebdav_devlog(1,"_mimetype>: $mime_type ","cby_webdav","_mimetype");
            
        return $mime_type;
    }

		/**
		* GET method handler
		* 
		* @param  array  parameter passing array
		* @return bool   true on success
		*/
     
    function GET(&$options) 
    {
    	$this->tx_cbywebdav_devlog(1,"=== GET START : ".serialize($options),"cby_webdav","GET");
			$t3io=$this->CFG->t3io;
        //$options["path"]=utf8_encode($options["path"]);
        // get absolute fs path to requested resource
        $virtualpath = $t3io->T3CleanFilePath($this->base . $options["path"]);

        // sanity check
  			$this->tx_cbywebdav_devlog(1,"get $virtualpath fe :".$t3io->T3FileExists($options["path"]).' : '.$options["path"],"cby_webdav","GET");

        if (!$t3io->T3FileExists($options["path"])) {
        	$this->tx_cbywebdav_devlog(1,"=== GET END : File does not exist","cby_webdav","GET");
        	return false;
        }
        
   			$this->tx_cbywebdav_devlog(1,"get ko :".$t3io->T3FileExists($options["path"]).' : '.$options["path"],"cby_webdav","GET");

           
        // is this a collection?
        if ($t3io->T3IsDir($virtualpath)) {
        		$this->tx_cbywebdav_devlog(1,"=== GET coll : ".$virtualpath,"cby_webdav","GET");
        	  $ret=$this->Getdir($virtualpath, $options);
        	  $this->tx_cbywebdav_devlog(1,"=== GET END : ".$ret,"cby_webdav","GET");
            return $ret;
            //return $this->GetDir($virtualpath, $options);
        }
        $this->tx_cbywebdav_devlog(1,"=== GET no coll : ","cby_webdav","GET");
            
        // detect resource type
        $options['mimetype'] = $this->_mimetype($virtualpath); 
                
        // detect modification time
        // see rfc2518, section 13.7
        // some clients seem to treat this as a reverse rule
        // requiering a Last-Modified header if the getlastmodified header was set
        $options['mtime'] = $t3io->T3FileMTime($virtualpath);
            
        // detect resource size
        $options['size'] = $t3io->T3FileSize($virtualpath);
            
        // no need to check result here, it is handled by the base class
	 			$info=$t3io->T3IsFile($virtualpath);
			 	$GLOBALS['data']=$info['data'];
 				//$this->tx_cbywebdav_devlog(1,"get wm".$info['isWebmount'],"cby_webdav");

	 			if ($info['isWebmount']) {
	 				// If file was uploaded through cbywebdav we link to it
	 				if ($info['tx_cbywebdav_file']) {
  							$this->tx_cbywebdav_devlog(1,"T3FilePath :".$t3io->T3MakeFilePath('/uploads/tx_cbywebdav/'.$info['tx_cbywebdav_file']),"cby_webdav","GET");
		    				$streamPath=$t3io->T3MakeFilePath('/uploads/tx_cbywebdav/'.$info['tx_cbywebdav_file']);
	 						  $options['stream'] = fopen($streamPath,"r");
		    	} else {
		    		// otherwise we open bodytext data
		    		$streamPath='var://GLOBALS/data';
		    		$options['stream'] = fopen(streamPath,"r");
		    	}
		    	
	 			} else {
 		    	$streamPath=$t3io->T3MakeFilePath($virtualpath);
       		$options['stream'] = fopen($streamPath, "r");
      	}
    	  $this->tx_cbywebdav_devlog(1,"=== GET END : ".$streamPath,"cby_webdav","GET");
      	return true;
    }

		/**
		* GET method handler for directories
		*
		* This is a very simple mod_index lookalike.
		* See RFC 2518, Section 8.4 on GET/HEAD for collections
		*
		* @param  string  directory path
		* @return void    function has to handle HTTP response itself
		*/

    function GetDir($virtualpath, &$options) 
    {
  		$this->tx_cbywebdav_devlog(1,"getdir start : $virtualpath ".serialize($options),"cby_webdav","GetDir");
			$t3io=$this->CFG->t3io;

			// What is this for redirecting with training slash????
      $path = $this->_slashify($options["path"]);
      if ($path != $options["path"]) {
				$this->tx_cbywebdav_devlog(2,"getdir : redirect $path :".$options["path"],"cby_webdav","GetDir");
        header("Location: ".$this->base_uri.$path);
        exit;
      }

      // fixed width directory column format
      $format = "%15s  %-19s  %-s\n";
      
      if (!$t3io->T3IsDir($options["path"])) {
					$this->tx_cbywebdav_devlog(2,"getdir : path does not exist $virtualpath","cby_webdav","GetDir");
          return false;
      }

			$fileList=$t3io->T3ListDir($options["path"]);
			
	    $this->tx_cbywebdav_devlog(1,"getdir file exists: ".htmlspecialchars($options['path']),"cby_webdav","GetDir");
	    $dir=$t3io->T3CleanFilePath('/'.$this->CFG->WEBDAVPREFIX.'/'.$options['path']);
      echo "<html><head><title>Index of ".htmlspecialchars($dir)."</title></head>\n";            
      echo "<h1>Index of ".htmlspecialchars($dir)."</h1>\n";
          
      echo "<pre>";
      printf($format, "Size", "Last modified", "Filename");
      echo "<hr>";
			if (is_array($fileList)) {
					foreach ($fileList as $fileName) {
						$this->tx_cbywebdav_devlog(1,"file:".$fileName,'cby_webdav',"GetDir");
            if ($fileName != "." && $fileName != "..") {
            	// another hack 
              $fullpath = $t3io->T3CleanFilePath($virtualpath."/".$fileName);
							$fileName=str_replace('?','%3F',$fileName);
              $name     = htmlspecialchars($fileName);
              $this->tx_cbywebdav_devlog(1,"file1: $fullpath : $fileName",'cby_webdav',"GetDir");
              printf($format, number_format($t3io->T3FileSize($fullpath)), strftime("%Y-%m-%d %H:%M:%S", $t3io->T3FileMTime($fullpath)), "<a href='$dir$name'>$name</a>");
					    $this->tx_cbywebdav_devlog(1,"file2:".serialize($this->fileinfo($options["path"].$fileName)),'cby_webdav',"GetDir");
					}
				}
			}
					/*
	        while ($filename = readdir($handle)) {
          if ($filename != "." && $filename != "..") {
              $fullpath = $virtualpath."/".$filename;
              $name     = htmlspecialchars($filename);
              printf($format, 
                     number_format(filesize($fullpath)),
                     strftime("%Y-%m-%d %H:%M:%S", filemtime($fullpath)), 
                     "<a href='$name'>$name</a>");
          }
          */
  
      echo "</pre>";
      //closedir($handle);
      echo "</html>\n";
      exit;
    }

    /**
     * PUT method handler
     * 
     * @param  array  parameter passing array
     * @return bool   true on success
     */

    function PUT(&$options) 
    {
    	
    		// Virtual Path is path given from client view.
    		// Physical Path is the real file path as stored on server
    		
  			$this->tx_cbywebdav_devlog(1,"=== PUT START : ".serialize($options),"cby_webdav","PUT");
				$t3io=$this->CFG->t3io;

        $virtualFilePath = $t3io->T3CleanFilePath($this->base .$options["path"]);

				// We have a conflict if filename is identical to dir name ..
				
        if (!@$t3io->T3IsDir(dirname($virtualFilePath))) {
            return "409 Conflict";
        }

        $options["new"] = !$t3io->T3FileExists($virtualFilePath);
	 			$virtualFilePath=$t3io->T3MakeFilePath($virtualFilePath);
	 			$info=$t3io->T3IsFileUpload($options["path"]);
	 			
	 			if ($info['isWebmount']) {
	 				
	 				// Web mount
	 				
					$info['ufile']=$virtualFilePath;
  			  $this->tx_cbywebdav_devlog(4,"PUT info : ".serialize($info),"cby_webdav","PUT");
  			  // in update mode we update directly tx_cbywebdav_file ... (No copy)
  			  // we can integrate versionning here later ...
  			  $physicalFilePath=$t3io->T3CleanFilePath($this->CFG->T3PHYSICALROOTDIR.$info['filepath']);
					if ($info['cmd']='update') $fp = fopen($physicalFilePath,"w");
					
					// In insert mode we create file in temporary folder ..;
					
       		if ($info['cmd']='insert') $fp = fopen($physicalFilePath,"w");
       		
	 			} else {
	 				// File Mount, we open the file
	 				$physicalFilePath=$virtualFilePath;
        	$fp = fopen($physicalFilePath, "w");
	 			}
	 			// We return stream to file
  			$this->tx_cbywebdav_devlog(1,"=== PUT END, webdav path :".$virtualFilePath.", file path : ".$physicalFilePath ,"cby_webdav","PUT");
        return $fp;
    }

   /**
 		 * After Put is called after file has been transferred
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */

    function AFTERPUT(&$options)
    {
    		// should add a few checks such as md5 checksum of file ...
    		// Just to be sure file was transferred correctly
    		
    		// Maybe log file transfers ???
    		
  			$this->tx_cbywebdav_devlog(7,"=== AFTER PUT START : ".serialize($options),"cby_webdav","PUT");
				$t3io=$this->CFG->t3io;
        $virtualFilePath = $this->base .$options["path"];
        if (!@$t3io->T3IsDir(dirname($virtualFilePath))) {
   					$this->tx_cbywebdav_devlog(7,"!!! ERROR AFTER PUT END, webdav path :".$virtualFilePath.", file path : ".$physicalFilePath ,"cby_webdav","PUT");
            return "409 Conflict";
        }

        //$options["new"] = !$t3io->T3FileExists($virtualFilePath);
	 			$virtualFilePath=$t3io->T3MakeFilePath($virtualFilePath);
	 			$info=$t3io->T3IsFileUpload($options["path"]);
 			  $this->tx_cbywebdav_devlog(7,"AFTER PUT info : ".serialize($info),"cby_webdav","PUT");
	 			if ($info['isWebmount']) {
					$info['ufile']=$virtualFilePath;
  			  $this->tx_cbywebdav_devlog(7,"afterput info : ".serialize($info),"cby_webdav","PUT");
					$t3io->T3LinkFileUpload($info);					//$GLOBALS['data']='';
					$physicalFilePath=$t3io->T3CleanFilePath($this->CFG->T3PHYSICALROOTDIR.$info['filepath']);
					unlink($physicalFilePath);

 	 			} else $physicalFilePath=$virtualFilePath;
 	 			
   			$this->tx_cbywebdav_devlog(7,"=== AFTER PUT END, webdav path :".$virtualFilePath.", file path : ".$physicalFilePath ,"cby_webdav","PUT");
        return true;
    }

		/**
		* MKCOL method handler
		* Creates Collection (directory).
		* @param  array  general parameter passing array
		* @return bool   true on success
		*/
		
    function MKCOL($options,$test=0) 
    {           
  			$this->tx_cbywebdav_devlog(1,"=== MKCOL START : ".serialize($options),"cby_webdav","MKCOL");
				$t3io=$this->CFG->t3io;
        $path=$t3io->_unslashify($this->CFG->T3ROOTDIR) .$this->_slashify($options["path"]);
        $path=$t3io->T3CleanFilePath($path);
	 			$info=$t3io->T3IsFile($path);
	 			
	 		  $parent = dirname($path);
        $name   = basename($path);
        //echo "<br>*= $parent:$name".serialize($info);
  			$this->tx_cbywebdav_devlog(1,"=== MKCOL info : ".serialize($info)."Options : ".serialize($options),"cby_webdav","MKCOL");
        
	 			if ($info['isWebmount']) {
					
					/*
					$res=$this->CFG->T3DB->exec_SELECTquery('uid','pages',"pid='".$info['pid']."' and title='".$name."'");
  				$this->tx_cbywebdav_devlog(1,"=== MKCOL inter : ".$this->CFG->T3DB->SELECTquery('uid','pages',"pid='".$info['pid']."' and title='".$name."'"),"cby_webdav","MKCOL");
					$resu=$this->CFG->T3DB->sql_num_rows($res);
	 				//if ($resu) return "409 Conflict";
					*/
					$parentinfo=$t3io->T3IsFile($parent);
					//echo $parent. "<br>".serialize($parentinfo);
  				$this->tx_cbywebdav_devlog(1,"=== MKCOL parentinfo : ".serialize($parentinfo),"cby_webdav","MKCOL");


         	if (!$parentinfo['exists']) {
        		  $this->tx_cbywebdav_devlog(1,"=== MKCOL END : 409 ","cby_webdav","MKCOL");
        	    return "409 Conflict";
        	}

        	if (!$parentinfo['isDir']) {
   					$this->tx_cbywebdav_devlog(1,"=== MKCOL END : 403 1","cby_webdav","MKCOL");
        	  return "403 Forbidden";
        	}
        	
        	if ($info['exists'] ) {
    					$this->tx_cbywebdav_devlog(1,"=== MKCOL END File: 405 $parent - $name","cby_webdav","MKCOL");
       	      return "405 Method not allowed";
        	}
        	
       		/*if ($info['isDir'] ) {
    					$this->tx_cbywebdav_devlog(1,"=== MKCOL END DIR: 405 $parent - $name","cby_webdav","MKCOL");
       	      return "405 Method not allowed $parent - $name";
        	} */   
        	
        	if (!empty($this->_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
   					$this->tx_cbywebdav_devlog(1,"=== MKCOL END : 415 ","cby_webdav","MKCOL");
        	  return "415 Unsupported media type";
        	}   
        	 	
					// We create the page
					$page['pid']=$info['pid'];
					$page['title']=$name;
					$page['tstamp']=time();
					
					//page['crdate']=time();
					
					if ($info['uid']) {
						$this->CFG->T3DB->exec_UPDATEquery('pages',"uid='$info[uid]'",$page);
   					$this->tx_cbywebdav_devlog(1,"=== MKCOL update coll  : ".serialize($page),"cby_webdav","MKCOL");
					} else {
						$page['cruser_id']=$t3io->user_uid;
						$page['crdate']=time();
						$page['perms_userid']=$t3io->user_uid;
						$page['perms_groupid']=$t3io->user_gid;
						$page['perms_user']=31; // a mettre en param CBY
						$page['perms_group']=27; // a mettre en param CBY
						$this->CFG->T3DB->exec_INSERTquery('pages',$page);
   					$this->tx_cbywebdav_devlog(1,"=== MKCOL create coll  : ".serialize($page).$this->CFG->T3DB->INSERTquery('pages',$page),"cby_webdav","MKCOL");
					}
					
					// We clear page caches ....
					if (!$test)  {
						$t3io->T3ClearPageCache($page['pid']);
						$t3io->T3ClearAllCache();
					}
	 			} else {
	 				
	 				// FILEMOUNT
	 				
	 				//$path=$t3io->T3MakeFilePath($path);
        	//$path=$t3io->T3CleanFilePath($path);
	 			

        	$parent = dirname($path);
        	$name   = basename($path);
					//echo "<br>file2 :".$parent;
					$parentinfo=$t3io->T3IsFile($parent);
					//echo "<br>".serialize($parentinfo);
        	
        	
  				$this->tx_cbywebdav_devlog(1,"=== MKCOL inter : path $path : parent $parent :  name $name","cby_webdav","MKCOL");

         	if (!$parentinfo['exists']) {
        		  $this->tx_cbywebdav_devlog(1,"=== MKCOL END : 409 ","cby_webdav","MKCOL");
        	    return "409 Conflict";
        	}
 
        	if (!$parentinfo['isDir']) {
   					$this->tx_cbywebdav_devlog(1,"=== MKCOL END : 403 1","cby_webdav","MKCOL");
        	  return "403 Forbidden";
        	}


        	if ($info['exists']) {
    					$this->tx_cbywebdav_devlog(1,"=== MKCOL END FILE: 405 $parent - $name","cby_webdav","MKCOL");
       	      return "405 Method not allowed";
        	}


        	if (!empty($this->_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
   					$this->tx_cbywebdav_devlog(1,"=== MKCOL END : 415 ","cby_webdav","MKCOL");
        	  return "415 Unsupported media type";
        	}
        	
	 				$parent=$t3io->T3MakeFilePath($parent);
        	$parent=$t3io->T3CleanFilePath($parent);
        	
        	$stat = @mkdir($parent."/".$name, 0777); // CBY rights must be better here ...
        	//echo $parent."/".$name;
        	if (!$stat) {
   					$this->tx_cbywebdav_devlog(1,"=== MKCOL END : 403 2:".$parent."/".$name,"cby_webdav","MKCOL");
         	  return "403 Forbidden r";                 
        	}
	 			}
	 			
  			$this->tx_cbywebdav_devlog(1,"=== MKCOL END : 201 ","cby_webdav","MKCOL");
        return ("201 Created");
    }
        
        
    /**
     * DELETE method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */

    function DELETE($options) 
    {
  		$this->tx_cbywebdav_devlog(0,"=== Delete start : ".$options["path"],"cby_webdav","DELETE");
			$t3io=$this->CFG->t3io;
      $path =$t3io->T3CleanFilePath($this->base . "/" .$options["path"]);	 
      $info=$t3io->T3IsFile($options["path"]);
  		$this->tx_cbywebdav_devlog(1,"=== Delete : $path,info:".serialize($info),"cby_webdav","DELETE");

		 	if ($info['isWebmount']) {
		 		if ($info['uid']==0) 	return "404 Not found";// a améliorer ....

				$arr=array('deleted'=>'1');
				$this->tx_cbywebdav_devlog(1,"=== Delete WM:".$info['isWebmount']." ; ".$info['isWebcontent']. " ; ".$info['uid']."; delete : ".$this->CFG->T3DB->UPDATEquery('pages',"uid='".$info['uid']."'",$arr),"cby_webdav","DELETE");
				if ($info['isDir']) $this->CFG->T3DB->exec_UPDATEquery('pages',"uid='".$info['uid']."'",$arr);
				if ($info['isFile']) $this->CFG->T3DB->exec_UPDATEquery('tt_content',"uid='".$info['uid']."'",$arr);
				$t3io->T3ClearPageCache($info['pid']);
				$t3io->T3ClearAllCache();
				$this->tx_cbywebdav_devlog(1,"delete : ".$info['pid'],"cby_webdav","DELETE");
		 	} else {	
		 		// File mount
	    	if (!$info['exists']) {
	      	return "404 Not found";
	    	}
				$path=$t3io->T3MakeFilePath($path);
  			$this->tx_cbywebdav_devlog(1,"=== Delete : $path","cby_webdav","DELETE");
	
	    	//if ($t3io->T3IsDir($path)) {
	    	if ($info['isDir']) {
	    		$query = "DELETE FROM {$this->db_prefix}properties WHERE path LIKE '".$this->_slashify($options["path"])."%'";
					$this->CFG->T3DB->exec_DELETEquery('tx_cbywebdav_webdav_properties',"path LIKE '".$this->_slashify($options["path"])."%'");
					// Major security issue here ........
 					$this->tx_cbywebdav_devlog(1,"=== Delete $path : System::rm('-rf $path')","cby_webdav","DELETE");
 					
 					// we allow deletion only in specific directories ...
 					
					if (strpos($path, $this->CFG->T3PHYSICALROOTDIR.'litmus')!==false || strpos($path, $this->CFG->T3PHYSICALROOTDIR.'fileadmin')!==false || strpos($path, $this->CFG->T3PHYSICALROOTDIR.'uploads/tx_cbywebdav')!==false) {
						$this->tx_cbywebdav_devlog(1,"=== Delete : System::rm('-rf $path') executed ","cby_webdav","DELETE");
 	    			System::rm("-rf $path");
	    		}
	    	} else {
	 				$this->tx_cbywebdav_devlog(1,"=== Delete : unlink($path) ","cby_webdav","DELETE");
    		  unlink($path);
	    	}
	   	 	$query = "DELETE FROM {$this->db_prefix}properties WHERE path = '$options[path]'";
				$this->CFG->T3DB->exec_DELETEquery('tx_cbywebdav_webdav_properties',"path = '$options[path]'");
	   	}
			$this->tx_cbywebdav_devlog(1,"=== delete end : ","cby_webdav","DELETE");
      return "204 No Content";
    }


		/**
		* MOVE method handler
		*
		* @param  array  general parameter passing array
		* @return bool   true on success
		*/

    function MOVE($options) 
    {
  			$this->tx_cbywebdav_devlog(1,"= MOVE".serialize($options),"cby_webdav","MOVE");

        $ret= $this->COPY($options, true);
   			$this->tx_cbywebdav_devlog(1,"= MOVE end : $ret".serialize($options),"cby_webdav","MOVE");
   			return $ret;
   }

    /**
     * COPY method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     * Cas possibles :
     
     * Copie FFichier sur FFichier ...
     * Copie FFichier dans FRep
     * Copie FRep dans FRep
     * Copie FRep dans FFichier => erreur ...
     * Copie WFichier sur WFichier ...
     * Copie WFichier dans WRep
     * Copie WRep dans WRep
     * Copie WRep dans WFichier => erreur ...
     * Copie FFichier sur WFichier ...
     * Copie FFichier dans WRep
     * Copie FRep dans WRep
     * Copie FRep dans WFichier => erreur ...
     * Copie WFichier sur FFichier ...
     * Copie WFichier dans FRep
     * Copie WRep dans FRep
     * Copie WRep dans FFichier => erreur ...
     
     * options["path"] : source 
     * options["dest"] : destination 
     * options["dest_url"] : source 
     * options["overwrite"] : destination 
     
     */
     
    function COPY($options, $move=false) 
    {
  			$this->tx_cbywebdav_devlog(1,"=== COPY start ".serialize($options),"cby_webdav","COPY");
				$t3io=$this->CFG->t3io;

        // TODO Property updates still broken (Litmus should detect this?)

        if (!empty($this->_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
	     		 $this->tx_cbywebdav_devlog(2,"=== COPY error 415","cby_webdav","COPY");
           return "415 Unsupported media type";
        }

        // no copying to different WebDAV Servers yet
        // TODO change test here ..
        if (isset($options["dest_url"])) {
	     		 $this->tx_cbywebdav_devlog(2,"=== COPY error 502","cby_webdav","COPY");
           return "502 bad gateway"; 
        }     

        $source = $t3io->T3MakeFilePath($this->base .$options["path"]);
        
				$sourceinfo=$t3io->T3IsFile($source); 
        if (!$sourceinfo['exists']) {
	     		$this->tx_cbywebdav_devlog(2,"=== COPY END error 404 : $source","cby_webdav","COPY");
        	return "404 Not found";
        }	
        $dest = $t3io->T3MakeFilePath($this->base.$options["dest"]);
				$destinfo=$t3io->T3IsFile($dest); 
				$parentdir  = dirname($dest);
				$parentinfo=$t3io->T3IsFile($parentdir); 
			
				// chemin pere invalide !!
				
				if (!$parentinfo['exists']) {
      		$this->tx_cbywebdav_devlog(2,"=== COPY error 409","cby_webdav","COPY");
          return "409 Conflict";
			  }
        $new = !$destinfo['exists'];
        
        // if we copy a directory destination must be a directory !
        if ($new && $sourceinfo['isDir']) $destinfo['isDir']=1; //TODO
        
     		$this->tx_cbywebdav_devlog(2,"===@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ T3FileExists  new : $new dest : $dest destinfo : ".serialize($destinfo)."source info :  ".serialize($sourceinfo),"cby_webdav","COPY");
        
        // If we are renaming a webmount file new is true ..
        //if ($t3io->T3CheckFilePathRename($sourceinfo,$destinfo) && !$new) $new=1;
     		$this->tx_cbywebdav_devlog(2,"=== COPY  new : $new source : $source dest : $dest","cby_webdav","COPY");
     		
        $existing_col = false;  
				
				// le fichier dest existe déjà (Répertoire ou fichier)...
				// a valider ...;
				
        if (!$new) {
        	  //we move source into a collection ....
        	  $destold = $options["dest"];
            if ($move && $destinfo['isDir']) {
                if (!$options["overwrite"]) {
      			 			$this->tx_cbywebdav_devlog(2,"=== COPY error 412","cby_webdav","COPY");
                  return "412 precondition failed";
                }
                $dest = $dest.basename($source);
                $destinfo=$t3io->T3IsFile($dest);
                // if source does not exist
                if (!$destinfo['exists']) {
                    $options["dest"] .= basename($source);
                } else {
                    $new          = true;
                    $existing_col = true;
                }
            }
            
            //echo "<br>new : $new";

            if ($options["overwrite"]) {
                $stat = $this->DELETE(array("path" => $options["dest"]));
                $new=true;
                $existing_col = true;
	     			 		$this->tx_cbywebdav_devlog(1,"=== COPY DELETE overwrite $stat, $dest, $destold","cby_webdav","COPY");
                if (($stat{0} != "2") && (substr($stat, 0, 3) != "404")) {
	     			 			$this->tx_cbywebdav_devlog(1,"=== COPY overwrite $stat","cby_webdav","COPY");
                  return $stat; 
                }
            } else {
     			 			$this->tx_cbywebdav_devlog(2,"=== COPY error 412 2","cby_webdav","COPY");
                return "412 precondition failed";
            }
        }
        
        // fin a valider 
        				
				/*if ($new && $destinfo['isFilemount'] && $destinfo['isDir']) {
        	$this->MKCOL(array("path"=>$options["dest"]));
        	//$destinfo['pid']=$t3io->T3GetPID($options["dest"]);
     			$this->tx_cbywebdav_devlog(2,"=== MKCOL $options[dest]","cby_webdav","COPY");
				}*/
				
        if ($sourceinfo['isDir'] && ($options["depth"] != "infinity") && ($options["depth"] != "0")) {
            // RFC 2518 Section 9.2, last paragraph
     			 $this->tx_cbywebdav_devlog(2,"=== COPY error 400","cby_webdav","COPY");
           return "400 Bad request";
        }
        
				// We are moving the source
        if ($move) {
	    			$this->tx_cbywebdav_devlog(2,"=== COPY move".serialize($sourceinfo)."#######################","cby_webdav","COPY");
 						
 						if ($sourceinfo['isWebmount'] && $sourceinfo['isDir']) {
							$page['pid']=$destinfo['pid'];
							$page['title']=basename($dest);
							if ($destinfo['isWebmount']) $page['title']=$t3io->T3ExtractPageTitle($destinfo['uid'],$page['title']);
							$where=" uid='$sourceinfo[uid]' ";
	    				$this->tx_cbywebdav_devlog(2,"=== MOVE ".$this->CFG->T3DB->UPDATEquery('pages',$where,$page),"cby_webdav","COPY");
							$this->CFG->T3DB->exec_UPDATEquery('pages',$where,$page); 							
 						} elseif ($sourceinfo['isWebmount'] && $sourceinfo['isFile']) {
							//$page['pid']=$info['pid'];
							//TODO handle upload file rename ...
							$content['pid']=$destinfo['pid'];
							$content['header']=basename($dest);
							$where=" uid='$sourceinfo[uid]' ";
	    				$this->tx_cbywebdav_devlog(2,"=== MOVE ".$this->CFG->T3DB->UPDATEquery('tt_content',$where,$content),"cby_webdav","COPY");
							$this->CFG->T3DB->exec_UPDATEquery('tt_content',$where,$content); 							
 						} else { 		
 							// Filemount move !!!	(rename)	     				     	
	            if (!@rename(rtrim($source,'/'), rtrim($dest,'/'))) {
	    					 $this->tx_cbywebdav_devlog(2,"=== MOVE error 500 rename(".rtrim($source,'/').",". rtrim($dest,'/').")","cby_webdav","COPY");
	               return "500 Internal server error";
	            }
	          }
            $destpath = $this->_unslashify($options["dest"]);
            if ($sourceinfo['isDir']) {
                $query = "UPDATE {$this->db_prefix}properties   SET path = REPLACE(path, '".$options["path"]."', '".$destpath."') WHERE path LIKE '".$this->_slashify($options["path"])."%'";
                mysql_query($query);
            }

            $query = "UPDATE {$this->db_prefix}properties   SET path = '".$destpath."  WHERE path = '".$options["path"]."'";
            mysql_query($query);
        } else { 
        		// We are only copying ....
	    			$this->tx_cbywebdav_devlog(2,"=== COPY no move ".serialize($sourceinfo)."#######################","cby_webdav","COPY");
            
            if ($sourceinfo['isDir'] && $sourceinfo['isFilemount']) { 
            	
            	// FM DIR COPY
            	
							$source=$this->_slashify($source);
              $files = System::find($source);
              $files = array_reverse($files);
							$dest=$this->_slashify($dest);
             	// If empty we still copy collection
              if (!is_array($files) || empty($files)) {
								$files = array($source);
							}

	    				$this->tx_cbywebdav_devlog(2,"=== COPY fm ".serialize($files)."#######################","cby_webdav","COPY");

							// File Copy ---------------
							
	            foreach ($files as $file) {
								$fileinfo=$t3io->T3IsFile($file); 
								if ($fileinfo['isDir']) {
								    $file = $this->_slashify($file);
								}			
												
	              $destfile = str_replace($source, $dest, $file);
	                    
							 $this->tx_cbywebdav_devlog(2,"=== fileinfo  : $destfile".serialize($fileinfo),"cby_webdav","COPY");
								if ($fileinfo['isDir']) {
								  //if (!$t3io->T3IsDir($destfile)) {
								      // TODO "mkdir -p" here? (only natively supported by PHP 5) 
								      if (!@mkdir($destfile)) {
												$this->tx_cbywebdav_devlog(2,"=== COPY1 error 409 3 file $file destfile : $destfile","cby_webdav","COPY");
												return "409 Conflict 3";
								      }
								 // } 
								} else {
								  if (!@copy($file, $destfile)) {
										 $this->tx_cbywebdav_devlog(2,"=== COPY2 error 409 file $file destfile $destfile","cby_webdav","COPY");
								     return "409 Conflict $file $destfile";
								  }
								}
							}
						  $this->tx_cbywebdav_devlog(2,"=== COPY FM files : ".serialize($files),"cby_webdav","COPY");

							
						} else if ($sourceinfo['isFile'] && $sourceinfo['isFilemount']) {  // FILE COPY ....
						  	$file = $source;
						  	$files = array($source);
								$fileinfo=$t3io->T3IsFile($file); 
								if ($fileinfo['isDir']) {
								    $file = $this->_slashify($file);
								}			
												
	              $destfile = str_replace($source, $dest, $file);
	                    
								if ($fileinfo['isDir']) {
								  if (!$destinfo['isDir']) {
								      // TODO "mkdir -p" here? (only natively supported by PHP 5) 
								      if (!@mkdir($destfile)) {
											 $this->tx_cbywebdav_devlog(2,"=== COPY1 error 409 file $file destfile : $destfile","cby_webdav","COPY");
								       return "409 Conflict 1";
								      }
								  } 
								} else {
								  if (!@copy($file, $destfile)) {
										 $this->tx_cbywebdav_devlog(2,"=== COPY2 error 409 file $file destfile $destfile","cby_webdav","COPY");
								     return "409 Conflict $file $destfile";
								  }
								}
							
			    			$this->tx_cbywebdav_devlog(2,"=== COPY FM files : ".serialize($files),"cby_webdav","COPY");
					 							
            } else if ($sourceinfo['isDir'] && $sourceinfo['isWebmount']) { 
            	
	            	// WM DIR COPY
								
								$source=$this->_slashify($source);
		    				$this->tx_cbywebdav_devlog(2,"=== COPY WM $source #######################","cby_webdav","COPY");
	              $files=array();
	              $lvl=$this->T3Copy($source,$dest, $files,0); 
		    				$this->tx_cbywebdav_devlog(2,"=== COPY WM files : ".serialize($files),"cby_webdav","COPY");
	                           
						} else if ($sourceinfo['isFile'] && $sourceinfo['isWebmount']) {  // FILE COPY ....
						  $files = array();
	            $lvl=$this->T3Copy($source,$dest, $files,0); 
		    			$this->tx_cbywebdav_devlog(2,"=== COPY WM 2 files : ".serialize($files),"cby_webdav","COPY");
						} else {
		    			$this->tx_cbywebdav_devlog(2,"=== COPY ERROR RRRRRRRRRRRRRRRRRRRRR : ".serialize($files),"cby_webdav","COPY");
						} 	
               
            if (!is_array($files) || empty($files)) { // N...O FILES = ERROR
    					 $this->tx_cbywebdav_devlog(2,"=== COPY error 500 2","cby_webdav","COPY");
               return "500 Internal server error2";
            }
            
            // -------- 
                                   
						// ---
						
            $query = "INSERT INTO {$this->db_prefix}properties SELECT *  FROM {$this->db_prefix}properties WHERE path = '".$options['path']."'";
            $res=mysql_query($query);

        }
  			$this->tx_cbywebdav_devlog(1,"=== COPY end ".(($new && !$existing_col) ? "201 Created" : "204 No Content"),"cby_webdav","COPY");
        return ($new && !$existing_col) ? "201 Created" : "204 No Content";  
        //return "201 Created";      
    }
    
    /* Copy T3 Ressource ...
    */
    
    function  T3COPY($source,$dest,&$filearray,$level) {
			$this->tx_cbywebdav_devlog(3,"=== T3COPY > ".$dest." ".$source,"cby_webdav","COPY");
			$t3io=$this->CFG->t3io;
			$source=$this->_slashify($source);
    	$filearray[$level][$source][]=$source;
			$sourceinfo=$t3io->T3IsFile($source); 
			$lvl=$level;
			$destinfo=$t3io->T3IsFile($dest);
			$file = $this->_slashify($source);
										
			$fileinfo=$t3io->T3IsFile($file); 
			$new=!$destinfo['exists']; 						
			$this->tx_cbywebdav_devlog(2,"=== T3COPY file ".serialize($fileinfo)." files : ".serialize($files),"cby_webdav","COPY");
										
			//if ($fileinfo['isWebmount'] && $fileinfo['isDir']) { // Répertoire Webmount

			// we handle copy root rename here ....
			$page['title']=basename($file);
			// we handle copy root rename here ....
			$this->tx_cbywebdav_devlog(3,"=== T3COPY fs ".$file." ".$source,"cby_webdav","COPY");
			// if we are first dir to copy we take dest dir name
			if ($file==$source && $level==0 && $new){
				$this->tx_cbywebdav_devlog(3,"=== T3COPY f=s ".$file." ".$source,"cby_webdav","COPY");
				$page['title']=basename($dest);
			}

			$page['pid']=$destinfo['pid'];
			if ($destinfo['isWebmount']) $page['title']=$t3io->T3ExtractPageTitle($destinfo['uid'],$page['title']);
			//$where=" uid='$sourceinfo[uid]' ";
			$this->tx_cbywebdav_devlog(2,"=== T3COPY ".$this->CFG->T3DB->INSERTquery('pages',$page),"cby_webdav","COPY");
					
			$this->CFG->T3DB->exec_INSERTquery('pages',$page); 		// copie récursive à mettre en place ici ...???					
			$pid=$this->CFG->T3DB->sql_insert_id();	

			// we copy the page content ...
			
			$this->tx_cbywebdav_devlog(2,"=== T3COPY $source sinfo : ".serialize($sourceinfo)." #######################","cby_webdav","COPY");
			
			// We add the tt_content, could also be tt_news, users (vcf ...)
			$content['pid']=$pid;
			
			$enable=$GLOBALS['TSFE']->sys_page->enableFields('tt_content',-1,array('fe_group'=>1));
			$res=$this->CFG->T3DB->exec_SELECTquery('header','tt_content','pid='.intval($sourceinfo['uid']).$enable );
			if ($res) {
				while ($row=$this->CFG->T3DB->sql_fetch_assoc($res)) {
					$file=$source.$row['header'];										
					$fileinfo=$t3io->T3IsFile($file);
					$destinfo=$t3io->T3IsFile($dest.'/'.$row['header']);					
					//TODO handle upload file rename ...
					//$content['header']=basename($file);
					
					$where=" uid='$destinfo[uid]' ";
					if ($destinfo['isWebmount']) {
						if ($destinfo['uid']) {
							$this->CFG->T3DB->exec_UPDATEquery('tt_content',$where,$content); 
						 	$this->tx_cbywebdav_devlog(2,"=== COPY ".$this->CFG->T3DB->UPDATEquery('tt_content',$where,$content),"cby_webdav","COPY");
						} else {	
 							$content['header']=basename($file); 
							$this->CFG->T3DB->exec_INSERTquery('tt_content',$content); 	
						 	$this->tx_cbywebdav_devlog(2,"=== COPY ".$this->CFG->T3DB->INSERTquery('tt_content',$content),"cby_webdav","COPY");
						}
					}
				}
			}
			// We get the directories (pages)....(should be a recursive call here ...
			$enable=$GLOBALS['TSFE']->sys_page->enableFields('pages',-1,array('fe_group'=>1));
			$res=$this->CFG->T3DB->exec_SELECTquery('title','pages','pid='.intval($sourceinfo['uid']).$enable );
			if ($res) {
				while ($row=$this->CFG->T3DB->sql_fetch_assoc($res)) {
					$this->T3COPY($source.$row['title'].'/',$dest.'/'.$page['title'],$filearray,$level+1);
				}
			}
			
			$this->tx_cbywebdav_devlog(2,"=== T3COPY files:" .serialize($filearray)." #######################","cby_webdav","COPY");
    }

		/**
		* PROPPATCH method handler
		*
		* @param  array  general parameter passing array
		* @return bool   true on success
		*/
		
    function PROPPATCH(&$options) 
    {
  			$this->tx_cbywebdav_devlog(1,"=== PROPPATCH ".serialize($options),"cby_webdav","PROPPATCH");

        global $prefs, $tab;

        $msg  = "";
        $path = $options["path"];
        $dir  = dirname($path)."/";
        $base = basename($path);
            
        foreach ($options["props"] as $key => $prop) {
            if ($prop["ns"] == "DAV:") {
                $options["props"][$key]['status'] = "403 Forbidden";
            } else {
                if (isset($prop["val"])) {
                    $query = "REPLACE INTO {$this->db_prefix}properties 
                                           SET path = '$options[path]'
                                             , name = '$prop[name]'
                                             , ns= '$prop[ns]'
                                             , value = '$prop[val]'";
                } else {
                    $query = "DELETE FROM {$this->db_prefix}properties 
                                        WHERE path = '$options[path]' 
                                          AND name = '$prop[name]' 
                                          AND ns = '$prop[ns]'";
                }       
   						$this->tx_cbywebdav_devlog(3,"propatch query $query","cby_webdav","PROPPATCH");
              mysql_query($query);
            }
        }
 			$this->tx_cbywebdav_devlog(1,"=== PROPPATCH","cby_webdav","PROPPATCH");
                        
        return "";
    }


    /**
     * LOCK method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    function LOCK(&$options) 
    {
  			$this->tx_cbywebdav_devlog(1,"LOCK start : ".serialize($options),"cby_webdav",'LOCK');
				$t3io=$this->CFG->t3io;

        // get absolute fs path to requested resource
        $virtualpath = $this->base . $options["path"];

        // TODO recursive locks on directories not supported yet
        
        if ($t3io->T3IsDir($virtualpath) && !empty($options["depth"])) {
  					//$this->tx_cbywebdav_devlog(2,"LOCK end no directory support !","cby_webdav");
  					$ret=$this->LOCKDIR($virtualpath,$options);
            //return "409 Conflict";
 						$this->tx_cbywebdav_devlog(1,"LOCK end : $ret","cby_webdav",'LOCK');
            return $ret;
        }
        $ret =$this->LOCKRESSOURCE($options);
 				$this->tx_cbywebdav_devlog(1,"LOCK end : $ret","cby_webdav",'LOCK');
				return $ret;
        /*$options["timeout"] = time()+300; // 5min. hardcoded

        if (isset($options["update"])) { // Lock Update
          //$where = "WHERE path = '$options[path]' AND token = '$options[update]'";

          //$query = "SELECT owner, exclusivelock FROM {$this->db_prefix}locks $where";
          $res   = $this->CFG->T3DB->exec_SELECTquery('owner, exclusivelock','tx_cbywebdav_webdav_locks',"path = '$options[path]' AND token = '$options[update]'");
 					$this->tx_cbywebdav_devlog(1,"LOCK update query : ".$this->CFG->T3DB->SELECTquery('owner, exclusivelock','tx_cbywebdav_webdav_locks',"path = '$options[path]' AND token = '$options[update]'"),"cby_webdav",'LOCK');
         	if ($res) {
	 					$row   = $this->CFG->T3DB->sql_fetch_assoc($res);
          	//mysql_free_result($res);

	          if (is_array($row)) {
	          	$query = "UPDATE {$this->db_prefix}locks   SET expires = '$options[timeout]' , modified = ".time()."$where";
	            mysql_query($query);
	         		$arr=array('expires' => "'$options[timeout]'",'modified'=> time());
	         	  $res   = $this->CFG->T3DB->exec_UPDATEquery('tx_cbywebdav_webdav_locks',"path = '$options[path]' AND token = '$options[update]'",$arr);
	            $options['owner'] = $row['owner'];
	            $options['scope'] = $row["exclusivelock"] ? "exclusive" : "shared";
	            $options['type']  = $row["exclusivelock"] ? "write"     : "read";
	
	            return true;
	          } else {
	            return false;
	          }
	       	} else {
	 					$this->tx_cbywebdav_devlog(2,"LOCK : pb db","cby_webdav");
					}
	      }
            
        $query = "INSERT INTO {$this->db_prefix}locks
                        SET token   = '$options[locktoken]'
                          , path    = '$options[path]'
                          , created = ".time()."
                          , modified = ".time()."
                          , owner   = '$options[owner]'
                          , expires = '$options[timeout]'
                          , exclusivelock  = " .($options['scope'] === "exclusive" ? "1" : "0")
            ;
			$arr=array('token'=>"$options[locktoken]",'path'=>"$options[path]",'created' => time(),'expires' => "$options[timeout]",'modified'=> time(),'owner' => "$options[owner]",'exclusivelock'=> ($options['scope'] === "exclusive" ? "1" : "0"));
		  $res  = $this->CFG->T3DB->exec_INSERTquery('tx_cbywebdav_webdav_locks',$arr);
			$this->tx_cbywebdav_devlog(1,"LOCK query :".$this->CFG->T3DB->INSERTquery('tx_cbywebdav_webdav_locks',$arr),"cby_webdav","LOCK");
			if ($res) {
						$ret=$this->CFG->T3DB->sql_affected_rows() ? "200 OK" : "409 Conflict";
						$this->tx_cbywebdav_devlog(1,"LOCK end : $ret","cby_webdav","LOCK");
	        	return $ret;
			} else {
				$this->tx_cbywebdav_devlog(2,"LOCK : pb db2","cby_webdav");		
			}*/
    }
    
    function LOCKDIR($virtualpath,&$options) 
    {
 			$this->tx_cbywebdav_devlog(1,"LOCKDIR start : dir : $virtualpath ".serialize($options),"cby_webdav",'LOCK');
			$t3io=$this->CFG->t3io;
			$files=$t3io->T3ListDir($virtualpath);
			foreach($files as $file) {
 				$this->tx_cbywebdav_devlog(1,"LOCKDIR file : $file","cby_webdav",'LOCK');
				//$ret =$this->LOCKRESSOURCE($options);
			}
 			$ret =$this->LOCKRESSOURCE($options);
 			$this->tx_cbywebdav_devlog(1,"LOCKDIR end : $ret","cby_webdav",'LOCK');
 			return $ret;
  	}

    function LOCKRESSOURCE(&$options) 
    {
  			$this->tx_cbywebdav_devlog(1,"LOCKRESSOURCE start : ".serialize($options),"cby_webdav",'LOCK');
				$t3io=$this->CFG->t3io;

        $options["timeout"] = time()+300; // 5min. hardcoded

        if (isset($options["update"])) { // Lock Update
          $res   = $this->CFG->T3DB->exec_SELECTquery('owner, exclusivelock','tx_cbywebdav_webdav_locks',"path = '$options[path]' AND token = '$options[update]'");
 					$this->tx_cbywebdav_devlog(1,"LOCK update query : ".$this->CFG->T3DB->SELECTquery('owner, exclusivelock','tx_cbywebdav_webdav_locks',"path = '$options[path]' AND token = '$options[update]'"),"cby_webdav",'LOCK');
         	if ($res) {
	 					$row   = $this->CFG->T3DB->sql_fetch_assoc($res);
          	//mysql_free_result($res);

	          if (is_array($row)) {
	          	$query = "UPDATE {$this->db_prefix}locks   SET expires = '$options[timeout]' , modified = ".time()."$where";
	            mysql_query($query);
	         		$arr=array('expires' => "'$options[timeout]'",'modified'=> time());
	         	  $res   = $this->CFG->T3DB->exec_UPDATEquery('tx_cbywebdav_webdav_locks',"path = '$options[path]' AND token = '$options[update]'",$arr);
	            $options['owner'] = $row['owner'];
	            $options['scope'] = $row["exclusivelock"] ? "exclusive" : "shared";
	            $options['type']  = $row["exclusivelock"] ? "write"     : "read";
	
	            return true;
	          } else {
	            return false;
	          }
	       	} else {
	 					$this->tx_cbywebdav_devlog(2,"LOCKRESSOURCE : pb db","cby_webdav");
					}
	      }
        //$options["owner"]=$this->_SERVER["PHP_AUTH_USER"];
        $query = "INSERT INTO {$this->db_prefix}locks
                        SET token   = '$options[locktoken]'
                          , path    = '$options[path]'
                          , created = ".time()."
                          , modified = ".time()."
                          , owner   = '$options[owner]'
                          , expires = '$options[timeout]'
                          , exclusivelock  = " .($options['scope'] === "exclusive" ? "1" : "0")
            ;
			$arr=array('token'=>"$options[locktoken]",'path'=>"$options[path]",'created' => time(),'expires' => "$options[timeout]",'modified'=> time(),'owner' => "$options[owner]",'exclusivelock'=> ($options['scope'] === "exclusive" ? "1" : "0"));
		  $res  = $this->CFG->T3DB->exec_INSERTquery('tx_cbywebdav_webdav_locks',$arr);
			$this->tx_cbywebdav_devlog(1,"LOCK query :".$this->CFG->T3DB->INSERTquery('tx_cbywebdav_webdav_locks',$arr),"cby_webdav","LOCK");
			if ($res) {
						$ret=$this->CFG->T3DB->sql_affected_rows() ? "200 OK" : "409 Conflict";
						$this->tx_cbywebdav_devlog(1,"LOCKRESSOURCE end : $ret","cby_webdav","LOCK");
	        	return $ret;
			} else {
				$this->tx_cbywebdav_devlog(2,"LOCKRESSOURCE : pb db2","cby_webdav");		
			}
    }
    
    /**
     * UNLOCK method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
     
    function UNLOCK(&$options) 
    {
  			$this->tx_cbywebdav_devlog(1,"UNLOCK, PATH  : ".serialize($options),"cby_webdav",'UNLOCK');

        $query = "DELETE FROM {$this->db_prefix}locks WHERE path = '$options[path]' AND token = '$options[token]'";
                        
        mysql_query($query);
        $ret=mysql_affected_rows() ? "204 No Content" : "409 Conflict";
	  		$this->tx_cbywebdav_devlog(1,"UNLOCK, end : $ret, query :$query","cby_webdav",'UNLOCK');
        return $ret;
    }

		/**
		* checkLock() helper
		*
		* @param  string resource path to check for locks
		* @return bool   true on success
		*/
     
    function checkLock($path) 
    {
  			$this->tx_cbywebdav_devlog(1,"checklock in path $path","cby_webdav","checkLock");

        $result = false;
 				$this->tx_cbywebdav_devlog(1,"checklock : ".$this->CFG->T3DB->SELECTquery('owner, token, created, modified, expires, exclusivelock','tx_cbywebdav_webdav_locks',"path = '$path'"),"cby_webdav","checkLock");           
  	    $res = $this->CFG->T3DB->exec_SELECTquery('owner, token, created, modified, expires, exclusivelock','tx_cbywebdav_webdav_locks',"path = '$path'");

        if ($res) {
  				$this->tx_cbywebdav_devlog(1,"checklock : ok ,".$res,"cby_webdav","checkLock");
          $row = $this->CFG->T3DB->sql_fetch_assoc($res);
            //mysql_free_result($res);

            if ($row) {
                $result = array( "type"    => "write",
                                 "scope"   => $row["exclusivelock"] ? "exclusive" : "shared",
                                 "depth"   => 0,
                                 "owner"   => $row['owner'],
                                 "token"   => $row['token'],
                                 "created" => $row['created'],   
                                 "modified" => $row['modified'],   
                                 "expires" => $row['expires']
                                 );
            }
        } else {
 					$this->tx_cbywebdav_devlog(2,"checklock : pb db :".t3lib_div::view_array($result),"cby_webdav");
				}
 				$this->tx_cbywebdav_devlog(1,"checklock".serialize($result),"cby_webdav","checkLock");
        return $result;
    }


		/**
		* create database tables for property and lock storage
		*
		* @param  void
		* @return bool   true on success
		*/
    function create_database() 
    {
  			$this->tx_cbywebdav_devlog(1,"create_database","cby_webdav","create_database");

        // TODO
        return false;
    }
}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
?>