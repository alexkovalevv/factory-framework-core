<?php
/**
 * Factory Function Library
 * 
 * ToDo: remove this file in future
 * 
 * @author Paul Kashtanoff <paul@byonepress.com>
 * @copyright (c) 2013, OnePress Ltd
 * 
 * @package factory-core 
 * @since 1.0.0
 */

/**
 * Returns a nonce based on a current wordpress blog options.
 * 
 * @since 1.0.0
 * @return string
 */
function factory_000_get_nonce() {
    $values = array('name', 'description', 'admin_email', 'url', 'language', 'version');
    $line = '';
    
    foreach($values as $value) $line .= get_bloginfo($value);
    return md5( $line );
}

/**
 * Prints a nonce based on a current wordpress blog options.
 * 
 * @since 1.0.0
 * @return string
 */
function factory_000_nonce() {
    echo factory_000_get_nonce();
}

// ------------------------------
// String functions
// ------------------------------

/**
 * Checks if $haystack strats with $needle.
 * 
 * @since 1.0.0
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function factory_000_starts_with($haystack, $needle) {
    return !strncmp($haystack, $needle, strlen($needle));
}

/**
 * Checks if $haystack ends with $needle.
 * 
 * @since 1.0.0
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function factory_000_ends_with($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) return true;
    return (substr($haystack, -$length) === $needle);
}

// ------------------------------
// Json functions
// ------------------------------

function factory_000_json_error($error) {
    echo json_encode(array('error' => $error));
    exit;
}

function factory_000_json_success( $data = array() ) {
    $data['error'] = false;
    echo json_encode( $data );
    exit;
}

// ------------------------------
// File functions
// ------------------------------

/**
 * Gets data about a given path.
 * 
 * @since 1.0.0
 * @param type $path A path to get data.
 * @return string[]
 */
function factory_000_pathinfo( $path ) {
    $data = pathinfo($path);
    $data['filename'] = factory_000_filename_without_ext($data['basename']);
    return $data;
}

/**
 * Gets a file name without its extention.
 * 
 * @since 1.0.0
 * @param string $filename A file name to process.
 * @return string
 */
function factory_000_filename_without_ext($filename){
    $pos = strripos($filename, '.');
    if($pos === false){
        return $filename;
    }else{
        return substr($filename, 0, $pos);
    }
}

/**
 * Gets a file extention.
 * 
 * @since 1.0.0
 * @param string $filename A file name to get an extention.
 * @return string
 */
function factory_000_filename_ext($filename){
    $result = explode('.', $filename);
    if ( empty($result) ) return null;
    return $result[count($result)-1];
}

function factory_000_get_human_filesize_by_path( $path ) {
    $bytes = filesize( $path );
    return factory_get_human_filesize( $bytes );
}

function factory_000_get_human_filesize( $bytes ) {
    if ($bytes >= 1073741824)
    {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    }
    elseif ($bytes >= 1048576)
    {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    }
    elseif ($bytes >= 1024)
    {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    }
    elseif ($bytes > 1)
    {
        $bytes = $bytes . ' bytes';
    }
    elseif ($bytes == 1)
    {
        $bytes = $bytes . ' byte';
    }
    else
    {
        $bytes = '0 bytes';
    }

    return $bytes;
}

// ------------------------------
// Date functions
// ------------------------------

/**
 * @since 1.0.0
 */
function factory_000_get_days( $startDate, $endDate ) {
    return floor( abs( $endDate - $startDate ) / (60*60*24) ) + 1;
}

/**
 * @since 1.0.0
 */
function factory_000_get_weeks( $startDate, $endDate ) {
    $days = factory_000_get_days( $startDate, $endDate );
    
    $startDay = date( "w", $startDate );
    $endDay = date( "w", $endDate );
    
    if ( $days < 7 ) {
        if ( $endDay < $startDay ) {
            return 2;
        } else {
            return 1;
        }
    } else {
        return floor( $days / 7 ); 
    }
}

/**
 * @since 1.0.0
 */
function factory_000_get_months( $startDate, $endDate ) {
    return floor( abs( $endDate - $startDate ) / (60*60*24*30) );
}

/**
 * Checks if a user is an administrator.
 * 
 * @since 1.5.0
 * @return type
 */
function factory_000_is_administrator() {
    return current_user_can( 'manage_options' );
}

/**
 * Prints hard-coded formatted html error.
 * 
 * @since 3.0.6
 * @param type $title a title of the error to print
 * @param type $message a message of the error to print
 * @return void
 */
function factory_000_print_error( $title, $message ) {
    ?>
    <div class="factory-error" style="padding: 10px; border: 3px solid #b23e3a; background: #cf4944; color: #fff;">
        <strong><?php echo $title ?></strong><br />
        <p style="margin: 0px;"><?php echo $message ?></p>
    </div>
    <?php
}

/**
 * Prints a script that resized parent iframe.
 * 
 * @since 3.0.6
 * @return void
 */
