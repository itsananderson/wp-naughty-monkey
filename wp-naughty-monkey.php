<?php
/*
 Plugin Name: WP Naughty Monkey
 Plugin URI: http://www.itsananderson.com/plugins/naughty-monkey/
 Description: Automatically bans users who try to log in using the 'admin' username.
 Version: 1.0
 Author: Will Anderson
 Author URI: http://www.itsananderson.com/
 */

class Naughty_Monkey {
	public static function start() {
		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	public static function init() {
		if ( self::enabled() ) {
			add_filter('authenticate', array( __CLASS__, 'authentication_check' ), 1000 * 1000, 3);

			$banned = self::get_banned_ip_list();
			if ( in_array($_SERVER['REMOTE_ADDR'], $banned) ) {
				wp_die("<p>Sorry little guy, looks like you've been a naughty monkey.</p>" .
					"<p>If you own this blog, you obviously did something very silly. " .
					"Check out <a href='http://www.itsananderson.com/plugins/naughty-monkey/owner-ban-fix/'>these instructions</a> on how to fix it and avoid this problem in the future.</p>" .
					"<p>Are you a legitimate, albeit curious visitor?" .
					"Now's the time to send a very appologetic email to the owner of this website asking them to remove you from the ban list." .
					"Can't find their email address? Well I don't know what it is, so I hope you can find them on Twitter!</p>");
			}
		} else {
			add_action( 'admin_notices', array( __CLASS__, 'admin_user_nag' ) );
		}
	}

	public static function authentication_check($user = null, $username = null, $password = null) {
		if ( 'admin' == $username ) {
			// ban these bastards!
			$banned = self::get_banned_ip_list();
			// add the new ip
			$banned[] = $_SERVER['REMOTE_ADDR'];
			self::save_banned_ip_list($banned);
			wp_die('Bad! Bad monkey!'); // pretty much says it all
		}
		return $user;
	}

	public static function get_banned_ip_list() {
		include(plugin_dir_path(__FILE__) . 'ban-list.php');
		return $ban_list;
	}

	public static function save_banned_ip_list($banned) {
		$file = fopen(plugin_dir_path(__FILE__) . 'ban-list.php', 'w');
		fwrite($file, '<?php $ban_list = array(\'' . implode($banned, "','") . '\'); ?>');
		fclose($file);
	}

	// if the user hasn't changed the default user name yet, they're n00bs. let them know.
	public static function admin_user_nag() {
		echo '<div class="error default-password-nag"><p>' .
			"Hey, so umm... Naughty Monkey really can't do anything until you change the default admin user name to something besides 'admin'.</p>" .
			"<p>No seriously, you should <a href='http://millionclues.com/guest-posts/change-wordpress-default-username-3-ways/'>change it</a>.</p></div>";
	}

	public static function enabled() {
		global $wpdb;
		// make sure there isn't a valid user in the database who's login name is 'admin'
		// otherwise they might get a nasty surprise when they log in
		$query = "SELECT COALESCE(COUNT(ID), 0) AS admin_exists FROM {$wpdb->users} WHERE user_login = 'admin'";
		$admin_exists = intval($wpdb->get_var($query));
		if ($admin_exists)
			return false;
		return true;
	}
}

Naughty_Monkey::Start();