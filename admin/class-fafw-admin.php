<?php
/**
 * The admin-specific functionality of the plugin.
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 * @link              https://finpose.com
 * @since             1.0.0
 * @package           Finpose
 * @author            info@finpose.com
 */

if ( !class_exists( 'fafw_Admin' ) ) {
  class fafw_Admin {

      /**
       * The ID of this plugin.
       *
       * @since    1.0.0
       * @access   private
       * @var      string    $plugin_name    The ID of this plugin.
       */
      private $plugin_name;

      /**
       * The version of this plugin.
       *
       * @since    1.0.0
       * @access   private
       * @var      string    $version    The current version of this plugin.
       */
      private $version;
      private $hook_suffixes = array();
      /**
       * Initialize the class and set its properties.
       *
       * @since    1.0.0
       * @param      string    $plugin_name       The name of this plugin.
       * @param      string    $version    The version of this plugin.
       */

      public $pageName = '';


      public function __construct( $plugin_name, $version ) {

          $this->plugin_name = $plugin_name;
          $this->version = $version;
          
      }

      /**
       * Register the stylesheets for the admin area.
       *
       * @since    1.0.0
       */
      public function enqueue_styles($hook = '') {
        //if( empty( $hook ) ) $hook = bp_core_do_network_admin() ? str_replace( '-network', '', get_current_screen()->id ) : get_current_screen()->id;

        if( in_array( $hook, $this->hook_suffixes ) ) {
          wp_enqueue_style('jquery-ui-css', FAFW_BASE_URL . 'admin/assets/lib/jqueryui/jquery-ui.min.css');
          wp_enqueue_style( 'finhelper', FAFW_BASE_URL . 'admin/assets/css/fin_helper.css', array(), $this->version, 'all' );
          wp_enqueue_style( 'fincss', FAFW_BASE_URL . 'admin/assets/css/fafw.css', array(), $this->version, 'all' );
          wp_enqueue_style( 'toastr', FAFW_BASE_URL . 'admin/assets/css/toastr.min.css', array(), $this->version, 'all' );
        }

      }

      /**
       * Register the JavaScript for the admin area.
       *
       * @since    1.0.0
       */
      public function enqueue_scripts($hook = '') {
        //if( empty( $hook ) ) $hook = bp_core_do_network_admin() ? str_replace( '-network', '', get_current_screen()->id ) : get_current_screen()->id;
        $screen = get_current_screen();
        $pageName = str_replace('accounting_page_fafw_', '', $screen->id);
        $pageName = str_replace('toplevel_page_fafw_', '', $pageName);
        $this->pageName = str_replace('admin_page_fafw_', '', $pageName);

        if( in_array( $hook, $this->hook_suffixes ) ) {
          add_thickbox();
          wp_enqueue_script('jquery-ui-datepicker');
          wp_enqueue_script( 'jqblock', FAFW_BASE_URL . 'admin/assets/js/jquery.blockUI.js', array( 'jquery' ), $this->version, true );
          wp_enqueue_script( 'fafwmain', FAFW_BASE_URL . 'admin/assets/js/main.js', array( 'jquery' ), $this->version, true );
          wp_enqueue_script( 'vue', FAFW_BASE_URL . 'admin/assets/js/vue.js', array( ), $this->version, false );
          if(in_array($this->pageName, array('taxes'))) {
            wp_enqueue_script( 'vuerouter', FAFW_BASE_URL . 'admin/assets/js/vue-router.min.js', array( 'vue' ), $this->version, false );
          }
          if(!in_array($this->pageName, array('pro'))) {
            wp_enqueue_script( 'vuepage', FAFW_BASE_URL . 'admin/assets/js/pages/'.$this->pageName.'.js', array( 'vue', 'fafwmain' ), $this->version, true );
          }
          if(in_array($this->pageName, array('dashboard'))) {
            wp_enqueue_script( 'finchart', FAFW_BASE_URL . 'admin/assets/js/Chart.min.js', array( 'jquery' ), $this->version, false );
          }
          wp_enqueue_script( 'table2csv', FAFW_BASE_URL . 'admin/assets/js/jquery.tabletoCSV.js', array( 'jquery' ), $this->version, false );
          wp_enqueue_script( 'toastr', FAFW_BASE_URL . 'admin/assets/js/toastr.min.js', array( 'jquery' ), $this->version, false );
          
          $symbol = '';
          if (function_exists('get_woocommerce_currency_symbol')) { $symbol = get_woocommerce_currency_symbol(); }  
          wp_localize_script('fafwmain', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'symbol'=>$symbol, 'fin_url'=> FAFW_BASE_URL, 'nonce'=>wp_create_nonce('fafwpost')));
        }
      }

      /**
       * Build Admin Menu
       *
       * @since    1.0.0
       */
      public function buildmenu() {
        $this->hook_suffixes[] = add_menu_page(true, __('Accounting', 'fafw'), 'view_woocommerce_reports', 'fafw_spendings', array($this, 'pageDisplay'), 'dashicons-list-view', 57);
        $this->hook_suffixes[] = add_submenu_page('fafw_spendings', __('Spendings', 'fafw'), __('Spendings', 'fafw'), 'view_woocommerce_reports', 'fafw_spendings', array($this, 'pageDisplay'));
        $this->hook_suffixes[] = add_submenu_page('fafw_spendings', __('Orders', 'fafw'), __('Orders', 'fafw'), 'view_woocommerce_reports', 'fafw_orders', array($this, 'pageDisplay'));
        $this->hook_suffixes[] = add_submenu_page('fafw_spendings', __('Taxes', 'fafw'), __('Taxes', 'fafw'), 'view_woocommerce_reports', 'fafw_taxes', array($this, 'pageDisplay'));
        $this->hook_suffixes[] = add_submenu_page('fafw_spendings', __('Accounts', 'fafw'), __('Accounts', 'fafw'), 'view_woocommerce_reports', 'fafw_accounts', array($this, 'pageDisplay'));
        $this->hook_suffixes[] = add_submenu_page('fafw_spendings', '', '<span class="dashicons dashicons-star-filled" style="font-size: 17px"></span> ' . __( 'Upgrade to Pro', 'fafw' ), 'view_woocommerce_reports', 'fafw_pro', array($this, 'pageDisplay'));

        // HIDDEN PAGES
        $this->hook_suffixes[] = add_submenu_page(null, __('Categories', 'fafw'), __('Categories', 'fafw'), 'view_woocommerce_reports', 'fafw_categories', array($this, 'pageDisplay'));
        $this->hook_suffixes[] = add_submenu_page(null, __('Inventory Items', 'fafw'), __('Inventory Items', 'fafw'), 'view_woocommerce_reports', 'fafw_inventory_items', array($this, 'pageDisplay'));
      
      }

      /**
       * Display requested page
       */
      public function pageDisplay() {

        $handlers = array(
          'spendings'=>'spendings',
          'categories'=>'spendings',
          'taxes'=>'taxes',
          'orders'=>'orders',
          'accounts'=>'accounts',
          'pro'=>'spendings'
        );

        $processes = array(
          'spendings'=>'getCosts',
          'categories'=>'getCategories',
          'taxes'=>'getTaxes',
          'orders'=>'pageOrders',
          'accounts'=>'pageAccounts',
          'pro'=>'pagePro'
        );

        if(current_user_can( 'view_woocommerce_reports' )) {
          if(isset($handlers[$this->pageName])) {
            $hc = $handlers[$this->pageName];
            require_once FAFW_PLUGIN_DIR . 'classes/'.$hc.'.class.php';
            $hn = 'fafw_'.$hc;
            $proc = null;
            if(isset($processes[$this->pageName])) {
              $proc = $processes[$this->pageName];
            }
            $handler = new $hn($proc);
          }
          include 'views/'.$this->pageName.'.php';
        } else {
          printf(
            '<div class="notice notice-error is-dismissible"><p><strong>%s</strong></p></div>',
            esc_html__( 'You are not allowed to display this page. Please contact administrator.', 'fafw' )
          );
        }
      }


  }
}
