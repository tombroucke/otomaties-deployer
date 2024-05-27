INSERT INTO `wp_wfconfig` (`name`, `val`, `autoload`)
VALUES
	('loginSec_maxFailures', '{{ max_login_failures:3 }}', 'yes'),
	('loginSec_maxForgotPasswd', '{{ max_forgot_password:5 }}', 'yes'),
	('loginSec_userBlacklist', 'admin\nadministrator\nwebmaster\neditor\nwpadmin\nwwwadmin\nwpenginesupport\nitsme\nhostingadmin\ninfo-bold-themes-com\n{{ domain }}\n{{ domain_extension }}', 'yes')
ON DUPLICATE KEY UPDATE
	`val` = VALUES(`val`),
	`autoload` = VALUES(`autoload`);
