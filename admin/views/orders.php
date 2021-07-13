<div id="finapp" class="fin-container">
	<div class="fin-head">
		<div class="fin-head-left">
			<span><?php esc_html_e( 'Orders', 'fafw' ); ?></span>
			<img src="<?php echo FAFW_BASE_URL; ?>assets/img/arrow-right.svg" class="icon">
			<span><?php esc_html_e( 'Filter & Export', 'fafw' ); ?></span>
		</div>
		<div class="fin-head-right">
			<div class="fin-timeframe">
				<button @click="exportCSV" id="export" class="fin-button flr"><?php esc_attr_e( 'Export', 'fafw' ); ?></button>
			</div>
		</div>
	</div>

	<div class="fin-content">
		<?php if(isset($summary)) { ?>
			<div class="sales-figures">
				<div class="sales-figure">
					<div class="sf-number">
					<?=esc_html($handler->view['info']['qty'])?>
					</div>
					<div class="sf-title">
						<?php _e('Items sold', 'fafw'); ?>
					</div>
				</div>
				<div class="sales-figure">
					<div class="sf-number">
					{{currencySymbol}}<?=esc_html($handler->view['info']['total'])?>
					</div>
					<div class="sf-title">
						<?php _e('Total', 'fafw'); ?>
					</div>
				</div>
				<div class="sales-figure">
					<div class="sf-number">
					{{currencySymbol}}<?=esc_html($handler->view['info']['avg'])?>
					</div>
					<div class="sf-title">
						<?php _e('Avg. Order Value', 'fafw'); ?>
					</div>
				</div>
				<div class="sales-figure">
					<div class="sf-number">
						<?=esc_html($handler->view['info']['avgtime'])?>
					</div>
					<div class="sf-title">
						<?php _e('Average Time for Sale', 'fafw'); ?>
					</div>
				</div>
				<div class="sales-figure">
					<div class="sf-number">
					{{currencySymbol}}<?=esc_html($handler->view['info']['pl'])?>
					</div>
					<div class="sf-title">
						<?php _e('Profit / Loss', 'fafw'); ?>
					</div>
				</div>
			</div>
		<?php } ?>

		<div class="orders-container">
			<div class="orders-left">
				<form id="form-filter" @submit.prevent="filterOrders">
				
					<div class="orders-menu">
						<div class="om-heading"><?php _e('Order', 'fafw'); ?></div>
						<div class="om-sub">
							<div class="om-sub-left"><?php _e('Status', 'fafw'); ?></div>
							<div class="om-sub-right">
								<select name="status" v-model="filters.status">
									<option value="all"><?php _e('All', 'fafw'); ?></option>
									<option value="completed"><?php _e('Completed', 'fafw'); ?></option>
									<option value="pending"><?php _e('Pending Payment', 'fafw'); ?></option>
									<option value="processing"><?php _e('Processing', 'fafw'); ?></option>
									<option value="on-hold"><?php _e('On hold', 'fafw'); ?></option>
									<option value="cancelled"><?php _e('Cancelled', 'fafw'); ?></option>
									<option value="refunded"><?php _e('Refunded', 'fafw'); ?></option>
									<option value="failed"><?php _e('Failed', 'fafw'); ?></option>
								</select>
							</div>
						</div>
						<div class="om-sub">
							<div class="om-sub-left"><?php _e('Total', 'fafw'); ?></div>
							<div class="om-sub-right">
								<select name="totalthan" v-model="filters.totalthan">
									<option value="greater"><?php _e('Greater than', 'fafw'); ?></option>
									<option value="lower"><?php _e('Lower than', 'fafw'); ?></option>
								</select>
							</div>
						</div>
						<div class="om-sub">
							<div class="om-sub-left"></div>
							<div class="om-sub-right"><input type="number" name="total" v-model="filters.total"></div>
						</div>
						<div class="om-heading"><?php _e('Date', 'fafw'); ?></div>
						<div class="om-sub">
							<div class="om-sub-left"><?php _e('Type', 'fafw'); ?></div>
							<div class="om-sub-right">
								<select name="datetype" v-model="filters.datetype">
									<option value="date_created"><?php _e('Date created', 'fafw'); ?></option>
									<option value="date_paid"><?php _e('Date paid', 'fafw'); ?></option>
									<option value="date_invoice"><?php _e('Invoice date', 'fafw'); ?> (WC Invoices & Packing Slips)</option>
								</select>
							</div>
						</div>
						<div class="om-sub">
							<div class="om-sub-left"><?php _e('Start', 'fafw'); ?></div>
							<div class="om-sub-right"><input type="text" id="datestart" name="datestart" data-validate="date" class="datepicker" v-model="filters.datestart"></div>
						</div>
						<div class="om-sub">
							<div class="om-sub-left"><?php _e('End', 'fafw'); ?></div>
							<div class="om-sub-right"><input type="text" id="dateend" name="dateend" data-validate="date" class="datepicker" v-model="filters.dateend"></div>
						</div>
						<div class="om-heading"><?php _e('Payment', 'fafw'); ?></div>
						<div class="om-sub">
							<div class="om-sub-left"><?php _e('Method', 'fafw'); ?></div>
							<div class="om-sub-right">
								<select name="gateway" v-model="filters.gateway">
									<option></option>
									<?php foreach($handler->view['gwlist'] as $gwid=>$gwname) { ?>
										<option value="<?=$gwid?>"><?=$gwname?></option>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="om-sub">
							<div class="om-sub-left"></div>
							<div class="om-sub-right"><input type="submit" class="fin-button flr" value="<?php esc_attr_e( 'Filter', 'fafw' ); ?>"></div>
						</div>
					</div>
				</form>
			</div>
			<div class="orders-right">
				<div class="orders-content">
					<table class="fin-table fin-table-thin fin-table-export" cellpadding="0" cellspacing="0">
						<thead>
							<tr>
								<th>ID</th>
								<th><?php _e('Date created', 'fafw'); ?></th>
								<th v-if="add_wcpdf>0"><?php _e('Invoice No', 'fafw'); ?></th>
								<th v-if="add_wcpdf>0"><?php _e('Invoice Date', 'fafw'); ?></th>
								<th><?php _e('Status', 'fafw'); ?></th>
								<th><?php _e('Account', 'fafw'); ?></th>
								<th><?php _e('Customer', 'fafw'); ?></th>
								<th><?php _e('Country', 'fafw'); ?></th>
								<th class="tar"><?php _e('Tax', 'fafw'); ?></th>
								<th class="tar"><?php _e('Shipping', 'fafw'); ?></th>
								<th class="tar"><?php _e('Shipping Tax', 'fafw'); ?></th>
								<th class="tar"><?php _e('Subtotal', 'fafw'); ?></th>
								<th class="tar"><?php _e('Total', 'fafw'); ?></th>
								<th class="tar"><?php _e('Currency', 'fafw'); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(order, index) in orders">
								<td><a :href="order.url" target="_blank">#{{order.id}}</a></td>
								<td>{{order.date}}</td>
								<td v-if="add_wcpdf>0">{{order.wcpdf_number}}</td>
								<td v-if="add_wcpdf>0">{{order.wcpdf_date}}</td>
								<td>{{printStatus(order.status)}}</td>
								<td>{{order.pm}}</td>
								<td>{{order.cus}}</td>
								<td>{{order.geo}}</td>
								<td class="tar">{{formatMoney(order.tax)}}</td>
								<td class="tar">{{formatMoney(order.shipamount)}}</td>
								<td class="tar">{{formatMoney(order.shiptax)}}</td>
								<td class="tar">{{formatMoney(order.st)}}</td>
								<td class="tar">{{formatMoney(order.total)}}</td>
								<td class="tar">{{order.currency}}</td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<th class="tal b"><?php _e('Totals', 'fafw'); ?></th>
								<th colspan="2" v-if="add_wcpdf>0"></th>
								<th colspan="5">
								<th>{{formatMoney(totals.tax)}}</th>
								<th>{{formatMoney(totals.shipamount)}}</th>
								<th>{{formatMoney(totals.shiptax)}}</th>
								<th>{{formatMoney(totals.st)}}</th>
								<th class="b">{{formatMoney(totals.total)}}</th>
								<th></th>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>


	</div>

</div>
