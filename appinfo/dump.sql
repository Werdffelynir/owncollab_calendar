SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";





CREATE TABLE IF NOT EXISTS `oc_collab_calendar` (
  `uid` varchar(255) NOT NULL,
  `id_tasks` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




ALTER TABLE `oc_collab_calendar`
  ADD PRIMARY KEY (`uid`);


