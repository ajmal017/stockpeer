<div class="span12 zone-content">
	
	<h1 class="text-center">Find the best options broker for your strategy</h1>
	
	<div class="row span9 well center">
		<form role="form" action="/options-broker-picker" method="get"> 
			
			<div class="row">				
			  <div class="control-group span4 offset1">
			    <label class="control-label">What options strategy do you trade?</label>
			    <div class="controls">
						<select class="input-large" name="strategy">
						  <option value="buy-options" <?=(Input::get('strategy') == 'buy-options') ? 'selected' : ''?>>Buy Options</option>
						  <option value="write-options" <?=(Input::get('strategy') == 'write-options') ? 'selected' : ''?>>Write Options</option>						  
						  <option value="vertical-spread" <?=(Input::get('strategy') == 'vertical-spread') ? 'selected' : ''?>>Vertical Spreads</option>
						  <option value="iron-condor" <?=(Input::get('strategy') == 'iron-condor') ? 'selected' : ''?>>Iron Condors</option>
						</select>
					</div>
			  </div>
			  
			  <div class="control-group span4">
			    <label class="control-label">
			    	How many lots per trade? 
			    	<a href="#" data-toggle="popover" title="" data-content="A lot is the quanity of the trade you are putting on. For example a vertical spread with a lot of 4 is selling 4 contracts and buying 4 contracts for a total of 8 contracts. An Iron Condor with a lot size of 2 is buying 4 contracts and selling 4 more for a total of 8 contracts. If you are just buying or selling an option your lot size will be the number of contracts you are trading." data-original-title="What is a lot size?">
				    		<i class="icon-question-sign"></i>
				    </a>
			    </label>
			    <div class="controls">
						<select class="input-large" name="lots">
							<?php for($i = 1; $i <= 500; $i++) : ?>
						  <option value="<?=$i?>" <?=(Input::get('lots') == $i) ? 'selected' : ''?>><?=$i?></option>
						  <?php endfor; ?>
						</select>
					</div>
			  </div>			  			  
			</div>					
			
			<div class="row">
		  	<button type="submit" class="btn btn-primary span2 center">Find The Best Broker For Me</button>
			</div>
		</form>
	</div>	
		
	<?php if(Input::get('strategy')) : ?>
	<div class="span12 row">
		<table class="table table-bordered table-striped table-responsive">
			<thead>
				<tr>
					<th>Broker</th>
					<th>Base Charge</th>
					<th>Option Charge</th>
					<th>Total Commission</th>																
				</tr>
			</thead>
			
			<tbody>
				<?php foreach($brokers AS $key => $row) : ?>
				<tr>
					<td>
						<a href="<?=$row['url']?>"><?=$row['name']?></a>
					</td>
					<td>$<?=$row['ticket_charge']?></td>
					<td>$<?=$row['per_option']?></td>	
					<td>$<?=$row['total_cost']?></td>											
				</tr>
				<?php endforeach; ?>					
			</tbody>	
		</table>
		<p style="font-size: 12px;">
      * Tastyworks does not charge for closing trades. We assume you close your trade so we show their commission at $0.50 instead of $1.00 for a fair comparable. <br />
      ** Tradier Brokerage has a minimum charge of $5 per single legs and $7 for multi legs.
		</p>		
	</div>
	<?php endif; ?>
	
</div>

<script>
$('[data-toggle="popover"]').popover();
</script>