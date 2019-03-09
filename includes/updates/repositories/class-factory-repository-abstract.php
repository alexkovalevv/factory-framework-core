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
abstract class Repository {
	
	protected $plugin;
	
	protected $is_premium;
	
	/**
	 * Repository constructor.
	 *
	 * @param Wbcr_Factory000_Plugin $plugin
	 * @param bool $is_premium
	 */
	abstract public function __construct( Wbcr_Factory000_Plugin $plugin, $is_premium = false );
	
	/**
	 * @return bool
	 */
	abstract public function need_check_updates();
	
	/**
	 * @return mixed
	 */
	abstract public function is_support_premium();
	
	/**
	 * @return string
	 */
	abstract public function get_download_url();
	
	/**
	 * @return string
	 */
	abstract public function get_last_version();
}