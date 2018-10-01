<?php
	/**
	 * Factory Plugin
	 *
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright (c) 2018, Webcraftic Ltd
	 *
	 * @package core
	 * @since 1.0.0
	 */

	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}

	if( defined('FACTORY_000_LOADED') ) {
		return;
	}
	define('FACTORY_000_LOADED', true);

	define('FACTORY_000_VERSION', '000');

	define('FACTORY_000_DIR', dirname(__FILE__));
	define('FACTORY_000_URL', plugins_url(null, __FILE__));

	load_plugin_textdomain('wbcr_factory_000', false, dirname(plugin_basename(__FILE__)) . '/langs');

	#comp merge
	require_once(FACTORY_000_DIR . '/includes/functions.php');
	require_once(FACTORY_000_DIR . '/includes/request.class.php');
	require_once(FACTORY_000_DIR . '/includes/base.class.php');

	require_once(FACTORY_000_DIR . '/includes/assets-managment/assets-list.class.php');
	require_once(FACTORY_000_DIR . '/includes/assets-managment/script-list.class.php');
	require_once(FACTORY_000_DIR . '/includes/assets-managment/style-list.class.php');

	require_once(FACTORY_000_DIR . '/includes/plugin.class.php');

	require_once(FACTORY_000_DIR . '/includes/activation/activator.class.php');
	require_once(FACTORY_000_DIR . '/includes/activation/update.class.php');
	#endcomp
