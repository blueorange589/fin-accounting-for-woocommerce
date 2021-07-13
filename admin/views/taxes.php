<!-- Page Content -->
<div id="finapp" class="fin-container">
	<div class="finrouter">
		<nav class="nav-tab-wrapper" v-if="tab=='list'">
			<router-link :to="{ name: 'home'}" @click.native="tab='home'" :class="'nav-tab' + (tab=='home'?' nav-tab-active':'')"> < <?php _e('Back', 'fafw'); ?></router-link>
			<!--<router-link :to="{ name: 'list'}" @click.native="tab='list'" :class="'nav-tab' + (tab=='list'?' nav-tab-active':'')"><?php _e('List', 'fafw'); ?></router-link>-->
    </nav>
		<div class="tab-content">
			<router-view ref="rw"></router-view>
		</div>
	</div>
</div>
<!-- /#app -->

<template id="taxhome">
<div>
	<div class="fin-head">
		<div class="fin-head-left">
			<span><?php _e('Taxes', 'fafw'); ?></span>
			<img src="<?php echo FAFW_BASE_URL; ?>assets/img/arrow-right.svg" class="icon">
			<span><?=$handler->selyear?></span>
		</div>
		<div class="fin-head-right">
			<div class="fin-timeframe">
				<form method="post" style="width:145px;">
				<?php wp_nonce_field( 'fafwpost', 'nonce' ); ?>
					<select name="year">
						<?php foreach ($handler->getYears() as $yk => $yv) { ?>
							<option value="<?=$yk?>" <?=$yk==$handler->selyear?'selected':''?>><?=$yv?></option>
						<?php } ?>
					</select>
					<button class="button-go"><?php _e('Go', 'fafw'); ?></button>
				</form>
				<a class="button-go button-fin" @click="addTaxPaidModal">+ <?php _e('Add Tax Paid', 'fafw'); ?></a>
			</div>
		</div>
	</div>
	<div class="fin-content">
		<div class="taxes-container">
			<table class="fin-taxes-table" cellpadding="0" cellspacing="0" class="m1">
				<thead>
					<tr>
						<th class="tal"><?=$handler->selyear?></th>
						<th><?php _e('Payable', 'fafw'); ?> ({{currencySymbol}})</th>
						<th><?php _e('Receivable', 'fafw'); ?> ({{currencySymbol}})</th>
						<th><?php _e('Paid', 'fafw'); ?> ({{currencySymbol}})</th>
						<th><?php _e('Balance', 'fafw'); ?> ({{currencySymbol}})</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($handler->view['taxes'] as $mname=>$mvals) { ?>
						<tr>
						<td class="tal b"><?=$mname?></td>
						<td><?=$mvals['payable']>0?'<a @click="listPayableTaxes(\''.$mvals['msu'].'\',\''.$mvals['mse'].'\')">'.$mvals['payable'].'</a>':0?></td>
						<td><?=$mvals['receivable']?></td>
						<td><?=$mvals['paid']?></td>
						<td class="<?=$mvals['balance']>0?'minus':'plus'?> b"><?=$mvals['balance']?></td>
					</tr>
					<?php } ?>
				</tbody>
				<tfoot>
					<tr>
						<th class="tal b"><?php _e('Totals', 'fafw'); ?></th>
						<th><?=$handler->view['totals']['payable']?></th>
						<th><?=$handler->view['totals']['receivable']?></th>
						<th><?=$handler->view['totals']['paid']?></th>
						<th class="<?=$handler->view['totals']['balance']>0?'minus':'plus'?> b"><?=$handler->view['totals']['balance']?></th>
					</tr>
				</tfoot>
			</table>
		</div>

	</div>



	<div id="addtaxpaid" class="hidden">
		<div id="fin-transfer" class="fin-modal">
			<div class="fin-modal-content">
				<h2 style="margin:16px 0px;"><?php esc_html_e( 'Add New Category', 'fafw' ); ?></h2>
				<form id="form-addtaxpaid" @submit.prevent="addTaxPaid">
					<input type="hidden" name="process" value="addTaxPaid">
					<input type="hidden" name="handler" value="taxes">
					
					<div class="flex">
						<div class="w50">
							<div class="pb1">
								<div><b><?php esc_html_e( 'Date Paid', 'fafw' ); ?></b><span class="placeholder flr">2019-06-25</span></div>
								<input type="text" name="datepaid" data-validate="date" class="datepicker">
							</div>
							<div class="pb1">
								<div><b><?php esc_html_e( 'Amount', 'fafw' ); ?></b><span class="placeholder flr">2178.14</span></div>
								<input type="text" name="amount" data-validate="money" @input="checkAllowed" @focus="flattenMoneyAdd" @blur="formatMoneyAdd">
							</div>
							<div class="pb1">
								<div><b><?php esc_html_e( 'Notes', 'fafw' ); ?></b><span class="placeholder flr"><?php _e('Optional', 'fafw'); ?></span></div>
								<input type="text" name="notes">
							</div>
							<div class="pb1">
								<div><b><?php esc_html_e( 'Payment ID', 'fafw' ); ?></b><span class="placeholder flr"><?php _e('Optional', 'fafw'); ?></span></div>
								<input type="text" name="payid" maxlength="128">
							</div>
							<hr>
							<div>
								<input type="submit" class="fin-button flr" value="<?php esc_attr_e( 'Save', 'fafw' ); ?>">
							</div>
						</div>
					</div>

				</form>
			</div>
		</div>
	</div>
