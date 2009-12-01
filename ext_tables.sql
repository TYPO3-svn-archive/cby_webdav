#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	tx_cbywebdav_file blob NOT NULL
);

CREATE TABLE tx_cbywebdav_locks (
  token varchar(255) NOT NULL default '',
  path varchar(200) NOT NULL default '',
  expires int(11) NOT NULL default '0',
  owner varchar(200) default '',
  recursive int(11) default '0',
  writelock int(11) default '0',
  created int(11) default '0',
  modified int(11) default '0',
  exclusivelock int(11) NOT NULL default '0',
 
  PRIMARY KEY (token),
  UNIQUE KEY token (token),
  KEY path (path),
  KEY path_2 (path),
  KEY path_3 (path,token),
  KEY expires (expires)
);


CREATE TABLE tx_cbywebdav_properties (
  path varchar(255) NOT NULL default '',
  name varchar(120) NOT NULL default '',
  ns varchar(120) NOT NULL default 'DAV:',
  created int(11) default '0',
  modified int(11) default '0',
  value text,
  PRIMARY KEY (path,name,ns),
  KEY path (path)
);