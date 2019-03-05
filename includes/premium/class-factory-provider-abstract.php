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
		
		add_action( 'wbcr/factory/license_activate', array( $this, 'register_cron_hooks' ), 10, 2 );
		add_action( 'wbcr/factory/license_deactivate', array( $this, 'register_cron_hooks' ), 10, 2 );
		add_action( "{$this->plugin->getPluginName()}_license_autosync", array( $this, 'license_cron_sync' ) );
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
	
	/**
	 * @return bool|mixed
	 */
	public function get_price() {
		return $this->get_setting( 'price' );
	}
	
	/**
	 * @param array $license_info
	 * @param string $plugin_name
	 */
	public function register_cron_hooks( $license_info, $plugin_name ) {
		if ( $this->plugin->getPluginName() == $plugin_name ) {
			if ( ! wp_next_scheduled( "{$plugin_name}_license_autosync" ) ) {
				wp_schedule_event( time(), 'twicedaily', "{$plugin_name}_license_autosync" );
			}
		}
	}
	
	/**
	 * @param array $license_info
	 * @param string $plugin_name
	 */
	public function clear_cron_hooks( $license_info, $plugin_name ) {
		if ( $this->plugin->getPluginName() == $plugin_name ) {
			if ( wp_next_scheduled( "{$plugin_name}_license_autosync" ) ) {
				wp_clear_scheduled_hook( "{$plugin_name}_license_autosync" );
			}
		}
	}
	
	public function license_cron_sync() {
		$this->sync();
	}
	
	/**
	 * @return bool
	 */
	public function is_install_package() {
		return false;
	}
	
	/**
	 * @return bool
	 */
	abstract public function is_activate();
	
	/**
	 * @return bool
	 */
	abstract public function is_active();
	
	/**
	 * @return string
	 */
	abstract public function get_plan();
	
	/**
	 * @return string
	 */
	abstract public function get_billing_cycle();
	
	/**
	 * @return \WBCR\Factory_000\Premium\Interfaces\License
	 */
	abstract public function get_license();
	
	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	abstract public function activate( $key );
	
	/**
	 * @return bool
	 */
	abstract public function deactivate();
	
	/**
	 * @return bool
	 */
	abstract public function sync();
	
	/**
	 * @return bool
	 */
	abstract public function has_paid_subscription();
	
	/**
	 * @return bool
	 */
	abstract public function cancel_paid_subscription();
	
}