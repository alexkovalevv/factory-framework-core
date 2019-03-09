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
	 * @var bool
	 */
	private $is_install_package;
	
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
	 * todo: Вынести с лицензионный менеджер
	 *
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
	 * todo: вынести в лицензионный менеджер
	 *
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
		if ( ! is_null( $this->is_install_package ) ) {
			return $this->is_install_package;
		}
		
		$premium_package = $this->get_package_data();
		
		if ( ! empty( $premium_package ) && ! empty( $premium_package['basename'] ) ) {
			$basename_part     = explode( '/', $premium_package['basename'] );
			$is_valid_basename = sizeof( $basename_part ) === 2;
			
			if ( $is_valid_basename && ! file_exists( WP_PLUGIN_DIR . '/' . $premium_package['basename'] ) ) {
				$this->delete_package();
				$this->is_install_package = false;
				
				return false;
			}
		}
		
		$this->is_install_package = ! empty( $premium_package );
		
		return $this->is_install_package;
	}
	
	/**
	 * @return bool|mixed|null
	 */
	public function get_package_data() {
		$premium_package = $this->plugin->getPopulateOption( 'premium_package' );
		
		if ( ! empty( $premium_package ) ) {
			return wp_parse_args( $premium_package, array(
				'basename'          => null,
				'version'           => null,
				'framework_version' => null
			) );
		}
		
		return null;
	}
	
	/**
	 * @param $plugin_data
	 *
	 * @throws Exception
	 */
	public function update_package_data( array $package ) {
		$parsed_args = wp_parse_args( $package, array(
			'basename'          => null,
			'version'           => null,
			'framework_version' => null
		) );
		
		if ( empty( $parsed_args['basename'] ) || empty( $parsed_args['version'] ) || empty( $parsed_args['framework_version'] ) ) {
			throw new Exception( 'You must pass the required attributes (basename, name, version).' );
		}
		
		$this->plugin->updatePopulateOption( 'premium_package', $parsed_args );
		$this->is_install_package = true;
	}
	
	public function delete_package() {
		$this->plugin->deletePopulateOption( 'premium_package' );
		$this->is_install_package = false;
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
	 * @return string|null
	 */
	abstract public function get_plan();
	
	/**
	 * @return string|null
	 */
	abstract public function get_billing_cycle();
	
	/**
	 * @return \WBCR\Factory_000\Premium\Interfaces\License
	 */
	abstract public function get_license();
	
	/**
	 * @return string|null
	 */
	abstract public function get_package_download_url();
	
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