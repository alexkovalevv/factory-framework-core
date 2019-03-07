<?php

namespace WBCR\Factory_000\Updates;

use Exception;
use Plugin_Installer_Skin;
use Plugin_Upgrader;
use WBCR\Factory_000\Premium\Provider;
use Wbcr_Factory000_Plugin;
use WBCR\Factory_Freemius_000\Updates\Freemius_Repository;
use Wbcr_FactoryPages000_ImpressiveThemplate;
use WP_Filesystem_Base;
use WP_Upgrader_Skin;

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
class Premium_Upgrader extends Upgrader {
	
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
		parent::__construct( $plugin );
		
		// todo: продумать поведение апгрейдера, если лицензия не активирована.
		// todo: что делать, если лицензия деактивирована, а премиум пакет установлен?
		// todo: как подчищать информацию при удалении пакета?
		
		if ( $this->plugin->premium->is_activate() && $this->plugin->premium->is_install_package() ) {
			$premium_package = $this->plugin->premium->get_package_data();
			
			if ( ! $premium_package ) {
				$this->plugin_basename      = $premium_package['base_path'];
				$this->plugin_main_file     = WP_PLUGIN_DIR . '/' . $premium_package['base_path'];
				$this->plugin_absolute_path = dirname( WP_PLUGIN_DIR . '/' . $premium_package['base_path'] );
			}
		} else {
			$this->plugin_basename      = null;
			$this->plugin_main_file     = null;
			$this->plugin_absolute_path = null;
		}
		
		if ( ! $this->repository->is_support_premium() ) {
			$settings = $this->get_settings();
			throw new Exception( "Repository {$settings['repository']} does not have support premium." );
		}
		
