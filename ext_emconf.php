<?php

########################################################################
# Extension Manager/Repository config file for ext: "cby_webdav"
# 
# Auto generated 16-04-2008 18:47
# 
# Manual updates:
# Only the data in the array - anything else is removed by next write
########################################################################

$EM_CONF[$_EXTKEY] = Array (
	'title' => 'WEBDAV for typo3',
	'description' => 'WEBDAV and FTP Daemon for TYPO3 based on NanoFTPd & PEAR::WEBDAV package, allows direct upload and download to web pages',
	'category' => 'services',
	'shy' => 1,
	'version' => '0.0.6',	// Don't modify this! Managed automatically during upload to repository.
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'alpha',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => 'tt_content',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Christophe BALISKY',
	'author_email' => 'christophe@balisky.org',
	'author_company' => 'Christophe Balisky',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => 'Array',
	'_md5_values_when_last_written' => 'a:47:{s:35:"class.tx_metaftpd_renderFtpFile.php";s:4:"15f2";s:26:"class.tx_metaftpd_t3io.php";s:4:"3dfe";s:10:"config.php";s:4:"2c64";s:21:"ext_conf_template.txt";s:4:"128d";s:12:"ext_icon.gif";s:4:"5911";s:17:"ext_localconf.php";s:4:"5aee";s:14:"ext_tables.php";s:4:"c6e0";s:14:"ext_tables.sql";s:4:"6665";s:16:"locallang_db.php";s:4:"f200";s:11:"package.xml";s:4:"a5e0";s:10:"webdav.php";s:4:"46f8";s:30:"tests/tx_metaftpd_testcase.php";s:4:"98fc";s:16:"static/setup.txt";s:4:"65df";s:29:"sv1/class.tx_metaftpd_sv1.php";s:4:"b121";s:21:"WEBDAV/Filesystem.php";s:4:"73e5";s:17:"WEBDAV/Server.php";s:4:"e01b";s:14:"WEBDAV/Var.php";s:4:"be9b";s:26:"WEBDAV/_parse_lockinfo.php";s:4:"2965";s:26:"WEBDAV/_parse_propfind.php";s:4:"2ee5";s:27:"WEBDAV/_parse_proppatch.php";s:4:"b0f8";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"e6f2";s:14:"mod1/index.php";s:4:"06d7";s:18:"mod1/locallang.php";s:4:"b3d0";s:22:"mod1/locallang_mod.php";s:4:"5adf";s:19:"mod1/moduleicon.gif";s:4:"5911";s:18:"nanoftpd/ChangeLog";s:4:"5183";s:16:"nanoftpd/LICENSE";s:4:"fdaf";s:15:"nanoftpd/README";s:4:"d8c1";s:19:"nanoftpd/config.php";s:4:"8bce";s:21:"nanoftpd/nanoftpd.php";s:4:"d4c0";s:21:"nanoftpd/lib/auth.php";s:4:"f3ce";s:25:"nanoftpd/lib/db_mysql.php";s:4:"defe";s:25:"nanoftpd/lib/db_pgsql.php";s:4:"723d";s:24:"nanoftpd/lib/db_text.php";s:4:"356a";s:20:"nanoftpd/lib/log.php";s:4:"b044";s:21:"nanoftpd/lib/pool.php";s:4:"422a";s:26:"nanoftpd/docs/README.dynip";s:4:"5f44";s:26:"nanoftpd/docs/README.mysql";s:4:"72a9";s:26:"nanoftpd/docs/README.pgsql";s:4:"63e2";s:25:"nanoftpd/docs/README.text";s:4:"81e9";s:25:"nanoftpd/log/nanoftpd.log";s:4:"6166";s:28:"nanoftpd/modules/io_file.php";s:4:"d886";s:27:"nanoftpd/modules/io_ips.php";s:4:"121f";s:14:"doc/manual.sxw";s:4:"1500";s:19:"doc/wizard_form.dat";s:4:"a923";s:20:"doc/wizard_form.html";s:4:"19e3";}',
	'suggests' => 'Array',
);

?>