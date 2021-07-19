<?php
/**
 * Class for Tax management
 *
 *
 * @link              https://finpose.com
 * @since             1.0.0
 * @package           Finpose
 * @author            info@finpose.com
 */
if ( !class_exists( 'fafw_taxes' ) ) {
  class fafw_taxes extends fafw_app {

    public $table = 'fin_taxpaid';
    public $v = 'getTaxes';
    public $p = '';

    public $selyear;
    public $selmonth;

    public $success = false;
    public $message = '';
    public $results = array();
    public $callback = '';

    /**
	 * Taxes Constructor
	 */
    public function __construct($v = 'getTaxes') {
      parent::__construct();

      $this->selyear = $this->curyear;
      $this->selmonth = $this->curmonth;

      // POST verification, before processing
      if($this->post) {
        $validated = $this->validate();
        if($validated) {
          $verified = wp_verify_nonce( $this->post['nonce'], 'fafwpost' );
          $can = current_user_can( 'view_woocommerce_reports' );
          if($verified && $can) {
            if(isset($this->post['process'])) {
              $p = $this->post['process'];
              unset(
                $this->post['process'],
                $this->post['handler'],
                $this->post['action'],
                $this->post['nonce'],
                $this->post['_wp_http_referer']
              );
              $this->$p();
            }
          }
        }
      }

      if($v != 'ajax') { $this->$v(); }

      if($this->ask->errmsg) { $this->view['errmsg'] = $this->ask->errmsg; }
    }

    /**
	 * Validate all inputs before use
	 */
    public function validate() {
      $status = true;

      foreach ($this->post as $pk => $pv) {
        if($pk == 'year') {
          if(intval($pv)>2030||intval($pv)<2010) {
            $status = false;
            $this->message = esc_html__( 'Year provided is invalid', 'fafw' );
          }
        }
        if($pk == 'month') {
          if(intval($pv)>12||intval($pv)<1) {
            $status = false;
            $this->message = esc_html__( 'Month provided is invalid', 'fafw' );
          }
        }
        if(in_array($pk, array('amount', 'tr'))) {
          if(!preg_match('/^(?!0\.00)\d{1,3}(,\d{3})*(\.\d\d)?$/', $pv)) {
            $status = false;
            $this->message = esc_html__( 'Invalid money format', 'fafw' );
          }
        }
        if($pk == 'datepaid') {
          if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $pv)) {
            $status = false;
            $this->message = esc_html__( 'Date format provided is invalid', 'fafw' );
          }
        }
        if($pk == 'payid') {
          if(strlen($pv)>64) {
            $status = false;
            $this->message = esc_html__( 'Payment ID can not be longer than 64 characters', 'fafw' );
          }
        }
        if($pk == 'notes') {
          if(strlen($pv)>256) {
            $status = false;
            $this->message = esc_html__( 'Notes can not be longer than 256 characters', 'fafw' );
          }
        }
      }

    return $status;
    }

    /**
	 * Get list of taxes
	 */
    public function getTaxes() {
      if(isset($this->post['year'])) {
        $this->selyear = $this->post['year'];
      }

      $mstart = $this->selyear.'-01-01';
      $mend = ($this->selyear+1).'-01-01';

      $msu = strtotime($mstart);
      $mse = strtotime($mend)-1;

      $taxes = array();
      foreach($this->presets->months as $mk=>$mon) {
        $thismonth = date('n', time());
        $thisyear = date('Y', time());
        if($thisyear==$this->selyear) {
          if(intval($mk)>$thismonth) {
            $taxes[$mon] = array('payable'=>'', 'receivable'=>'', 'paid'=>'', 'balance'=>'');
          } else {
            $taxes[$mon] = array('payable'=>0, 'receivable'=>0, 'paid'=>0, 'balance'=>0);
          }
        } else {
          $taxes[$mon] = array('payable'=>0, 'receivable'=>0, 'paid'=>0, 'balance'=>0);
        }
      }
      
      $args = array( 'status' => 'completed', 'date_paid' => $mstart."...".$mend, 'limit' => -1 );
      $orders = wc_get_orders( $args );
      foreach( $orders as $order ) {
        $odate = $order->get_date_paid();
        $omonth = date('F', strtotime($odate));
        $mn = date('n', strtotime($odate));
        if(!$taxes[$omonth]['payable']) { $taxes[$omonth]['payable'] = 0; }
        $taxes[$omonth]['payable'] += $order->get_total_tax();
        $taxes[$omonth]['msu'] = strtotime($this->selyear.'-'.$mn.'-01');
        $taxes[$omonth]['mse'] = strtotime($this->selyear.'-'.($mn<10?'0'.($mn+1):($mn+1)).'-01');
      }

      $w = "WHERE siteid='%d' AND (datepaid BETWEEN '%d' AND '%d')";
      $spendings = $this->ask->selectRows("SELECT * FROM fin_costs $w", array($this->view['siteid'], $msu, $mse));
      foreach ($spendings as $s) {
        $smonth = date('F', $s->datepaid);
        if(!isset($taxes[$smonth]['receivable'])) { $taxes[$smonth]['receivable'] = 0; }
        $tr = $s->tr?floatval($s->tr):0;
        if(!$taxes[$smonth]['receivable']) { $taxes[$smonth]['receivable'] = 0; }
        $taxes[$smonth]['receivable'] += $tr;
      }

      $w = "WHERE siteid='%d' AND (datepaid BETWEEN '%d' AND '%d')";
      $txps = $this->ask->selectRows("SELECT * FROM fin_taxpaid $w", array($this->view['siteid'], $msu, $mse));
      foreach($txps as $txp) { 
        $tmonth = date('F', $txp->datepaid);
        if(!isset($taxes[$tmonth]['paid'])) { $taxes[$tmonth]['paid'] = 0; }
        if(!$taxes[$tmonth]['paid']) { $taxes[$tmonth]['paid'] = 0; }
        $taxes[$tmonth]['paid'] += $txp->amount;
      }

      $this->view['taxes'] = array();
      $this->view['totals'] = array('payable'=>0, 'receivable'=>0, 'paid'=>0, 'balance'=>0);
      foreach($taxes as $moname=>$vals) {
        $this->view['taxes'][$moname] = $vals;
        if($this->view['taxes'][$moname]['balance']===0) {
          $this->view['taxes'][$moname]['balance'] = $vals['payable'] - $vals['receivable'] - $vals['paid']; 
        }
        if($vals['payable']) { $this->view['totals']['payable'] += $vals['payable']; }
        if($vals['receivable']) { $this->view['totals']['receivable'] += $vals['receivable']; }
        if($vals['paid']) { $this->view['totals']['paid'] += $vals['paid']; }
        if($this->view['taxes'][$moname]['balance']) { $this->view['totals']['balance'] += $this->view['taxes'][$moname]['balance']; }
      }

    }

    public function getTaxRates() {
      $all_tax_rates = [];
      $tax_classes = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
      if ( !in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
          array_unshift( $tax_classes, '' );
      }
      foreach ( $tax_classes as $tax_class ) { // For each tax class, get all rates.
          $taxes = WC_Tax::get_rates_for_tax_class( $tax_class );
          $all_tax_rates = array_merge( $all_tax_rates, $taxes );
      }
      
      $listrates = array();
      foreach($all_tax_rates as $atr) {
        $listrates[$atr->tax_rate_id] = $atr;
      }
      $this->payload['rates'] = $listrates;
    }

    public function listPayableTaxes() {
      $this->getTaxRates();

      $args = array( 'status' => 'completed', 'date_paid' => $this->post['msu']."...".$this->post['mse'], 'limit' => -1 );
      $orders = wc_get_orders( $args );
      
      $oitems = array();
      $summary = array();
      $totals = array('tax'=>0, 'ship'=>0, 'net_price'=>0);
      foreach( $orders as $order ) {
        $oid = $order->get_id();
        $odate = date('d M,H:i', strtotime($order->get_date_paid()));
        $ourl = $order->get_edit_order_url();

        $invnumber = '';
        if(function_exists('wcpdf_get_document')) {
          $invoice = wcpdf_get_document( 'invoice', $order );
          if ( $invoice && $invoice->exists() ) {
            $invoice_number = $invoice->get_number();
            $invnumber = $invoice_number->get_plain();
          }
        }

        if(function_exists('run_wf_woocommerce_packing_list')) {
          $invnumber = get_post_meta( $oid, 'wf_invoice_number', true );
        }
        
        foreach( $order->get_items('tax') as $item ){
          $titem = array();
          $titem['oid'] = $oid;
          $titem['ourl'] = $ourl;
          $titem['odate'] = $odate;
          $titem['invoice_number'] = $invnumber;
          $titem['name']        = $item->get_name(); // Get rate code name (item title)
          $titem['rate_code']   = $item->get_rate_code(); // Get rate code
          $titem['rate_label']  = $item->get_label(); // Get label
          $titem['rate_id']    = $rate_id = $item->get_rate_id(); // Get rate Id
          $titem['tax_rate']   = $this->payload['rates'][$rate_id]->tax_rate;
          $titem['tax_total']   = $this->format($item->get_tax_total()); // Get tax total amount (for this rate)
          $titem['net_price']   = $this->format($titem['tax_total'] ? (($titem['tax_total']*100) / $titem['tax_rate']) : 0);
          $titem['ship_total']  = $this->format($item->get_shipping_tax_total()); // Get shipping tax total amount (for this rate)
          $titem['is_compound'] = $item->is_compound(); // check if is compound (conditional)
          $titem['compound']    = $item->get_compound(); // Get compound
          $oitems[] = $titem;

          $totals['tax'] += $titem['tax_total'];
          $totals['ship'] += $titem['ship_total'];
          $totals['net_price'] += $titem['net_price'];

          $skey = number_format(floor($titem['tax_rate']*100)/100,2, '.', '');
          if(!isset($summary[$skey])) {
            $summary[$skey] = 0;
          }
          $summary[$skey] += $titem['tax_total'];
        } 

      }
      $this->payload['payable'] = $oitems;
      $this->payload['summary'] = $summary;
      $this->payload['totals'] = $this->autoFormat($totals);
      $this->success = true;
    }

    /**
	 * Add record for the Tax Paid
	 */
    public function addTaxPaid() {
      $this->post['tid'] = $this->randomChars();
      $this->post['siteid'] = $this->view['siteid'];
      $this->post['amount'] = $this->moneyToDB($this->post['amount']);
      $this->post['datepaid'] = strtotime($this->post['datepaid']);
      $this->post['timecr'] = time();
      $add = $this->put->insert($this->table, $this->post);
      if(!$add) {
        $this->message = $this->put->errmsg;
        return;
      }
    $this->callback = 'reload';
    $this->results = $p;
    $this->success = true;
    }


  }
}
