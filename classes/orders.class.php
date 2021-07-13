<?php
/**
 * Class for Sales
 *
 *
 * @link              https://finpose.com
 * @since             1.1.0
 * @package           Finpose
 * @author            info@finpose.com
 */
if ( !class_exists( 'fafw_orders' ) ) {
  class fafw_orders extends fafw_app {

    public $v = 'buildSalesReport';
    public $p = '';

    public $selyear;
		public $selmonth;
		public $selq;

    public $success = false;
    public $message = '';
    public $results = array();
    public $callback = '';

    /**
	 * Reporting Constructor
	 */
    public function __construct($v = 'getDashboard') {
      parent::__construct();
      $this->selyear = $this->curyear;
			$this->selmonth = $this->curmonth;
			$this->selq = $this->curq;

			$this->payload['egws'] = $this->getAccounts();

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
			}

		return $status;
		}

		public function pageDashboard() {

		}

		public function getDashboard() {
			$filters = json_decode(stripslashes($this->post['filters']), true);

			if($filters['datestart'] && $filters['dateend']) {
				$msu = strtotime($filters['datestart']);
      	$mse = strtotime($filters['dateend']);
			} else {
				$qend = 1 + ($this->selq*3);
				$qstart = $qend-3;
				$mstart = $this->selyear.'-'.$this->addZero($qstart).'-01';
				$mend = $this->selyear.'-'.$this->addZero($qend).'-01';
				if($this->selq=='4') { $mend = ($this->selyear+1).'-01-01'; }

				$msu = strtotime($mstart);
				$mse = strtotime($mend)-1;
			}
			$this->payload['filters']['datestart'] = date('Y-m-d', $msu);
      $this->payload['filters']['dateend'] = date('Y-m-d', $mse);

			$orders = $this->ask->getOrdersByDate($msu, $mse);
			
			$info = array('qty'=>0,'total'=>0,'avg'=>0, 'taxpyb'=> 0, 'taxrcv'=> 0,'orders'=>array(), 'pm'=> array(), 'bs'=>array(), 'geo'=>array());
			$numorders = 0;
			foreach ($orders as $order) {
				//$info['qty'] += $order->get_item_count();
				$subtotal = $order->get_subtotal();
				$ototal = $order->get_total();
				$info['total'] += $ototal;
				$geo = $order->get_billing_country();
				$gn = WC()->countries->countries[ $geo ];
				if(!isset($info['geo'][$gn])) {$info['geo'][$gn] = 0;}
				$info['geo'][$gn] += $subtotal;

				$pm = $order->get_payment_method_title();
				if(!isset($info['pm'][$pm])) {$info['pm'][$pm] = 0;}
				$info['pm'][$pm] += $subtotal;

				$items = $order->get_items();
				foreach ( $items as $item ) {
					$name = $item->get_name();
					$qty = $item->get_quantity();
					$st = $order->get_item_subtotal($item);
					$tot = $st*$qty;
					if(!isset($info['bs'][$name])) {$info['bs'][$name] = 0;}
					$info['bs'][$name] += $qty;
					$info['qty'] += $qty;
				}

				$cus = new WC_Customer($order->get_customer_id());
				$od['id'] = $order->get_id();
				$od['url'] = $order->get_edit_order_url();
				$od['date'] = date('d M,H:i', strtotime($order->get_date_completed()));
				$od['pm'] = $pm;
				$od['cus'] = $cus->get_first_name().' '.$cus->get_last_name();
				$od['geo'] = $gn;
				$od['tax'] = $order->get_total_tax();
				$od['sh'] = $order->get_shipping_total();
				$od['st'] = $subtotal;
				$info['orders'][] = $od;
				$info['taxpyb']+=$od['tax'];
				$numorders++;
			}
			$info['numorders'] = $numorders;
			$info['avg'] = $numorders>0?$info['total']/$info['numorders']:0;
			$diff = $mse-$msu;
			if($this->selyear==$this->curyear && $this->selq==$this->curq) {
				$diff = time()-$msu;
			}
			$avgtime = $numorders ? $diff/$numorders : 0;

			$date = new DateTime();
			$date->setTimestamp($avgtime);
			$date2 = new DateTime('@0');

			if($avgtime > 172800) {
				$info['avgtime'] = $date2->diff($date)->format("%ad %Hh");
			} elseif($avgtime > 86400) {
				$info['avgtime'] = $date2->diff($date)->format("%ad %Hh");
			} elseif($avgtime > 0)  {
				$info['avgtime'] = $date2->diff($date)->format("%Hh %im");
			} else {
				$info['avgtime'] = 'N/A';
			}

			$w = "WHERE siteid='%d' AND datepaid BETWEEN '%d' AND '%d' ";
      $vals = array($this->view['siteid'], $msu, $mse);
      $q = "SELECT * FROM fin_costs $w";
			$srows = $this->ask->selectRows($q, $vals);
			
			$spendingCats = $this->getSpendingCategories();
			$cats = array();
      $types = array();
      $pms = array();
      $sptotal = 0;

      foreach($spendingCats as $sc) {
        $cats[$sc['name']] = 0;
      }

			$totals = array('amount'=>0, 'tr'=>0);
			$info['stotal'] = 0;
      foreach ($srows as $sr) {
				if($sr->cat!='inventory') { 
					$info['taxrcv'] += $sr->tr;
					$info['stotal'] += $sr->amount; 
				} 

				$typename = $this->presets->costTypes[$sr->type];
        $catname = $spendingCats[$sr->cat]['name'];
        $pmname = $this->payload['egws'][$sr->paidwith]['name'];

        if(!isset($types[$typename])) { $types[$typename]=0; }
        if(!isset($pms[$pmname])) { $pms[$pmname]=0; }
        $cats[$catname] += $sr->amount;
        $types[$typename] += $sr->amount;
        $pms[$pmname] += $sr->amount;
        $sptotal += $sr->amount;
			}
			arsort($cats);
      ksort($types);
			arsort($pms);
			
			$info['pl'] = $info['total']-$info['stotal'];
			$info['spo'] = $numorders>0?$this->format($info['stotal']/$info['numorders']):0;

			$cogs = $this->ask->getVar("SELECT SUM(cost) AS cogs FROM fin_inventory WHERE siteid='%d' AND timesold BETWEEN %d AND %d", array($this->view['siteid'], $msu, $mse));
			$info['cogs'] = $cogs?$cogs:0;
			$info['taxes'] = $info['taxpyb'] - $info['taxrcv'];
			$info['profit'] = $info['total'] - ($info['cogs'] + $info['taxes'] + $info['stotal']);
			$info['margin'] = $info['total']>0?round($info['profit'] / $info['total'] * 100):0;
			$days = floor($diff / 86400);
			$info['days'] = $days;
			$info['ppd'] = $this->format($info['profit'] / $days);

			arsort($info['pm']);
			arsort($info['bs']);
			arsort($info['geo']);
			$info['pm'] = $this->autoFormat(array_slice($info['pm'], 0, 5, true));
			$info['bs'] = array_slice($info['bs'], 0, 5, true);
			$info['geo'] = $this->autoFormat(array_slice($info['geo'], 0, 5, true));
			$info['total'] = $this->format($info['total']);
			$info['avg'] = $this->format($info['avg']);
			$info['pl'] = $this->format($info['pl']);

			$this->payload['info'] = $info;
			
			$this->payload['charttypes'] = $this->autoFormat($types, false);
      $this->payload['chartpms'] = $this->autoFormat($pms, false);
      $this->payload['chartcats'] = array_slice($this->autoFormat($cats, false), 0, 8, true);
      $this->payload['sptotal'] = $this->format($sptotal);
      $this->payload['cats'] = get_option('fin-cost-categories');
		}

		/**
		 * Generate Sales Reports
		 */
		private function buildSalesReport() {
			if(isset($this->post['year'])) {
        $this->selyear = $this->post['year'];
        $this->selmonth = $this->post['month'];
      }
      $mstart = $this->selyear.'-'.$this->selmonth.'-01';
      $mend = $this->selyear.'-'.($this->selmonth+1).'-01';
      if($this->selmonth=='12') { $mend = ($this->selyear+1).'-01-01'; }

      $msu = strtotime($mstart);
			$mse = strtotime($mend)-1;

			$dlast = intval(date('d', $mse));
			$dtoday = intval(date('d', time()));
			$dmonth = intval(date('m', time()));
			$dfirst = intval(date('d', $msu));
			$labels = $sales = $spendings = array();
			for($li=$dfirst;$li<=$dlast;$li++) {
				$labels[] = intval($li);
				if($this->selmonth == $dmonth) {
					//if($li<=$dtoday) {
						$sales[] = 0;
						$spendings[] = 0;
					//}
				} else {
					$sales[] = 0;
					$spendings[] = 0;
				}
			}

			$orders = $this->ask->getOrdersByDate($msu, $mse);

			$info = array('qty'=>0,'total'=>0,'avg'=>0,'orders'=>array(), 'pm'=> array(), 'bs'=>array(), 'geo'=>array());
			$numorders = 0;
			foreach ($orders as $order) {
				$info['qty'] += $order->get_item_count();
				$subtotal = $order->get_subtotal();
				$info['total'] += $subtotal;
				$geo = $order->get_billing_country();
				$gn = WC()->countries->countries[ $geo ];
				if(!isset($info['geo'][$gn])) {$info['geo'][$gn] = 0;}
				$info['geo'][$gn] += $subtotal;

				$pm = $order->get_payment_method_title();
				if(!isset($info['pm'][$pm])) {$info['pm'][$pm] = 0;}
				$info['pm'][$pm] += $subtotal;

				$items = $order->get_items();
				foreach ( $items as $item ) {
					$name = $item->get_name();
					$qty = $item->get_quantity();
					$st = $order->get_item_subtotal($item);
					$tot = $st*$qty;
					if(!isset($info['bs'][$name])) {$info['bs'][$name] = 0;}
					$info['bs'][$name] += $tot;
				}

				$cus = new WC_Customer($order->get_customer_id());
				$od['id'] = $order->get_id();
				$od['url'] = $order->get_edit_order_url();
				$od['date'] = date('d M,H:i', strtotime($order->get_date_completed()));
				$od['pm'] = $pm;
				$od['cus'] = $cus->get_first_name().' '.$cus->get_last_name();
				$od['geo'] = $gn;
				$od['tax'] = $order->get_total_tax();
				$od['sh'] = $order->get_shipping_total();
				$od['st'] = $subtotal;
				$info['orders'][] = $od;

				$odate = intval(date('d', strtotime($order->get_date_completed())));
				$sales[$odate-1] += $subtotal;
				$numorders++;
			}
			$info['avg'] = $info['qty'] ? $info['total']/$info['qty'] : 0;
			$diff = time()-$msu;
			$avgtime = $numorders ? $diff/$numorders : 0;

			$date = new DateTime();
			$date->setTimestamp($avgtime);
			$date2 = new DateTime('@0');

			if($avgtime > 172800) {
				$info['avgtime'] = $date2->diff($date)->format("%adays %Hhrs");
			} elseif($avgtime > 86400) {
				$info['avgtime'] = $date2->diff($date)->format("%aday %Hhrs");
			} elseif($avgtime > 0)  {
				$info['avgtime'] = $date2->diff($date)->format("%Hhrs %imin");
			} else {
				$info['avgtime'] = 'N/A';
			}

			$w = "WHERE datepaid BETWEEN '%d' AND '%d' ";
      $vals = array($msu, $mse);
      $q = "SELECT * FROM fin_costs $w";
      $srows = $this->ask->selectRows($q, $vals);

			$totals = array('amount'=>0, 'tr'=>0);
			$info['stotal'] = 0;
      foreach ($srows as $sr) {
				$info['stotal'] += $sr->amount;
				$sdate = intval(date('d', $sr->datepaid));
				$spendings[$sdate-1] += $sr->amount;
			}
			$info['pl'] = $info['total']-$info['stotal'];

			arsort($info['pm']);
			arsort($info['bs']);
			arsort($info['geo']);
			$info['pm'] = $this->autoFormat(array_slice($info['pm'], 0, 5, true));
			$info['bs'] = $this->autoFormat(array_slice($info['bs'], 0, 5, true));
			$info['geo'] = $this->autoFormat(array_slice($info['geo'], 0, 5, true));
			$info['total'] = $this->format($info['total']);
			$info['avg'] = $this->format($info['avg']);
			$info['pl'] = $this->format($info['pl']);

			$this->view['info'] = $info;
			$this->view['chart']['labels'] = json_encode($labels);
			$this->view['chart']['sales'] = json_encode($sales);
			$this->view['chart']['spendings'] = json_encode($spendings);

		}

		public function pageOrders() {
			$bigws = WC()->payment_gateways->get_available_payment_gateways();
			$this->view['gwlist'] = array();
			if( $bigws ) {
				foreach( $bigws as $slug=>$bigw ) {
					$this->view['gwlist'][$slug] = $bigw->title;
				}
			}
		}

		public function getOrders() {
			$this->payload['filters'] = $filters = json_decode(stripslashes($this->post['filters']), true);

      $mstart = $this->selyear.'-'.$this->selmonth.'-01';
			$mend = $this->selyear.'-'.$this->addZero($this->selmonth+1).'-01';
			if($this->selmonth=='12') { $mend = ($this->selyear+1).'-01-01'; }
			if($filters['datestart']) { $mstart = $filters['datestart']; }
			if($filters['dateend']) { $mend = $filters['dateend']; }

			$this->payload['filters']['datestart'] = $mstart;
			$this->payload['filters']['dateend'] = $mend;
			
			$datetype = "date_created";
			if($filters['datetype']) { $datetype = $filters['datetype']; }

			$status = "all";
			if($filters['status']) { $status = $filters['status']; }

			$gateway = "";
			if($filters['gateway']) { $gateway = $filters['gateway']; }
			$this->payload['total'] = "";
			if($filters['total']) { $this->payload['total'] = $filters['total']; }
			$this->payload['totalthan'] = "greater";
			if($filters['totalthan']) { $this->payload['totalthan'] = $filters['totalthan']; }

      $msu = strtotime($mstart);
			$mse = strtotime($mend)-1;

			if($msu>$mse) {
				$this->message = "End date should be after start date";
				return false;
			}

			$args =  array('limit' => -1);

			if($status != 'all') { $args['status'] = $status; }
			if($gateway) { $args['payment_method'] = $gateway; }
			if($datetype=='date_invoice') {
				$args['meta_key'] = '_wcpdf_invoice_date';
				$args['meta_compare'] = 'BETWEEN';
				$args['meta_value'] = array( $msu, $mse );
				$args['meta_type'] = 'numeric';
			} else {
				$args[$datetype] = $mstart."...".$mend;
			}
			$orders = wc_get_orders( $args );

			$info = array('qty'=>0,'total'=>0,'avg'=>0,'orders'=>array(), 'pm'=> array(), 'bs'=>array(), 'geo'=>array());
			$totals = array('tax'=>0,'shiptax'=>0,'shipamount'=>0,'st'=>0,'total'=>0);
			$numorders = 0;
			$viewOrders = array();
			foreach ($orders as $order) {
				$negative = false;
				if ( is_a( $order, 'WC_Order_Refund' ) ) {
					$order = wc_get_order( $order->get_parent_id() );
					$negative = true;
				}

				$include = true;
				$order_data = $order->get_data();
				$total = $order_data['total'];
				
				if($filters['total']) {
					if($filters['totalthan']=="greater") { if($total<$filters['total']) { $include=false; }}
					if($filters['totalthan']=="lower") { if($total>$filters['total']) { $include=false; }}
				}

				if($include) {
					//$info['qty'] += $order->get_item_count();
					//$subtotal = $order->get_subtotal();
					//$info['total'] += $subtotal;
					$geo = $order_data['billing']['country'];
					$gn = WC()->countries->countries[ $geo ];
					$pm = $order_data['payment_method_title'];
					$cus = new WC_Customer($order_data['customer_id']);

					//$order_meta = get_post_meta($order->get_meta_data());
					//print_r($order->get_meta_data());

					$od['id'] = $order_data['id'];
					$od['status'] = $order_data['status'];
					$od['url'] = $order->get_edit_order_url();
					$oddate = $order->get_date_created();
					$odtime = strtotime($oddate);
					$od['date'] = date('Y-m-d H:i', $odtime);
					$od['pm'] = $pm;
					$od['currency'] = $order->get_currency();
					$od['cus'] = $cus->get_first_name().' '.$cus->get_last_name();
					$od['geo'] = $gn;
					$od['tax'] = $negative?-$order_data['total_tax']:$order_data['total_tax'];
					$od['shiptotal'] = $negative?-$order_data['shipping_total']:$order_data['shipping_total'];
					$od['shiptax'] = $negative?-$order_data['shipping_tax']:$order_data['shipping_tax'];
					$od['shipamount'] = $negative?-($od['shiptotal']-$od['shiptax']):($od['shiptotal']-$od['shiptax']);
					$od['st'] = $negative?-($order_data['total'] - $order_data['total_tax'] - $order_data['shipping_total']):($order_data['total'] - $order_data['total_tax'] - $order_data['shipping_total']);
					$od['total'] = $negative?-$order_data['total']:$order_data['total'];

					$this->payload['add_wcpdf'] = 0;
					if(function_exists('wcpdf_get_document')) {
						$this->payload['add_wcpdf'] = 1;
						$invoice = wcpdf_get_document( 'invoice', $order );
						if ( $invoice && $invoice->exists() ) {
							$invoice_number = $invoice->get_number(); // this retrieves the number object with all the data related to the number
							//$invoice_number_formatted = $invoice_number->get_formatted();
							//$invoice_number_plain = $invoice_number->get_plain();
							$od['wcpdf_date'] = $invoice->get_date()->format('Y-m-d H:i:s');
							$od['wcpdf_number'] = $invoice_number->get_plain();
						} else {
							$od['wcpdf_date'] = '';
							$od['wcpdf_number'] = '';
						}
					} else {
						$od['wcpdf_date'] = '';
						$od['wcpdf_number'] = '';
					}

					$viewOrders[] = $od;

					$totals['tax'] += $od['tax'];
					$totals['shiptax'] += $od['shiptax'];
					$totals['shipamount'] += $od['shipamount'];
					$totals['st'] += $od['st'];
					$totals['total'] += $od['total'];

					//$odate = intval(date('d', strtotime($order->get_date_completed())));
					//$sales[$odate-1] += $subtotal;
					$numorders++;
				}
			}
			$this->payload['info'] = array();
			$this->payload['orders'] = $viewOrders;
			$this->payload['totals'] = $totals;
			$this->success = true;
		}

		/**
		 * Get list of spending categories
		 */
		private function getSpendingCategories() {
			$costs = get_option('fin-cost-categories')?:array();
			$expenses = get_option('fin-expense-categories')?:array();
			$acqs = get_option('fin-acquisition-categories')?:array();
			return array_merge($costs,$expenses,$acqs);
		}

  }
}
