<?php

namespace WBCR\Factory_000\Premium;

use Exception;
use Wbcr_Factory000_Plugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @author        Webcraftic <wordpress.webraftic@gmail.com>, Alex Kovalev <alex.kovalevv@gmail.com>
 * @link          https://webcraftic.com
 * @copyright (c) 2018 Webraftic Ltd
 * @version       1.0
 */
class Manager {

	/**
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  4.1.6
	 * @var array
	 */
	public static $providers;

	/**
	 * @var Wbcr_Factory000_Plugin
	 */
	protected $plugin;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * Manager constructor.
	 *
	 * @param Wbcr_Factory000_Plugin $plugin
	 * @param array                  $settings
	 *
	 * @throws Exception
	 */
	public function __construct( Wbcr_Factory000_Plugin $plugin, array $settings ) {
		$this->plugin   = $plugin;
		$this->settings = $settings;
	}

	/**
	 * @param Wbcr_Factory000_Plugin $plugin
	 * @param array                  $settings
	 *
	 * @return \WBCR\Factory_Freemius_000\Premium\Provider
	 * @throws Exception
	 */
	public static function instance( Wbcr_Factory000_Plugin $plugin, array $settings ) {
		$premium_manager = new Manager( $plugin, $settings );

		return $premium_manager->instance_provider();
	}

	/**
	 * @param $provider_name
	 *
	 * @return \WBCR\Factory_Freemius_000\Premium\Provider
	 * @throws Exception
	 */
	public function instance_provider() {
		$provider_name = $this->get_setting( 'provider' );

		if ( isset( self::$providers[ $provider_name ] ) ) {
			if ( self::$providers[ $provider_name ] instanceof Provider ) {
				throw new Exception( "Provider {$provider_name} must extend the class WBCR\Factory_000\Premium\Provider interface!" );
			}

			return new self::$providers[ $provider_name ]( $this->plugin, $this->settings );
		}

		throw new Exception( "Provider {$provider_name} is not supported!" );
	}

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	protected function get_setting( $name ) {
		return isset( $this->settings[ $name ] ) ? $this->settings[ $name ] : null;
	}
}