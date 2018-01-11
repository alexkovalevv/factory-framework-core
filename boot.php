<?php
	/**
	 * Factory Plugin
	 *
	 * @author Paul Kashtanoff <paul@byonepress.com>, Webcraftic <wordpress.webraftic@gmail.com>
	 * @copyright (c) 2013, OnePress Ltd, (c) 2017 Webcraftic Ltd
	 *
	 * @package core
	 * @since 1.0.1
	 */

	if( defined('FACTORY_000_LOADED') ) {
		return;
	}
	define('FACTORY_000_LOADED', true);

	define('FACTORY_000_VERSION', '000');

	define('FACTORY_000_DIR', dirname(__FILE__));
	define('FACTORY_000_URL', plugins_url(null, __FILE__));

	#comp merge
	require(FACTORY_000_DIR . '/includes/assets-managment/assets-list.class.php');
	require(FACTORY_000_DIR . '/includes/assets-managment/script-list.class.php');
	require(FACTORY_000_DIR . '/includes/assets-managment/style-list.class.php');

	require(FACTORY_000_DIR . '/includes/functions.php');
	require(FACTORY_000_DIR . '/includes/plugin.class.php');

	require(FACTORY_000_DIR . '/includes/activation/activator.class.php');
	require(FACTORY_000_DIR . '/includes/activation/update.class.php');
	#endcomp
