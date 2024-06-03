INSERT INTO `wp_wfconfig` (`name`, `val`, `autoload`)
VALUES 
	('scansEnabled_checkGSB', '1', 'yes'),
	('scansEnabled_checkHowGetIPs', '1', 'yes'),
	('scansEnabled_checkReadableConfig', '1', 'yes'),
	('scansEnabled_comments', '1', 'yes'),
	('scansEnabled_core', '1', 'yes'),
	('scansEnabled_coreUnknown', '1', 'yes'),
	('scansEnabled_diskSpace', '1', 'yes'),
	('scansEnabled_fileContents', '1', 'yes'),
	('scansEnabled_fileContentsGSB', '1', 'yes'),
	('scansEnabled_geoipSupport', '1', 'yes'),
	('scansEnabled_highSense', '1', 'yes'),
	('scansEnabled_malware', '1', 'yes'),
	('scansEnabled_oldVersions', '1', 'yes'),
	('scansEnabled_options', '1', 'yes'),
	('scansEnabled_passwds', '1', 'yes'),
	('scansEnabled_plugins', '1', 'yes'),
	('scansEnabled_posts', '1', 'yes'),
	('scansEnabled_scanImages', '1', 'yes'),
	('scansEnabled_suspectedFiles', '1', 'yes'),
	('scansEnabled_suspiciousAdminUsers', '1', 'yes'),
	('scansEnabled_suspiciousOptions', '1', 'yes'),
	('scansEnabled_themes', '1', 'yes'),
	('scansEnabled_wafStatus', '1', 'yes'),
	('scansEnabled_wpscan_directoryListingEnabled', '1', 'yes'),
	('scansEnabled_wpscan_fullPathDisclosure', '1', 'yes'),
	('scanType', 'highsensitivity', 'yes'),
	('scan_exclude', '', 'yes'),
	('scan_force_ipv4_start', '0', 'yes'),
	('scan_include_extra', '', 'yes'),
	('scan_maxDuration', '', 'yes'),
	('scan_maxIssues', '1000', 'yes'),
	('scan_max_resume_attempts', '2', 'yes')
ON DUPLICATE KEY UPDATE
	`val` = VALUES(`val`),
	`autoload` = VALUES(`autoload`);