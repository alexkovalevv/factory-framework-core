<?php
	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}
	
	if( !class_exists('Wbcr_Factory000_Base') ) {
		class  Wbcr_Factory000_Base {

			/**
			 * Is a current page one of the admin pages?
			 *
			 * @since 1.0.0
			 * @var bool
			 */
			public $is_admin;
			
			/**
			 * Префикс для пространства имен среди опций Wordpress
			 *
			 * @var string
			 */
			protected $prefix;

			/**
			 * Заголовок плагина
			 *
			 * @var string
			 */
			protected $plugin_title;

			/**
			 * Название плагина
			 *
			 * @var string
			 */
			protected $plugin_name;

			/**
			 * Версия плагина
			 *
			 * @var string
			 */
			protected $plugin_version;

			/**
			 * Тип сборки плагина. Возможнные варианты: free, premium, trial
			 *
			 * @var string
			 */
			protected $plugin_build;

			/**
			 * @var string
			 */
			protected $plugin_assembly;

			/**
			 * Абсолютный путь к основному файлу плагина.
			 *
			 * @var string
			 */
			protected $main_file;

			/**
			 * Абсолютный путь к директории плагина
			 *
			 * @var string
			 */
			protected $plugin_root;

			/**
			 * Относительный путь к директории плагина
			 *
			 * @var string
			 */
			protected $relative_path;

			/**
			 * Ссылка на директорию плагина
			 *
			 * @var string
			 */
			protected $plugin_url;

			/**
			 * @var bool
			 */
			private $is_network_active;


			/**
			 * Буферизуем опции плагинов в этот атрибут, для быстрого доступа
			 *
			 * @var array
			 */
			private static $_opt_buffer = array();
			
			/**
			 * Экзамеляр класса Wbcr_Factory000_Request, необходим управляет http запросами
			 *
			 * @var Wbcr_Factory000_Request
			 */
			public $request;
			
			public function __construct($plugin_path, $data)
			{
				$this->request = new Wbcr_Factory000_Request();

				foreach((array)$data as $option_name => $option_value) {
					if( property_exists($this, $option_name) ) {
						$this->$option_name = $option_value;
					}
				}

				if( empty($this->prefix) || empty($this->plugin_title) || empty($this->plugin_version) || empty($this->plugin_build) ) {
					throw new Exception('One of the required attributes has not been passed (prefix,plugin_title,plugin_name,plugin_version,plugin_build).');
				}

				// saves plugin basic paramaters
				$this->main_file = $plugin_path;
				$this->plugin_root = dirname($plugin_path);
				$this->relative_path = plugin_basename($plugin_path);
				$this->plugin_url = plugins_url(null, $plugin_path);

				// used only in the module 'updates'
				$this->plugin_slug = !empty($this->plugin_name) ? $this->plugin_name : basename($plugin_path);

				// Makes sure the plugin is defined before trying to use it
				if( !function_exists('is_plugin_active_for_network') ) {
					require_once(ABSPATH . '/wp-admin/includes/plugin.php');
				}

				$this->is_network_active = is_plugin_active_for_network($this->relative_path);

				if( !isset(self::$_opt_buffer[$this->prefix]) ) {

					if( $this->isNetworkActive() ) {
						$cache_options = get_site_option($this->prefix . 'cache_options', array());
					} else {
						$cache_options = get_option($this->prefix . 'cache_options', array());
					}

					if( empty($cache_options) || !is_array($cache_options) ) {
						$cache_options = array();
						if( $this->isNetworkActive() ) {
							delete_option($this->prefix . 'cache_options');
						} else {
							delete_site_option($this->prefix . 'cache_options');
						}
					}

					self::$_opt_buffer[$this->prefix] = $cache_options;
				}
			}

			/**
			 * Активирован ли сайт в режиме мультисайтов и мы находимся в области суперадминистратора
			 * @return bool
			 */
			public function isMultisiteNetworkAdmin()
			{
				return is_multisite() && is_network_admin();
			}

			/**
			 * Активирован ли плагин для сети
			 * @return bool
			 */
			public function isNetworkActive()
			{
				return $this->is_network_active;
			}

			/**
			 * Получает список активных сайтов сети
			 * @return array|int
			 */
			public function getActiveSites($args = array('archived' => 0, 'mature' => 0, 'spam' => 0, 'deleted' => 0))
			{
				global $wp_version;

				if( version_compare($wp_version, '4.6', '>=') ) {
					return get_sites($args);
				} else {
					$converted_array = array();

					$sites = wp_get_sites($args);

					if( empty($sites) ) {
						return $converted_array;
					}

					foreach((array)$sites as $key => $site) {
						$obj = new stdClass();
						foreach($site as $attr => $value) {
							$obj->$attr = $value;
						}
						$converted_array[$key] = $obj;
					}

					return $converted_array;
				}
			}
			
			/**
			 * Получает опцию из кеша или из базы данные, если опция не кешируемая,
			 * то опция тянется только из базы данных. Не кешируемые опции это массивы,
			 * сериализованные массивы, строки больше 150 символов
			 *
			 * @param string $option_name
			 * @param bool $default
			 * @return mixed|void
			 */
			public function getOption($option_name, $default = false)
			{
				if( $option_name == 'cache_options' ) {
					return $default;
				}
				
				$get_cache_option = $this->getOptionFromCache($option_name);
				
				if( !is_null($get_cache_option) ) {
					return $get_cache_option === false ? $default : $get_cache_option;
				}
				if( $this->isNetworkActive() ) {
					$option_value = get_site_option($this->prefix . $option_name);
				} else {
					$option_value = get_option($this->prefix . $option_name);
				}
				
				if( $this->isCacheable($option_value) ) {
					$this->setCacheOption($option_name, $this->normalizeValue($option_value));
				}
				
				return $option_value === false ? $default : $this->normalizeValue($option_value);
			}
			
			/**
			 * Обновляет опцию в базе данных и в кеше, кеш обновляется только кешируемых опций.
			 * Не кешируемые опции это массивы, сериализованные массивы, строки больше 150 символов
			 *
			 * @param string $option_name
			 * @param mixed $value
			 * @return void
			 */
			public function updateOption($option_name, $value)
			{
				if( $this->isCacheable($value) ) {
					$this->setCacheOption($option_name, $this->normalizeValue($value));
				} else {
					if( isset(self::$_opt_buffer[$this->prefix][$option_name]) ) {
						unset(self::$_opt_buffer[$this->prefix][$option_name]);

						$this->updateOption('cache_options', self::$_opt_buffer[$this->prefix]);
					}
				}

				if( $this->isNetworkActive() ) {
					update_site_option($this->prefix . $option_name, $value);
				} else {
					update_option($this->prefix . $option_name, $value);
				}
			}
			
			/**
			 * Пакетное обновление опций, также метод пакетно обновляет кеш в базе данных
			 * и в буфере опций, кеш обновляется только кешируемых опций. Не кешируемые опции это массивы,
			 * сериализованные массивы, строки больше 150 символов
			 *
			 * @param array $options
			 * @return bool
			 */
			public function updateOptions($options)
			{
				if( empty($options) ) {
					return false;
				}
				
				foreach((array)$options as $option_name => $option_value) {
					$this->updateOption($option_name, $option_value);
				}
				
				$this->updateCacheOptions($options);
				
				return true;
			}
			
			/**
			 * Удаляет опцию из базы данных, если опция есть в кеше,
			 * индивидуально удаляет опцию из кеша.
			 *
			 * @param string $option_name
			 * @return void
			 */
			public function deleteOption($option_name)
			{
				if( isset(self::$_opt_buffer[$this->prefix][$option_name]) ) {
					unset(self::$_opt_buffer[$this->prefix][$option_name]);
					
					$this->updateOption('cache_options', self::$_opt_buffer[$this->prefix]);
				}

				if( $this->isNetworkActive() ) {
					delete_site_option($this->prefix . $option_name . '_is_active');
					delete_site_option($this->prefix . $option_name);
				} else {
					delete_option($this->prefix . $option_name . '_is_active');
					delete_option($this->prefix . $option_name);
				}
			}
			
			/**
			 * Пакетное удаление опций, после удаления опции происходит очистка кеша и буфера опций
			 *
			 * @param array $options
			 * @return void
			 */
			public function deleteOptions($options)
			{
				if( !empty($options) ) {
					foreach((array)$options as $option_name) {
						if( isset(self::$_opt_buffer[$this->prefix][$option_name]) ) {
							unset(self::$_opt_buffer[$this->prefix][$option_name]);
						}
						if( $this->isNetworkActive() ) {
							delete_site_option($this->prefix . $option_name . '_is_active');
							delete_site_option($this->prefix . $option_name);
						} else {
							delete_option($this->prefix . $option_name . '_is_active');
							delete_option($this->prefix . $option_name);
						}
					}

					$this->updateOption('cache_options', self::$_opt_buffer[$this->prefix]);
				}
			}
			
			/**
			 * Сбрасывает кеш опций, удаляет кеш из базы данных и буфер опций
			 *
			 * @return bool
			 */
			public function flushOptionsCache()
			{
				if( isset(self::$_opt_buffer[$this->prefix]) ) {
					unset(self::$_opt_buffer[$this->prefix]);
					self::$_opt_buffer[$this->prefix] = array();
				}
				
				$this->deleteOption('cache_options');
			}


			/**
			 * Возвращает название опции в пространстве имен плагина
			 *
			 * @param string $option_name
			 * @return null|string
			 */
			public function getOptionName($option_name)
			{
				$option_name = trim(rtrim($option_name));
				if( empty($option_name) || !is_string($option_name) ) {
					return null;
				}

				return $this->prefix . $option_name;
			}

			/**
			 * Проверяет является ли опция кешируемой. Кешируемые опции это массивы,
			 * сериализованные массивы, строки больше 150 символов.
			 *
			 * @param string $data - переданое значение опции
			 * @return bool
			 */
			public function isCacheable($data)
			{
				if( (is_string($data) && (is_serialized($data) || strlen($data) > 150)) || is_array($data) ) {
					return false;
				}

				return true;
			}

			/**
			 * Приведение значений опций к строгому типу данных
			 *
			 * @param mixed $string
			 * @return bool|int
			 */
			public function normalizeValue($data)
			{
				if( is_string($data) ) {
					$check_string = rtrim(trim($data));

					if( $check_string == "1" || $check_string == "0" ) {
						return intval($data);
					} else if( $check_string === 'false' ) {
						return false;
					} else if( $check_string === 'true' ) {
						return true;
					}
				}

				return $data;
			}

			/**
			 * Получает все опций текущего плагина
			 *
			 * @param bool $is_cacheable - только кешируемые опции, кешируемые опции это массивы,
			 * сериализованные массивы, строки больше 150 символов
			 * @return array
			 */
			protected function getAllPluginOptions($is_cacheable = true)
			{
				global $wpdb;
				$options = array();

				if( $this->isNetworkActive() ) {
					$network_id = get_current_network_id();

					$request = $wpdb->get_results($wpdb->prepare("
						SELECT meta_key, meta_value
						FROM {$wpdb->sitemeta}
						WHERE site_id = '%d' AND meta_key
						LIKE '%s'", $network_id, $this->prefix . "%"));
				} else {
					$request = $wpdb->get_results($wpdb->prepare("
						SELECT option_name, option_value
						FROM {$wpdb->options}
						WHERE option_name
						LIKE '%s'", $this->prefix . "%"));
				}

				if( !empty($request) ) {
					foreach((array)$request as $option) {
						if( $this->isNetworkActive() ) {
							$options_name = $option->meta_key;
							$option_value = $option->meta_value;
						} else {
							$options_name = $option->option_name;
							$option_value = $option->option_value;
						}
						if( $is_cacheable && !$this->isCacheable($option_value) ) {
							continue;
						}
						$options[$options_name] = $this->normalizeValue($option_value);
					}
				}

				return $options;
			}


			/**
			 * Записывает только одну опцию в кеш базы данных и в буфер
			 *
			 * @param string $option_name
			 * @param string $value
			 * @return void
			 * @throws Exception
			 */
			protected function setCacheOption($option_name, $value)
			{
				$this->setBufferOption($option_name, $value);

				if( !empty(self::$_opt_buffer[$this->prefix]) ) {
					$this->updateOption('cache_options', self::$_opt_buffer[$this->prefix]);
				}
			}

			/**
			 * Пакетное обновление опций в кеше и буфер опций,
			 * все записываемые опции приводятся к регламентированному типу данных
			 *
			 * @param array $options
			 * @return bool
			 * @throws Exception
			 */
			protected function updateCacheOptions($options)
			{
				foreach((array)$options as $option_name => $value) {
					$option_name = str_replace($this->prefix, '', $option_name);
					$this->setBufferOption($option_name, $this->normalizeValue($value));
				}

				if( !empty(self::$_opt_buffer[$this->prefix]) ) {
					$this->updateOption('cache_options', self::$_opt_buffer[$this->prefix]);
				}

				return false;
			}

			/**
			 * Получает опцию из кеша или буфера, если опция не найдена и буфер пуст,
			 * то заполняет буфер кеширумыми опциями, которые уже записаны в базу данных.
			 *
			 * @param string $option_name
			 * @return null
			 * @throws Exception
			 */
			protected function getOptionFromCache($option_name)
			{
				if( empty(self::$_opt_buffer[$this->prefix]) ) {
					$all_options = $this->getAllPluginOptions();
					
					if( !empty($all_options) ) {
						$this->updateCacheOptions($all_options);
					}
				}
				
				$buffer_option = $this->getBufferOption($option_name);
				
				if( !is_null($buffer_option) ) {
					return $buffer_option;
				}
				
				return null;
			}
			
			/**
			 * Получает опцию из буфера опций
			 *
			 * @param string $option_name
			 * @return null|mixed
			 */
			private function getBufferOption($option_name)
			{
				if( isset(self::$_opt_buffer[$this->prefix][$option_name]) ) {
					return self::$_opt_buffer[$this->prefix][$option_name];
				}
				
				return null;
			}
			
			/**
			 * Записывает опции в буфер опций, если опция уже есть в буфере и их значения не совпадают,
			 * то новое значение перезаписывает старое
			 *
			 * @param string $option_name
			 * @param string $option_value
			 */
			private function setBufferOption($option_name, $option_value)
			{
				if( !isset(self::$_opt_buffer[$this->prefix][$option_name]) ) {
					self::$_opt_buffer[$this->prefix][$option_name] = $option_value;
				} else {
					if( self::$_opt_buffer[$this->prefix][$option_name] !== $option_value ) {
						self::$_opt_buffer[$this->prefix][$option_name] = $option_value;
					}
				}
			}
		}
	}