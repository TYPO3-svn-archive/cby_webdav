<?
require_once "WEBDAV/Filesystem.php";
$webDav = new HTTP_WebDAV_Server_Filesystem;
require_once str_replace('//','/', str_replace('\\','/', $_SERVER['DOCUMENT_ROOT']))."/typo3conf/ext/cby_webdav/config.php";
$webDav->ServeRequest($WebDav_Dir_Root,$CFG);
?>
