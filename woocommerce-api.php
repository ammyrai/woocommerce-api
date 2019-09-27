<?php
/*
 *Plugin Name: Woocommerce API
 *Plugin URI :
 *Description: woocommerece REST API plugin
 *Author: Asif
 *Version: 1.0
 *Author URI: 2beards.co
 */

/*
Woocommerce API is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
*/

if (!defined('ABSPATH')) { exit; }

if (!defined('WOOCOMMERCE_API_PLUGIN_VERSION')) {
    define('WOOCOMMERCE_API_PLUGIN_VERSION', '1.0');
}
// error_reporting(1);

/**
** Check if WooCommerce is active
**/
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'woo_api_inactive_notice');
    return;
}

function woo_api_inactive_notice() {
    if ( current_user_can( 'activate_plugins' ) ) :
        if ( !class_exists( 'WooCommerce' ) ) :
            ?>
            <div id="message" class="error">
                <p>
                    <?php
                    printf(
                        __('%s requires %sWooCommerce%s to be active.', 'pos-pickup-store'),
                        '<strong>Woocommerce API</strong>',
                        '<a href="http://wordpress.org/plugins/woocommerce/" target="_blank" >',
                        '</a>'
                    );
                    ?>
                </p>
            </div>      
            <?php
        endif;
    endif;
}

// PLUGIN INSTALL FUNCTION
function woo_api_plugin_install() {
	/**
	* Check if WooCommerce are active
	**/
	if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

		// Deactivate the plugin
		deactivate_plugins( __FILE__ );

		// Throw an error in the wordpress admin console
		$error_message = __('POS Store plugin requires WooCommerce to be installed and active. You can download <a href="https://woocommerce.com/" target="_blank">WooCommerce here</a>.', 'woocommerce');
		die($error_message);
	}else{
        // register_tables();
        if (!file_exists(plugin_dir_path(__FILE__).'logs')) {
            mkdir(plugin_dir_path(__FILE__).'logs', 0777, true);
        }
	}
}

// PLUGIN ACTIVATION HOOK
register_activation_hook( __FILE__, 'woo_api_plugin_install' );

function woo_api_activated_redirect( $plugin ) {
    if( $plugin == plugin_basename( __FILE__ ) ) {
        exit( wp_redirect( admin_url( 'admin.php?page=woo_api' ) ) );
    }
}
add_action( 'activated_plugin', 'woo_api_activated_redirect' );

// CREATING MAIN MENUS
function woo_api_options_page() {
    add_menu_page(
        'Woo API',
        'Woo API',
        'manage_options',
        'woo_api',
        'woo_api_setting',
        plugin_dir_url(__FILE__) . 'assets/img/woo-api-logo_20x20.png',
        20
    );
}
// ADD MENU HOOK
add_action( 'admin_menu', 'woo_api_options_page' );
// MENU STORE SHOW FUNCTION
include_once 'includes/setting.php';

// ENABLE DISABLE API
if (get_option('api-checkbox') == 1) {
    include_once 'includes/api.php';
}

