<?php

/**
 * Plugin Name: Sync eCommerce NEO
 * Plugin URI: https://www.closemarketing.es
 * Description: Imports Products and data from NEO to WooCommerce.
 * Author: closemarketing
 * Author URI: https://www.closemarketing.es/
 * Version: 1.4
 *
 * @package WordPress
 * Text Domain: sync-ecommerce-neo
 * Domain Path: /languages
 * License: GNU General Public License version 3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
defined( 'ABSPATH' ) || exit;
define( 'WCSEN_VERSION', '1.4' );
define( 'WCSEN_PLUGIN', __FILE__ );
define( 'WCSEN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCSEN_PLUGIN_DIR', untrailingslashit( dirname( WCSEN_PLUGIN ) ) );
define( 'WCSEN_TABLE_SYNC', 'wcsen_product_sync' );
define( 'PLUGIN_SLUG', 'sync-ecommerce-neo' );
define( 'PLUGIN_PREFIX', 'wcsync_' );
define( 'PLUGIN_OPTIONS', 'sync_ecommerce_neo' );
define( 'EXPIRE_TOKEN', 259200 );
// Loads translation.
add_action( 'init', 'wcsen_load_textdomain' );
/**
 * Load plugin textdomain.
 */
function wcsen_load_textdomain()
{
    load_plugin_textdomain( 'sync-ecommerce-neo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}


if ( function_exists( 'cmk_fs' ) ) {
    cmk_fs()->set_basename( false, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    
    if ( !function_exists( 'cmk_fs' ) ) {
        /**
         * Create a helper function for easy SDK access.
         *
         * @return function Dynamic init.
         */
        function cmk_fs()
        {
            global  $cmk_fs ;
            
            if ( !isset( $cmk_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';
                $cmk_fs = fs_dynamic_init( array(
                    'id'             => '7463',
                    'slug'           => 'sync-ecommerce-neo',
                    'premium_slug'   => 'sync-ecommerce-neo-premium',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_383663f6536abd96fc0baa8081b21',
                    'is_premium'     => false,
                    'premium_suffix' => '',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'trial'          => array(
                    'days'               => 7,
                    'is_require_payment' => false,
                ),
                    'menu'           => array(
                    'slug'       => 'import_sync-ecommerce-neo',
                    'first-path' => 'admin.php?page=import_sync-ecommerce-neo&tab=settings',
                ),
                    'is_live'        => true,
                ) );
            }
            
            return $cmk_fs;
        }
        
        // Init Freemius.
        cmk_fs();
        // Signal that SDK was initiated.
        do_action( 'cmk_fs_loaded' );
    }

}

// Includes files.
require_once dirname( __FILE__ ) . '/includes/helpers-functions.php';
require_once dirname( __FILE__ ) . '/includes/class-sync-admin.php';
require_once dirname( __FILE__ ) . '/includes/class-sync-import.php';
register_activation_hook( __FILE__, 'wcsen_create_db' );
/**
 * Creates the database
 *
 * @since  1.0
 * @access private
 * @return void
 */
function wcsen_create_db()
{
    global  $wpdb ;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . WCSEN_TABLE_SYNC;
    // DB Tasks.
    $sql = "CREATE TABLE {$table_name} (\n\t    sync_prodid INT NOT NULL,\n\t    synced bit(1) NOT NULL DEFAULT b'0',\n          UNIQUE KEY sync_prodid (sync_prodid)\n    \t) {$charset_collate};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
