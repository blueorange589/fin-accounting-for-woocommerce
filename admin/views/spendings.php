<div id="finapp" class="fin-container">

	<div class="fin-head">
		<div class="fin-head-left">
			<span><?php esc_html_e( 'Spendings', 'fafw' ); ?></span>
		</div>
		<div class="fin-head-right">
			
		</div>
	</div>
	<div class="fin-content">

		<div class="fin-tabs-container">
			<div class="fin-tabs-left fx6">
				<div class="fin-tabs spending-tabs">
				<div :class="'fin-tab' + (type=='all'?' active':'')">
						<a @click="switchTab('all')"><?php esc_html_e( 'All', 'fafw' ); ?></a>
					</div>
					<div :class="'fin-tab' + (type=='cost'?' active':'')">
						<a @click="switchTab('cost')"><?php esc_html_e( 'Costs', 'fafw' ); ?></a>
					</div>
					<div :class="'fin-tab' + (type=='expense'?' active':'')">
						<a @click="switchTab('expense')"><?php esc_html_e( 'Expenses', 'fafw' ); ?></a>
					</div>
					<div :class="'fin-tab' + (type=='acquisition'?' active':'')">
						<a @click="switchTab('acquisition')"><?php esc_html_e( 'Acquisition', 'fafw' ); ?></a>
					</div>
				</div>
			</div>
			<div class="fin-tabs-right fx4 tar">
				<div class="fin-timeframe">
					<form id="form-datefilter" @submit.prevent="filterByDate">
						<input type="text" name="datestart" data-validate="date" class="datepicker datefilter" v-model="filters.datestart">
						<input type="text" name="dateend" data-validate="date" class="datepicker datefilter" v-model="filters.dateend">
						<button type="submit" class="button-go"><?php _e('Go', 'fafw'); ?></button>
					</form>
				</div>
			</div>
		</div>
		<div class="spendings-container">
			<div class="spendings-left">
				<h4><?php esc_html_e( 'Filter by Category', 'fafw' ); ?></h4>
				<ul id="cats-left">
					<li v-for="(item, index) in leftcats" :class="(index==cat?'catactive':'')">
						<a @click="setCategory(index)">{{item.name}}</a> 
						<span v-if="index==cat"><a @click="editCategoryModal(index, item)"><img :src="siteurl + 'admin/assets/img/pencil.svg'"/></a></span>
					</li>
				</ul>
				<ul>
					<li><a @click="addCategoryModal">+ <?php esc_html_e( 'Add New Category', 'fafw' ); ?></a></li>
				</ul>
			</div>
			<div class="spendings-right">
				<div class="productfilter">
					<div class="pageactions">
						<a class="fin-button fin-button-xs" @click="addSpendingModal">+ <?php esc_html_e( 'Add New', 'fafw' ); ?></a>
						<a @click="exportCSV" class="fin-button fin-button-xs"><?php esc_attr_e( 'Export', 'fafw' ); ?></a>
					</div>
					<div class="pagefilters" v-if="type=='all'">
						<select name="paidwith" v-model="filters.paidwith" @change="getSpendings">
							<option value="0"><?php esc_html_e( 'All Accounts', 'fafw' ); ?></option>
							<?php foreach ($handler->view['accounts'] as $acslug => $acc) { ?>
								<option value="<?=$acslug?>"><?=$acc['name']?></option>
							<?php } ?>
						</select>
						<select name="items" v-model="filters.pid" @change="getSpendings">
							<option value="0"><?php esc_html_e( 'All Items', 'fafw' ); ?></option>
							<?php foreach ($handler->view['products'] as $product) { ?>
								<option value="<?=$product->get_id()?>"><?=$product->get_name()?></option>
							<?php } ?>
						</select>
					</div>
					
				</div>
				<table class="fin-table" cellpadding="0" cellspacing="0">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'fafw' ); ?></th>
							<th v-if="type=='all'"><?php esc_html_e( 'Type', 'fafw' ); ?></th>
							<th><?php esc_html_e( 'Category', 'fafw' ); ?></th>
							<th><?php esc_html_e( 'Journal Code', 'fafw' ); ?></th>
							<th><?php esc_html_e( 'Paid With', 'fafw' ); ?></th>
							<th><?php esc_html_e( 'Date Paid', 'fafw' ); ?></th>
							<th class="tar"><?php esc_html_e( 'Amount', 'fafw' ); ?> ({{currencySymbol}})</th>
							<th class="tar"><?php esc_html_e( 'Tax Receivable', 'fafw' ); ?> ({{currencySymbol}})</th>
							<th class="tar"><?php esc_html_e( 'Actions', 'fafw' ); ?></th>
						</tr>
					</thead>
					<tbody id="spending-rows">
							<tr v-for="(spd, index) in spendings">
								<td><span v-if="spd.attfile"><a :href="spd.attfile" target="_blank"><img :src="siteurl + 'admin/assets/img/attachment.svg'"/></a></span><span v-if="spd.notes.length>0"><span class="tooltip"><img :src="siteurl + 'admin/assets/img/note-text.svg'"/><span class="tooltiptext">{{spd.notes}}</span></span></span>{{spd.name}}</td>
								<td v-if="type=='all'">{{capitalizeFirstLetter(spd.type)}}</td>
								<td>{{categoryName(spd)}}</td>
								<td>{{categoryCode(spd)}}</td>
								<td>{{spd.pm ? spd.pm.name : ''}}</td>
								<td>{{spd.datepaid}}</td>
								<td class="tar">{{spd.amountFormatted}}</td>
								<td class="tar">{{spd.trFormatted}}</td>
								<td class="tar">
									<a href="javascript:void(0);" @click="displayUploader(index, spd)"><img :src="siteurl + 'admin/assets/img/upload.svg'"/></a>
									<a href="javascript:void(0);" @click="editSpending(index, spd)"><img :src="siteurl + 'admin/assets/img/pencil.svg'"/></a>
									<a href="javascript:void(0);" @click="deleteSpending(index, spd.coid)"><img :src="siteurl + 'admin/assets/img/cross.svg'"/></a>
								</td>
							</tr>
					</tbody>
					<tfoot>
						<tr>
							<th>Totals</th>
							<th v-if="type=='all'"></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th class="tar">{{totals.amount}}</th>
							<th class="tar">{{totals.tr}}</th>
							<th></th>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>

	</div>

	


	<div id="addcategory" class="hidden">
		<div id="fin-transfer" class="fin-modal">
			<div class="fin-modal-content">
				<h2 style="margin:16px 0px;"><?php esc_html_e( 'Add New Category', 'fafw' ); ?></h2>
				<form id="form-addcategory" @submit.prevent="addCategory">
					<input type="hidden" name="process" value="addSpendingCategory">
					<input type="hidden" name="handler" value="spendings">
					<div class="flex">
						<div class="w50">
							<div class="pb1">
								<b><?php esc_html_e( 'Type', 'fafw' ); ?></b>
								<select name="type">
									<option value="cost"><?php _e('Cost', 'fafw'); ?></option>
									<option value="expense"><?php _e('Expense', 'fafw'); ?></option>
									<option value="acquisition"><?php _e('Acquisition', 'fafw'); ?></option>
								</select>
							</div>
							<div class="pb1">
								<b><?php esc_html_e( 'Name', 'fafw' ); ?></b>
								<input type="text" name="name" data-validate="required" maxlength="128">
							</div>
							<div class="pb1">
								<div><b><?php esc_html_e( 'Journal Code', 'fafw' ); ?></b><span class="placeholder flr"><?php _e('Optional', 'fafw'); ?></span></div>
								<input type="text" name="jcode" maxlength="32">
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

	<div id="editCategoryModal" class="hidden">
		<div id="fin-transfer" class="fin-modal">
			<div class="fin-modal-content">
				<h2 style="margin:16px 0px;"><?php esc_html_e( 'Edit Category', 'fafw' ); ?></h2>
				<form id="form-editcategory" @submit.prevent="editCategory">
					<input type="hidden" name="process" value="editCategory">
					<input type="hidden" name="handler" value="spendings">
					<input type="hidden" id="editkey" name="key" value="" v-model="catrow.slug">
					<input type="hidden" id="edittype" name="type" value="" v-model="catrow.type">
					<div class="flex">
						<div class="w50">
							<div class="pb1">
								<b><?php esc_html_e( 'Name', 'fafw' ); ?></b>
								<input id="editname" type="text" name="name" data-validate="required" maxlength="128" v-model="catrow.name">
							</div>
							<div class="pb1">
								<div><b><?php esc_html_e( 'Journal Code', 'fafw' ); ?></b><span class="placeholder flr">Optional</span></div>
								<input id="editjc" type="text" name="jcode" maxlength="32" v-model="catrow.jcode">
							</div>
							<hr>
							<div>
								<input type="submit" class="fin-button flr" value="<?php esc_attr_e( 'Save', 'fafw' ); ?>">
							</div>
						</div>
						<div class="w50">
							<div class="w100 pb1 tac">
								<a href="#" @click="removeCategory(catrow.type, catrow.slug)" class="fin-button danger"><?php esc_attr_e( 'Delete Category', 'fafw' ); ?></a>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>


	<div id="addnew" class="hidden">
		<div id="fin-transfer" class="fin-modal">
			<div class="fin-modal-content">
			<h2 style="margin:16px 0px;"><?php esc_html_e( 'Add New Spending', 'fafw' ); ?></h2>
			<form id="form-addnewspending" @submit.prevent="addNewSpending">
				
				<input type="hidden" name="process" value="addSpending">
				<input type="hidden" name="handler" value="spendings">
				
				<div class="flex">
					<div class="w50">
						<div class="flex container-form">
							<div class="w90">
								<div>
									<b><?php esc_html_e( 'Name', 'fafw' ); ?></b>
									<input type="text" name="name" data-validate="required" maxlength="128">
								</div>
								<div>
									<b><?php esc_html_e( 'Type', 'fafw' ); ?></b>
									<select name="type" @change="setCatlist">
										<option value="cost" selected><?php esc_html_e( 'Cost', 'fafw' ); ?></option>
										<option value="expense"><?php esc_html_e( 'Expense', 'fafw' ); ?></option>
										<option value="acquisition"><?php esc_html_e( 'Acquisition', 'fafw' ); ?></option>
									</select>
								</div>
								<div>
									<b><?php esc_html_e( 'Category', 'fafw' ); ?></b>
									<select name="cat" id="cat-select-add">
										<option v-for="(item, index) in catlist" :value="index">{{item.name}}</option>
									</select>
								</div>
								<div>
									<div><b><?php esc_html_e( 'Amount', 'fafw' ); ?></b><span class="placeholder flr">2154.68</span></div>
									<input type="text" name="amount" data-validate="money" @input="checkAllowed" @focus="flattenAdd" @blur="formatAdd">
								</div>
								<div>
									<div><b><?php esc_html_e( 'Tax Receivable', 'fafw' ); ?></b><span class="placeholder flr">27.89</span></div>
									<input type="text" name="tr" data-validate="money" @input="checkAllowed" @focus="flattenAdd" @blur="formatAdd" value="0">
								</div>
							</div>
						</div>
					</div>
					<div class="w50">
						<div class="flex container-form">
							<div class="w90">
								<div>
									<b><?php esc_html_e( 'Paid With', 'fafw' ); ?></b>
									<select name="paidwith">
										<?php foreach ($handler->view['accounts'] as $acslug => $acc) { ?>
											<option value="<?=$acslug?>"><?=$acc['name']?></option>
										<?php } ?>
									</select>
								</div>
								<div>
									<div><b><?php esc_html_e( 'Date Paid', 'fafw' ); ?></b><span class="placeholder flr">2019-06-25</span></div>
									<input type="text" name="datepaid" data-validate="date" class="datepicker">
								</div>
								<div>
									<div><b><?php esc_html_e( 'Items', 'fafw' ); ?></b></div>
									<select name="items">
										<option value="0"><?php esc_html_e( 'All Items', 'fafw' ); ?></option>
										<?php foreach ($handler->view['products'] as $product) { ?>
											<option value="<?=$product->get_id()?>"><?=$product->get_name()?></option>
										<?php } ?>
									</select>
								</div>
								<div>
									<b><?php esc_html_e( 'Notes', 'fafw' ); ?></b>
									<input type="text" name="notes">
								</div>
								<hr>
								<div>
									<input type="submit" class="fin-button flr" value="<?php esc_attr_e( 'Save', 'fafw' ); ?>">
								</div>
							</div>
						</div>
					</div>

				</div>
			</form>
		</div>
		</div>
	</div>


	<div id="editSpendingModal" class="hidden">
		<div id="fin-transfer" class="fin-modal">
			<div class="fin-modal-content">
			<h2 style="margin:16px 0px;"><?php esc_html_e( 'Edit Spending', 'fafw' ); ?></h2>
			<form id="form-editspending" @submit.prevent="updateSpending">
				<input type="hidden" name="process" value="editSpending">
				<input type="hidden" name="handler" value="spendings">
				<input type="hidden" id="editkey" name="key" value="" v-model="row.coid">
				<input type="hidden" name="attfile" v-model="row.attfile">
				<div class="flex">
					<div class="w50">
						<div class="flex container-form">
							<div class="w90">
								<div>
									<b><?php esc_html_e( 'Name', 'fafw' ); ?></b>
									<input type="text" name="name" data-validate="required" maxlength="128" v-model="row.name">
								</div>
								<div>
									<b><?php esc_html_e( 'Type', 'fafw' ); ?></b>
									<select name="type" @change="setCatlist" v-model="row.type">
										<option value="cost">Cost</option>
										<option value="expense">Expense</option>
										<option value="acquisition">Acquisition</option>
									</select>
								</div>
								<div>
									<b><?php esc_html_e( 'Category', 'fafw' ); ?></b>
									<select name="cat" id="cat-select-edit" v-model="row.cat">
										<option v-for="(item, index) in catlist" :value="index">{{item.name}}</option>
									</select>
								</div>
								<div>
									<div><b><?php esc_html_e( 'Amount', 'fafw' ); ?></b><span class="placeholder flr">2154.68</span></div>
									<input type="text" name="amount" data-validate="money" @input="checkAllowed" @focus="flattenEdit('amountFormatted')" @blur="formatEdit('amountFormatted')" v-model="row.amountFormatted" />
								</div>
								<div>
									<div><b><?php esc_html_e( 'Tax Receivable', 'fafw' ); ?></b><span class="placeholder flr">27.89</span></div>
									<input type="text" name="tr" data-validate="money" @input="checkAllowed" @focus="flattenEdit('trFormatted')" @blur="formatEdit('trFormatted')" v-model="row.trFormatted" />
								</div>
							</div>
						</div>
					</div>
					<div class="w50">
						<div class="flex container-form">
							<div class="w90">
								<div>
									<b><?php esc_html_e( 'Paid With', 'fafw' ); ?></b>
									<select name="paidwith" v-model="row.paidwith">
										<?php foreach ($handler->view['accounts'] as $acslug => $acc) { ?>
											<option value="<?=$acslug?>"><?=$acc['name']?></option>
										<?php } ?>
									</select>
								</div>
								<div>
									<div><b><?php esc_html_e( 'Date Paid', 'fafw' ); ?></b><span class="placeholder flr">2019-06-25</span></div>
									<input type="text" name="datepick" data-validate="date" class="datepicker" v-model="row.datepick">
								</div>
								<div>
									<div><b><?php esc_html_e( 'Items', 'fafw' ); ?></b></div>
									<select name="items">
										<option value="0"><?php esc_html_e( 'All Items', 'fafw' ); ?></option>
										<?php foreach ($handler->view['products'] as $product) { ?>
											<option value="<?=$product->get_id()?>"><?=$product->get_name()?></option>
										<?php } ?>
									</select>
								</div>
								<div>
									<b><?php esc_html_e( 'Notes', 'fafw' ); ?></b>
									<input type="text" name="notes" v-model="row.notes">
								</div>
								<hr>
								<div>
									<input type="submit" class="fin-button flr" value="<?php esc_attr_e( 'Save', 'fafw' ); ?>">
								</div>
							</div>
						</div>
					</div>

				</div>
			</form>
		</div>
		</div>
	</div>


	<div id="attachmentModal" class="hidden">
		<div id="fin-transfer" class="fin-modal">
			<div class="fin-modal-content">
			<h2 style="margin:16px 0px;"><?php esc_html_e( 'Attach File to Spending', 'fafw' ); ?></h2>
			<form id="form-attach" @submit.prevent="uploadAttachment">
				<?php wp_nonce_field( 'fafwpost', 'nonce' ); ?>
				<input type="hidden" name="process" value="attachFile">
				<input type="hidden" name="handler" value="spendings">
				<input type="hidden" id="attkey" name="key" v-model="row.coid">
				<div class="flex">
					<div class="w50">
						<div class="flex container-form">
							<div class="w90">
								<div>
									<b><?php esc_html_e( 'Name', 'fafw' ); ?></b>
									<span>{{row.name}}</span>
								</div>
								<div>
									<b><?php esc_html_e( 'Choose file', 'fafw' ); ?></b>
									<input type="file" id="upfile" name="file" data-validate="required">
								</div>
								<div>
									<input type="submit" class="fin-button flr" value="<?php esc_attr_e( 'Save', 'fafw' ); ?>">
								</div>
							</div>
						</div>
					</div>
					<div class="w50">
						<div class="flex container-form">
							<div class="w90">
								<?php esc_html_e( 'This operation will override any existing attachments for this spending.', 'fafw' ); ?>
							</div>
						</div>
					</div>

				</div>
			</form>
		</div>
		</div>
	</div>






</div>


