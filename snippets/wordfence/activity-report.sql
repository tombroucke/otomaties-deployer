INSERT INTO `wp_wfconfig` (`name`, `val`, `autoload`)
VALUES
	('email_summary_dashboard_widget_enabled', '0', 'yes'),
	('email_summary_enabled', '', 'yes')
ON DUPLICATE KEY UPDATE
	`val` = VALUES(`val`),
	`autoload` = VALUES(`autoload`);