function factory_000_iframe_resize_script( $wrapId ) {
    ?>
    <script>
        var $ = window.parent.jQuery;
        var $doc = $(window.document);
        var $parentDoc = $(window.parent.document);
        
        var $body = $doc.find("body");
        if ( $body.length === 1 ) {
            
            var heigth = $body.height() + 20;
            if ( heigth < 150 ) heigth = 150;
    
            var $iframe = $parentDoc.find("#<?php echo $wrapId ?> iframe");
            $iframe.height(heigth);
        }
    </script>
    <?php
}

/**
 * Sets a lazy redirect.
 * 
 * @since 3.0.6
 * @return void
 */
function factory_000_set_lazy_redirect( $url ) {
    update_option('factory_lazy_redirect', $url );
}

add_action('admin_init', 'factory_000_do_lazy_redirect');
function factory_000_do_lazy_redirect() {
    $url = get_option('factory_lazy_redirect', null );
    if ( empty($url) ) return;
    
    delete_option('factory_lazy_redirect');
    wp_redirect($url);
}


	/**
	 * Fires functions attached to a deprecated filter hook.
	 *
	 * When a filter hook is deprecated, the apply_filters() call is replaced with
	 * apply_filters_deprecated(), which triggers a deprecation notice and then fires
	 * the original filter hook.
	 *
	 * This is a copy of `apply_filters_deprecated` introduced in WP 4.6.
	 *
	 * @since 1.0.0
	 *
	 * @see factory_000_deprecated_hook()
	 *
	 * @param string $tag The name of the filter hook.
	 * @param array $args Array of additional function arguments to be passed to apply_filters().
	 * @param string $version The version of BP Block Users that deprecated the hook.
	 * @param string $replacement Optional. The hook that should have been used.
	 * @param string $message Optional. A message regarding the change.
	 *
	 * @return mixed
	 */
	function factory_000_apply_filters_deprecated($tag, $args, $version, $replacement = false, $message = null)
	{
		if( function_exists('apply_filters_deprecated') ) {
			return apply_filters_deprecated($tag, $args, $version, $replacement, $message);
		}
		if( !has_filter($tag) ) {
			return $args[0];
		}
		factory_000_deprecated_hook($tag, $version, $replacement, $message);

		return apply_filters_ref_array($tag, $args);
	}

	/**
	 * Fires functions attached to a deprecated action hook.
	 *
	 * When an action hook is deprecated, the do_action() call is replaced with
	 * do_action_deprecated(), which triggers a deprecation notice and then fires
	 * the original hook.
	 *
	 * This is a copy of `do_action_deprecated` introduced in WP 4.6.
	 *
	 * @since 1.0.0
	 *
	 * @see _deprecated_hook()
	 *
	 * @param string $tag The name of the action hook.
	 * @param array $args Array of additional function arguments to be passed to do_action().
	 * @param string $version The version of BP Block Users that deprecated the hook.
	 * @param string $replacement Optional. The hook that should have been used.
	 * @param string $message Optional. A message regarding the change.
	 *
	 * @return void
	 */
	function factory_000_do_action_deprecated($tag, $args, $version, $replacement = false, $message = null)
	{
		if( function_exists('do_action_deprecated') ) {
			do_action_deprecated($tag, $args, $version, $replacement, $message);

			return;
		}
		if( !has_action($tag) ) {
			return;
		}
		factory_000_deprecated_hook($tag, $version, $replacement, $message);
		do_action_ref_array($tag, $args);
	}

	/**
	 * Marks a deprecated action or filter hook as deprecated and throws a notice.
	 *
	 * Use the 'factory_000_deprecated_hook_run' action to get the backtrace describing where the
	 * deprecated hook was called.
	 *
	 * Default behavior is to trigger a user error if WP_DEBUG is true.
	 *
	 * This function is called by the do_action_deprecated() and apply_filters_deprecated()
	 * functions, and so generally does not need to be called directly.
	 *
	 * This is a copy of `_deprecated_hook` introduced in WP 4.6.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param string $hook The hook that was used.
	 * @param string $version The version of WordPress that deprecated the hook.
	 * @param string $replacement Optional. The hook that should have been used.
	 * @param string $message Optional. A message regarding the change.
	 */
	function factory_000_deprecated_hook($hook, $version, $replacement = null, $message = null)
	{
		/**
		 * Fires when a deprecated hook is called.
		 *
		 * @since 1.0.0
		 *
		 * @param string $hook The hook that was called.
		 * @param string $replacement The hook that should be used as a replacement.
		 * @param string $version The version of BP Block Users that deprecated the argument used.
		 * @param string $message A message regarding the change.
		 */
		do_action('deprecated_hook_run', $hook, $replacement, $version, $message);
		/**
		 * Filter whether to trigger deprecated hook errors.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $trigger Whether to trigger deprecated hook errors. Requires
		 *                      `WP_DEBUG` to be defined true.
		 */
		if( WP_DEBUG && apply_filters('deprecated_hook_trigger_error', true) ) {
			$message = empty($message)
				? ''
				: ' ' . $message;
			if( !is_null($replacement) ) {
				trigger_error(sprintf(__('%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.'), $hook, $version, $replacement) . $message);
			} else {
				trigger_error(sprintf(__('%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.'), $hook, $version) . $message);
			}
		}
	}

