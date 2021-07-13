<?php
/**
 * Class for Accounts management
 *
 *
 * @link              https://finpose.com
 * @since             1.0.0
 * @package           Finpose
 * @author            info@finpose.com
 */
if ( !class_exists( 'fafw_accounts' ) ) {
  class fafw_accounts extends fafw_app {

    public $table = 'fin_accounts';
    public $v = 'allAccounts';
    public $p = '';

    public $selyear;
    public $selmonth;

    public $success = false;
    public $message = '';
    public $results = array();
    public $callback = '';

    /**
	 * Constructor for Accounts class
	 */
    public function __construct($v = 'allAccounts') {
      parent::__construct();
      $this->selyear = $this->curyear;
      $this->selmonth = $this->curmonth;

      

      $this->view['accounts'] = get_option('fafw_accounts')?:array();

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
        if($pk == 'name') {
          if(strlen($pv)<4 || strlen($pv)>32) {
            $status = false;
            $this->message = esc_html__( 'Account name must be between 4-32 characters', 'fafw' );
          }
        }
        if($pk == 'key') {
          if(strlen($pv)>32) {
            $status = false;
            $this->message = esc_html__( 'Account slug can not be longer than 32 characters', 'fafw' );
          }
        }
        if($pk == 'enabled') {
          if(!in_array($pv, array('0','1'))) {
            $status = false;
            $this->message = esc_html__( 'Invalid status', 'fafw' );
          }
        }
        if(in_array($pk, array('tfrom', 'tto'))) {
          if(!in_array($pv, array_keys($this->view['accounts']))) {
            $status = false;
            $this->message = esc_html__( 'Invalid account provided', 'fafw' );
          }
        }
        if($pk == 'datetransfer') {
          if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $pv)) {
            $status = false;
            $this->message = esc_html__( 'Date format provided is invalid', 'fafw' );
          }
        }
        if($pk == 'notes') {
          if(strlen($pv)>32) {
            $status = false;
            $this->message = esc_html__( 'Notes can not be longer than 256 characters', 'fafw' );
          }
        }
      }

    return $status;
    }

    public function pageAccounts() {
        
    }

    /*
     * Display transactions for all accounts
     */
    private function getTransactions() {
      if(!$this->setFilters()) {
        return false;
      }
      $filters = $this->payload['filters'];
      $msu = strtotime($filters['datestart']);
      $mse = strtotime($filters['dateend']);

      $selacc = $filters['account'];

      $gwrows = array();
      $txns = array();
      $totals = array( 'payin'=>0, 'payout'=>0, 'transferin'=>0, 'transferout'=>0 , 'balance'=>0 );
      foreach ($this->view['accounts'] as $ak=>$acc) {
        $gwrow = array( 'name'=>$acc['name'], 'type'=> $acc['type'], 'slug'=>$ak, 'payin'=>0, 'payout'=>0, 'transferin'=>0, 'transferout'=>0 );
        //if($acc['builtin']) {
          $args = array( 'limit'=> -1, 'status' => 'completed', 'payment_method' => $ak, 'date_paid' => $filters['datestart']."...".$filters['dateend'] );
          $orders = wc_get_orders( $args );
          foreach( $orders as $order ) {
            $amt = $order->get_total();
            $gwrow['payin'] += $amt;
            $dtu = strtotime($order->get_date_completed());
            $dt = $this->dateFormat($dtu);
            if(!$selacc || $ak == $selacc) {
              $txns[] = array('dtu'=>$dtu, 'date'=>$dt, 'type'=>'Order', 'cls'=>'plus', 'account'=>$acc['name'], 'amount'=>$amt, 'notes'=> 'Order ID: '.$order->get_id());
            }
          }
        //}
        $w = "WHERE siteid='%d' AND (datepaid BETWEEN '%d' AND '%d') AND paidwith='%s'";
        $q = "SELECT * FROM fin_costs $w";
        $costs = $this->ask->selectRows($q, array($this->view['siteid'], $msu, $mse, $ak));
        foreach ($costs as $c) {
          $gwrow['payout'] += $c->amount;
          $dt = $this->dateFormat($c->datepaid);
          if(!$selacc || $ak == $selacc) {
            $txns[] = array('dtu'=>$c->datepaid, 'date'=>$dt, 'type'=>ucfirst($c->type), 'cls'=>'minus', 'account'=>$acc['name'], 'amount'=>(float)$c->amount, 'notes'=>$c->notes);
          }
        }

        $q = "SELECT * FROM fin_transfers WHERE siteid='%d' AND (datetransfer BETWEEN '%d' AND '%d') AND (tfrom='%s' OR tto='%s')";
        $trs = $this->ask->selectRows($q, array($this->view['siteid'], $msu, $mse, $ak, $ak));
        foreach ($trs as $t) {
          if($t->tfrom==$ak) {
            $gwrow['transferout'] += $t->amount;
            $dt = $this->dateFormat($t->timecr);
            if(!$selacc || $ak == $selacc) {
              $txns[] = array('dtu'=>$t->timecr, 'date'=>$dt, 'type'=>'Transfer Out', 'cls'=>'minus', 'account'=>$acc['name'], 'amount'=>(float)$t->amount, 'notes'=>$t->notes);
            }
          }
          if($t->tto==$ak) {
            $gwrow['transferin'] += $t->amount;
            $dt = $this->dateFormat($t->timecr);
            if(!$selacc || $ak == $selacc) {
              $txns[] = array('dtu'=>$t->timecr, 'date'=>$dt, 'type'=>'Transfer In', 'cls'=>'plus', 'account'=>$acc['name'], 'amount'=>(float)$t->amount, 'notes'=>$t->notes);
            }
          }
        }
        $in = $gwrow['payin'] + $gwrow['transferin'];
        $out = $gwrow['payout'] + $gwrow['transferout'];
        $gwrow['balance'] = $this->view['accounts'][$ak]['balance'] = $in - $out;
        $totals['payin'] += $gwrow['payin'];
        $totals['payout'] += $gwrow['payout'];
        $totals['transferin'] += $gwrow['transferin'];
        $totals['transferout'] += $gwrow['transferout'];
        $totals['balance'] += $gwrow['balance'];
        $gwrows[] = $gwrow;
      }

      usort($txns, array('fin_accounts','dateSort'));

      $this->payload['date'] = $this->selyear.' '.date('F', $msu);
      $this->payload['accounts'] = $this->autoFormat($gwrows);
      $this->payload['txns'] = $this->autoFormat($txns);
      $this->payload['totals'] = $this->autoFormat($totals);
    }

    /**
	 * Adds new built-in account
	 */
    private function addBuiltIn($slug) {
      $payment_gateways   = WC_Payment_Gateways::instance();
      $payment_gateway    = $payment_gateways->payment_gateways()[$slug];
      $acc['name'] = $payment_gateway->title;
      $acc['timecr'] = time();
      $acc['type'] = 'builtin';
      $$acc['enabled'] = '1';
      $add = $this->put->insert($this->table, $acc);
      if($add) {
        $this->retrieveAccounts();
      }
    }

    /**
	 * Adds custom account
	 */
    private function addAccount() {
      $accs = $this->getAccounts();

      if($this->post['source']=='existing') {
        $ak = $this->post['gwslug'];
        $payment_gateways   = WC_Payment_Gateways::instance();
        $payment_gateway    = $payment_gateways->payment_gateways()[$ak];
        $arr['name'] = $payment_gateway->title;
        $arr['builtin'] = 1;
        $arr['type'] = 'gateway';
      }

      if($this->post['source']=='restore') {
        $ak = $this->post['restoreslug'];
        $archive = get_option('fafw_removed_accounts');
        $arr = $archive[$ak];
        unset($archive[$ak]);
        update_option( 'fafw_removed_accounts', $archive );
      }

      if($this->post['source']=='new') {
        $ak = $this->slugit($this->post['name']);
        $arr = array('name'=>$this->post['name'], 'builtin'=> 0, 'type'=>$this->post['type']);
      }
      
      $accs[$ak] = $arr;
      update_option( 'fafw_accounts', $accs );
      $this->callback = 'reload';
      $this->results = $accs;
      $this->success = true;
    }

    /**
	 * Transfer between accounts
	 */
    private function transfer() {
      $this->post['trid'] = $this->randomChars();
      $this->post['siteid'] = $this->view['siteid'];
      $this->post['amount'] = $this->moneyToDB($this->post['amount']);
      $this->post['datetransfer'] = strtotime($this->post['datetransfer']);
      $this->post['timecr'] = time();
      $add = $this->put->insert('fin_transfers', $this->post);
      if(!$add) {
        $this->message = $this->put->errmsg;
        return;
      }
      $this->callback = 'transfer';
      $this->results = $p;
      $this->success = true;
    }

    /**
	 * Delete an account
	 */
  public function deleteAccount() {
    $accs = $this->getAccounts();
    $slug = $this->post['slug'];
    $acc = isset($accs[$slug])?$accs[$slug]:'';
    if(!$acc) return false;

    // archive it
    $archive = get_option('fafw_removed_accounts');
    $archive[$slug] = $acc;
    update_option( 'fafw_removed_accounts', $archive );

    // remove it
    unset($accs[$slug]);
    update_option( 'fafw_accounts', $accs );
    $this->success = true;
  }

    /**
	 * Add account modal variables
	 */
  public function addAccountVars() {
    $this->payload['removedAccounts'] = get_option('fafw_removed_accounts');
    $bigws = WC()->payment_gateways->get_available_payment_gateways();
    $gwlist = array();
    if( $bigws ) {
      foreach( $bigws as $bigw ) {
        $gwlist[$bigw->id] = $bigw->title;
      }
    }
    $this->payload['gwlist'] = $gwlist;
    $this->success = true;
  }


    /**
	 * Edit account name
	 */
  private function editAccount() {
    $accs = $this->getAccounts();
    $key = $this->post['key'];
    $acc = $accs[$key];
    if(!$acc) return false;
    $acc['name'] = $this->post['name'];
    $acc['type'] = $this->post['type'];
    $accs[$key] = $acc;
    update_option( 'fafw_accounts', $accs );
    $this->results = $accs;
    $this->success = true;
  }

   /**
	 * Sort by Date ASC
	 */
  private static function dateSort($a, $b) {
    return $a['dtu'] - $b['dtu'];
  }

  /**
	 * Import Gateways (BuiltIn Accounts) and save
	 */
  public function addExistingAccounts() {
    $finaccs = get_option('fafw_accounts');
    $bigws = WC()->payment_gateways->get_available_payment_gateways();
    if( $bigws ) {
      foreach( $bigws as $bigw ) {
        if(!isset($finaccs[$bigw->id])) {
          $finaccs[$bigw->id] = array('name'=>$bigw->title, 'builtin'=> 1, 'type'=>'gateway', 'enabled'=>1);
        }
      }
    }
    update_option( 'fafw_accounts', $finaccs );
  }

  public function getAccountList() {
      $this->payload['accounts'] = $this->getAccounts();
  }

}
}
