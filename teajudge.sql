SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

CREATE DATABASE IF NOT EXISTS `teajudge` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `teajudge`;

CREATE TABLE IF NOT EXISTS `course` (
`cid` int(11) NOT NULL,
  `cname` varchar(256) COLLATE utf8_bin NOT NULL,
  `author` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


CREATE TABLE IF NOT EXISTS `folder` (
`fid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `fname` varchar(128) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `group` (
`gid` int(11) NOT NULL,
  `gname` varchar(128) COLLATE utf8_bin NOT NULL,
  `canCreateUser` tinyint(2) NOT NULL DEFAULT '0',
  `canCreateTask` tinyint(2) NOT NULL DEFAULT '0',
  `canGrant` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT IGNORE INTO `group` (`gid`, `gname`, `canCreateUser`, `canCreateTask`, `canGrant`) VALUES
(1, 'admin', 1, 1, 1),
(2, 'teacher', 0, 1, 0);

CREATE TABLE IF NOT EXISTS `lang` (
`lid` int(11) NOT NULL,
  `lcode` varchar(10) COLLATE utf8_bin NOT NULL,
  `lname` varchar(25) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT IGNORE INTO `lang` (`lid`, `lcode`, `lname`) VALUES
(1, 'c_cpp', 'C/C++'),
(2, 'python', 'Python');

CREATE TABLE IF NOT EXISTS `task` (
`tid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `fid` int(11) DEFAULT NULL,
  `tname` varchar(128) COLLATE utf8_bin NOT NULL,
  `torder` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `user` (
`uid` int(11) NOT NULL,
  `login` varchar(64) COLLATE utf8_bin NOT NULL,
  `password` varchar(130) COLLATE utf8_bin NOT NULL,
  `email` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `firstname` varchar(64) COLLATE utf8_bin NOT NULL,
  `lastname` varchar(64) COLLATE utf8_bin NOT NULL,
  `canCreateUser` tinyint(2) NOT NULL DEFAULT '0',
  `canCreateTask` tinyint(2) NOT NULL DEFAULT '0',
  `canGrant` tinyint(2) NOT NULL DEFAULT '0',
  `restoreToken` varchar(128) COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT IGNORE INTO `user` (`uid`, `login`, `password`, `email`, `firstname`, `lastname`, `canCreateUser`, `canCreateTask`, `canGrant`, `restoreToken`) VALUES
(1, 'admin', 'c3284d0f94606de1fd2af172aba15bf3', NULL, '', 'Administrator', 0, 0, 0, NULL);

CREATE TABLE IF NOT EXISTS `user_course` (
  `uid` int(11) DEFAULT NULL,
  `gid` int(11) DEFAULT NULL,
  `cid` int(11) NOT NULL,
  `fid` int(11) DEFAULT NULL,
  `startTime` int(10) unsigned NOT NULL,
  `endTime` int(10) unsigned NOT NULL,
  `clearTime` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `user_group` (
  `uid` int(11) NOT NULL,
  `gid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT IGNORE INTO `user_group` (`uid`, `gid`) VALUES
(1, 1);

CREATE TABLE IF NOT EXISTS `user_task` (
`utid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `tid` int(11) NOT NULL,
  `vid` int(11) NOT NULL,
  `passed` tinyint(3) unsigned NOT NULL,
  `time` varchar(600) COLLATE utf8_bin NOT NULL,
  `memory` varchar(600) COLLATE utf8_bin NOT NULL,
  `status` varchar(400) COLLATE utf8_bin NOT NULL,
  `lid` int(11) NOT NULL,
  `ranges` varchar(512) COLLATE utf8_bin NOT NULL,
  `submissionCode` text COLLATE utf8_bin NOT NULL,
  `history` mediumtext COLLATE utf8_bin NOT NULL,
  `startDate` int(10) unsigned NOT NULL,
  `submissionDate` int(10) unsigned NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `variant` (
`vid` int(11) NOT NULL,
  `tid` int(11) NOT NULL,
  `statement` text COLLATE utf8_bin NOT NULL,
  `testCount` tinyint(3) unsigned NOT NULL,
  `testNames` text COLLATE utf8_bin NOT NULL,
  `testData` text COLLATE utf8_bin NOT NULL,
  `testAnswer` text COLLATE utf8_bin NOT NULL,
  `publicity` varchar(528) COLLATE utf8_bin NOT NULL,
  `timeLimit` smallint(5) unsigned NOT NULL,
  `memoryLimit` tinyint(3) unsigned NOT NULL,
  `help` varchar(300) COLLATE utf8_bin NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `variant_lang` (
`vlid` int(11) NOT NULL,
  `tid` int(11) NOT NULL,
  `vid` int(11) NOT NULL,
  `lid` int(11) NOT NULL,
  `pattern` text COLLATE utf8_bin NOT NULL,
  `ranges` varchar(512) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE `course`
 ADD PRIMARY KEY (`cid`), ADD KEY `author` (`author`);

ALTER TABLE `folder`
 ADD PRIMARY KEY (`fid`), ADD KEY `cid` (`cid`);

ALTER TABLE `group`
 ADD PRIMARY KEY (`gid`);

ALTER TABLE `lang`
 ADD PRIMARY KEY (`lid`);

ALTER TABLE `task`
 ADD PRIMARY KEY (`tid`), ADD KEY `cid` (`cid`), ADD KEY `fid` (`fid`);

ALTER TABLE `user`
 ADD PRIMARY KEY (`uid`), ADD UNIQUE KEY `login` (`login`);

ALTER TABLE `user_course`
 ADD KEY `cid` (`cid`), ADD KEY `fid` (`fid`), ADD KEY `uid` (`uid`), ADD KEY `gid` (`gid`);

ALTER TABLE `user_group`
 ADD KEY `uid` (`uid`), ADD KEY `gid` (`gid`);

ALTER TABLE `user_task`
 ADD PRIMARY KEY (`utid`), ADD KEY `uid` (`uid`), ADD KEY `tid` (`tid`), ADD KEY `vid` (`vid`), ADD KEY `lid` (`lid`);

ALTER TABLE `variant`
 ADD PRIMARY KEY (`vid`), ADD KEY `tid` (`tid`);

ALTER TABLE `variant_lang`
 ADD PRIMARY KEY (`vlid`), ADD KEY `vid` (`vid`), ADD KEY `lid` (`lid`), ADD KEY `tid` (`tid`);


ALTER TABLE `course`
MODIFY `cid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `folder`
MODIFY `fid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `group`
MODIFY `gid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
ALTER TABLE `lang`
MODIFY `lid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
ALTER TABLE `task`
MODIFY `tid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `user`
MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
ALTER TABLE `user_task`
MODIFY `utid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `variant`
MODIFY `vid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `variant_lang`
MODIFY `vlid` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `course`
ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`author`) REFERENCES `user` (`uid`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `folder`
ADD CONSTRAINT `folder_ibfk_1` FOREIGN KEY (`cid`) REFERENCES `course` (`cid`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `task`
ADD CONSTRAINT `task_ibfk_1` FOREIGN KEY (`cid`) REFERENCES `course` (`cid`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `task_ibfk_2` FOREIGN KEY (`fid`) REFERENCES `folder` (`fid`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `user_course`
ADD CONSTRAINT `user_course_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `user_course_ibfk_2` FOREIGN KEY (`cid`) REFERENCES `course` (`cid`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `user_course_ibfk_3` FOREIGN KEY (`fid`) REFERENCES `folder` (`fid`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `user_course_ibfk_4` FOREIGN KEY (`gid`) REFERENCES `group` (`gid`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user_group`
ADD CONSTRAINT `user_group_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `user_group_ibfk_2` FOREIGN KEY (`gid`) REFERENCES `group` (`gid`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user_task`
ADD CONSTRAINT `user_task_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `user_task_ibfk_2` FOREIGN KEY (`tid`) REFERENCES `task` (`tid`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `user_task_ibfk_3` FOREIGN KEY (`vid`) REFERENCES `variant` (`vid`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `user_task_ibfk_4` FOREIGN KEY (`lid`) REFERENCES `lang` (`lid`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `variant`
ADD CONSTRAINT `variant_ibfk_1` FOREIGN KEY (`tid`) REFERENCES `task` (`tid`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `variant_lang`
ADD CONSTRAINT `variant_lang_ibfk_3` FOREIGN KEY (`tid`) REFERENCES `task` (`tid`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `variant_lang_ibfk_1` FOREIGN KEY (`vid`) REFERENCES `variant` (`vid`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `variant_lang_ibfk_2` FOREIGN KEY (`lid`) REFERENCES `lang` (`lid`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
