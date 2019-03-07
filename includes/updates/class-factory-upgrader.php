<?php

namespace WBCR\Factory_000\Updates;

use Exception;
use Wbcr_Factory000_Plugin;
use WBCR\Factory_Freemius_000\Updates\Freemius_Repository;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @author Webcraftic <wordpress.webraftic@gmail.com>, Alex Kovalev <alex.kovalevv@gmail.com>
 * @link https://webcraftic.com
 * @copyright (c) 2018 Webraftic Ltd
 * @version 1.0
 */
class Upgrader {
	
	/**
	 * @var Wbcr_Factory000_Plugin
	 */
	protected $plugin;
	
	/**
	 * @var string
	 */
	protected $plugin_basename;
	
	/**
	 * @var string
	 */
	protected $plugin_main_file;
	
	/**
	 * @var string
	 */
	protected $plugin_absolute_path;
	
	/**
	 * Имя плагина, для которого нужно проверять обновления
	 *
	 * @var string
	 */
	protected $plugin_slug;
	
	/**
	 * @var Repository
	 */
	protected $repository;
	
	/**
	 * @var array
	 */
	protected $rollback = array(
		'prev_stable_version' => null
	);
	
	/**
	 * Manager constructor.
	 *
	 * @since 4.1.1
	 *
	 * @param Wbcr_Factory000_Plugin $plugin
	 * @param $args
	 * @param bool $is_premium
	 *
	 * @throws Exception
	 */
	public function __construct( Wbcr_Factory000_Plugin $plugin ) {
		
		$this->plugin = $plugin;
		
		$this->plugin_basename      = $plugin->get_paths()->basename;
		$this->plugin_main_file     = $plugin->get_paths()->main_file;
		$this->plugin_absolute_path = $plugin->get_paths()->absolute;
		
		$settings = $this->get_settings();
		
		$this->plugin_slug = $settings['slug'];
		$this->rollback    = $settings['rollback_settings'];
		
		if ( empty( $this->plugin_slug ) || ! is_string( $this->plugin_slug ) ) {
			throw new Exception( 'Argument {slug} can not be empty and must be of type string.' );
		}
		
		$this->repository = $this->get_repository( $settings['repository'] );
		
		$this->init_hooks();
	}
	
	/**
	 * @return array
	 */
	protected function get_settings() {
		$settings = $this->plugin->getPluginInfoAttr( 'updates_settings' );
		
		return wp_parse_args( $settings, array(
			'repository'        => 'wordpress',
			'slug'              => '',
			'maybe_rollback'    => false,
			'rollback_settings' => array(
				'prev_stable_version' => '0.0.0'
			)
		) );
	}
	
	/**
	 * @since 4.1.1
	 */
	protected function init_hooks() {
		
		if ( $this->repository->need_check_updates() ) {
			
			$plugin_name = $this->plugin->getPluginName();
			
			if ( is_admin() ) {
				add_action( "wbcr/factory/plugin_{$plugin_name}_activation", array( $this, 'register_cron_tasks' ) );
				add_action( "wbcr/factory/plugin_{$plugin_name}_deactivation", array( $this, 'clear_cron_tasks' ) );
				
				// if a special constant set, then forced to check updates
				if ( defined( 'FACTORY_UPDATES_DEBUG' ) && FACTORY_UPDATES_DEBUG ) {
					//$this->check_auto_updates();
				}
			}
			
			// an action that is called by the cron to check updates
			add_action( "wbcr/factory/updates/check_for_{$this->plugin_slug}", array(
				$this,
				'check_auto_updates'
			) );
		}
	}
	
	/**
	 * Calls on plugin activation or updating.
	 * @since 4.1.1
	 */
	public function register_cron_tasks() {
		// set cron tasks and clear last version check data
		if ( ! wp_next_scheduled( "wbcr/factory/updates/check_for_{$this->plugin_slug}" ) ) {
			wp_schedule_event( time(), 'twicedaily', "wbcr/factory/updates/check_for_{$this->plugin_slug}" );
		}
		
		$this->clear_updates();
	}
	
