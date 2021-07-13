<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @link              https://finpose.com
 * @since             1.0.0
 * @package           Finpose
 * @author            info@finpose.com
 */

if ( !class_exists( 'fafw' ) ) {
  class fafw {

      /**
       * The loader that's responsible for maintaining and registering all hooks that power
       * the plugin.
       *
       * @since    1.0.0
       * @access   protected
       * @var      Plugin_Name_Loader    $loader    Maintains and registers all hooks for the plugin.
       */
      protected $loader;

      /**
       * The unique identifier of this plugin.
       *
       * @since    1.0.0
       * @access   protected
       * @var      string    $plugin_name    The string used to uniquely identify this plugin.
       */
      protected $plugin_name;

      /**
       * The current version of the plugin.
       *
       * @since    1.0.0
       * @access   protected
       * @var      string    $version    The current version of the plugin.
       */
      protected $version;

      /**
       * Define the core functionality of the plugin.
       *
       * Set the plugin name and the plugin version that can be used throughout the plugin.
       * Load the dependencies, define the locale, and set the hooks for the admin area and
       * the public-facing side of the site.
       *
       * @since    1.0.0
       */
      public function __construct() {
          if ( defined( 'FAFW_VERSION' ) ) {
              $this->version = FAFW_VERSION;
          } else {
              $this->version = '1.0.0';
          }
          $this->plugin_name = 'fafw';
          
          $this->load_dependencies();
          $this->define_admin_hooks();
          //$this->define_public_hooks();

          // load app class
          require_once FAFW_PLUGIN_DIR . 'classes/app.class.php';

      }

      /**
       * Load the required dependencies for this plugin.
       *
       * Include the following files that make up the plugin:
       *
       * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
       * - Plugin_Name_i18n. Defines internationalization functionality.
       * - Plugin_Name_Admin. Defines all hooks for the admin area.
       * - Plugin_Name_Public. Defines all hooks for the public side of the site.
       *
       * Create an instance of the loader which will be used to register the hooks
       * with WordPress.
       *
       * @since    1.0.0
       * @access   private
       */
      private function load_dependencies() {

          /**
           * The class responsible for orchestrating the actions and filters of the
           * core plugin.
           */
          require_once FAFW_PLUGIN_DIR . 'includes/class-fafw-loader.php';

          /**
           * The class responsible for defining all actions that occur in the admin area.
           */
          require_once FAFW_PLUGIN_DIR . 'admin/class-fafw-admin.php';

          $this->loader = new fafw_Loader();

      }

      /**
       * Register all of the hooks related to the admin area functionality
       * of the plugin.
       *
       * @since    1.0.0
       * @access   private
       */
      private function define_admin_hooks() {

          $plugin_admin = new fafw_Admin( $this->get_plugin_name(), $this->get_version() );


          $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
          $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
          $this->loader->add_action( 'admin_menu', $plugin_admin, 'buildmenu' );


      }

      /**
       * Register all of the hooks related to the public-facing functionality
       * of the plugin.
       *
       * @since    1.0.0
       * @access   private
       */
      private function define_public_hooks() {

          $plugin_public = new fafw_Public( $this->get_plugin_name(), $this->get_version() );

          $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
          $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

      }

      /**
       * Run the loader to execute all of the hooks with WordPress.
       *
       * @since    1.0.0
       */
      public function run() {
          $this->loader->run();
      }

      /**
       * The name of the plugin used to uniquely identify it within the context of
       * WordPress and to define internationalization functionality.
       *
       * @since     1.0.0
       * @return    string    The name of the plugin.
       */
      public function get_plugin_name() {
          return $this->plugin_name;
      }

      /**
       * The reference to the class that orchestrates the hooks with the plugin.
       *
       * @since     1.0.0
       * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
       */
      public function get_loader() {
          return $this->loader;
      }

      /**
       * Retrieve the version number of the plugin.
       *
       * @since     1.0.0
       * @return    string    The version number of the plugin.
       */
      public function get_version() {
          return $this->version;
      }

  }
}
