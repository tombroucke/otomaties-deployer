INSERT INTO `wp_wfconfig` (`name`, `val`, `autoload`)
VALUES 
	('bannedURLs', '/.env,/*.env,/wp-config.php*,/wp-config.txt,/*.old,/*.ini,/.git,/vendor*,/*.log,/wp-vcd.php,/xmlrpc.php,/admin*,/wanted/adm.php,/pvp*,/pvp.php,/admiinn,/adm.php,/*.temp,/*.bak,/adminer.php,/adminer*,/phpMyAdmin*,/pma,/*wp-config.php*,/*wlwmanifest.xml', 'yes')
ON DUPLICATE KEY UPDATE 
	`val` = VALUES(`val`), 
	`autoload` = VALUES(`autoload`);
