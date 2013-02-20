<?php
/*
 Plugin Name: Naughty Monkey
 Plugin URI: http://www.itsananderson.com/plugins/ban-master/
 Description: Automatically bans users who try to log in using the 'admin' username.
 Version: 1.0
 Author: Will Anderson
 Author URI: http://www.itsananderson.com/
 */

// should bad monkeys be punnished?
function nmonkey_enabled() {
	global $wpdb;
	// make sure there isn't a valid user in the database who's login name is 'admin'
	// otherwise they might get a nasty surprise when they log in
	$query = "SELECT COALESCE(COUNT(ID), 0) AS admin_exists FROM {$wpdb->users} WHERE user_login = 'admin'";
	$admin_exists = intval($wpdb->get_var($query));
	if ($admin_exists)
		return false;
	return true;
}

if ( nmonkey_enabled() ) {
	// hook into the authentication so we can look for naughty monkeys
	add_filter('authenticate', 'nmonkey_authentication_check', 0, 3);

	// run on the 'authenticate' filter
	function nmonkey_authentication_check($user = null, $username = null, $password = null) {
		if ( 'admin' == $username ) {
			// ban these bastards!
			$banned = nmonkey_get_banned_ip_list();
			// add the new ip
			$banned[] = $_SERVER['REMOTE_ADDR'];
			nmonkey_save_banned_ip_list($banned);
			wp_die('Bad! Bad monkey!'); // pretty much says it all
		}
	}

	// retrieve the list of banned monkeys
	function nmonkey_get_banned_ip_list() {
		include(plugin_dir_path(__FILE__) . 'ban-list.php');
		return $ban_list;
	}

	// save our new list of naughty monkeys
	// if I had more hair and ate bananas I think I'd be monkey santa
	function nmonkey_save_banned_ip_list($banned) {
		$file = fopen(plugin_dir_path(__FILE__) . 'ban-list.php', 'w');
		fwrite($file, '<?php $ban_list = array("' . implode($banned, "','") . '"); ?>');
		fclose($file);
	}

	// if this monkey is on the naughty list, deny his request
	$banned = nmonkey_get_banned_ip_list();
	if ( in_array($_SERVER['REMOTE_ADDR'], $banned) ) {
		wp_die("<p>Sorry little guy, looks like you've been a naughty monkey.</p>" .
				"<p>If you own this blog, you obviously did something very silly. " .
				"Check out <a href='http://www.itsananderson.com/plugins/naughty-monkey/owner-ban-fix/'>these instructions</a> on how to fix it and avoid this problem in the future.</p>" .
				"<p>Are you a legitimate, albeit curious visitor?" .
				"Now's the time to send a very appologetic email to the owner of this website asking them to remove you from the ban list." .
				"Can't find their email address? Well I don't know what it is, so I hope you can find them on Twitter!</p>");
	}
} else {
	add_action('admin_notices', 'nmonkey_admin_user_nag');

	// if the user hasn't changed the default user name yet, they're n00bs. let them know.
	function nmonkey_admin_user_nag() {
		echo "<div class='error default-password-nag'><p>" .
			"Hey, so umm... Naughty Monkey really can't do anything until you change the default admin user name to something besides 'admin'.</p>" .
			"<p>No seriously, you should <a href='http://millionclues.com/guest-posts/change-wordpress-default-username-3-ways/'>change it</a>.</p></div>";

	}
}
?>
