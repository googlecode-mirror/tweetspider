<?php
/*
 * tweetspider
 * Created on Apr 20, 2010
 * Author : nb
 * Email  : bharadhwaj.n@gmail.com
 * Twitter: @bharadhwaj
 */
 include("databaseConfig.php");
$dbHandle = mysql_connect($host, $usr, $pwd)or die(mysql_error());
 
mysql_set_charset('utf8_unicode_ci',$dbHandle);
echo "Connected to MySQL<br />";


mysql_select_db($db) or die(mysql_error());
echo "Connected to $db<br/>";
 
 $status_sql = "CREATE TABLE IF NOT EXISTS statustimeline (
  `statusID` varchar(20) collate utf8_bin NOT NULL COMMENT 'unique status id for each tweet',
  `userID` varchar(20) collate utf8_bin NOT NULL COMMENT 'userID maps to user profile',
  `description` varchar(150) character set utf8 collate utf8_unicode_ci NOT NULL,
  `hyperlink` varchar(200) collate utf8_bin default NULL COMMENT 'hyperlink, null if absent',
  `creationTime` datetime NOT NULL default '0000-00-00 00:00:00' COMMENT 'timestamp for the tweet',
  `replytosid` varchar(20) collate utf8_bin default NULL COMMENT 'the status id to which current tweet is a reply',
  `replytouid` varchar(20) collate utf8_bin default NULL COMMENT 'the user id to which current user replied',
  `tweetsource` varchar(30) collate utf8_bin default NULL,
  PRIMARY KEY  (`statusID`),
  KEY `userID` (`userID`,`replytosid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";


$profile_sql = "CREATE TABLE IF NOT EXISTS userprofile (
  `userID` varchar(20) collate utf8_bin NOT NULL,
  `username` varchar(20) collate utf8_bin NOT NULL COMMENT 'screen name of user',
  `description` varchar(200) collate utf8_bin default NULL COMMENT 'description text of user, possible occupation can be identified',
  `location` varchar(60) collate utf8_bin default NULL COMMENT 'location of user',
  `webpage` varchar(200) collate utf8_bin default NULL COMMENT 'webpage if any for the user',
  `followers` varchar(10) collate utf8_bin NOT NULL default '0' COMMENT 'authority count',
  PRIMARY KEY  (`userID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='basic user profiling';";

 mysql_query($status_sql) or die(mysql_error());
 mysql_query($profile_sql) or die(mysql_error());
 echo "You are now all set to run run.php";
?>
