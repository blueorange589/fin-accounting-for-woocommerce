<?php
/**
 * Fired during plugin activation.
 * This class defines all code necessary to run during the plugin's activation.
 * @link              https://finpose.com
 * @since             1.0.0
 * @package           Finpose
 * @author            info@finpose.com
 */
class fafw_Activator {

	/**
	 * Activation hook
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
    $fv = get_option('fafw_version');
    if(!$fv) {
      self::createTables();
      add_option( 'fafw_version', FAFW_VERSION );
      add_option( 'fafw_db_version', FAFW_DBVERSION );
      self::addDefaultOptions();
      self::addAccounts();
    } else {
      if($fv!=FAFW_VERSION) {
        update_option( 'fafw_version', FAFW_VERSION );
        self::addDefaultOptions();
        self::addAccounts();
      }

      $dbv = get_option('fafw_db_version');
      if($dbv && ($dbv!=FAFW_DBVERSION)) {
        self::updateTables();
      }
    }
	}

  public static function createTables() {
    global $wpdb;

      $charset_collate = $wpdb->get_charset_collate();

      $sql = "CREATE TABLE `fin_costs` (
        `coid` varchar(8) NOT NULL,
        `siteid` tinyint(4) NOT NULL DEFAULT '0',
        `type` enum('cost', 'expense', 'acquisition') NOT NULL,
        `cat` varchar(32) NOT NULL,
        `paidwith` varchar(32) NOT NULL,
        `items` varchar(512) NOT NULL DEFAULT '.',
        `amount` float(11,2) NOT NULL,
        `tr` float(11,2) NOT NULL DEFAULT '0.00',
        `name` varchar(128) NOT NULL,
        `notes` varchar(512) NOT NULL DEFAULT '',
        `datepaid` int(11) NOT NULL,
        `timecr` int(11) NOT NULL,
        `attfile` varchar(512) DEFAULT NULL,
        UNIQUE KEY coid (coid)
      ) $charset_collate;";

      $sql .= "CREATE TABLE `fin_inventory` (
        `iid` varchar(8) NOT NULL,
        `siteid` tinyint(4) NOT NULL DEFAULT '0',
        `pid` int(11) NOT NULL,
        `is_sold` enum('0', '1') NOT NULL DEFAULT '0',
        `cost` float(11,2) NOT NULL,
        `soldprice` float(11,2) NOT NULL,
        `timecr` int(11) NOT NULL,
        `timesold` int(11) NOT NULL,
        `data` text NOT NULL,
        UNIQUE KEY iid (iid)
      ) $charset_collate;";

      $sql .= "CREATE TABLE `fin_transfers` (
        `trid` varchar(8) NOT NULL,
        `siteid` tinyint(4) NOT NULL DEFAULT '0',
        `tfrom` varchar(32) NOT NULL,
        `tto` varchar(32) NOT NULL,
        `amount` float(11,2) NOT NULL,
        `notes` varchar(256) NOT NULL,
        `datetransfer` int(11) NOT NULL,
        `timecr` int(11) NOT NULL,
        UNIQUE KEY trid (trid)
      ) $charset_collate;";

      $sql .= "CREATE TABLE `fin_taxpaid` (
        `tid` varchar(8) NOT NULL,
        `siteid` tinyint(4) NOT NULL DEFAULT '0',
        `payid` varchar(64) NOT NULL,
        `amount` float(11,2) NOT NULL,
        `notes` varchar(256) NOT NULL,
        `datepaid` int(11) NOT NULL,
        `timecr` int(11) NOT NULL,
        UNIQUE KEY tid (tid)
      ) $charset_collate;";

      if ( ! function_exists('dbDelta') ) {
          require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      }

      dbDelta( $sql );

  }

  public static function updateTables() {
  global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE `fin_costs` (
      `coid` varchar(8) NOT NULL,
      `siteid` tinyint(4) NOT NULL DEFAULT '0',
      `type` enum('cost', 'expense', 'acquisition') NOT NULL,
      `cat` varchar(32) NOT NULL,
      `paidwith` varchar(32) NOT NULL,
      `items` varchar(512) NOT NULL DEFAULT '.',
      `amount` float(11,2) NOT NULL,
      `tr` float(11,2) NOT NULL DEFAULT '0.00',
      `name` varchar(128) NOT NULL,
      `notes` varchar(512) NOT NULL DEFAULT '',
      `datepaid` int(11) NOT NULL,
      `timecr` int(11) NOT NULL,
      `attfile` varchar(512) DEFAULT NULL,
      UNIQUE KEY coid (coid)
    ) $charset_collate;";

    $sql .= "CREATE TABLE `fin_inventory` (
      `iid` varchar(8) NOT NULL,
      `siteid` tinyint(4) NOT NULL DEFAULT '0',
      `pid` int(11) NOT NULL,
      `is_sold` enum('0', '1') NOT NULL DEFAULT '0',
      `cost` float(11,2) NOT NULL,
      `soldprice` float(11,2) NOT NULL,
      `timecr` int(11) NOT NULL,
      `timesold` int(11) NOT NULL,
      `data` text NOT NULL,
      UNIQUE KEY iid (iid)
    ) $charset_collate;";

    $sql .= "CREATE TABLE `fin_transfers` (
      `trid` varchar(8) NOT NULL,
      `siteid` tinyint(4) NOT NULL DEFAULT '0',
      `tfrom` varchar(32) NOT NULL,
      `tto` varchar(32) NOT NULL,
      `amount` float(11,2) NOT NULL,
      `notes` varchar(256) NOT NULL,
      `datetransfer` int(11) NOT NULL,
      `timecr` int(11) NOT NULL,
      UNIQUE KEY trid (trid)
    ) $charset_collate;";

    $sql .= "CREATE TABLE `fin_taxpaid` (
      `tid` varchar(8) NOT NULL,
      `siteid` tinyint(4) NOT NULL DEFAULT '0',
      `payid` varchar(64) NOT NULL,
      `amount` float(11,2) NOT NULL,
      `notes` varchar(256) NOT NULL,
      `datepaid` int(11) NOT NULL,
      `timecr` int(11) NOT NULL,
      UNIQUE KEY tid (tid)
    ) $charset_collate;";

    if ( ! function_exists('dbDelta') ) {
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    }

    dbDelta( $sql );

    update_option( 'fafw_db_version', FAFW_DBVERSION );
  }

  /**
   * Add default options required by the plugin
   */
  private static function addDefaultOptions() {
    $opt1 = get_option('fin-spending-types');
    if(!$opt1) {
      $costTypes = array(
        'cost' => 'Cost',
        'expense' => 'Expense',
        'acquisition' => 'Acquisition'
      );
      add_option( 'fin-spending-types', $costTypes );
    }

    $opt2 = get_option('fin-cost-categories');
    if(!$opt2) {
      $costCategories = array(
        'inventory' => array('jcode'=>'','name'=>'Inventory'),
        'customs' => array('jcode'=>'','name'=>'Customs'),
        'shipping' => array('jcode'=>'','name'=>'Shipping'),
        'employees' => array('jcode'=>'','name'=>'Employees'),
        'office' => array('jcode'=>'','name'=>'Office'),
        'storage' => array('jcode'=>'','name'=>'Storage'),
        'maintenance' => array('jcode'=>'','name'=>'Maintenance')
      );
      add_option( 'fin-cost-categories', $costCategories );
    }

    $opt3 = get_option('fin-expense-categories');
    if(!$opt3) {
      $expenseCategories = array(
        'services' => array('jcode'=>'','name'=>'Services'),
        'contracts' => array('jcode'=>'','name'=>'Contracts'),
        'hardware' => array('jcode'=>'','name'=>'Hardware'),
        'software' => array('jcode'=>'','name'=>'Software'),
        'online-tools' => array('jcode'=>'','name'=>'Online Tools'),
        'travel' => array('jcode'=>'','name'=>'Travel')
      );
      add_option( 'fin-expense-categories', $expenseCategories );
    }

    $opt4 = get_option('fin-acquisition-categories');
    if(!$opt4) {
      $acquisitionCategories = array(
        'online-ads' => array('jcode'=>'','name'=>'Online Advertising'),
        'offline-ads' => array('jcode'=>'','name'=>'Offline Advertising'),
        'email-marketing' => array('jcode'=>'','name'=>'Email Marketing'),
        'affiliate' => array('jcode'=>'','name'=>'Affilate Marketing'),
        'sponsorships' => array('jcode'=>'','name'=>'Sponsorships'),
        'contests' => array('jcode'=>'','name'=>'Contests'),
        'giveaways' => array('jcode'=>'','name'=>'Giveaways')
      );
      add_option( 'fin-acquisition-categories', $acquisitionCategories );
    }
  }

  /**
   * Import Gateways (BuiltIn Accounts) and save
   */
  private static function addAccounts() {
    $accs = get_option('fafw_accounts');
    if(!$accs) {
      $bigws = WC()->payment_gateways->get_available_payment_gateways();
      $finaccs = array(
        'cash' => array('name'=>'Cash', 'builtin'=> 0, 'type'=>'other', 'enabled'=>1)
      );
      if( $bigws ) {
        foreach( $bigws as $bigw ) {
          $finaccs[$bigw->id] = array('name'=>$bigw->title, 'builtin'=> 1, 'type'=>'gateway', 'enabled'=>1);
        }
      }
      add_option( 'fafw_accounts', $finaccs );
    }
  }


}
