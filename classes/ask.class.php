<?php
/**
 * Class to manage database SELECT operations
 *
 *
 * @link              https://finpose.com
 * @since             1.0.0
 * @package           Finpose
 * @author            info@finpose.com
 */
if ( !class_exists( 'fafw_ask' ) ) {
  class fafw_ask {

    public $db;
    public $errmsg;

    /**
	 * Constructor
	 */
    function __construct($db) {
    global $wpdb;
      $this->db = $wpdb;
    }

    /**
	 * Get active products
	 */
    function getActiveProducts() {
      $args = array(
        'status' => 'publish',
        'limit'  => -1
      );
    return wc_get_products( $args );
    }

    /**
	 * Get All products
	 */
    function getAllProducts() {
      $args = array(
        'status' => array('draft', 'pending', 'private', 'publish'),
        'limit'  => -1
      );
    return wc_get_products( $args );
    }

    /**
	 * Get orders by date
	 */
    function getOrdersByDate($start,$end) {
      $args = array( 'status' => 'completed', 'date_paid' => $start."...".$end, 'limit' => -1 );
      $orders = wc_get_orders( $args );
    return $orders;
    }

    /**
	 * Get Order by ID
	 */
    function getOrderById($oid) {
      return wc_get_order($oid);
    }

    /**
	 * Select Rows
	 */
    function selectRows($q, $vals) {
      $r = $this->db->get_results( $this->db->prepare($q, $vals) );
    return $r;
    }

    /**
	 * Select Row
	 */
    function selectRow($q, $vals) {
      $r = $this->db->get_row( $this->db->prepare($q, $vals) );
    return $r;
    }

    /**
	 * Get Var
	 */
  function getVar($q, $vals=array()) {
    $r = $this->db->get_var( $this->db->prepare($q, $vals) );
  return $r;
  }

  }
}
