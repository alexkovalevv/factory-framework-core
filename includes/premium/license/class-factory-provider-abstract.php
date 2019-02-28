<?php

namespace WBCR\Factory_000\Premium\License;

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
abstract class Provider {
	
	/**
	 * @var Wbcr_Factory000_Plugin
	 */
	protected $plugin;
	
	/**
	 * @var array
	 */
	protected $settings;
	
	/**
	 * Provider constructor.
	 *
	 * @param Wbcr_Factory000_Plugin $plugin
	 * @param array $settings
	 */
	public function __construct( Wbcr_Factory000_Plugin $plugin, array $settings ) {
		$this->plugin   = $plugin;
		$this->settings = $settings;
	}
	
	/**
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}
	
	/**
	 * @param $name
	 * @param bool $default
	 *
	 * @return bool|mixed
	 */
	public function get_setting( $name, $default = false ) {
		return isset( $this->settings[ $name ] ) && ! empty( $this->settings[ $name ] ) ? $this->settings[ $name ] : $default;
	}
	
	abstract public function has_paid_subscription();
	
	abstract public function get_license_key();
	
	abstract public function get_secret_license_key();
	
	abstract public function get_license_expiration_time();
	
	abstract public function get_count_active_sites();
	
	abstract public function get_plan();
	
	abstract public function is_install_premium();
	
	abstract public function is_license_activate();
	
	abstract public function is_lifetime();
	
	abstract public function license_activate();
	
	abstract public function license_deactivate();
	
	abstract public function paid_subscription_cancel();
}