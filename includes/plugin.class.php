<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wbcr_Factory000_Plugin' ) ) {
	/**
	 * The file contains the class to register a plugin in the Factory.
	 *
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright (c) 2018 Webcraftic Ltd
	 *
	 * @package factory-core
	 * @since 1.0.0
	 */
	abstract class Wbcr_Factory000_Plugin extends Wbcr_Factory000_Base {
		
		/**
		 * The Bootstrap Manager class
		 *
		 * @var Wbcr_FactoryBootstrap000_Manager
		 */
		public $bootstrap;
		
		/**
		 * The Bootstrap Manager class
		 *
		 * @var Wbcr_FactoryForms000_Manager
		 */
		public $forms;
		
		/**
		 * @var Wbcr_FactoryLicense000_Base
		 */
		public $license_manager;
		
		/**
		 * A class name of an activator to activate the plugin.
		 *
		 * @var string
		 */
		protected $activator_class = array();
		
		/**
		 * Подключенные модули фреймворка
		 *
		 * @var array
		 */
		private $loaded_factory_modules = array();
		
		/**
		 * Подключенные компоненнты плагина
		 *
		 * @var array[] Wbcr_Factory000_Plugin
		 */
		private $plugin_addons;
		
		/**
		 * Creates an instance of Factory plugin.
		 *
		 * @param string $plugin_path A full path to the main plugin file.
		 * @param array $data A set of plugin data.
		 *
		 * @since 1.0.0
		 * @throws Exception
		 */
		public function __construct( $plugin_path, $data ) {
			parent::__construct( $plugin_path, $data );
			
			// INIT PLUGIN FRAMEWORK MODULES
			$this->init_framework_modules();
			
			// INIT PLUGIN MIGRATIONS
			$this->init_plugin_migrations();
			
			// INIT PLUGIN UPDATES
			$this->init_plugin_updates();
			
			// INIT PLUGIN PREMIUM FEATURES
			$this->init_plugin_premium_features();
			
			// init actions
			$this->register_plugin_hooks();
		}
		
		/**
		 * Возвращает ссылку на внутреннюю страницу плагина
		 *
		 * @param string $page_id
		 *
		 * @sicne: 4.0.8
		 * @return string|void
		 */
		public function getPluginPageUrl( $page_id, $args = array() ) {
			if ( ! class_exists( 'Wbcr_FactoryPages000' ) ) {
				throw new Exception( 'The factory_pages_000 module is not included.' );
			}
			
			if ( ! is_admin() ) {
				_doing_it_wrong( __METHOD__, __( 'You cannot use this feature on the frontend.' ), '4.0.8' );
				
				return null;
			}
			
			return Wbcr_FactoryPages000::getPageUrl( $this, $page_id, $args );
		}
		
		/**
		 * @param Wbcr_FactoryBootstrap000_Manager $bootstrap
		 */
		public function setBootstap( Wbcr_FactoryBootstrap000_Manager $bootstrap ) {
			$this->bootstrap = $bootstrap;
		}
		
		/**
		 * @param Wbcr_FactoryForms000_Manager $forms
		 */
		public function setForms( Wbcr_FactoryForms000_Manager $forms ) {
			$this->forms = $forms;
		}
		
		/**
		 * Устанавливает текстовый домен для плагина
		 */
		public function setTextDomain( $domain, $plugin_dir ) {
			$locale = apply_filters( 'plugin_locale', is_admin() ? get_user_locale() : get_locale(), $domain );
			
			$mofile = $domain . '-' . $locale . '.mo';
			
			if ( ! load_textdomain( $domain, $plugin_dir . '/languages/' . $mofile ) ) {
				load_muplugin_textdomain( $domain );
			}
		}
		
		/**
		 * @param $class_name
		 * @param $file_path
		 *
		 * @throws Exception
		 */
		public function registerPage( $class_name, $file_path ) {
			if ( $this->isNetworkActive() && ! is_network_admin() ) {
				return;
			}
			
			if ( ! file_exists( $file_path ) ) {
				throw new Exception( 'The page file was not found by the path {' . $file_path . '} you set.' );
			}
			
			require_once( $file_path );
			
			if ( ! class_exists( $class_name ) ) {
				throw new Exception( 'A class with this name {' . $class_name . '} does not exist.' );
			}
			
			if ( ! class_exists( 'Wbcr_FactoryPages000' ) ) {
				throw new Exception( 'The factory_pages_000 module is not included.' );
			}
			
			Wbcr_FactoryPages000::register( $this, $class_name );
		}
		
		/**
		 * @param string $class_name
		 * @param string $path
		 */
		public function registerType( $class_name, $file_path ) {
			
			if ( ! file_exists( $file_path ) ) {
				throw new Exception( 'The page file was not found by the path {' . $file_path . '} you set.' );
			}
			
			require_once( $file_path );
			
			if ( ! class_exists( $class_name ) ) {
				throw new Exception( 'A class with this name {' . $class_name . '} does not exist.' );
			}
			
			if ( ! class_exists( 'Wbcr_FactoryTypes000' ) ) {
				throw new Exception( 'The factory_types_000 module is not included.' );
			}
			
			Wbcr_FactoryTypes000::register( $class_name, $this );
		}
		
		/**
		 * Загружает специальные модули для расширения Factory фреймворка.
		 * Разработчик плагина сам выбирает, какие модули ему нужны для
		 * создания плагина.
		 *
		 * Модули фреймворка хранятся в libs/factory/framework
		 *
		 * @throws Exception
		 * @return void
		 */
		protected function init_framework_modules() {
			if ( ! empty( $this->load_factory_modules ) ) {
				foreach ( (array) $this->load_factory_modules as $module ) {
					$scope = isset( $module[2] ) ? $module[2] : 'all';
					
					if ( $scope == 'all' || ( is_admin() && $scope == 'admin' ) || ( ! is_admin() && $scope == 'public' ) ) {
						
						if ( ! file_exists( $this->plugin_root . '/' . $module[0] . '/boot.php' ) ) {
							throw new Exception( 'Module ' . $module[1] . ' is not included.' );
						}
						
						$module_boot_file = $this->plugin_root . '/' . $module[0] . '/boot.php';
						require_once $module_boot_file;
						
						$this->loaded_factory_modules[ $module[1] ] = $module_boot_file;
						
						do_action( 'wbcr_' . $module[1] . '_plugin_created', $this );
					}
				}
			}
			
			do_action( 'wbcr_factory_000_core_modules_loaded-' . $this->plugin_name );
		}
		
		/**
		 * Загружает аддоны для плагина, как часть проекта, а не как отдельный плагин
		 *
		 * @param array $addons - массив со списком загружаемых аддонов.
		 * array(
		 * 'hide_login_page' => -  ключ, идентификатора массива с информацией об аддоне
		 * array(
		 * 'WHLP_Plugin', - имя основного класса аддона
		 * WCL_PLUGIN_DIR . '/components/hide-login-page/hide-login-page.php' - пусть к основному файлу аддона
		 * ));
		 */
		protected function loadAddons( $addons ) {
			if ( empty( $addons ) ) {
				return;
			}
			
			foreach ( $addons as $addon_name => $addon_path ) {
				if ( ! isset( $this->plugin_addons[ $addon_name ] ) ) {
					
					// При подключении аддона, мы объявляем константу, что такой аддон уже загружен
					// $addon_name индентификатор аддона в вверхнем регистре
					$const_name = strtoupper( 'LOADING_' . str_replace( '-', '_', $addon_name ) . '_AS_ADDON' );
					
					if ( ! defined( $const_name ) ) {
						define( $const_name, true );
					}
					
					require_once( $addon_path[1] );
					
					// Передаем аддону информацию о родительском плагине
					$plugin_data = $this->getPluginInfo();
					
					// Устанавливаем метку для аддона, которая указывает на то, что это аддон
					$plugin_data['as_addon'] = true;
					
					// Передаем класс родителя в аддон, для того,
					// чтобы аддон использовал экземпляр класса родителя, а не создавал свой собственный.
					$plugin_data['plugin_parent'] = $this;
					
					// Создаем экземпляр класса аддона и записываем его в список загруженных аддонов
					if ( class_exists( $addon_path[0] ) ) {
						$this->plugin_addons[ $addon_name ] = new $addon_path[0]( $this->main_file, $plugin_data );
					}
				}
			}
		}
		
		/**
		 * Registers a class to activate the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @param string $className class name of the plugin activator.
		 *
		 * @return void
		 */
		public function registerActivation( $className ) {
			$this->activator_class[] = $className;
		}
		
		/**
		 * Setups actions related with the Factory Plugin.
		 *
		 * @since 1.0.0
		 */
		private function register_plugin_hooks() {
			add_action( 'init', array( $this, 'checkPluginVersioninDatabase' ) );
			
			if ( is_admin() ) {
				add_filter( 'wbcr_factory_000_core_admin_allow_multisite', '__return_true' );
			}
			
			// register activation hooks
			if ( is_admin() ) {
				register_activation_hook( $this->main_file, array( $this, 'forceActivationHook' ) );
				register_deactivation_hook( $this->main_file, array( $this, 'deactivationHook' ) );
			}
		}
		
		/**
		 * Checks the plugin version in database. If it's not the same as the currernt,
		 * it means that the plugin was updated and we need to execute the update hook.
		 *
		 * Calls on the hook "plugins_loaded".
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function checkPluginVersioninDatabase() {
			
			// checks whether the plugin needs to run updates.
			if ( is_admin() ) {
				$plugin_version = $this->getPluginVersionFromDatabase();
				
				if ( $plugin_version != $this->plugin_build . '-' . $this->plugin_version ) {
					$this->activationOrUpdateHook( false );
				}
			}
		}
		
		/**
		 * Returns the plugin version from database.
		 *
		 * @since 1.0.0
		 * @return string|null The plugin version registered in the database.
		 */
		//todo: изменить название опции, проверять версию плагинов для компонентов
		public function getPluginVersionFromDatabase() {
			if ( $this->isNetworkActive() ) {
				$plugin_versions = get_site_option( 'factory_plugin_versions', array() );
			} else {
				$plugin_versions = get_option( 'factory_plugin_versions', array() );
			}
			
			$plugin_version = isset( $plugin_versions[ $this->plugin_name ] ) ? $plugin_versions[ $this->plugin_name ] : null;
			
			return $plugin_version;
		}
		
		/**
		 * Registers in the database a new version of the plugin.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		
		//todo: изменить название опции, проверять версию плагинов для компонентов
		public function updatePluginVersionInDatabase() {
			if ( $this->isNetworkActive() ) {
				$plugin_versions = get_site_option( 'factory_plugin_versions', array() );
			} else {
				$plugin_versions = get_option( 'factory_plugin_versions', array() );
			}
			
			$plugin_versions[ $this->plugin_name ] = $this->plugin_build . '-' . $this->plugin_version;
			
			if ( $this->isNetworkActive() ) {
				update_site_option( 'factory_plugin_versions', $plugin_versions );
			} else {
				update_option( 'factory_plugin_versions', $plugin_versions );
			}
		}
		
		public function activate() {
			$this->forceActivationHook();
		}
		
		public function deactivate() {
			$this->deactivationHook();
		}
		
		/**
		 * Executes an activation hook for this plugin immediately.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function forceActivationHook() {
			$this->activationOrUpdateHook( true );
		}
		
		/**
		 * Executes an activation hook or an update hook.
		 *
		 * @param bool $forceActivation If true, then executes an activation hook.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function activationOrUpdateHook( $force_activation = false ) {
			
			$db_version = $this->getPluginVersionFromDatabase();
			do_action( 'wbcr_factory_000_plugin_activation_or_update_' . $this->plugin_name, $force_activation, $db_version, $this );
			
			// there are not any previous version of the plugin in the past
			if ( ! $db_version ) {
				$this->activationHook();
				
				$this->updatePluginVersionInDatabase();
				
				return;
			}
			
			$parts           = explode( '-', $db_version );
			$prevous_build   = $parts[0];
			$prevous_version = $parts[1];
			
			// if another build was used previously
			if ( $prevous_build != $this->plugin_build ) {
				$this->migrationHook( $prevous_build, $this->plugin_build );
				$this->activationHook();
				
				$this->updatePluginVersionInDatabase();
				
				return;
			}
			
			// if another less version was used previously
			if ( version_compare( $prevous_version, $this->plugin_version, '<' ) ) {
				$this->updateHook( $prevous_version, $this->plugin_version );
			}
			
			// standart plugin activation
			if ( $force_activation ) {
				$this->activationHook();
			}
			
			// else nothing to do
			$this->updatePluginVersionInDatabase();
			
			return;
		}
		
		/**
		 * It's invoked on plugin activation. Don't excite it directly.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function activationHook() {
			$cancelled = apply_filters( 'wbcr_factory_000_cancel_plugin_activation_' . $this->plugin_name, false );
			
			if ( $cancelled ) {
				return;
			}
			
			if ( ! empty( $this->activator_class ) ) {
				foreach ( (array) $this->activator_class as $activator_class ) {
					$activator = new $activator_class( $this );
					$activator->activate();
				}
			}
			
			/**
			 * @since 4.1.1 - deprecated
			 */
			wbcr_factory_000_do_action_deprecated( 'wbcr_factory_000_plugin_activation', array(
				$this
			), '4.0.9', "wbcr/factory/plugin_activation" );
			
			/**
			 * @since 4.1.1 - added
			 */
			do_action( 'wbcr/factory/plugin_activation', $this->plugin_name );
			
			/**
			 * @since 4.1.1 - deprecated
			 */
			wbcr_factory_000_do_action_deprecated( 'wbcr_factory_000_plugin_activation_' . $this->plugin_name, array(
				$this
			), '4.0.9', "wbcr/factory/plugin_{$this->plugin_name}_activation" );
			
			/**
			 * @since 4.1.1 - added
			 */
			do_action( "wbcr/factory/plugin_{$this->plugin_name}_activation" );
			
			// just time to know when the plugin was activated the first time
			$activated = $this->getPopulateOption( 'plugin_activated', 0 );
			
			if ( ! $activated ) {
				$this->updatePopulateOption( 'plugin_activated', time() );
			}
		}
		
		/**
		 * It's invoked on plugin deactionvation. Don't excite it directly.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function deactivationHook() {
			$cancelled = apply_filters( 'wbcr_factory_000_cancel_plugin_deactivation_' . $this->plugin_name, false );
			
			if ( $cancelled ) {
				return;
			}
			
			/**
			 * @since 4.1.1 - deprecated
			 */
			wbcr_factory_000_do_action_deprecated( 'wbcr_factory_000_plugin_deactivation', array(
				$this
			), '4.0.9', "wbcr/factory/plugin_deactivation" );
			
			/**
			 * @since 4.1.1 - added
			 */
			do_action( 'wbcr/factory/plugin_deactivation', $this->plugin_name );
			
			/**
			 * @since 4.1.1 - deprecated
			 */
			wbcr_factory_000_do_action_deprecated( 'wbcr_factory_000_plugin_deactivation_' . $this->plugin_name, array(
				$this
			), '4.0.9', "wbcr/factory/plugin_{$this->plugin_name}_deactivation" );
			
			/**
			 * @since 4.1.1 - added
			 */
			do_action( "wbcr/factory/plugin_{$this->plugin_name}_deactivation" );
			
			if ( ! empty( $this->activator_class ) ) {
				foreach ( (array) $this->activator_class as $activator_class ) {
					$activator = new $activator_class( $this );
					$activator->deactivate();
				}
			}
		}
		
		/**
		 * Finds migration items and install ones.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function migrationHook( $previos_build, $current_build ) {
			$migration_file = $this->plugin_root . '/migrations' . $previos_build . '-' . $current_build . '.php';
			if ( ! file_exists( $migration_file ) ) {
				return;
			}
			
			$classes = $this->getClasses( $migration_file );
			if ( count( $classes ) == 0 ) {
				return;
			}
			
			include_once( $migration_file );
			$migrationClass = $classes[0]['name'];
			
			$migrationItem = new $migrationClass( $this );
			$migrationItem->install();
		}
		
		/**
		 * Finds upate items and install the ones.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function updateHook( $old, $new ) {
			// converts versions like 0.0.0 to 000000
			$old_number = $this->getVersionNumber( $old );
			$new_number = $this->getVersionNumber( $new );
			
			$update_files = $this->plugin_root . '/migrations';
			$files        = $this->findFiles( $update_files );
			
			if ( empty( $files ) ) {
				return;
			}
			
			// finds updates that has intermediate version
			foreach ( $files as $item ) {
				if ( ! preg_match( '/^\d+$/', $item['name'] ) ) {
					continue;
				}
				
				$item_number = intval( $item['name'] );
				
				if ( $item_number > $old_number && $item_number <= $new_number ) {
					$classes = $this->getClasses( $item['path'] );
					if ( count( $classes ) == 0 ) {
						return;
					}
					
					foreach ( $classes as $path => $class_data ) {
						include_once( $path );
						$update_class = $class_data['name'];
						
						$update = new $update_class( $this );
						$update->install();
					}
				}
			}
		}
		
		/**
		 * @since 4.1.1
		 * @throws Exception
		 */
		protected function init_plugin_migrations() {
			if ( file_exists( $this->plugin_root . '/migrations' ) ) {
				throw new Exception( 'Starting with version 4.1.1 of the Core for Factory framework module, you must create a "migrations" folder in the root of your plugin to store the migration of the plugin.' );
			}
		}
		
		/**
		 * Создает нового рабочего для проверки обновлений и апгрейда текущего плагина.         *
		 *
		 * @since 4.1.1         *
		 *
		 * @param array $data
		 *
		 * @throws Exception
		 * @return void
		 */
		protected function init_plugin_updates() {
			
			if ( ! is_array( $this->updates ) ) {
				throw new Exception( 'Starting with version 4.1.1 of the Core for Factory framework module, you must use the updates class property to install the plugin update configuration. Now, to specify where the plugin migration is stored, you must set the path for the migrations property.' );
			}
			
			if ( ! isset( $this->updates['free'] ) ) {
				return;
			}
			
			try {
				$this->modules_dependent( array( 'factory_updates_000', 'factory_notices_000' ) );
			} catch( Exception $e ) {
				throw new Exception( $e->getMessage() );
			}
			
			new WBCR\Factory_Updates_000\Worker( $this, $this->updates['free'] );
		}
		
		/**
		 * Начинает инициализацию лицензирования текущего плагина. Доступ к менеджеру лицензий можно
		 * получить через свойство license_manager.
		 *
		 * Дополнительно создает рабочего, чтобы совершить апгрейд до премиум версии
		 * и запустить проверку обновлений для этого модуля.
		 *
		 * @since 4.1.1
		 * @throws Exception
		 */
		protected function init_plugin_premium_features() {
			if ( empty( $this->licensing ) ) {
				return;
			}
			
			try {
				$this->modules_dependent( array(
					'factory_updates_000',
					'factory_license_000',
					'factory_notices_000'
				) );
			} catch( Exception $e ) {
				throw new Exception( $e->getMessage() );
			}
			
			$parsed_args = wp_parse_args( $this->licensing, array(
				'freemius_plugin_id'  => null,
				'freemius_public_key' => null,
				'slug'                => null
			) );
			
			$plugin_id  = $parsed_args['freemius_plugin_id'];
			$public_key = $parsed_args['freemius_public_key'];
			$slug       = $parsed_args['slug'];
			
			$this->license_manager = new Wbcr_FactoryLicense000_Base( $this, $plugin_id, $public_key, $slug );
			
			if ( isset( $this->updates['premium'] ) ) {
				new WBCR\Factory_Updates_000\Worker( $this, $this->updates['premium'], $this->license_manager );
			}
		}
		
		/**
		 * @since 4.1.1
		 *
		 * @param array | string $dependents
		 *
		 * @throws Exception
		 */
		protected function modules_dependent( $dependents ) {
			$modules = array();
			
			if ( is_array( $dependents ) ) {
				foreach ( $dependents as $module_name ) {
					if ( ! isset( $this->loaded_factory_modules[ $module_name ] ) ) {
						$modules[] = $module_name;
					}
				}
			} else {
				$modules[] = $dependents;
			}
			
			if ( ! empty( $modules ) ) {
				throw new Exception( "Ошибки в Factory framework. Ваша конфигурация плагина требует подключения дополнительных модулей фреймворка: " . implode( ',', $modules ) . "." );
			}
		}
		
		/**
		 * Converts string representation of the version to the numeric.
		 *
		 * @since 1.0.0
		 *
		 * @param string $version A string version to convert.
		 *
		 * @return integer
		 */
		protected function getVersionNumber( $version ) {
			preg_match( '/(\d+)\.(\d+)\.(\d+)/', $version, $matches );
			if ( count( $matches ) == 0 ) {
				return false;
			}
			
			$number = '';
			$number .= ( strlen( $matches[1] ) == 1 ) ? '0' . $matches[1] : $matches[1];
			$number .= ( strlen( $matches[2] ) == 1 ) ? '0' . $matches[2] : $matches[2];
			$number .= ( strlen( $matches[3] ) == 1 ) ? '0' . $matches[3] : $matches[3];
			
			return intval( $number );
		}
		
		// ----------------------------------------------------------------------
		// Finding files
		// ----------------------------------------------------------------------
		
		/**
		 * Returns a list of files at a given path.
		 *
		 * @param string $path path for search
		 */
		private function findFiles( $path ) {
			return $this->findFileOrFolders( $path, true );
		}
		
		/**
		 * Returns a list of folders at a given path.
		 *
		 * @param string $path path for search
		 */
		private function findFolders( $path ) {
			return $this->findFileOrFolders( $path, false );
		}
		
		/**
		 * Returns a list of files or folders at a given path.
		 *
		 * @param string $path path for search
		 * @param bool $files files or folders?
		 */
		private function findFileOrFolders( $path, $areFiles = true ) {
			if ( ! is_dir( $path ) ) {
				return array();
			}
			
			$entries = scandir( $path );
			if ( empty( $entries ) ) {
				return array();
			}
			
			$files = array();
			foreach ( $entries as $entryName ) {
				if ( $entryName == '.' || $entryName == '..' ) {
					continue;
				}
				
				$filename = $path . '/' . $entryName;
				if ( ( $areFiles && is_file( $filename ) ) || ( ! $areFiles && is_dir( $filename ) ) ) {
					$files[] = array(
						'path' => str_replace( "\\", "/", $filename ),
						'name' => $areFiles ? str_replace( '.php', '', $entryName ) : $entryName
					);
				}
			}
			
			return $files;
		}
		
		/**
		 * Gets php classes defined in a specified file.
		 *
		 * @param string $path
		 */
		private function getClasses( $path ) {
			
			$phpCode = file_get_contents( $path );
			
			$classes = array();
			$tokens  = token_get_all( $phpCode );
			
			$count = count( $tokens );
			for ( $i = 2; $i < $count; $i ++ ) {
				if ( is_array( $tokens ) && $tokens[ $i - 2 ][0] == T_CLASS && $tokens[ $i - 1 ][0] == T_WHITESPACE && $tokens[ $i ][0] == T_STRING ) {
					
					$extends = null;
					if ( $tokens[ $i + 2 ][0] == T_EXTENDS && $tokens[ $i + 4 ][0] == T_STRING ) {
						$extends = $tokens[ $i + 4 ][1];
					}
					
					$class_name       = $tokens[ $i ][1];
					$classes[ $path ] = array(
						'name'    => $class_name,
						'extends' => $extends
					);
				}
			}
			
			/**
			 * result example:
			 *
			 * $classes['/plugin/items/filename.php'] = array(
			 *      'name'      => 'PluginNameItem',
			 *      'extendes'  => 'PluginNameItemBase'
			 * )
			 */
			
			return $classes;
		}
		
		// ----------------------------------------------------------------------
		// Public methods
		// ----------------------------------------------------------------------
		
		public function newScriptList() {
			return new Wbcr_Factory000_ScriptList( $this );
		}
		
		public function newStyleList() {
			return new Wbcr_Factory000_StyleList( $this );
		}
	}
}
