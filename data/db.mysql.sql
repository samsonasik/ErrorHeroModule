DROP TABLE IF EXISTS `log`;

CREATE TABLE `log` (
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` int(11) NOT NULL,
  `event` text NOT NULL,
  `url` varchar(2000) NOT NULL,
  `file` varchar(2000) NOT NULL,
  `line` int(11) NOT NULL,
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
