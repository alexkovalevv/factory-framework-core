<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wbcr_Factory000_Base' ) ) {
	class  Wbcr_Factory000_Base {
		
		/**
		 * Instance class Wbcr_Factory000_Request, required manages http requests
		 *
		 * @var Wbcr_Factory000_Request
		 */
		public $request;
		
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
		 *      {type} array free {
		 *           Update settings for free plugin.
		 *
		 *           {type} string repository    Type where we download plugin updates
		 *                                       (wordpress | freemius | other)
		 *
		 *           {type} string slug          Plugin slug
		 *
		 *           {type} array rollback       Settings for rollback to the previous version of
		 *                                       the plugin, will gain only one option prev_stable_version,
		 *                                       you must specify previous version of the plugin
		 *      }
		 *     {type} array premium {
		 *          Update settings for premium plugin.
		 *
		 *          Settings is inherited from free attribute.
		 *     }
		 * }
		 */
		protected $updates = array();
		
		
		/**
		 * Optional. Settings for download, update and upgrage to premium of the plugin.
		 *
		 * @var array {
		 *      {type} string license_server License server type freemius | webcraftic
		 *      {type} string store          Store where freemius plugin was sold | codecanyon | template_monster         *
		 *      {type} string plugin_id      Plugin id, only for freemius
		 *      {type} string public_key     Plugin public key, only for freemius
		 *      {type} string slug           Plugin name, only for freemius         *
		 * }
		 */
		protected $licensing = array();
		
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
			$this->request     = new Wbcr_Factory000_Request();
			
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
			
			// Makes sure the plugin is defined before trying to use it
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
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
		 * @return bool
		 */
		public function isNetworkAdmin() {
			return is_multisite() && is_network_admin();
		}
		
		/**
		 * Активирован ли плагин для сети
		 *
		 * @since 4.0.8
		 * @return bool
		 */
		public function isNetworkActive() {
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
		 *
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
		
		/**
		 * Получает все опции плагина
		 *
		 * @since 4.0.8
		 * @return array
		 */
		public function loadAllOptions() {
			global $wpdb;
			
			$is_option_loaded = wp_cache_get( $this->prefix . 'all_options_loaded', $this->prefix . 'options' );
			
			if ( false === $is_option_loaded ) {
				$result = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '{$this->prefix}%'" );
				
				$options = array();
				
				if ( ! empty( $result ) ) {
					wp_cache_add( $this->prefix . 'all_options_loaded', 1, $this->prefix . 'options' );
					
					foreach ( $result as $option ) {
						$value = maybe_unserialize( $option->option_value );
						$value = $this->normalizeValue( $value );
						
						wp_cache_add( $option->option_name, $value, $this->prefix . 'options' );
						$options[ $option->option_name ] = $value;
					}
					
					/**
					 * @since 4.0.9
					 */
					do_action( 'wbcr/factory/all_options_loaded', $options, $this->plugin_name );
				}
			}
		}
		
		/**
		 * Получает все опции плагина
		 *
		 * @since 4.0.8
		 * @return void
		 */
		public function loadAllNetworkOptions() {
			global $wpdb;
			
			$network_id = (int) get_current_network_id();
			
			$is_option_loaded = wp_cache_get( $network_id . ":" . $this->prefix . 'all_options_loaded', $this->prefix . 'network_options' );
			
			if ( false === $is_option_loaded ) {
				wp_cache_add_global_groups( array( $this->prefix . 'network_options' ) );
				
				$result = $wpdb->get_results( "SELECT meta_key, meta_value FROM {$wpdb->sitemeta} WHERE site_id='{$network_id}' AND meta_key LIKE '{$this->prefix}%'" );
				
				$options = array();
				if ( ! empty( $result ) ) {
					wp_cache_add( $network_id . ":" . $this->prefix . 'all_options_loaded', 1, $this->prefix . 'network_options' );
					
					foreach ( $result as $option ) {
						$value = maybe_unserialize( $option->meta_value );
						$value = $this->normalizeValue( $value );
						
						$cache_key = $network_id . ":" . $option->meta_key;
						wp_cache_add( $cache_key, $value, $this->prefix . 'network_options' );
						$options[ $option->meta_key ] = $value;
					}
					
					/**
					 * @since 4.0.9
					 */
					do_action( 'wbcr/factory/all_network_options_loaded', $options, $this->plugin_name );
				}
			}
		}
		
		/**
		 * Если плагин установлен для сети, то метод возвращает опции только для сети,
		 * иначе метод возвращает опцию для текущего сайта.
		 *
		 * @since 4.0.8
		 *
		 * @param string $option_name
		 * @param string $default
		 *
		 * @return bool|mixed
		 */
		public function getPopulateOption( $option_name, $default = false ) {
			if ( $this->isNetworkActive() ) {
				$option_value = $this->getNetworkOption( $option_name, $default );
			} else {
				$option_value = $this->getOption( $option_name, $default );
			}
			
			return apply_filters( "wbcr/factory/populate_option_{$option_name}", $option_value, $option_name, $default );
		}
		
		/**
		 * Получает опцию для сети, используется в режиме мультисайтов
		 *
		 * @param $option_name
		 * @param bool $default
		 *
		 * @return bool|mixed
		 */
		public function getNetworkOption( $option_name, $default = false ) {
			if ( empty( $option_name ) || ! is_string( $option_name ) ) {
				throw new Exception( 'Option name must be a string and must not be empty.' );
			}
			
			if ( ! is_multisite() ) {
				return $this->getOption( $option_name, $default );
			}
			
			$this->loadAllNetworkOptions();
			
			$network_id   = (int) get_current_network_id();
			$cache_key    = $network_id . ':' . $this->prefix . $option_name;
			$option_value = wp_cache_get( $cache_key, $this->prefix . 'network_options' );
			
			if ( false === $option_value ) {
				$option_value = $default;
			}
			
			/**
			 * @param mixed $option_value
			 * @param string $option_name
			 * @param mixed $default
			 * @param int $network_id
			 *
			 * @since 4.0.8
			 */
			
			return apply_filters( "wbcr/factory/network_option_{$option_name}", $option_value, $option_name, $default, $network_id );
		}
		
		/**
		 * Получает опцию из кеша или из базы данныеs
		 *
		 * @since 4.0.0
		 * @since 4.0.8 - полностью переделан
		 *
		 * @param string $option_name
		 * @param bool $default
		 *
		 * @return mixed
		 */
		public function getOption( $option_name, $default = false ) {
			if ( empty( $option_name ) || ! is_string( $option_name ) ) {
				throw new Exception( 'Option name must be a string and must not be empty.' );
			}
			
			$this->loadAllOptions();
			
			$option_value = wp_cache_get( $this->prefix . $option_name, $this->prefix . 'options' );
			
			if ( false === $option_value ) {
				$option_value = $default;
			}
			
			/**
			 * @param mixed $option_value
			 * @param string $option_name
			 * @param mixed $default
			 *
			 * @since 4.0.8
			 */
			
			return apply_filters( "wbcr/factory/option_{$option_name}", $option_value, $option_name, $default );
		}
		
		/**
		 * @param $option_name
		 * @param $option_value
		 *
		 * @return bool
		 */
		public function updatePopulateOption( $option_name, $option_value ) {
			if ( $this->isNetworkActive() ) {
				$this->updateNetworkOption( $option_name, $option_value );
			} else {
				$this->updateOption( $option_name, $option_value );
			}
		}
		
		/**
		 * Обновляет опцию для сети в базе данных и в кеше
		 *
		 * @since 4.0.8
		 *
		 * @param string $option_name
		 * @param mixed $value
		 *
		 * @return bool
		 */
		public function updateNetworkOption( $option_name, $option_value ) {
			$network_id = (int) get_current_network_id();
			$cache_key  = $network_id . ':' . $this->prefix . $option_name;
			wp_cache_set( $cache_key, $option_value, $this->prefix . 'network_options' );
			
			$result = update_site_option( $this->prefix . $option_name, $option_value );
			
			/**
			 * @param mixed $option_value
			 * @param string $option_name
			 *
			 * @since 4.0.8
			 */
			do_action( "wbcr/factory/update_network_option", $option_name, $option_value );
			
			return $result;
		}
		
		/**
		 * Обновляет опцию в базе данных и в кеше
		 *
		 * @since 4.0.0
		 * @since 4.0.8 - полностью переделан
		 *
		 * @param string $option_name
		 * @param mixed $value
		 *
		 * @return bool
		 */
		public function updateOption( $option_name, $option_value ) {
			wp_cache_set( $this->prefix . $option_name, $option_value, $this->prefix . 'options' );
			$result = update_option( $this->prefix . $option_name, $option_value );
			
			/**
			 * @param mixed $option_value
			 * @param string $option_name
			 *
			 * @since 4.0.8
			 */
			do_action( "wbcr/factory/update_option", $option_name, $option_value );
			
			return $result;
		}
		
		/**
		 * Удаляет опцию из базы данных, если опция есть в кеше,
		 * индивидуально удаляет опцию из кеша.
		 *
		 * @param string $option_name
		 *
		 * @return void
		 */
		public function deletePopulateOption( $option_name ) {
			if ( $this->isNetworkActive() ) {
				$this->deleteNetworkOption( $option_name );
			} else {
				$this->deleteOption( $option_name );
			}
		}
		
		/**
		 * Удаляет опцию из базы данных, если опция есть в кеше,
		 * индивидуально удаляет опцию из кеша.
		 *
		 * @param string $option_name
		 *
		 * @return bool
		 */
		public function deleteNetworkOption( $option_name ) {
			$network_id   = (int) get_current_network_id();
			$cache_key    = $network_id . ':' . $this->prefix . $option_name;
			$delete_cache = wp_cache_delete( $cache_key, $this->prefix . 'network_options' );
			
			$delete_opt1 = delete_site_option( $this->prefix . $option_name );
			
			return $delete_cache && $delete_opt1;
		}
		
		/**
		 * Удаляет опцию из базы данных, если опция есть в кеше,
		 * индивидуально удаляет опцию из кеша.
		 *
		 * @param string $option_name
		 *
		 * @return bool
		 */
		public function deleteOption( $option_name ) {
			$delete_cache = wp_cache_delete( $this->prefix . $option_name, $this->prefix . 'options' );
			
			// todo: удалить, когда большая часть пользователей обновятся до современных релизов
			$delete_opt1 = delete_option( $this->prefix . $option_name . '_is_active' );
			$delete_opt2 = delete_option( $this->prefix . $option_name );
			
			return $delete_cache && $delete_opt1 && $delete_opt2;
		}
		
		/**
		 * Сбрасывает объектный кеш опций
		 *
		 * @return bool
		 */
		public function flushOptionsCache() {
			return wp_cache_flush();
		}
		
		/**
		 * Возвращает название опции в пространстве имен плагина
		 *
		 * @param string $option_name
		 *
		 * @return null|string
		 */
		public function getOptionName( $option_name ) {
			$option_name = trim( rtrim( $option_name ) );
			if ( empty( $option_name ) || ! is_string( $option_name ) ) {
				return null;
			}
			
			return $this->prefix . $option_name;
		}
		
		/**
		 * Приведение значений опций к строгому типу данных
		 *
		 * @param mixed $string
		 *
		 * @return bool|int
		 */
		public function normalizeValue( $data ) {
			if ( is_string( $data ) ) {
				$check_string = rtrim( trim( $data ) );
				
				if ( $check_string == "1" || $check_string == "0" ) {
					return intval( $data );
				} else if ( $check_string === 'false' ) {
					return false;
				} else if ( $check_string === 'true' ) {
					return true;
				}
			}
			
			return $data;
		}
	}
}