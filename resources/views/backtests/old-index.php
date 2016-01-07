<div class="span12 zone-content">
	
	<h1>Backtester</h1>
	
	<div class="row span11 well">
		<form role="form">
			
			<div class="row">				
			  <div class="control-group span4">
			    <label class="control-label">Backtest Type</label>
			    <div class="controls">
						<select class="input-large">
						  <option value="precent-away">Put Credit Spread - Precent Away</option>
						</select>
					</div>
			  </div>
			  
			  <div class="control-group span4">
			    <label class="control-label">Start Date</label>
			    <div class="controls">
						<input type="date" placeholder="Text input" />
					</div>
			  </div>			  

			  <div class="control-group span3">
			    <label class="control-label">End Date</label>
			    <div class="controls">
						<input type="date" placeholder="Text input" />
					</div>
			  </div>	
			  
			</div>
			

			<div class="row">
				
			  <div class="control-group span4">
			    <label class="control-label">Starting Balance</label>
					<div class="controls">
					  <input type="text" style="height: 20px;" placeholder="10,000.00" />
					</div>			    
			  </div>	
			  
			  <div class="control-group span4">
			    <label class="control-label">Open Trade %</label>
					<select class="input-large">
						<?php for($i = 1; $i <= 100; $i++) : ?>
					  <option value="<?=$i?>"><?=$i?>%</option>
						<?php endfor; ?>
					</select>
			  </div>				  
			  
			  <div class="control-group span3">
			    <label class="control-label">Close Trade Profit %</label>
					<select class="input-large">
						<?php for($i = 5; $i <= 100; $i = $i + 5) : ?>
					  <option value="<?=$i?>"><?=$i?>%</option>
						<?php endfor; ?>
					</select>
			  </div>				  			  	
			  
			</div>			
			
			
			<div class="row">
				
			  <div class="control-group span4">
			    <label class="control-label">Ticker Symbol</label>
			    <div class="controls">
						<select class="input-large">
						  <option value="spy">SPY - SPDR S&P 500 ETF</option>
						  <option value="iwm">IWM - iShares Russell 2000 ETF</option>						  
						</select>
					</div>
			  </div>
			  
			  <div class="control-group span4">
			    <label class="control-label">Spread Width</label>
					<select class="input-large">
					  <option value="1">1</option>
					  <option value="2">2</option>	
					  <option value="3">3</option>	
					  <option value="4">4</option>	
					  <option value="5">5</option>	
					  <option value="6">6</option>	
					  <option value="7">7</option>
					  <option value="8">8</option>
					  <option value="9">9</option>
					  <option value="10">10</option>
					</select>
			  </div>			  

			  <div class="control-group span3">
			    <label class="control-label">Lot Size Per Trade</label>
					<select class="input-large">
						<?php for($i = 1; $i <= 100; $i++) : ?>
					  <option value="<?=$i?>"><?=$i?></option>
						<?php endfor; ?>
					</select>
			  </div>	
			  
			</div>
			
			<div class="row">
				
			  <div class="control-group span4">
			    <label class="control-label">Min. Credit Received</label>
					<div class="controls">
					  <input type="text" style="height: 20px;" placeholder="0.15" />
					</div>			    
			  </div>	
			  
			  <div class="control-group span4">
			    <label class="control-label">Min. Days To Expire</label>
					<div class="controls">
					  <input type="number" style="height: 20px;" placeholder="1" />
					</div>
			  </div>	
			  
			  <div class="control-group span3">
			    <label class="control-label">Max Days To Expire</label>
					<div class="controls">
					  <input type="number" style="height: 20px;" placeholder="45" />
					</div>
			  </div>				  			  	
			  
			</div>						
			
			<div class="row">
		  	<button type="submit" class="btn btn-primary span2 offset9">Run Backtest</button>
			</div>
		</form>
	</div>	
		
	<div class="span12 row">
		<table class="table table-bordered table-striped table-responsive">
			<thead>
				<tr>
					<th>Col #1</th>
					<th>Col #2</th>
					<th>Col #3</th>						
				</tr>
			</thead>
			
			<tbody>
				<tr>
					<td>Col #1</td>
					<td>Col #2</td>
					<td>Col #3</td>						
				</tr>
				
				<tr>
					<td>Col #1</td>
					<td>Col #2</td>
					<td>Col #3</td>						
				</tr>
				
				<tr>
					<td>Col #1</td>
					<td>Col #2</td>
					<td>Col #3</td>						
				</tr>
				
				<tr>
					<td>Col #1</td>
					<td>Col #2</td>
					<td>Col #3</td>						
				</tr>						
			</tbody>	
		</table>
	</div>
	
</div>