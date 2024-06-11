INSERT INTO `{{ db_prefix }}wfconfig` (`name`, `val`, `autoload`)
VALUES
	('notification_blogHighlights', '0', 'yes'),
	('notification_productUpdates', '0', 'yes'),
	('notification_promotions', '0', 'yes'),
	('notification_scanStatus', '0', 'yes'),
	('notification_securityAlerts', '1', 'yes'),
	('notification_updatesNeeded', '0', 'yes')
ON DUPLICATE KEY UPDATE
	`val` = VALUES(`val`),
	`autoload` = VALUES(`autoload`);