	/**
	 * Calls on plugin deactivation .
	 * @since 4.1.1
	 */
	public function clear_cron_tasks() {
		// clear cron tasks and license data
		if ( wp_next_scheduled( "wbcr/factory/updates/check_for_{$this->plugin_slug}" ) ) {
			wp_unschedule_hook( "wbcr/factory/updates/check_for_{$this->plugin_slug}" );
		}
		
		$this->clear_updates();
	}
	
	/**
	 * @param $args
	 *
	 * @return string
	 */
	protected function get_admin_url( $args ) {
		$url = admin_url( 'plugins.php', $args );
		
		if ( $this->plugin->isNetworkActive() ) {
			$url = network_admin_url( 'plugins.php', $args );
		}
		
		return add_query_arg( $args, $url );
	}
	
	/**
	 *
	 */
	public function check_auto_updates() {
		$test = 'fsdf';
	}
	
	/**
	 * @param $repository_name
	 *
	 * @since 4.1.1
	 * @throws Exception
	 * @return Repository
	 */
	protected function get_repository( $repository_name ) {
		switch ( $repository_name ) {
			case 'wordpress':
				return new Wordpress_Repository( $this->plugin );
				break;
			case 'freemius':
				if ( ! defined( 'FACTORY_FREEMIUS_000_LOADED' ) ) {
					throw new Exception( 'If you have to get updates from the Freemius repository, you need to install the freemius module.' );
				}
				
				return new Freemius_Repository( $this->plugin );
				break;
			default:
				return $this->instance_other_repository( $repository_name );
				break;
		}
	}
	
	/**
	 * @since 4.1.1
	 *
	 * @param string $name
	 * @param bool $is_premium
	 *
	 * @return Repository
	 * @throws Exception
	 */
	protected function instance_other_repository( $name ) {
		$other_repositories = array();
		
		/**
		 * @since 4.1.1
		 * @type array $other_repositories
		 */
		$other_repositories = apply_filters( 'wbcr/factory/updates_manager/repositories', $other_repositories );
		
		if ( ! isset( $other_repositories[ $name ] ) ) {
			return null;
		}
		
		$repository_data = $other_repositories[ $name ];
		
		if ( ! isset( $repository_data['name'] ) || ! isset( $repository_data['class_path'] ) || ! isset( $repository_data['class_name'] ) ) {
			throw new Exception( 'Repository data must contain the required attributes name, class_path, class_name!' );
		}
		
		if ( ! file_exists( $repository_data['class_path'] ) ) {
			throw new Exception( 'File with new repository class not found. Please check the correctness of used path: ' . $repository_data['class_path'] );
		}
		
		if ( ! class_exists( $repository_data['class_name'] ) ) {
			throw new Exception( 'Class ' . $repository_data['class_name'] . ' is not found. Please check if class name is filled out correctly.' );
		}
		
		require_once $repository_data['class_path'];
		
		return new $repository_data['class_name']( $this->plugin );
	}
	
	
	/**
	 * Clears info about updates for the plugin.
	 *
	 * @since 4.1.1
	 */
	public function clear_updates() {
		/*delete_option('onp_version_check_' . $this->plugin->pluginName);
		$this->lastCheck = null;
		
		$transient = $this->changePluginTransient(get_site_transient('update_plugins'));
		if( !empty($transient) ) {
			unset($transient->response[$this->plugin->relativePath]);
			onp_updates_000_set_site_transient('update_plugins', $transient);
		}*/
	}
	
	/**
	 * Fix a bug when the message offering to change assembly appears even if the assemble is correct.
	 *
	 * @since 4.1.1
	 */
	public function clear_transient() {
		/*$screen = get_current_screen();
		if( empty($screen) ) {
			return;
		}
		
		if( in_array($screen->base, array('plugins', 'update-core')) ) {
			$this->updatePluginTransient();
		}*/
	}
	
	/**
	 * Need to check updates?
	 *
	 * @since 4.1.1
	 */
	public function need_check_updates() {
	
	}
	
	/**
	 * Need to change a current assembly?
	 *
	 * @since 4.1.1
	 */
	public function need_change_assembly() {
	
	}
	
	/**
	 * Returns true if a plugin version has been checked up to the moment.
	 *
	 * @since 4.1.1
	 */
	public function is_version_checked() {
	
	}
	
	/**
	 * @since 4.1.1
	 */
	public function check_updates() {
	
	}
	
	/**
	 * @since 4.1.1
	 */
	public function rollback() {
	
	}
}