</div>
</template>

<template id="taxlist">
		<div>
			<div class="fin-head">
				<div class="fin-head-left">
					<span><?php _e('Taxes Payable', 'fafw'); ?></span>
					<img src="<?php echo FAFW_BASE_URL; ?>assets/img/arrow-right.svg" class="icon">
					<span>{{title}}</span>
				</div>
				<div class="fin-head-right">
					<div class="fin-timeframe">
						<button @click="exportCSV" id="export" class="fin-button flr"><?php esc_attr_e( 'Export', 'fafw' ); ?></button>
					</div>
				</div>
			</div>
			
			<table class="fin-table" cellpadding="0" cellspacing="0">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Order ID', 'fafw' ); ?></th>
							<th><?php esc_html_e( 'Invoice No', 'fafw' ); ?></th>
							<th><?php esc_html_e( 'Date Paid', 'fafw' ); ?></th>
							<th><?php esc_html_e( 'Tax Class', 'fafw' ); ?></th>
							<th><?php esc_html_e( 'Tax Rate', 'fafw' ); ?> %</th>
							<th class="tar"><?php esc_html_e( 'Net Price', 'fafw' ); ?> (<?php esc_html_e( 'Calculated', 'fafw' ); ?>)</th>
							<th class="tar"><?php esc_html_e( 'Tax Total', 'fafw' ); ?></th>
							<th class="tar"><?php esc_html_e( 'Ship. Tax', 'fafw' ); ?></th>
							<th class="tar"><?php esc_html_e( 'Compound', 'fafw' ); ?></th>
						</tr>
					</thead>
					<tbody>
							<tr v-for="(tr, index) in tlist">
								<td><a :href="tr.ourl" target="_blank">#{{tr.oid}}</a></td>
								<td>{{tr.invoice_number}}</td>
								<td>{{tr.odate}}</td>
								<td>{{tr.rate_code}}</td>
								<td>{{tr.tax_rate}}</td>
								<td class="tar">{{tr.net_price}}</td>
								<td class="tar">{{tr.tax_total}}</td>
								<td class="tar">{{tr.ship_total}}</td>
								<td class="tar">{{tr.compound?'Yes':'No'}}</td>
							</tr>
					</tbody>
					<tfoot>
						<tr>
							<th>Totals</th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th class="tar">{{totals.net_price}}</th>
							<th class="tar">{{totals.tax}}</th>
							<th class="tar">{{totals.ship}}</th>
							<th class="tar"></th>
						</tr>
					</tfoot>
				</table>
				<table class="tax-summary" cellpadding="0" cellspacing="0">
					<thead>
						<tr>
								<th colspan="2"><b><?php _e( 'Summary', 'fafw' ); ?></b></th>
						</tr>
						<tr>
							<th class="tal"><?php _e( 'Tax Rate', 'fafw' ); ?></th>
							<th class="tal"><?php _e( 'Total', 'fafw' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(val, key) in summary">
								<td>{{key}}%</td>
								<td>{{val}}</td>
						</tr>
					</tbody>
				</table>
		</div>
</template>