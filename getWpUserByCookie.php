<?php
//==============================================================================
// Using only the user's WordPress cookie and a MySql connection
// (ie: not wp-load.php), check to see if a user is
// logged in to your WordPress install.
//
// $user = getWpUserByCookie();
// @returns array (
//   [id]    => 123
//   [name]  => logged_in_username
//   [email] => email@address.com
//   [caps]  => Array (
//       [administrator] => 1
//     )
// )
//

//==============================================================================
// copy and paste these values instead of
// reading+parsing (ie: slower) from wp-config.php
define('WP_SITEURL',
       'https://www.domain.com');
define('LOGGED_IN_KEY',
       '0000000000000000000000000000000000000000000000000000000000000000');
define('LOGGED_IN_SALT',
       '0000000000000000000000000000000000000000000000000000000000000000');

define('DB_HOST', "localhost");
define('DB_USER', "username");
define('DB_PASS', "password");
define('DB_NAME', "database");

function getWpUserByCookie() : ?array
{
	//------------------------------------------------------------
	// parse our cookie data
	$cookiename = "wordpress_logged_in_".md5(WP_SITEURL);
	if (empty($_COOKIE[$cookiename]))
		return null;

	list($username,$expires,$token,$hmac) = explode('|',$_COOKIE[$cookiename]);

	//------------------------------------------------------------
	// grab our relevant user data from the database
	$conn = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
	$query = "SELECT caps.meta_value       as caps,
	                 sess.meta_value       as sess,
	                 wp_users.user_pass    as pass,
	                 wp_users.display_name as name,
	                 wp_users.user_email   as email,
	                 wp_users.ID           as id
	          FROM wp_users
	          	LEFT JOIN wp_usermeta as sess ON sess.user_id = wp_users.ID
	          		AND sess.meta_key = 'session_tokens'
	          	LEFT JOIN wp_usermeta as caps ON caps.user_id = wp_users.ID
	          		AND caps.meta_key = 'wp_capabilities'
	          WHERE user_login = '$username' LIMIT 1";
	$result = mysqli_query($conn,$query);
	$user   = mysqli_fetch_array($result,MYSQLI_ASSOC);

	if (empty($user))
		return null;

	$key_data  = "$username|".substr($user['pass'],8,4)."|$expires|$token";
	$key       = hash_hmac('md5',$key_data,LOGGED_IN_KEY.LOGGED_IN_SALT);
	$hash      = hash_hmac('sha256',"$username|$expires|$token",$key);
	if (!hash_equals($hash,$hmac))
		return null;

	//------------------------------------------------------------
	// check the keys in the database against the cookie.
	$token_hash = hash('sha256',$token);
	$sess       = unserialize($user['sess']);
	if (!isset($sess[$token_hash]))               return null;
	if (!isset($sess[$token_hash]['expiration'])) return null;
	if ($sess[$token_hash]['expiration'] > time()){
		return [
			'id'       => $user['id'],
			'name'     => $user['name'],
			'email'    => $user['email'],
			'username' => $username,
			'caps'     => unserialize($user['caps']),
		];
	}
	return null;
}

//==============================================================================
// example
if (0) {
	echo "<pre>";
	$timer = microtime(true);
	$user  = getWpUserByCookie();
	$tdiff = microtime(true) - $timer;
	echo "$tdiff\n";
	if (null != $user) echo print_r($user,1);
	else               echo "not validatedl";
}
