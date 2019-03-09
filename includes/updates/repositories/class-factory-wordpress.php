<?php

namespace WBCR\Factory_000\Updates;

// Exit if accessed directly
use Wbcr_Factory000_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 *
 * @author Webcraftic <wordpress.webraftic@gmail.com>, Alex Kovalev <alex.kovalevv@gmail.com>
 * @link https://webcraftic.com
 * @copyright (c) 2018 Webraftic Ltd
 * @version 1.0
 */
class Wordpress_Repository extends Repository {
	
	/**
	 * Wordpress constructor.
	 *
	 * @param Wbcr_Factory000_Plugin $plugin
	 * @param bool $is_premium
	 */
	public function __construct( Wbcr_Factory000_Plugin $plugin, $is_premium = false ) {
		$this->plugin     = $plugin;
		$this->is_premium = $is_premium;
	}
	
	/**
	 * @return bool
	 */
	public function need_check_updates() {
		return false;
	}
	
	/**
	 * @return bool
	 */
	public function is_support_premium() {
		return false;
	}
	
	/**
	 * @return string
	 */
	public function get_download_url() {
		return '';
	}
	
	/**
	 * @return string
	 */
	public function get_last_version() {
		return '0.0.0';
	}
	
	public function check_updates() {
	
	}
	
	/**
	 * @return bool
	 */
	public function need_update() {
		return false;
	}
}