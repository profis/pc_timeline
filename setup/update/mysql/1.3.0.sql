CREATE TABLE `{prefix}plugin_timeline_days` (
  `controller` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `cpid` int(11) NOT NULL,
  `days` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`controller`,`cpid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `{prefix}plugin_timeline_index` (
  `controller` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `cpid` int(11) NOT NULL,
  `date` char(10) COLLATE utf8_unicode_ci NOT NULL,
  `counter` int(11) NOT NULL,
  PRIMARY KEY (`controller`,`cpid`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
