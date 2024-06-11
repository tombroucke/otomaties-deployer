INSERT INTO `{{ db_prefix }}wfconfig` (`name`, `val`, `autoload`) VALUES
	('other_hideWPVersion', '1', 'yes'),
	('disableCodeExecutionUploads', '1', 'yes'),
	('disableCodeExecutionUploadsPHP7Migrated', '1', 'yes'),
	('autoUpdate', '1', 'yes'),
	('liveActivityPauseEnabled', '1', 'yes'),
	('other_WFNet', '1', 'yes')
ON DUPLICATE KEY UPDATE
	`val` = VALUES(`val`),
	`autoload` = VALUES(`autoload`);
