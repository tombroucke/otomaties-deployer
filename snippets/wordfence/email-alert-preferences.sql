INSERT INTO `{{ db_prefix }}wfconfig` (`name`, `val`, `autoload`)
VALUES
	('alertEmails', '{{ alert_email:tom@tombroucke.be }}', 'yes'),
	('alertOn_adminLogin', '0', 'yes'),
	('alertOn_block', '0', 'yes'),
	('alertOn_breachLogin', '0', 'yes'),
	('alertOn_firstAdminLoginOnly', '0', 'yes'),
	('alertOn_firstNonAdminLoginOnly', '0', 'yes'),
	('alertOn_loginLockout', '0', 'yes'),
	('alertOn_lostPasswdForm', '0', 'yes'),
	('alertOn_nonAdminLogin', '0', 'yes'),
	('alertOn_scanIssues', '1', 'yes'),
	('alertOn_severityLevel', '75', 'yes'),
	('alertOn_throttle', '0', 'yes'),
	('alertOn_update', '0', 'yes'),
	('alertOn_wafDeactivated', '1', 'yes'),
	('alertOn_wordfenceDeactivated', '1', 'yes'),
	('alert_maxHourly', '0', 'yes'),
	('notification_securityAlerts', '1', 'yes'),
	('wafAlertInterval', '600', 'yes'),
	('wafAlertOnAttacks', '0', 'yes'),
	('wafAlertThreshold', '100', 'yes'),
	('wafAlertWhitelist', '', 'yes')
ON DUPLICATE KEY UPDATE
	`val` = VALUES(`val`),
	`autoload` = VALUES(`autoload`);
