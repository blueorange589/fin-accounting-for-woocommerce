<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://finpose.com
 * @since             1.0.0
 * @package           finpose
 *
 * @wordpress-plugin
 * Plugin Name:       Fin Accounting for WooCommerce
 * Plugin URI:        https://finpose.com
 * Description:       Bookkeeping for WooCommerce from the WordPress Dashboard.
 * Version:           1.0.0
 * WC requires at least:  3.0.0
 * WC tested up to:       5.0.0
 * Author:            Finpose
 * Author URI:        https://finpose.com
 * Text Domain:       fafw
 * Domain Path:       /languages
 *
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }


define( 'FAFW_VERSION', '1.0.0' );
define( 'FAFW_DBVERSION', '2.2.0' );
define( 'FAFW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FAFW_BASE_URL', plugin_dir_url( __FILE__ ) );
define( 'FAFW_ENV', 'production' );
define( 'FAFW_WP_URL', get_site_url() );
define( 'FAFW_WPADMIN_URL', get_admin_url() );

/**
 * Check if WooCommerce is installed & activated
 */
function fafw_is_woocommerce_activated() {
  $blog_plugins = get_option( 'active_plugins', array() );
  $site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option('active_sitewide_plugins' ) ) : array();

  if ( in_array( 'woocommerce/woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce/woocommerce.php'] ) ) {
      return true;
  } else {
      return false;
  }
}

/**
 * Generate error message if WooCommerce is not active
 */
function fafw_need_woocommerce() {
  $plugin_name = "Fin Accounting";
  printf(
    '<div class="notice error"><p><strong>%s</strong></p></div>',
    sprintf(
        esc_html__( '%s requires WooCommerce 3.0 or greater to be installed & activated!', 'fafw' ),
        $plugin_name
    )
  );
}

/**
 * Return error if WooCommerce is not active
 */
if (fafw_is_woocommerce_activated()) {

  /**
   * Activation hook
   */
  function fafw_activate() {
    require_once FAFW_PLUGIN_DIR . 'includes/class-fafw-activator.php';
    fafw_Activator::activate();
  }

  /**
   * Deactivation hook
   */
  function fafw_deactivate() {
    require_once FAFW_PLUGIN_DIR . 'includes/class-fafw-deactivator.php';
    fafw_Deactivator::deactivate();
  }

  /**
   * Register activation/deactivation hooks
   */
  register_activation_hook( __FILE__, 'fafw_activate' );
  register_deactivation_hook( __FILE__, 'fafw_deactivate' );

  /**
   * If version mismatch, upgrade
   */
  if ( FAFW_VERSION != get_option('fafw_version' )) {
    add_action( 'plugin_loaded', 'fafw_activate' );
  }

  /**
   * Handle AJAX requests
   */
  add_action( 'wp_ajax_fafw', 'fafw_ajax_request' );
  function fafw_ajax_request(){
    if(current_user_can( 'view_woocommerce_reports' )) {
      require FAFW_PLUGIN_DIR . 'includes/class-fafw-ajax.php';
      $ajax = new fafw_Ajax();
      // Sanitize every POST data as string, additional sanitation will be applied inside methods when necessary
      $p = array_map('sanitize_text_field', $_POST);
      $ajax->run($p);
      wp_die();
    }
  }

  /**
   * Adjust Inventory when a sale is made (completed)
   */
  function fafw_woocommerce_order_status_completed( $order_id ) {
    global $wpdb;
    $order = wc_get_order( $order_id );
    $items = $order->get_items();
    foreach ( $items as $item ) {
      $productid = $item->get_product_id();
      $variationid = $item->get_variation_id();
      $itemid = $variationid ? $variationid : $productid;
      $quantity = $item->get_quantity();
      $subtotal = $subtotal = $item->get_subtotal();
      $soldprice = $subtotal/$quantity;
      for($i=1;$i<=$quantity;$i++) {
        $nextinstock = $wpdb->get_var("SELECT iid FROM fin_inventory WHERE pid='$itemid' AND is_sold='0' ORDER BY timecr ASC LIMIT 1");
        if($nextinstock) {
          $extdata = json_encode(array('orderid'=>$order_id));
          $wpdb->update('fin_inventory', array('is_sold'=>'1', 'timesold'=>time(), 'soldprice'=>$soldprice, 'data'=>$extdata), array('iid'=>$nextinstock));
        }
      }
    }
  }
  add_action( 'woocommerce_order_status_completed', 'fin_woocommerce_order_status_completed', 10, 1 );

  /**
   * Adjust Inventory when order is refunded
   */
  function fafw_woocommerce_order_refunded( $order_id ) {
    global $wpdb;
    $order = wc_get_order( $order_id );
    $items = $order->get_items();
    foreach ( $items as $item ) {
      $productid = $item->get_product_id();
      $variationid = $item->get_variation_id();
      $itemid = $variationid ? $variationid : $productid;
      $quantity = $item->get_quantity();
      $subtotal = $subtotal = $item->get_subtotal();
      $soldprice = $subtotal/$quantity;
      for($i=1;$i<=$quantity;$i++) {
        $lastitemsold = $wpdb->get_var("SELECT iid FROM fin_inventory WHERE pid='$itemid' AND is_sold='1' ORDER BY timesold DESC LIMIT 1");
        if($lastitemsold) {
          $extdata = json_encode(array('orderID'=>$order_id, 'refunded'=>1));
          $wpdb->update('fin_inventory', array('is_sold'=>'0', 'timesold'=>0, 'soldprice'=>0, 'data'=>$extdata), array('iid'=>$lastitemsold));
        }
      }
    }
  }
  add_action( 'woocommerce_order_refunded', 'fin_woocommerce_order_refunded', 10, 1 );

  /**
   * Custom product query / LIKE operator
   */
  add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'fafw_custom_query_var', 10, 2 );
  function fafw_custom_query_var( $query, $query_vars ) {
      if ( isset( $query_vars['like_name'] ) && ! empty( $query_vars['like_name'] ) ) {
          $query['s'] = esc_attr( $query_vars['like_name'] );
      }

      return $query;
  }

  /**
   * Load FAFW
   */
  add_action( 'wp_loaded', function() {
    if(current_user_can( 'view_woocommerce_reports' )) {
      $user = wp_get_current_user();
      $roles = ( array ) $user->roles;
      if ( is_admin() || in_array("shop_manager", $roles)) {
        require FAFW_PLUGIN_DIR . 'includes/class-fafw.php';
        $plugin = new fafw();
        $plugin->run();
      }
    }
  }, 30 );

} else {
  add_action( 'admin_notices', 'fin_need_woocommerce' );
  return;
}

function fafw_load_textdomain() {
  load_plugin_textdomain( 'fafw', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'fafw_load_textdomain' );


