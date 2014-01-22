-- --------------------------------------------------------

-- 
-- テーブルの構造 `login_external_user_mapping`
-- 

CREATE TABLE `login_external_user_mapping` (
  `external_id`        varchar(256) NOT NULL,
  `user_id`            varchar(40) NOT NULL DEFAULT '',
  `eppn_flag`          tinyint(3) NOT NULL DEFAULT '1',
  `active_flag`        tinyint(3) NOT NULL DEFAULT '1',
  `entityid`           varchar(256) NOT NULL,
  `auth_type`          tinyint(3) NOT NULL DEFAULT '1',
  `insert_time`        varchar(14) NOT NULL DEFAULT '',
  `insert_user_id`     varchar(40) NOT NULL DEFAULT '',
  `insert_user_name`   varchar(255) NOT NULL DEFAULT '',
  `update_time`        varchar(14) NOT NULL DEFAULT '',
  `update_user_id`     varchar(40) NOT NULL DEFAULT '',
  `update_user_name`   varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`external_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM;

