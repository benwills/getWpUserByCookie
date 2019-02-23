# getWpUserByCookie()

Using only the user's WordPress cookie and a MySql connection (ie: not wp-load.php), check to see if a user is logged in to your WordPress install.

	$user = getWpUserByCookie();
	@returns array (
	  [id]    => 123
	  [name]  => logged_in_username
	  [email] => email@address.com
	  [caps]  => Array (
	      [administrator] => 1
	    )
	)
