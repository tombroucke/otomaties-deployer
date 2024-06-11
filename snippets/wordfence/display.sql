INSERT INTO `{{ db_prefix }}wfconfig` (`name`, `val`, `autoload`)
VALUES
	('displayAutomaticBlocks', '1', 'yes'),
	('displayTopLevelBlocking', '1', 'yes'),
	('displayTopLevelLiveTraffic', '0', 'yes'),
	('displayTopLevelOptions', '1', 'yes'),
	('liveTraf_displayExpandedRecords', '0', 'no')
ON DUPLICATE KEY UPDATE
	`val` = VALUES(`val`),
	`autoload` = VALUES(`autoload`);
