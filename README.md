# getWpUserByCookie()

Using only the user's WordPress cookie and a MySql connection (ie: not wp-load.php), check to see if a user is logged in to your WordPress install.

## Background

I wrote this because I like WordPress as a CMS, some of the plugins available for it, and its basic access control. Using it allows me to focus my time on writing applications, rather than the support structure around those applications.

You would normally authenticate to WordPress, from _outside_ of WordPress, by including wp-load.php. The problem is, I don't like how slow it is, and I'm sure there's a ton of stuff in there I simply don't need. All I need is what's returned by this function, which was designed to authenticate as quickly as possible.

## Performance

As a performance comparison, on my machine, it took about 0.03 seconds to authenticate via including wp-config.php. getWpUserByCookie() takes about 0.0012 seconds. I can now authenticate from 33 times per second, to 833 times per second.

	$user = getWpUserByCookie();
	@returns array (
	  [id]    => 123
	  [name]  => logged_in_username
	  [email] => email@address.com
	  [caps]  => Array (
	      [administrator] => 1
	    )
	)
