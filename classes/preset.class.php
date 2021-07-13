<?php
/**
 * Container class of predefined presets
 *
 *
 * @link              https://finpose.com
 * @since             1.0.0
 * @package           Finpose
 * @author            info@finpose.com
 */
if ( !class_exists( 'fafw_preset' ) ) {
  class fafw_preset {

    public $costTypes = array(
      'cost' => 'Cost',
      'expense' => 'Expense',
      'acquisition' => 'Acquisition'
    );

    public $costCategories = array(
      'inventory' => 'Inventory',
      'customs' => 'Customs',
      'shipping' => 'Shipping',
      'employees' => 'Employees',
      'office' => 'Office',
      'storage' => 'Storage',
      'maintenance' => 'Maintenance',
      'other' => 'Other'
    );

    public $expenseCategories = array(
      'services' => 'Services',
      'contracts' => 'Contracts',
      'hardware' => 'Hardware',
      'software' => 'Software',
      'online-tools' => 'Online Tools',
      'travel' => 'Travel',
      'other' => 'Other'
    );

    public $acquisitionCategories = array(
      'online-ads' => 'Online Advertising',
      'offline-ads' => 'Offline Advertising',
      'email-marketing' => 'Email Marketing',
      'affiliate' => 'Affilate Marketing',
      'sponsorships' => 'Sponsorships',
      'contests' => 'Contests',
      'giveaways' => 'Giveaways',
      'other' => 'Other'
    );

    public $currencies = array(
      'AUD' => 'Australian Dollar',
      'CAD' => 'Canadian Dollar',
      'JPY' => 'Japanese Yen',
      'EUR' => 'Euro',
      'USD' => 'US Dollar',
      'GBP' => 'Pound sterling'
    );

    public $months = array(
      '01' => 'January',
      '02' => 'February',
      '03' => 'March',
      '04' => 'April',
      '05' => 'May',
      '06' => 'June',
      '07' => 'July',
      '08' => 'August',
      '09' => 'September',
      '10' => 'October',
      '11' => 'November',
      '12' => 'December'
    );

    /**
	 * Constructor for Presets
	 */
    function __construct() {
      return get_object_vars($this);
    }

  }
}
