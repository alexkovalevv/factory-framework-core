<?php

namespace WBCR\Factory_000\Premium;

use Exception;
use Wbcr_Factory000_Plugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Выполняет проверку обновлений, обновления, скачивание плагинов
 *
 * @author Webcraftic <wordpress.webraftic@gmail.com>, Alex Kovalev <alex.kovalevv@gmail.com>
 * @link https://webcraftic.com
 * @copyright (c) 2018 Webraftic Ltd
 * @version 1.0
 */
class Manager {
	
	/**
	 * @var Wbcr_Factory000_Plugin
	 */
	protected $plugin;
	
	/**
	 * @var \WBCR\Factory_000\Premium\License\Provider
	 */
	protected $provider;
	
	/**
	 * @var array
	 */
	protected $settings;
	
	/**
	 * Manager constructor.
	 *
	 * @param Wbcr_Factory000_Plugin $plugin
	 * @param array $settings
	 *
	 * @throws Exception
	 */
	public function __construct( Wbcr_Factory000_Plugin $plugin, array $settings ) {
		
		$this->plugin   = $plugin;
		$this->settings = $settings;
		
		$provider_name = $this->get_setting( 'provider' );
		
		if ( 'freemius' == $provider_name ) {
			$this->provider = new \WBCR\Factory_Freemius_000\Licensing\Provider( $this->plugin, $settings );
		} else if ( 'codecanyon' == $provider_name ) {
			throw new Exception( 'Codecanyon provider is not supported!' );
		} else if ( 'templatemonster' == $provider_name ) {
			throw new Exception( 'Templatemonster provider is not supported!' );
		} else if ( 'other' == $provider_name ) {
			throw new Exception( 'Other provider is not supported!' );
		}
		//new Plugin_Updates_Manager( $this->plugin, $this->updates['premium'], true );
	}
	
	protected function get_setting( $name ) {
		return isset( $this->settings[ $name ] ) ? $this->settings[ $name ] : null;
	}
	
	public function has_premium() {
		return $this->plugin->has_premium() && $this->provider instanceof License\Provider;
	}
	
	public function has_paid_subscription() {
		return true;
	}
	
	public function get_license_provider_name() {
		return $this->get_setting( 'provider' );
	}
	
	public function get_license_key() {
		#sk_f=>}-5vuHp$3*wPQHxd(AD3<);1&i
	}
	
	public function get_secret_license_key() {
		#sk_f=>}-5vuHp$3******d(AD3<);1&i
	}
	
	public function get_license_expiration_time() {
	
	}
	
	public function get_count_active_sites() {
	
	}
	
	public function get_plan() {
	
	}
	
	public function is_install_premium() {
	
	}
	
	public function is_license_activate() {
	
	}
	
	public function is_lifetime() {
	
	}
	
	public function license_activate() {
	
	}
	
	public function license_deactivate() {
	
	}
	
	public function paid_subscription_cancel() {
	
	}
	
}