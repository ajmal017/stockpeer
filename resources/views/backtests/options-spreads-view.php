<div class="zone-content">
		
	
	<div class="row">
  	<div class="col-md-12">
    	<div class="well clearfix">
      	

    		<div class="row">				
      	  
    		  <div class="control-group span4 offset1">
    		    <label class="control-label"><b>Backtest Name</b></label>
            <p><?=$backtest['BackTestsName']?></p>
    		  </div>
    		      			  
    		</div>
    		
    		<div class="row">				
    		  <div class="control-group span4 offset1">
    		    <label class="control-label"><b>Backtest Type</b></label>
    		    <p><?=$backtest['BackTestsType']?></p>
    		  </div>
    		  
    		  <div class="control-group span3">
    		    <label class="control-label"><b>Start Date</b></label>
    		    <p><?=date('n/j/Y', strtotime($backtest['BackTestsStart']))?></p>
    		  </div>			  
    
    		  <div class="control-group span3">
    		    <label class="control-label"><b>End Date</b></label>
    		    <p><?=date('n/j/Y', strtotime($backtest['BackTestsEnd']))?></p>
    		  </div>	
    		  
    		</div>
    		
    
    		<div class="row">
    		  
    		  <div class="control-group span4 offset1">
    		    <label class="control-label"><b>Starting Balance</b></label>
    		    <p>$<?=number_format($backtest['BackTestsStartBalance'], 0)?></p>	    
    		  </div>	
    		  
    		  <div class="control-group span3">
    		    <label class="control-label"><b>Trade Size</b></label>
    		    <p><?=$backtest['BackTestsTradeSize']?></p>
    		  </div>				  
    		  
    		  <div class="control-group span3">
    		    <label class="control-label"><b>Close Trade</b></label>
    		    <p><?=$backtest['BackTestsCloseAt']?></p>
    		  </div>				  			  	
    		  
    		</div>	
    		  					

    		<div class="row">
    		  
    		  <div class="control-group span4 offset1">
    		    <label class="control-label"><b>Backtest Start</b></label>
    		    <p><?=date('n/j/Y g:i:s a', strtotime($backtest['BackTestsClockStart']))?></p>   
    		  </div>	
    		  
    		  <div class="control-group span3">
    		    <label class="control-label"><b>Backtest Ended</b></label>
    		    <p><?=date('n/j/Y g:i:s a', strtotime($backtest['BackTestsClockEnd']))?></p> 
    		  </div>				  
    		  
    		  <div class="control-group span3">
    		    <label class="control-label"><b>Backtest Status</b></label>
    		    <p><?=$backtest['BackTestsStatus']?></p> 
    		  </div>				  			  	
    		  
    		</div>
    		
    		
    		<div class="row">
    		  
    		  <div class="control-group span4 offset1">
    		    <label class="control-label"><b>Profit</b></label>
    		    <p>$<?=number_format($backtest['BackTestsProfit'], 0)?></p>	  
    		  </div>	
    		  
    		  <div class="control-group span3">
    		    <label class="control-label"><b>CAGR</b></label>
    		    <p><?=$backtest['BackTestsCagr']?>%</p>	 
    		  </div>				  
    		  
    		  <div class="control-group span3">
    		    <label class="control-label"><b>Average Days In Trade</b></label>
    		    <p><?=$backtest['BackTestsAvgDaysInTrade']?></p> 
    		  </div>				  			  	
    		  
    		</div>    			
    		  
    		<div class="row">
    		  
    		  <div class="control-group span4 offset1">
    		    <label class="control-label"><b>Win Rate</b></label>
    		    <p><?=$backtest['BackTestsWinRate']?>%</p>	  
    		  </div>	
    		  
    		  <div class="control-group span3">
    		    <label class="control-label"><b>Total Trades</b></label>
    		    <p><?=$backtest['BackTestsTotalTrades']?></p>	 
    		  </div>				  
    		  
    		  <div class="control-group span3">
    		    <label class="control-label"><b>Loss Trades</b></label>
    		    <p><?=$backtest['BackTestsLosses']?></p> 
    		  </div>				  			  	
    		  
    		</div> 

    		
    	</div>
  	</div>
	</div>
	
				
	<div class="row">
  	
      
    <div class="panel panel-default panel-primary">
      <div class="panel-heading">Backtest Trades</div>

      <div class="table-responsive">

        
        <table class="table table-striped">  	
          <thead>
            <tr>
              <th class="text-center">Open Date</th>
              <th class="text-center">Close Date</th>
              <th class="text-center">Spread</th>
              <th class="text-center">Expire</th>
              <th class="text-center">Lots</th>
              <th class="text-center">Stock Open</th>
              <th class="text-center">Stock Close</th>
              <th class="text-center">Diff</th>
              <th class="text-center">Vix Open</th>
              <th class="text-center">Vix Close</th> 
              <th class="text-center">Short Delta Open</th>
              <th class="text-center">Short Delta Close</th>       
              <th class="text-center">Stopped</th>
        <!--       <th class="text-center">Costs</th> -->
              <th class="text-center">Open Credit</th>
              <th class="text-center">Close Credit</th>                
              <th class="text-center">Profit</th>
              <th class="text-center">Balance</th>
            </tr>    
          </thead>
          
          <tbody>
            
            <?php foreach($backtest['Trades'] AS $key => $row) : ?>
            <tr>
              <td class="text-center"><?=date('n/j/y', strtotime($row['BackTestTradesOpen']))?></td>
              <td class="text-center"><?=date('n/j/y', strtotime($row['BackTestTradesClose']))?></td>
              <td class="text-center"><?=$row['BackTestTradesLongLeg1']?> / <?=$row['BackTestTradesShortLeg1']?></td>
              <td class="text-center"><?=date('n/j/y', strtotime($row['BackTestTradesExpire1']))?></td>
              <td class="text-center"><?=$row['BackTestTradesLots']?></td>
              <td class="text-center">$<?=number_format($row['BackTestTradesSymStart'], 0)?></td>
              <td class="text-center">$<?=number_format($row['BackTestTradesSymEnd'], 0)?></td>
              <td class="text-center"><?=$row['BackTestTradesSymDiff']?>%</td>
              <td class="text-center"><?=$row['BackTestTradesVixStart']?></td>
              <td class="text-center"><?=$row['BackTestTradesVixEnd']?></td>   
              <td class="text-center"><?=$row['BackTestTradesShortDeltaStart1']?></td>
              <td class="text-center"><?=$row['BackTestTradesShortDeltaEnd1']?></td>         
              <td class="text-center"><?=$row['BackTestTradesStopped']?></td>                
        <!--       <td class="text-center">${{ row.BackTestTradesCommissions | number:2 }}</td> -->
              <td class="text-center">$<?=number_format($row['BackTestTradesOpenCredit'], 2)?></td>
              <td class="text-center">$<?=number_format($row['BackTestTradesCloseCredit'], 2)?></td>                
              <td class="text-center">$<?=number_format($row['BackTestTradesProfit'], 0)?></td>
              <td class="text-center">$<?=number_format($row['BackTestTradesBalance'], 0)?></td>
            </tr>
            <?php endforeach; ?>
                       
          </tbody>
        </table>






      </div>
    </div>

    
	</div>
</div>