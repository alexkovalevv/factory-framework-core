<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Базовый класс
 *
 * @author Webcraftic <wordpress.webraftic@gmail.com>, Alex Kovalev <alex.kovalevv@gmail.com>
 * @link https://webcraftic.com
 * @copyright (c) 2018 Webraftic Ltd
 * @since 4.0.8
 */
if ( ! class_exists( 'Wbcr_Factory000_Base' ) ) {
	class  Wbcr_Factory000_Base {
		
		use WBCR\Factory_000\Options;
		
		/**
		 * Namespace Prefix among Wordpress Options
		 *
		 * @var string
		 */
		protected $prefix;
		
		/**
		 * Plugin title
		 *
		 * @var string
		 */
		protected $plugin_title;
		
		/**
		 * Plugin name. Valid characters [A-z0-9_-]
		 *
		 * @var string
		 */
		protected $plugin_name;
		
		/**
		 * Plugin version. Valid characters [0-9.]
		 * Example: 1.4.5
		 *
		 * @var string
		 */
		protected $plugin_version;
		
		/**
		 * Type of assembly plugin. Possible options: free, premium, trial
		 *
		 * @var string
		 */
		protected $plugin_build;
		
		/**
		 * @var string
		 */
		protected $plugin_assembly;
		
		/**
		 * Absolute path to the main file of the plugin.
		 *
		 * @var string
		 */
		protected $main_file;
		
		/**
		 * Absolute path to plugin directory
		 *
		 * @var string
		 */
		protected $plugin_root;
		
		/**
		 * Relative path to plugin directory
		 *
		 * @var string
		 */
		protected $relative_path;
		
		/**
		 * Link to the plugin directory
		 *
		 * @var string
		 */
		protected $plugin_url;
		
		
		/**
		 * Optional. Settings for plugin updates from a remote repository.
		 *
		 * @var array {
		 *
		 *    Update settings for free plugin.
		 *
		 *    {type} string repository    Type where we download plugin updates
		 *                                       (wordpress | freemius | other)
		 *
		 *    {type} string slug          Plugin slug
		 *
		 *    {type} array rollback       Settings for rollback to the previous version of
		 *                                       the plugin, will gain only one option prev_stable_version,
		 *                                       you must specify previous version of the plugin         *
		 * }
		 */
		protected $updates_provider = array();
		
		/**
		 * Does plugin have a premium version?
		 *
		 * @var bool
		 */
		protected $has_premium = false;
		
		/**
		 * Store where premium plugin was sold (freemius | codecanyon | template_monster)
		 * By default: freemius
		 *
		 * @var string
		 */
		protected $license_provider = 'freemius';
		
		/**
		 * Optional. Settings for download, update and upgrage to premium of the plugin.
		 *
		 * @var array {
		 *      {type} string license_provider            Store where premium plugin was sold (freemius | codecanyon | template_monster)
		 *      {type} string plugin_id                   Plugin id, only for freemius
		 *      {type} string public_key                  Plugin public key, only for freemius
		 *      {type} string slug                        Plugin name, only for freemius
		 *
		 *      {type} array  premium_plugin_updates {
		 *              Update settings for free plugin.
		 *
		 *              {type} array rollback             Settings for rollback to the previous version of
		 *                                                the plugin, will gain only one option prev_stable_version,
		 *                                                you must specify previous version of the plugin         *
		 *      }
		 * }
		 */
		protected $license_provider_settings = array();
		
		/**
		 * Required. Framework modules needed to develop a plugin.
		 *
		 * @var array {
		 * Array with information about the loadable module
		 *      {type} string $module [0]   Relative path to the module directory
		 *      {type} string $module [1]   Module name with prefix 000
		 *      {type} string $module [2]   Scope:
		 *                                  admin  - Module will be loaded only in the admin panel,
		 *                                  public - Module will be loaded only on the frontend
		 *                                  all    - Module will be loaded everywhere
		 * }
		 */
		protected $load_factory_modules = array(
			array( 'libs/factory/bootstrap', 'factory_bootstrap_000', 'admin' ),
			array( 'libs/factory/forms', 'factory_forms_000', 'admin' ),
			array( 'libs/factory/pages', 'factory_pages_000', 'admin' ),
		);
		
		/**
		 * @var array
		 */
		private $plugin_data;
		
		/**
		 * @since 4.0.8 - добавлена дополнительная логика
		 *
		 * @param string $plugin_path
		 * @param array $data
		 *
		 * @throws Exception
		 */
		public function __construct( $plugin_path, $data ) {
			$this->plugin_data = $data;
			
			foreach ( (array) $data as $option_name => $option_value ) {
				if ( property_exists( $this, $option_name ) ) {
					$this->$option_name = $option_value;
				}
			}
			
			if ( empty( $this->prefix ) || empty( $this->plugin_title ) || empty( $this->plugin_version ) || empty( $this->plugin_build ) ) {
				throw new Exception( 'One of the required attributes has not been passed (prefix,plugin_title,plugin_name,plugin_version,plugin_build).' );
			}
			
			// saves plugin basic paramaters
			$this->main_file     = $plugin_path;
			$this->plugin_root   = dirname( $plugin_path );
			$this->relative_path = plugin_basename( $plugin_path );
			$this->plugin_url    = plugins_url( null, $plugin_path );
			
			// used only in the module 'updates'
			$this->plugin_slug = ! empty( $this->plugin_name ) ? $this->plugin_name : basename( $plugin_path );
		}
		
		public function has_premium() {
			return $this->has_premium;
		}
		
		/**
		 * @return string
		 */
		public function getPluginTitle() {
			return $this->plugin_title;
		}
		
		/**
		 * @return string
		 */
		public function getPrefix() {
			return $this->prefix;
		}
		
		/**
		 * @return string
		 */
		public function getPluginName() {
			return $this->plugin_name;
		}
		
		/**
		 * @return string
		 */
		public function getPluginVersion() {
			return $this->plugin_version;
		}
		
		/**
		 * @return string
		 */
		public function getPluginBuild() {
			return $this->plugin_build;
		}
		
		/**
		 * @return string
		 */
		public function getPluginAssembly() {
			return $this->plugin_assembly;
		}
		
		/**
		 * @return stdClass
		 */
		public function getPluginPathInfo() {
			
			$object = new stdClass;
			
			$object->main_file     = $this->main_file;
			$object->plugin_root   = $this->plugin_root;
			$object->relative_path = $this->relative_path;
			$object->plugin_url    = $this->plugin_url;
			
			return $object;
		}
		
		/**
		 * @param $attr_name
		 *
		 * @return null
		 */
		public function getPluginInfoAttr( $attr_name ) {
			if ( isset( $this->plugin_data[ $attr_name ] ) ) {
				return $this->plugin_data[ $attr_name ];
			}
			
			return null;
		}
		
		/**
		 * @return object
		 */
		public function getPluginInfo() {
			return (object) $this->plugin_data;
		}
		
		/**
		 * Активирован ли сайт в режиме мультисайтов и мы находимся в области суперадминистратора
		 * TODO: Вынести метод в функции
		 * @return bool
		 */
		public function isNetworkAdmin() {
			return is_multisite() && is_network_admin();
		}
		
		/**
		 * Активирован ли плагин для сети
		 * TODO: Вынести метод в функции
		 * @since 4.0.8
		 * @return bool
		 */
		public function isNetworkActive() {
			// Makes sure the plugin is defined before trying to use it
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			
			$activate = is_plugin_active_for_network( $this->relative_path );
			
			if ( ! $activate && $this->isNetworkAdmin() && isset( $_GET['action'] ) && $_GET['action'] == 'activate' ) {
				$is_activate_for_network = isset( $_GET['plugin_status'] ) && $_GET['plugin_status'] == 'all';
				
				if ( $is_activate_for_network ) {
					return true;
				}
			}
			
			return $activate;
		}
		
		/**
		 * Получает список активных сайтов сети
		 * TODO: Вынести метод в функции
		 * @since 4.0.8
		 * @return array|int
		 */
		public function getActiveSites( $args = array( 'archived' => 0, 'mature' => 0, 'spam' => 0, 'deleted' => 0 ) ) {
			global $wp_version;
			
			if ( version_compare( $wp_version, '4.6', '>=' ) ) {
				return get_sites( $args );
			} else {
				$converted_array = array();
				
				$sites = wp_get_sites( $args );
				
				if ( empty( $sites ) ) {
					return $converted_array;
				}
				
				foreach ( (array) $sites as $key => $site ) {
					$obj = new stdClass();
					foreach ( $site as $attr => $value ) {
						$obj->$attr = $value;
					}
					$converted_array[ $key ] = $obj;
				}
				
				return $converted_array;
			}
		}
	}
}