<?php

/** Initialize vars from extension conf */
$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it here:
$initVars = array('listen_addr','listen_port','low_port','high_port','max_conn','max_conn_per_ip','natural_file_names');
foreach($initVars as $var) {
  $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY][$var] = $_EXTCONF[$var] ? trim($_EXTCONF[$var]) : "";
}

?>