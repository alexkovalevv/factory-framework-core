<?php
/**
 * Factory Plugin
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>
 * @since         1.0.0
 * @package       core
 * @copyright (c) 2018, Webcraftic Ltd
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'FACTORY_000_LOADED' ) ) {
	return;
}

define( 'FACTORY_000_LOADED', true );

define( 'FACTORY_000_VERSION', '4.1.6' );

define( 'FACTORY_000_DIR', dirname( __FILE__ ) );
define( 'FACTORY_000_URL', plugins_url( null, __FILE__ ) );

load_plugin_textdomain( 'wbcr_factory_000', false, dirname( plugin_basename( __FILE__ ) ) . '/langs' );

#comp merge
require_once( FACTORY_000_DIR . '/includes/functions.php' );

require_once( FACTORY_000_DIR . '/includes/entities/class-factory-paths.php' );
require_once( FACTORY_000_DIR . '/includes/entities/class-factory-support.php' );

require_once( FACTORY_000_DIR . '/includes/class-factory-requests.php' );
require_once( FACTORY_000_DIR . '/includes/class-factory-options.php' );
require_once( FACTORY_000_DIR . '/includes/class-factory-plugin-base.php' );
require_once( FACTORY_000_DIR . '/includes/class-factory-migrations.php' );
require_once( FACTORY_000_DIR . '/includes/class-factory-notices.php' );

// ASSETS
require_once( FACTORY_000_DIR . '/includes/assets-managment/class-factory-assets-list.php' );
require_once( FACTORY_000_DIR . '/includes/assets-managment/class-factory-script-list.php' );
require_once( FACTORY_000_DIR . '/includes/assets-managment/class-factory-style-list.php' );

// PREMIUM
require_once( FACTORY_000_DIR . '/includes/premium/class-factory-license-interface.php' );
require_once( FACTORY_000_DIR . '/includes/premium/class-factory-provider-abstract.php' );
require_once( FACTORY_000_DIR . '/includes/premium/class-factory-manager.php' );

// UPDATES
require_once( FACTORY_000_DIR . '/includes/updates/repositories/class-factory-repository-abstract.php' );
require_once( FACTORY_000_DIR . '/includes/updates/repositories/class-factory-wordpress.php' );
require_once( FACTORY_000_DIR . '/includes/updates/class-factory-upgrader.php' );
require_once( FACTORY_000_DIR . '/includes/updates/class-factory-premium-upgrader.php' );

require_once( FACTORY_000_DIR . '/includes/class-factory-plugin-abstract.php' );

require_once( FACTORY_000_DIR . '/includes/activation/class-factory-activator.php' );
require_once( FACTORY_000_DIR . '/includes/activation/class-factory-update.php' );
#endcomp
