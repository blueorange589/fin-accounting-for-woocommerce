<?php
/**
 * Class for Costs management
 *
 *
 * @link              https://finpose.com
 * @since             1.0.0
 * @package           Finpose
 * @author            info@finpose.com
 */
if ( !class_exists( 'fafw_spendings' ) ) {
  class fafw_spendings extends fafw_app {

    public $table = 'fin_costs';
    public $v = 'getCosts';
    public $p = '';

    public $selyear;
    public $selmonth;
    public $selcat = '';

    public $success = false;
    public $message = '';
    public $payload = array();
    public $callback = '';

    /**
	 * Constructor
	 */
    public function __construct($v = 'getCosts') {
      parent::__construct();

      $this->selyear = $this->curyear;
      $this->selmonth = $this->curmonth;

      $this->view['accounts'] = $this->getAccounts();

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
      
      if($v != 'ajax') {
        $args = array(
            'status' => 'publish',
        );
        $this->view['products'] = wc_get_products( $args );

        $this->$v();
      }

      if($this->ask->errmsg) { $this->view['errmsg'] = $this->ask->errmsg; }
    }

    /**
	 * Validate all inputs before use
	 */
    public function validate() {
      $status = true;

      foreach ($this->post as $pk => $pv) {
        if($pk == 'type') {
          if(!in_array($pv, array_keys($this->presets->costTypes)) && $pv != 'all') {
            $status = false;
            $this->message = esc_html__( 'Invalid Type', 'fafw' );
          }
        }
        if($pk == 'paidwith') {
          if(strlen($pv)>32) {
            $status = false;
            $this->message = esc_html__( 'Invalid Paid With Information', 'fafw' );
          }
        }
        if($pk == 'items') {
          if($pv != intval($pv)) {
            $status = false;
            $this->message = esc_html__( 'Invalid items', 'fafw' );
          }
        }
        if(in_array($pk, array('amount', 'tr'))) {
          if(!preg_match('/^(?!0\.00)\d{1,3}(,\d{3})*(\.\d\d)?$/', $pv) && $pv!='0.00') {
            $status = false;
            $this->message = esc_html__( 'Invalid money format', 'fafw' );
          }
        }
        if($pk == 'name') {
          if(strlen($pv)>128) {
            $status = false;
            $this->message = esc_html__( 'Name can not be longer than 128 characters', 'fafw' );
          }
        }
        if($pk == 'notes') {
          if(strlen($pv)>512) {
            $status = false;
            $this->message = esc_html__( 'Notes can not be longer than 512 characters', 'fafw' );
          }
        }
        if($pk == 'datepaid') {
          if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $pv)) {
            $status = false;
            $this->message = esc_html__( 'Date format provided is invalid', 'fafw' );
          }
        }
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
      }

    return $status;
    }

    private function pagePro() {
      
    }

    /**
	 * Get costs
	 */
    private function getCosts() {
      if(isset($_GET['finact'])) {
        if($_GET['finact']=='restore') { $this->restoreCategories(); }
      }
    }

    private function getSpendings() {
      if(!$this->setFilters()) {
        return false;
      }
      $filters = $this->payload['filters'];
      $msu = strtotime($filters['datestart']);
      $mse = strtotime($filters['dateend']);
      
      $type = 'cost';
      if(isset($this->post['type'])) { $type = $this->post['type']; }

      $w = "WHERE siteid='%d' AND datepaid BETWEEN '%d' AND '%d' ";
      $vals = array($this->view['siteid'], $msu, $mse);

      if(isset($this->post['type']) && $this->post['type']!='all') {
        $w.="AND type = '%s' ";
        $vals[] = $type;
      }

      if(isset($this->post['cat']) && $this->post['cat']) {
        $w.="AND cat = '%s' ";
        $vals[] = $this->post['cat'];
      }

      if($filters['paidwith']) {
        $w.="AND paidwith = '%s' ";
        $vals[] = $filters['paidwith'];
      }

      if($filters['pid']) {
        $w.="AND items = '%s' ";
        $vals[] = $filters['pid'];
      }

      $w .= "ORDER BY datepaid DESC";
      $q = "SELECT * FROM ".$this->table." $w";
      
      $costs = $this->ask->selectRows($q, $vals);
      $totals = array('amount'=>0, 'tr'=>0);

      foreach ($costs as $r=>$c) {
        $costs[$r]->amountFormatted = $this->format($c->amount);
        $costs[$r]->trFormatted = $this->format($c->tr);
        $costs[$r]->pm = $this->view['accounts'][$c->paidwith];
        $costs[$r]->datepick = date("Y-m-d", $c->datepaid);
        $costs[$r]->datepaid = $this->dateFormat($c->datepaid);
        $totals['amount'] += $c->amount;
        $totals['tr'] += $c->tr;
      }
      $totals['amount'] = $this->format($totals['amount']);
      $totals['tr'] = $this->format($totals['tr']);

      if(isset($this->post['withcats']) && $this->post['withcats'] == 'true') {
        $this->getCategories();
      }

      $this->payload['totals'] = $totals;
      $this->payload['data'] = $costs;
      $this->success = true;
    }

    /**
	 * Add a new spending
	 */
    private function addSpending() {
      $copy = $this->post;
      $copy['coid'] = $this->randomChars();
      $copy['siteid'] = $this->view['siteid'];
      $copy['amount'] = $this->moneyToDB($copy['amount']);
      if(!$copy['tr']) { $copy['tr'] = 0; }
      $copy['tr'] = $this->moneyToDB($copy['tr']);
      $copy['datepaid'] = strtotime($copy['datepaid']);
      $copy['timecr'] = time();
      $add = $this->put->insert($this->table, $copy);
      if(!$add) {
        $this->message = $this->put->errmsg;
        return;
      }
      $cats = get_option('fin-'.$this->post['type'].'-categories');
      $this->post['name'] = esc_html($this->post['name']);
      $this->post['notes'] = esc_html($this->post['notes']);
      $this->post['catname'] = $cats[$this->post['cat']]['name'];
      $this->post['pm']['name'] = $this->view['accounts'][$this->post['paidwith']]['name'];
      $this->post['timecr'] = $this->dateFormat($this->post['timecr']);
      $this->post['datepaid'] = $this->dateFormat(strtotime($this->post['datepaid']));
      $this->post['amountFormatted'] = $this->format($copy['amount']);
      $this->post['trFormatted'] = $this->format($copy['tr']);
      $this->callback = 'addSpending';
      $this->payload = $this->post;
      $this->message = esc_html__( 'Added new spending successfully', 'fafw' );
      $this->success = true;
    }

     /**
	 * Edit a spending
	 */
  private function editSpending() {
    $copy = $this->post;
    $key = $this->post['coid'] = $this->post['key'];
    $copy['datepaid'] = strtotime($copy['datepick']);
    unset($copy['key'], $copy['datepick']);
    $copy['amount'] = $this->moneyToDB($copy['amount']);
    if(!$copy['tr']) { $copy['tr'] = 0; }
    $copy['tr'] = $this->moneyToDB($copy['tr']);
    $edit = $this->put->update($this->table, $copy, array('coid'=>$key));
    if(!$edit) {
      $this->message = $this->put->errmsg;
      return;
    }
    $cats = get_option('fin-'.$this->post['type'].'-categories');
    $this->post['name'] = esc_html($this->post['name']);
    $this->post['notes'] = esc_html($this->post['notes']);
    $this->post['catname'] = $cats[$this->post['cat']]['name'];
    $this->post['pm']['name'] = $this->view['accounts'][$this->post['paidwith']]['name'];
    $this->post['timecr'] = $this->dateFormat($this->post['timecr']);
    $this->post['datepaid'] = $this->dateFormat(strtotime($this->post['datepick']));
    $this->post['amountFormatted'] = $this->format($copy['amount']);
    $this->post['trFormatted'] = $this->format($copy['tr']);
    $this->callback = 'reload';
    $this->payload = $this->post;
    $this->message = esc_html__( 'Updated successfully', 'fafw' );
    $this->success = true;
  }

  /**
	 * Attach file to the spending
	 */
  public function attachFile() {
    require_once(ABSPATH.'wp-admin/includes/file.php');
    $uploadedfile = $_FILES['file'];
    $key = $this->post['key'];
    $movefile = wp_handle_upload($uploadedfile, array('test_form' => false)); 
    
    if ( $movefile ){
      $this->put->update($this->table, array('attfile'=>$movefile['url']), array('coid'=>$key));
      $this->callback = 'reload';
      $this->payload = $movefile;
      $this->message = esc_html__( 'Uploaded successfully', 'fafw' );
      $this->success = true;
      return;
    }
    $this->message = esc_html__( 'Unable to upload file', 'fafw' );
  }

    /**
	 * Remove a spending from DB
	 */
    private function removeSpending() {
      $del = $this->put->delete($this->table, array('coid'=>$this->post['key']));
      if($del) {
        $this->message = esc_html__( 'Removed spending from records successfully.', 'fafw' );
        $this->success = true;
      }
    }

    /**
	 * Remove category
	 */
    private function removeSpendingCategory() {

    }

    /**
	 * List cost categories
	 */
    public function getCategories() {
      $this->payload['categories']['cost'] = get_option('fin-cost-categories');
      $this->payload['categories']['expense'] = get_option('fin-expense-categories');
      $this->payload['categories']['acquisition'] = get_option('fin-acquisition-categories');
      $this->success = true;
    return $this->payload;
    }

    /**
	 * Add new spending category
	 */
    private function addSpendingCategory() {
      $ctypes = array(
        'cost' => 'Cost',
        'expense' => 'Expense',
        'acquisition' => 'Acquisition'
      );
      if(!in_array($this->post['type'], array_keys($ctypes))) { return false; }
      $opt = 'fin-'.$this->post['type'].'-categories';
      $current = get_option($opt);
      $slug = $this->slugit($this->post['name']);

      if($current) {
        $current[$slug] = array('jcode'=>$this->post['jcode'], 'name'=>$this->post['name']);
        update_option($opt, $current);
      } else {
        $cadata = array($slug=>array('jcode'=>$this->post['jcode'], 'name'=>$this->post['name']));
        add_option($opt, $cadata);
      }
      $this->payload = array($slug=>$this->post);
      $this->message = esc_html__( 'Added new category successfully', 'fafw' );
      $this->success = true;
    return;
    }

    /**
	 * Remove existing category
	 */
    public function removeCategory() {
      $type = $this->post['type'];
      $opt = 'fin-'.$type.'-categories';
      $data = get_option($opt);
      $key = $this->post['cat'];
      unset($data[$key]);
      update_option($opt, $data);
      $this->callback = 'reload';
      $this->payload = $this->post;
      $this->success = true;
      $this->message = esc_html__( 'Success', 'fafw' );
    }

    /**
	 * Edit category name and journal code
	 */
    private function editCategory() {
      $opt = 'fin-'.$this->post['type'].'-categories';
      $data = get_option($opt);
      $key = $this->post['key'];
      $data[$key] = array(
        'jcode'=> $this->post['jcode'],
        'name'=> $this->post['name']
      );
      update_option($opt, $data);
      $this->callback = 'reload';
      $this->payload = $this->post;
      $this->success = true;
      $this->message = esc_html__( 'Success', 'fafw' );
    }

    /**
	 * Get list of spending categories
	 */
    private function getSpendingCategories() {
      $costs = get_option('fin-cost-categories');
      $expenses = get_option('fin-expense-categories');
      $acqs = get_option('fin-acquisition-categories');
      return array_merge($costs,$expenses,$acqs);
    }

    /**
	 * Restore default spending categories
	 */
    private function restoreCategories() {
      $opt1 = get_option('fin-spending-types');
      if($opt1) {
        $costTypes = array(
          'cost' => 'Cost',
          'expense' => 'Expense',
          'acquisition' => 'Acquisition'
        );
        update_option( 'fin-spending-types', $costTypes );
      }

      $opt2 = get_option('fin-cost-categories');
      if($opt2) {
        $costCategories = array(
          'inventory' => array('jcode'=>'','name'=>'Inventory'),
          'customs' => array('jcode'=>'','name'=>'Customs'),
          'shipping' => array('jcode'=>'','name'=>'Shipping'),
          'employees' => array('jcode'=>'','name'=>'Employees'),
          'office' => array('jcode'=>'','name'=>'Office'),
          'storage' => array('jcode'=>'','name'=>'Storage'),
          'maintenance' => array('jcode'=>'','name'=>'Maintenance')
        );
        update_option( 'fin-cost-categories', $costCategories );
      }

      $opt3 = get_option('fin-expense-categories');
      if($opt3) {
        $expenseCategories = array(
          'services' => array('jcode'=>'','name'=>'Services'),
          'contracts' => array('jcode'=>'','name'=>'Contracts'),
          'hardware' => array('jcode'=>'','name'=>'Hardware'),
          'software' => array('jcode'=>'','name'=>'Software'),
          'online-tools' => array('jcode'=>'','name'=>'Online Tools'),
          'travel' => array('jcode'=>'','name'=>'Travel')
        );
        update_option( 'fin-expense-categories', $expenseCategories );
      }

      $opt4 = get_option('fin-acquisition-categories');
      if($opt4) {
        $acquisitionCategories = array(
          'online-ads' => array('jcode'=>'','name'=>'Online Advertising'),
          'offline-ads' => array('jcode'=>'','name'=>'Offline Advertising'),
          'email-marketing' => array('jcode'=>'','name'=>'Email Marketing'),
          'affiliate' => array('jcode'=>'','name'=>'Affilate Marketing'),
          'sponsorships' => array('jcode'=>'','name'=>'Sponsorships'),
          'contests' => array('jcode'=>'','name'=>'Contests'),
          'giveaways' => array('jcode'=>'','name'=>'Giveaways')
        );
        update_option( 'fin-acquisition-categories', $acquisitionCategories );
      }
    }

  }
}