		$this->init_hooks();
	}
	
	/**
	 * @return array
	 */
	protected function get_settings() {
		$settings = $this->plugin->getPluginInfoAttr( 'license_settings' );
		
		$updates_settings = isset( $settings['updates_settings'] ) ? $settings['updates_settings'] : array();
		
		if ( is_array( $settings ) ) {
			$updates_settings['repository'] = $settings['provider'];
			$updates_settings['slug']       = $settings['slug'];
		}
		
		return wp_parse_args( $updates_settings, array(
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
	 * @return bool
	 */
	/*protected function need_check_premium_updates() {
		return $this->plugin->premium->is_activate() && $this->plugin->premium->is_install_package();
	}*/
	
	/**
	 * @since 4.1.1
	 */
	protected function init_hooks() {
		parent::init_hooks();
		
		if ( $this->repository->need_check_updates() ) {
			
			if ( $this->repository->need_check_updates() && is_admin() ) {
				add_action( "wbcr/factory/license_activate", array( $this, "register_cron_tasks" ) );
				add_action( "wbcr/factory/license_deactivate", array( $this, "clear_cron_tasks" ) );
			}
			
			if ( $this->plugin->premium->is_activate() && ! $this->plugin->premium->is_install_package() ) {
				$plugin_base = $this->plugin->get_paths()->basename;
				
				add_action( "wbcr_factory_notices_000_list", array( $this, "intall_notice_everywhere" ) );
				add_action( "after_plugin_row_{$plugin_base}", array( $this, "install_notice_in_plugin_row" ), 100, 3 );
				add_action( "admin_print_styles-plugins.php", array( $this, "print_styles_for_plugin_row" ) );
				add_action( 'wbcr/factory/pages/impressive/print_all_notices', array(
					$this,
					'install_notice_in_plugin_interface'
				), 10, 2 );
			}
			
			add_action( 'admin_init', array( $this, 'init_actions' ) );
			/*if ( ! WP_FS__IS_PRODUCTION_MODE ) {
				add_filter( 'http_request_host_is_external', array(
					$this,
					'http_request_host_is_external_filter'
				), 10, 3 );
			}*/
		}
	}
	
	/**
	 * @since 4.1.1
	 */
	public function init_actions() {
		if ( isset( $_GET['wbcr_factory_premium_updates_action'] ) ) {
			$action = $this->plugin->request->get( 'wbcr_factory_premium_updates_action' );
			
			check_admin_referer( "factory_premium_{$action}" );
			
			switch ( $action ) {
				case 'install':
					
					try {
						$this->install();
					} catch( Exception $e ) {
						wp_die( $e->getMessage() );
					}
					
					break;
				case 'check_updates':
					
					$this->check_auto_updates();
					
					break;
				case 'delete':
					
					//$this->delete_premium();
					
					break;
				case 'cancel_license':
					try {
						$this->plugin->premium->deactivate();
					} catch( Exception $e ) {
						wp_die( $e->getMessage() );
					}
					
					break;
			}
		}
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
	 * @return string
	 */
	public function get_upgrade_premium_url() {
		$args = array( 'wbcr_factory_premium_updates_action' => 'install' );
		
		return wp_nonce_url( $this->get_admin_url( $args ), "factory_premium_upgrade" );
	}
	
	/**
	 * @return string
	 */
	public function get_cancel_license_url() {
		$args = array( 'wbcr_factory_premium_updates_action' => 'cancel_license' );
		
		return wp_nonce_url( $this->get_admin_url( $args ), "factory_premium_cancel_license" );
	}
	
	/**
	 * @return string
	 */
	public function get_check_updates_premium_url() {
		$args = array( 'wbcr_factory_premium_updates_action' => 'check_updates' );
		
		return wp_nonce_url( $this->get_admin_url( $args ), "factory_premium_check_updates" );
	}
	
	/**
	 * @return string
	 */
	public function get_delete_premium_url() {
		$args = array( 'wbcr_factory_premium_updates_action' => 'delete' );
		
		return wp_nonce_url( $this->get_admin_url( $args ), "factory_premium_delete" );
	}
	
	/**
	 * @since 4.1.1
	 * @throws Exception
	 */
	public function install() {
		
		if ( $this->plugin->premium->is_install_package() ) {
			throw new Exception( 'Premium package is already installed!' );
		}
		
		global $wp_filesystem;
		
		if ( ! current_user_can( 'install_plugins' ) ) {
			throw new Exception( 'Sorry, you are not allowed to install plugins on this site.' );
		}
		
		if ( ! $wp_filesystem ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}
			WP_Filesystem();
		}
		
		if ( ! WP_Filesystem( false, WP_PLUGIN_DIR ) ) {
			throw new Exception( 'You are not allowed to edt folders/files on this site' );
		} else {
			
			// If plugin is installed before we update the premium package in database.
			// ------------------------------------------------------------------------
			$plugins = get_plugins( $plugin_folder = '' );
			
			if ( ! empty( $plugins ) ) {
				foreach ( (array) $plugins as $plugin_base => $plugin ) {
					$basename_parts = explode( '/', $plugin_base );
					if ( sizeof( $basename_parts ) == 2 && $basename_parts[0] == $this->plugin_slug ) {
						
						$this->plugin_basename      = $plugin_base;
						$this->plugin_main_file     = WP_PLUGIN_DIR . '/' . $plugin_base;
						$this->plugin_absolute_path = dirname( WP_PLUGIN_DIR . '/' . $plugin_base );
						
						$this->update_package_data();
						
						return;
					}
				}
			}
			// ------------------------------------------------------------------------
			
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/misc.php' );
			
			if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
				// Include required resources for the installation.
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}
			$download_url = $this->repository->get_download_url();
			
			//add_filter( 'async_update_translation', '__return_false', 1 );
			
			$skin_args = array(
				'type'   => 'web',
				'title'  => sprintf( 'Installing plugin: %s', $this->plugin->getPluginTitle() . ' Premium' ),
				'url'    => esc_url_raw( $download_url ),
				'nonce'  => 'install-plugin_' . $this->plugin_slug,
				'plugin' => '',
				'api'    => null,
				'extra'  => array(
					'slug' => $this->plugin_slug
				),
			);
			//$skin = new WP_Ajax_Upgrader_Skin( $skin_args );
			require_once( ABSPATH . 'wp-admin/admin-header.php' );
			
			if ( ! $this->plugin->premium->is_install_package() ) {
				$skin = new Plugin_Installer_Skin( $skin_args );
			} else {
				$skin = new WP_Upgrader_Skin( $skin_args );
			}
			
			$upgrader = new Plugin_Upgrader( $skin );
			
			if ( empty( $download_url ) ) {
				throw new Exception( 'You must pass the download url to upgrade up premium package.' );
			}
			
			$install_result = $upgrader->install( $download_url );
			
			include( ABSPATH . 'wp-admin/admin-footer.php' );
			
			if ( is_wp_error( $install_result ) ) {
				throw new Exception( $install_result->get_error_message(), $install_result->get_error_code() );
			} elseif ( is_wp_error( $skin->result ) ) {
				throw new Exception( $skin->result->get_error_message(), $skin->result->get_error_code() );
			} elseif ( is_null( $install_result ) ) {
				global $wp_filesystem;
				
				$error_code    = 'unable_to_connect_to_filesystem';
				$error_message = 'Unable to connect to the filesystem. Please confirm your credentials.';
				
				// Pass through the error from WP_Filesystem if one was raised.
				if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
					$error_message = $wp_filesystem->errors->get_error_message();
				}
				
				throw new Exception( $error_message, $error_code );
			}
			
			$this->plugin_basename      = $upgrader->plugin_info();
			$this->plugin_main_file     = WP_PLUGIN_DIR . '/' . $this->plugin_basename;
			$this->plugin_absolute_path = dirname( WP_PLUGIN_DIR . '/' . $this->plugin_basename );
			
			$this->update_package_data();
			
			die();
		}
	}
	
	/**
	 * @param array $plugin_data
	 *
	 * @throws Exception
	 */
	public function update_package_data() {
		
		if ( ! $this->plugin_main_file ) {
			return;
		}
		
		//$plugin_data = get_plugin_data( $this->plugin_main_file );
		
		$default_headers = array(
			'Version'          => 'Version',
			'FrameworkVersion' => 'Framework Version'
		);
		
		$plugin_data = get_file_data( $this->plugin_main_file, $default_headers, 'plugin' );
		
		$this->plugin->premium->update_package_data( array(
			'base_path'         => $this->plugin_basename,
			'version'           => $plugin_data['Version'],
			'framework_version' => isset( $plugin_data['FrameworkVersion'] ) ? $plugin_data['FrameworkVersion'] : null,
		) );
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
            tr[data-plugin="<?= $this->plugin->get_paths()->basename ?>"] th,
            tr[data-plugin="<?= $this->plugin->get_paths()->basename ?>"] td {
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
		$upgrade_url        = $this->get_upgrade_premium_url();
		$cancel_license_url = $this->get_cancel_license_url();
		
		return sprintf( __( 'Congratulations, you have activated a premium license! Please install premium add-on to use pro features now.
        <a href="%s">Install</a> premium add-on or <a href="%s">cancel</a> license.', '' ), $upgrade_url, $cancel_license_url );
	}
}