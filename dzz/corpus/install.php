<?php
/* @authorcode  c847417817641cfe67af4008fac750a0
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
if(!defined('IN_DZZ') || !defined('IN_ADMIN')) {
	exit('Access Denied');
}

$sql = <<<EOF


CREATE TABLE IF NOT EXISTS dzz_corpus (
  cid int(10) NOT NULL AUTO_INCREMENT,
  `name` char(80) NOT NULL DEFAULT '',
  uid int(10) NOT NULL DEFAULT '0' COMMENT '创建人',
  username char(30) NOT NULL DEFAULT '',
  perm tinyint(1) NOT NULL DEFAULT '0' COMMENT '权限：公开或私有',
  aid smallint(6) NOT NULL DEFAULT '0' COMMENT '封面背景图aid',
  documents smallint(10) NOT NULL DEFAULT '0' COMMENT '文档数',
  follows smallint(6) NOT NULL DEFAULT '0' COMMENT '关注数',
  members smallint(6) NOT NULL DEFAULT '0',
  hot smallint(6) NOT NULL COMMENT '热度',
  viewnum int(10) unsigned NOT NULL DEFAULT '0',
  titlehide tinyint(1) NOT NULL DEFAULT '0' COMMENT '背景图是否显示标题',
  forbidcommit tinyint(1) NOT NULL DEFAULT '0',
  archiveuid int(10) NOT NULL DEFAULT '0',
  archivetime int(10) NOT NULL DEFAULT '0',
  deleteuid int(10) NOT NULL DEFAULT '0',
  deletetime int(10) NOT NULL DEFAULT '0',
  dateline int(10) NOT NULL DEFAULT '0',
  forceindex tinyint(1) NOT NULL DEFAULT '0',
  forceindex1 tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (cid),
  KEY uid (uid),
  KEY dateline (dateline),
  KEY hot (hot),
  KEY archivetime (archivetime),
  KEY deletetime (deletetime)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS dzz_corpus_class (
  fid smallint(6) NOT NULL AUTO_INCREMENT,
  fname varchar(80) NOT NULL DEFAULT '',
  `type` enum('folder','file') NOT NULL DEFAULT 'folder',
  did int(10) NOT NULL DEFAULT '0' COMMENT '是文档时，记录此文档的did',
  cid int(10) unsigned NOT NULL DEFAULT '0',
  pfid smallint(6) NOT NULL DEFAULT '0',
  uid int(10) NOT NULL DEFAULT '0',
  username char(30) NOT NULL DEFAULT '',
  disp smallint(6) NOT NULL DEFAULT '0',
  dateline int(10) NOT NULL DEFAULT '0',
  deletetime int(10) NOT NULL DEFAULT '0',
  deleteuid int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (fid),
  KEY cid (cid),
  KEY disp (disp)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS dzz_corpus_event (
  eid int(10) NOT NULL AUTO_INCREMENT,
  uid int(10) unsigned NOT NULL DEFAULT '0',
  username char(30) NOT NULL DEFAULT '',
  body_template varchar(30) NOT NULL DEFAULT '',
  body_data text NOT NULL,
  bz varchar(80) NOT NULL DEFAULT '',
  dateline int(11) NOT NULL,
  PRIMARY KEY (eid),
  KEY uid (uid),
  KEY dateline (dateline),
  KEY bz (bz)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS dzz_corpus_setting (
  skey varchar(255) NOT NULL DEFAULT '',
  svalue text NOT NULL,
  PRIMARY KEY (skey)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS dzz_corpus_user (
  id int(10) NOT NULL AUTO_INCREMENT,
  cid int(10) unsigned NOT NULL DEFAULT '0',
  uid int(10) unsigned NOT NULL DEFAULT '0',
  username char(30) NOT NULL DEFAULT '',
  perm tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:关注；2：:普通成员;3：管理员',
  hot int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户活跃度',
  dnum int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建文档数',
  dateline int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  UNIQUE KEY cid_uid (cid,uid),
  KEY uid (uid),
  KEY cid (cid),
  KEY hot (hot),
  KEY dateline (dateline),
  KEY perm (perm)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


EOF;

runquery($sql);


$finish = true;
