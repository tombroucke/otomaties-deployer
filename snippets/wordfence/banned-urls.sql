INSERT INTO `{{ db_prefix }}wfconfig` (`name`, `val`, `autoload`)
VALUES 
	('bannedURLs', '/*.sql,/.env,/*.env,/wp-config.php*,/wp-config.txt,/*.old,/*.ini,/.git,/vendor*,/*.log,/wp-vcd.php,/xmlrpc.php,/admin*,/wanted/adm.php,/pvp*,/pvp.php,/admiinn,/adm.php,/*.temp,/*.bak,/adminer.php,/adminer*,/phpMyAdmin*,/pma,/*wp-config.php*,/*wlwmanifest.xml,/installer.php,/.env.local,/.aws/*,/_cat/indices,/api/php.php,/.env.bak,/.env.php,/.env.backup,/.env.prod,/.env.test,/.env.dev,/.env.production,/.env.staging,/telescope/requests,/.vscode,/pma,/PMA,/swagger,/recentservers.xml,/sftp-config.json,/aws.json,/aws.yml,/config/default.json,/filezilla.xml,/FileZilla.xml,/sitemanager.xml,/app.ini,/db.zip,/production.ini,/config.ini,/database.tar.gz,/database.sql.gz,/.docker,/db.sql.gz,/Dockerfile*', 'yes')
ON DUPLICATE KEY UPDATE 
	`val` = VALUES(`val`), 
	`autoload` = VALUES(`autoload`);
