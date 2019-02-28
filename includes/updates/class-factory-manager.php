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
	 * @param Wbcr_Factory000_Plugin $plugin
	 * @param $args
	 * @param bool $is_premium
	 *
	 * @throws Exception
	 */
	public function __construct( Wbcr_Factory000_Plugin $plugin, $args, $is_premium = false ) {
		
		$this->plugin     = $plugin;
		$this->is_premium = $is_premium;
		
		$parsed_args = wp_parse_args( $args, array(
			'repository' => 'wordpress',
			'slug'       => '',
			'rollback'   => array(
				'prev_stable_version' => '0.0.0'
			)
		) );
		
		$this->plugin_slug = $parsed_args['slug'];
		$this->rollback    = $parsed_args['rollback'];
		
		if ( empty( $this->plugin_slug ) || ! is_string( $this->plugin_slug ) ) {
			throw new Exception( 'Argument {slug} can not be empty and must be of type string.' );
		}
		
		$this->repository = $this->get_repository( $parsed_args['repository'] );
		
		if ( $this->is_premium && ! $this->repository->is_support_premium() ) {
			throw new Exception( "Repository {$parsed_args['repository']} does not have support premium." );
		}
		
		$this->init_hooks();
	}
	
	public function init_hooks() {
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
			add_action( "wbcr/factory/updates/check_for_{$this->plugin_slug}", array( $this, 'check_auto_updates' ) );
		}
		
		/*if ( ! $this->license_manager->is_install_premium() ) {
			$plugin_base = $plugin->getPluginPathInfo()->relative_path;
			
			add_action( "wbcr_factory_notices_000_list", array( $this, "intall_notice_everywhere" ) );
			add_action( "after_plugin_row_{$plugin_base}", array( $this, "install_notice_in_plugin_row" ), 100, 3 );
			add_action( "admin_print_styles-plugins.php", array( $this, "print_styles_for_plugin_row" ) );
			add_action( 'wbcr/factory/pages/impressive/print_all_notices', array(
				$this,
				'install_notice_in_plugin_interface'
			), 10, 2 );
		}*/
		/*if ( ! WP_FS__IS_PRODUCTION_MODE ) {
			add_filter( 'http_request_host_is_external', array(
				$this,
				'http_request_host_is_external_filter'
			), 10, 3 );
		}*/
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
	
	public function check_auto_updates() {
		$test = 'fsdf';
	}
	
	/**
	 * @param $repository_name
	 *
	 * @throws Exception
	 * @return Repository
	 */
	protected function get_repository( $repository_name ) {
		switch ( $repository_name ) {
			case 'wordpress':
				return new Wordpress_Repository( $this->plugin, $this->is_premium );
				break;
			case 'freemius':
				if ( ! defined( 'FACTORY_FREEMIUS_000_LOADED' ) ) {
					throw new Exception( 'If you have to get updates from the Freemius repository, you need to install the freemius module.' );
				}
				
				return new Freemius_Repository( $this->plugin, $this->is_premium );
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
		
		return new $repository_data['class_name']( $this->plugin, $this->is_premium );
	}
	
	
	/**
	 * Clears info about updates for the plugin.
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
	 * @since 4.1.1
	 */
	public function need_check_updates() {
	
	}
	
	/**
	 * Need to change a current assembly?
	 * @since 4.1.1
	 */
	public function need_change_assembly() {
	
	}
	
	/**
	 * Returns true if a plugin version has been checked up to the moment.
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
	public function upgrade() {
	
	}
	
	/**
	 * @since 4.1.1
	 */
	public function rollback() {
	
	}
	
	/**
	 * Выводит уведомление внутри интерфейса плагина, на всех страницах плагина.
	 *
	 * @since 4.1.1
	 *
	 * @param Wbcr_Factory000_Plugin $plugin
	 * @param Wbcr_FactoryPages000_ImpressiveThemplate $obj
	 *
	 * @return void
	 */
	public function install_notice_in_plugin_interface( $plugin, $obj ) {
		$obj->printWarningNotice( $this->get_please_install_premium_text() );
	}
	
	/**
	 * Выводит уведомление на всех страницах админ панели Wordpress
	 *
	 * @since 4.1.1
	 *
	 * @param $notices *
	 *
	 * @return array
	 */
	public function intall_notice_everywhere( $notices ) {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return $notices;
		}
		
		$notices[] = array(
			'id'              => 'please_install_premium_for_' . $this->plugin->getPluginName(),
			'type'            => 'warning',
			'dismissible'     => true,
			'dismiss_expires' => 0,
			'text'            => '<p><b>Robin image optimizer:</b> ' . $this->get_please_install_premium_text() . '</p>'
		);
		
		return $notices;
	}
	
	/**
	 * Выводит уведомление в строке плагина (на странице плагинов),
	 * что нужно установить премиум плагин.
	 *
	 * @since 4.1.1
	 * @see WP_Plugins_List_Table
	 *
	 * @param string $plugin_file
	 * @param array $plugin_data
	 * @param string $status
	 *
	 * @return void
	 */
	public function install_notice_in_plugin_row( $plugin_file, $plugin_data, $status ) {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		};
		?>
        <tr class="plugin-update-tr active update wbcr-factory-updates">
            <td colspan="3" class="plugin-update colspanchange">
                <div class="update-message notice inline notice-warning notice-alt">
                    <p>
						<?= $this->get_please_install_premium_text(); ?>
                    </p>
                </div>
            </td>
        </tr>
		<?php
	}
	
	/**
	 * Печатает стили для уведомления о загрузке премиум версии на странице плагинов.
	 *
	 * @since 4.1.1
	 * @return void
	 */
	public function print_styles_for_plugin_row() {
		?>
        <style>
            tr[data-plugin="<?= $this->plugin->getPluginPathInfo()->relative_path ?>"] th,
            tr[data-plugin="<?= $this->plugin->getPluginPathInfo()->relative_path ?>"] td {
                box-shadow: none !important;
            }

            .wbcr-factory-updates .update-message {
                background-color: #f5e9f5 !important;
                border-color: #dab9da !important;
            }
        </style>
		<?php
	}
	
	/**
	 * Текст уведомления. Уведомление требует установить премиум плагина,
	 * чтобы открыть дополнительные функции плагина.
	 *
	 * @since 4.1.1
	 * @return string
	 */
	protected function get_please_install_premium_text() {
		//$upgrade_url        = $this->license_manager->get_upgrade_url();
		$upgrade_url        = $this->get_download_url();
		$cancel_license_url = '';
		
		return sprintf( __( 'Congratulations, you have activated a premium license! Please install premium add-on to use pro features now.
        <a href="%s">Install</a> premium add-on or <a href="%s">cancel</a> license.', '' ), $upgrade_url, $cancel_license_url );
	}
}