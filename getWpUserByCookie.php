<?php
define('WP_SITEURL',
       'https://ov8.lofi.ltd');
define('LOGGED_IN_KEY',
       '1xUx{HKrXK3(<->=cy`zPT8|9YvRRKYM2KNtQ}s^(hV+.@mYkFsX-~+d~X}(q^OT');
define('LOGGED_IN_SALT',
       'dg(Vn~I>x%#/]LY(F-ibX/b<~}0v(-qBb1+uqXL+y5tW.#OWGB5]8))P=A,#Dtk|');

define('DB_HOST', "localhost");
define('DB_USER', "root");
define('DB_PASS', "wills1");
define('DB_NAME', "ov8_lofi_ltd");

//======================================b========================================
class WpApp
{
	private $user      = null;
	private $hasAccess = false;
	private $urlBase   = '';

	//----------------------------------------------------------------------------
	public function __construct(string $urlBase)
	{
		$this->user    = $this->getWpUserByCookie();
		print_r($this->user);
		$this->urlBase = $urlBase;
	}

	//------------------------------------------------------------
	// render our html
	//
	public function Render()
	{
		header("content-type:application/json");
		// echo json_encode($json);
		// echo print_r($_POST, 1);
		echo print_r($_COOKIE, 1);
		// echo print_r($this, 1);
	}

	//------------------------------------------------------------
	// public utils
	//
	public function UserCheckCaps(array $caps = null) : bool {
		if (count(array_intersect_key($this->user['caps'], $caps)))
			return true;
		return false;
	}
	public function UserId() : ?string {
		return isset($this->user['email']) ? $this->user['email'] : NULL;
	}
	public function UserCaps() : ?string {
		return isset($this->user['caps']) ? $this->user['caps'] : NULL;
	}
	public function UserEmail() : ?string {
		return isset($this->user['email']) ? $this->user['email'] : NULL;
	}
	public function UserName() : ?string {
		return isset($this->user['name']) ? $this->user['name'] : NULL;
	}
	public function UserUsername() : ?string {
		return isset($this->user['username']) ? $this->user['username'] : NULL;
	}

	//------------------------------------------------------------
	// private utils
	//
	private function getWpUserByCookie() : ?array
	{
		// https://github.com/benwills/getWpUserByCookie
		// parse our cookie data
		$cookiename = "wordpress_logged_in_".md5(WP_SITEURL);
		if (empty($_COOKIE[$cookiename]))
			return null;

		list($username,$expires,$token,$hmac) = explode('|',$_COOKIE[$cookiename]);

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

		if (   !$user
		    || !isset($user)
		    || !is_array($user)
		    || (0 == count($user)))
			return null;

		$key_data = "$username|".substr($user['pass'],8,4)."|$expires|$token";
		$key      = hash_hmac('md5',$key_data,LOGGED_IN_KEY.LOGGED_IN_SALT);
		$hash     = hash_hmac('sha256',"$username|$expires|$token",$key);
		if (!hash_equals($hash,$hmac))
			return null;

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
}


//==============================================================================
//
// Init
//

$timer = microtime(true);
$WpApp = new WpApp('/app/');
$timer_diff = microtime(true) - $timer;

if (!$WpApp->UserCheckCaps(['Administrator'])) {
	header("Location: /wp-admin/wp-login.php",TRUE,302);
	exit;
}
$WpApp->Render();

echo "active plugins:\n";
$active_plugins = get_option('active_plugins');
print_r($active_plugins);

echo "WpApp timer: $timer_diff\n";

