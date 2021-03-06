<div class="zone-content" ng-controller="BacktestCtrl">
	
	<h1>Backtester</h1>
	
	<div class="row" ng-show="started">
  	
    <div class="progress">
      <div class="bar bar-success" style="width: {{ progress }}%;"></div>
      {{ progress }}%
    </div>
  
	</div>		
	
	<div class="row well" ng-show="summary">
    <table class="backtest-summary-table">
      <tr>
        <td class="col-label">Balance:</td>
        <td>$<span ng-bind="backtest.BackTestsEndBalance | number:0"></span></td>
        
        <td class="col-label">Profit:</td>
        <td>$<span ng-bind="backtest.BackTestsProfit | number:0"></span></td>        
        
        <td class="col-label">CAGR:</td>
        <td><span ng-bind="backtest.BackTestsCagr"></span>%</td>
        
        <td class="col-label">Avg. Days:</td>
        <td><span ng-bind="backtest.BackTestsAvgDaysInTrade | number:0"></span></td>
      </tr>
      
      
      <tr>
        <td class="col-label">Avg. Credit:</td>
        <td>$<span ng-bind="backtest.BackTestsAvgCredit"></span></td>
        
        <td class="col-label">Win Rate:</td>
        <td><span ng-bind="backtest.BackTestsWinRate"></span>%</td>
        
        <td class="col-label"># Trades:</td>
        <td><span ng-bind="backtest.BackTestsTotalTrades"></span></td>
        
        <td class="col-label"># Loss Trades:</td>
        <td><span ng-bind="backtest.BackTestsLosses"></span></td>                                        
      </tr>

    </table>
    
    <div class="row backtest-summary-share">
      <div class="span9">
        Share: <a href="<?=URL::to('/backtests/option-spreads')?>/{{ backtest.BackTestsPublicHash }}" target="_blank"><?=URL::to('/backtests/option-spreads')?>/{{ backtest.BackTestsPublicHash }}</a>
      </div>
      
      <div class="span2">
        <a hrf="" class="btn btn-primary" ng-click="another_backtest()">Another Backtest</a>
      </div>
    </div>
	</div>
	
	<div class="row well" ng-hide="summary || started">
		<form role="form">
			
			<div class="row">				
			  <div class="control-group span4 ml-60">
			    <label class="control-label">Backtest Type</label>
			    <div class="controls">						
            <select class="input-large" ng-model="fields.BackTestsType">
              <option value="Put Credit Spreads">Put Credit Spread</option>               
            </select>						
					</div>
			  </div>
			  
			  <div class="control-group span4 ml-60">
			    <label class="control-label">Start Date</label>
			    <div class="controls">
						<input type="text" placeholder="Text input" class="form-control" ng-model="fields.BackTestsStart" style="height: 20px;" />
					</div>
			  </div>			  

			  <div class="control-group span3 ml-60">
			    <label class="control-label">End Date</label>
			    <div class="controls">
						<input type="text" placeholder="Text input" class="form-control" ng-model="fields.BackTestsEnd" style="height: 20px;" />
					</div>
			  </div>	
			  
			</div>
			
			<div class="row">
  			
			  <div class="control-group span4 ml-60">
  			  
          <label class="control-label">Open Signal</label>
          
          <select class="form-control" ng-model="fields.BackTestsOpenAt">
            <option value="precent-away">Percent To Downside</option>
          </select>   			  

			  </div>
			  
			  <div class="control-group span4 ml-60">
					
          <label class="control-label">Short Percent Away</label>
          <select class="form-control" ng-model="fields.BackTestOpenPercentAway">
            <option value="0.05">0.05% Down</option>
            <option value="0.75">0.75% Down</option>
            <option value="1.00">1.00% Down</option>
            <option value="1.25">1.25% Down</option>
            <option value="1.50">1.50% Down</option>    
            <option value="1.75">1.75% Down</option>
            <option value="2.00">2.00% Down</option>
            <option value="2.25">2.25% Down</option>
            <option value="2.50">2.50% Down</option>  
            <option value="2.75">2.75% Down</option>
            <option value="3.00">3.00% Down</option>
            <option value="3.25">3.25% Down</option>
            <option value="3.50">3.50% Down</option>    
            <option value="3.75">3.75% Down</option>
            <option value="4.00">4.00% Down</option>
            <option value="4.25">4.25% Down</option>
            <option value="4.50">4.50% Down</option>  
            <option value="4.75">4.75% Down</option>  
            <option value="5.00">5.00% Down</option>                                  
          </select>				
					
			  </div>			  

			  <div class="control-group span3 ml-60">
          <label class="control-label">Min Credit To Open</label>
          <select class="form-control" ng-model="fields.BackTestsMinOpenCredit">
            <option value="0.05">$0.05</option>
            <option value="0.10">$0.10</option>
            <option value="0.11">$0.11</option>
            <option value="0.12">$0.12</option>
            <option value="0.13">$0.13</option>    
            <option value="0.14">$0.14</option>
            <option value="0.15">$0.15</option>
            <option value="0.16">$0.16</option>
            <option value="0.17">$0.17</option>  
            <option value="0.18">$0.18</option>
            <option value="0.19">$0.19</option>
            <option value="0.20">$0.20</option>
            <option value="0.21">$0.21</option>    
            <option value="0.22">$0.22</option>
            <option value="0.23">$0.23</option>
            <option value="0.24">$0.24</option>
            <option value="0.25">$0.25</option>  
            <option value="0.26">$0.26</option>  
            <option value="0.27">$0.27</option>
            <option value="0.28">$0.28</option>
            <option value="0.29">$0.29</option>
            <option value="0.30">$0.30</option>                                                 
          </select>	
			  </div>	  			
  			
			</div>

			<div class="row">
				
			  <div class="control-group span4 ml-60">
			    <label class="control-label">Starting Balance</label>
          
          <div class="controls input-prepend">
            <span class="add-on">$</span>
            <input type="text" class="form-control" ng-model="fields.BackTestsStartBalance" style="height: 20px;" />
          </div>				    
		    
			  </div>	
			  
			  <div class="control-group span4 ml-60">
			    <label class="control-label">Trade Size</label>
			    
          <select class="input-large" ng-model="fields.BackTestsTradeSize">
            <optgroup label="Compouding">
              <option value="percent-1">1% of Balance</option>
              <option value="percent-2">2% of Balance</option>
              <option value="percent-3">3% of Balance</option>
              <option value="percent-4">4% of Balance</option>
              <option value="percent-5">5% of Balance</option>
              <option value="percent-6">6% of Balance</option>
              <option value="percent-7">7% of Balance</option>
              <option value="percent-8">8% of Balance</option>
              <option value="percent-9">9% of Balance</option>
              <option value="percent-10">10% of Balance</option>      					  
              <option value="percent-15">15% of Balance</option> 
              <option value="percent-20">20% of Balance</option>   
              <option value="percent-25">25% of Balance</option>   
              <option value="percent-30">30% of Balance</option>  
              <option value="percent-50">50% of Balance</option> 
              <option value="percent-60">60% of Balance</option>  
              <option value="percent-70">70% of Balance</option>         					        					    
              <option value="percent-75">75% of Balance</option>  
              <option value="percent-80">80% of Balance</option>
              <option value="percent-90">90% of Balance</option>
              <option value="percent-95">95% of Balance</option>       					         					           					  
              <option value="percent-100">100% of Balance</option>         					        					        					      					      					        					        					        		
            </optgroup>
            
            <optgroup label="Non-Compounding">
              <option value="fixed-1">1 Lot Fixed</option>
              <option value="fixed-2">2 Lots Fixed</option>
              <option value="fixed-3">3 Lots Fixed</option>
              <option value="fixed-4">4 Lots Fixed</option>
              <option value="fixed-5">5 Lots Fixed</option>
              <option value="fixed-6">6 Lots Fixed</option>
              <option value="fixed-7">7 Lots Fixed</option>
              <option value="fixed-8">8 Lots Fixed</option>
              <option value="fixed-9">9 Lots Fixed</option>
              <option value="fixed-10">10 Lots Fixed</option>    					        					        					         					       					        					        					        					        
              <option value="fixed-11">11 Lots Fixed</option>
              <option value="fixed-12">12 Lots Fixed</option>
              <option value="fixed-13">13 Lots Fixed</option>
              <option value="fixed-14">14 Lots Fixed</option>
              <option value="fixed-15">15 Lots Fixed</option>
              <option value="fixed-16">16 Lots Fixed</option>
              <option value="fixed-17">17 Lots Fixed</option>
              <option value="fixed-18">18 Lots Fixed</option>
              <option value="fixed-19">19 Lots Fixed</option>
              <option value="fixed-20">20 Lots Fixed</option>        					     					        					        					         					       					        					        					        
              <option value="fixed-21">21 Lots Fixed</option>
              <option value="fixed-22">22 Lots Fixed</option>
              <option value="fixed-23">23 Lots Fixed</option>
              <option value="fixed-24">24 Lots Fixed</option>
              <option value="fixed-25">25 Lots Fixed</option>
              <option value="fixed-26">26 Lots Fixed</option>
              <option value="fixed-27">27 Lots Fixed</option>
              <option value="fixed-28">28 Lots Fixed</option>
              <option value="fixed-29">29 Lots Fixed</option>
              <option value="fixed-30">30 Lots Fixed</option>   
              <option value="fixed-31">31 Lots Fixed</option>
              <option value="fixed-32">32 Lots Fixed</option>
              <option value="fixed-33">33 Lots Fixed</option>
              <option value="fixed-34">34 Lots Fixed</option>
              <option value="fixed-35">35 Lots Fixed</option>
              <option value="fixed-36">36 Lots Fixed</option>
              <option value="fixed-37">37 Lots Fixed</option>
              <option value="fixed-38">38 Lots Fixed</option>
              <option value="fixed-39">39 Lots Fixed</option>
              <option value="fixed-40">40 Lots Fixed</option>
              <option value="fixed-41">41 Lots Fixed</option>
              <option value="fixed-42">42 Lots Fixed</option>
              <option value="fixed-43">43 Lots Fixed</option>
              <option value="fixed-44">44 Lots Fixed</option>
              <option value="fixed-45">45 Lots Fixed</option>
              <option value="fixed-46">46 Lots Fixed</option>
              <option value="fixed-47">47 Lots Fixed</option>
              <option value="fixed-48">48 Lots Fixed</option>
              <option value="fixed-49">49 Lots Fixed</option>
              <option value="fixed-50">50 Lots Fixed</option> 
              <option value="fixed-75">75 Lots Fixed</option>
              <option value="fixed-100">100 Lots Fixed</option>
              <option value="fixed-150">150 Lots Fixed</option>
              <option value="fixed-200">200 Lots Fixed</option>
              <option value="fixed-300">300 Lots Fixed</option>
              <option value="fixed-400">400 Lots Fixed</option>    					        					         					        					         					        					    			    
             </optgroup>
          </select>			    

			  </div>				  
			  
			  <div class="control-group span3 ml-60">
			    <label class="control-label">Close Trade</label>
			 
          <select class="input-large" ng-model="fields.BackTestsCloseAt">
            <option value="let-expire">Let Expire</option>
            
            <optgroup label="Target Credit">
              <option value="credit-0.01">Close @ $0.01</option>   
              <option value="credit-0.02">Close @ $0.02</option>   
              <option value="credit-0.03">Close @ $0.03</option>  
              <option value="credit-0.04">Close @ $0.04</option>   
              <option value="credit-0.05">Close @ $0.05</option>   
              <option value="credit-0.06">Close @ $0.06</option>  
              <option value="credit-0.07">Close @ $0.07</option>   
              <option value="credit-0.08">Close @ $0.08</option>   
              <option value="credit-0.09">Close @ $0.09</option>  
              <option value="credit-0.10">Close @ $0.10</option>   
              <option value="credit-0.11">Close @ $0.11</option>   
              <option value="credit-0.12">Close @ $0.12</option>  
              <option value="credit-0.13">Close @ $0.13</option>   
              <option value="credit-0.14">Close @ $0.14</option>   
              <option value="credit-0.15">Close @ $0.15</option>  
              <option value="credit-0.16">Close @ $0.16</option>   
              <option value="credit-0.17">Close @ $0.17</option>   
              <option value="credit-0.18">Close @ $0.18</option>  
              <option value="credit-0.19">Close @ $0.19</option>   
              <option value="credit-0.20">Close @ $0.20</option>    					  
            </optgroup>
            
          </select>			    

			  </div>				  			  	
			  
			</div>			
			
			
			<div class="row">
				
			  <div class="control-group span4 ml-60">
			    <label class="control-label">Ticker Symbol</label>
			    <div class="controls">
						<select class="input-large">
						  <option value="spy">SPY - SPDR S&P 500 ETF</option>
						  <?php /* <option value="iwm">IWM - iShares Russell 2000 ETF</option> */ ?>						  
						</select>
					</div>
			  </div>
			  
			  <div class="control-group span4 ml-60">
			    <label class="control-label">Spread Width</label>
					<select class="input-large" ng-model="fields.BackTestsSpreadWidth">
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

			  <div class="control-group span3 ml-60">
          <label class="control-label">Stop Loss</label>
          <select class="form-control" ng-model="fields.BackTestsStopAt">
            <option value="no-stop">No Stop</option>
            <option value="touch-short-leg">Sell On Touching Short Leg</option>                                                                                                                                                                               
          </select>					
			  </div>	
			  
			</div>
			
			<div class="row">
							  
			  <div class="control-group span4 ml-60">
  			  					
          <label class="control-label">Min Days To Expiration</label>
          <select class="form-control" ng-model="fields.BackTestsMinDaysExpire">
            <option value="1">1 Day</option>
            <option value="2">2 Days</option>
            <option value="3">3 Days</option>
            <option value="4">4 Days</option>
            <option value="5">5 Days</option>    
            <option value="6">6 Days</option>
            <option value="7">7 Days</option>
            <option value="8">8 Days</option>
            <option value="9">9 Days</option>  
            <option value="10">10 Days</option>
            <option value="11">11 Days</option>
            <option value="12">12 Days</option>
            <option value="13">13 Days</option>    
            <option value="14">14 Days</option>
            <option value="15">15 Days</option>
            <option value="16">16 Days</option>
            <option value="17">17 Days</option>  
            <option value="18">18 Days</option>
            <option value="19">19 Days</option>
            <option value="20">20 Days</option>
            <option value="21">21 Days</option>    
            <option value="22">22 Days</option>
            <option value="23">23 Days</option>
            <option value="24">24 Days</option>
            <option value="25">25 Days</option>  
            <option value="26">26 Days</option>
            <option value="27">27 Days</option>
            <option value="28">28 Days</option>
            <option value="29">29 Days</option>    
            <option value="30">30 Days</option>
            <option value="31">31 Days</option>
            <option value="32">32 Days</option>
            <option value="33">33 Days</option>  
            <option value="34">34 Days</option>
            <option value="35">35 Days</option>
            <option value="36">36 Days</option>
            <option value="37">37 Days</option>    
            <option value="38">38 Days</option>
            <option value="39">39 Days</option>
            <option value="40">40 Days</option>
            <option value="41">41 Days</option> 
            <option value="42">42 Days</option>
            <option value="43">43 Days</option>  
            <option value="44">44 Days</option>
            <option value="45">45 Days</option>
            <option value="46">46 Days</option>
            <option value="47">47 Days</option>    
            <option value="48">48 Days</option>
            <option value="49">49 Days</option>
            <option value="50">50 Days</option>
            <option value="51">51 Days</option>  
            <option value="52">52 Days</option>
            <option value="53">53 Days</option>
            <option value="54">54 Days</option>
            <option value="55">55 Days</option>    
            <option value="56">56 Days</option>
            <option value="57">57 Days</option>
            <option value="58">58 Days</option>
            <option value="59">59 Days</option>
            <option value="60">60 Days</option>                                                                                                                                                                                
          </select>					
					
			  </div>	
			  
			  <div class="control-group span4 ml-60">
          <label class="control-label">Max Days To Expiration</label>
          <select class="form-control" ng-model="fields.BackTestsMaxDaysExpire">
            <option value="1">1 Day</option>
            <option value="2">2 Days</option>
            <option value="3">3 Days</option>
            <option value="4">4 Days</option>
            <option value="5">5 Days</option>    
            <option value="6">6 Days</option>
            <option value="7">7 Days</option>
            <option value="8">8 Days</option>
            <option value="9">9 Days</option>  
            <option value="10">10 Days</option>
            <option value="11">11 Days</option>
            <option value="12">12 Days</option>
            <option value="13">13 Days</option>    
            <option value="14">14 Days</option>
            <option value="15">15 Days</option>
            <option value="16">16 Days</option>
            <option value="17">17 Days</option>  
            <option value="18">18 Days</option>
            <option value="19">19 Days</option>
            <option value="20">20 Days</option>
            <option value="21">21 Days</option>    
            <option value="22">22 Days</option>
            <option value="23">23 Days</option>
            <option value="24">24 Days</option>
            <option value="25">25 Days</option>  
            <option value="26">26 Days</option>
            <option value="27">27 Days</option>
            <option value="28">28 Days</option>
            <option value="29">29 Days</option>    
            <option value="30">30 Days</option>
            <option value="31">31 Days</option>
            <option value="32">32 Days</option>
            <option value="33">33 Days</option>  
            <option value="34">34 Days</option>
            <option value="35">35 Days</option>
            <option value="36">36 Days</option>
            <option value="37">37 Days</option>    
            <option value="38">38 Days</option>
            <option value="39">39 Days</option>
            <option value="40">40 Days</option>
            <option value="41">41 Days</option> 
            <option value="42">42 Days</option>
            <option value="43">43 Days</option>  
            <option value="44">44 Days</option>
            <option value="45">45 Days</option>
            <option value="46">46 Days</option>
            <option value="47">47 Days</option>    
            <option value="48">48 Days</option>
            <option value="49">49 Days</option>
            <option value="50">50 Days</option>
            <option value="51">51 Days</option>  
            <option value="52">52 Days</option>
            <option value="53">53 Days</option>
            <option value="54">54 Days</option>
            <option value="55">55 Days</option>    
            <option value="56">56 Days</option>
            <option value="57">57 Days</option>
            <option value="58">58 Days</option>
            <option value="59">59 Days</option>
            <option value="60">60 Days</option>                                                                                                                                                                                
          </select>					
			  </div>
			  
			  <div class="control-group span3 ml-60">					
          <label class="control-label">Trade Select</label>
          <select class="form-control" ng-model="fields.BackTestsTradeSelect">
            <option value="lowest-credit">Lowest Credit</option>
            <option value="median-credit">Median Credit</option>
            <option value="highest-credit">Highest Credit</option>
          </select>							    
			  </div>				  				  			  	
			  
			</div>		
			
			
			
			<div class="row">
							  
			  <div class="control-group span4 ml-60">
  			  
          <label class="control-label">One Trade At A Time</label>
          
          <select class="form-control" ng-model="fields.BackTestsOneTradeAtTime">
            <option value="Yes">Yes</option>
            <option value="No">No</option>                
          </select>				
					
			  </div>	
			  
			  <div class="control-group span4 ml-60">
				
			  </div>
			  
			  <div class="control-group span3 ml-60">					
						    
			  </div>				  				  			  	
			  
			</div>				
			
							
			
			<div class="row">
  			<a href="" class="span2 mt-25" ng-show="trades.length" ng-click="back_to_summary()">Back To Summary</a>
  			<span class="span2 mt-25" ng-show="! trades.length">&nbsp;</span>
		  	<button type="submit" class="btn btn-primary span2 offset7" ng-click="run_backtest()">Run Backtest</button>
			</div>
		</form>				
	</div>
	
	
  <div class="backtest-summary" ng-show="trades.length">
  
    <ul class="nav nav-pills">
      <li ng-class="{ active: (backtest_summary_tab == 'performance') }"><a href="" ng-click="backtest_summary_click('performance')">Performance</a></li>
      <li ng-class="{ active: (backtest_summary_tab == 'trades') }"><a href="" ng-click="backtest_summary_click('trades')">Trades</a></li>
    </ul>  
    	
    <div class="row" ng-show="backtest_summary_tab == 'performance'">	
      <div id="account_balance_chart" style="width: 1230px; height: 600px;"></div>	
    </div>
    
    <div class="row" ng-show="backtest_summary_tab == 'trades'">
      <table class="table table-bordered table-striped table-responsive trades-table">
      	<thead>
      		<tr>
      			<th>Open Date</th>
      			<th>Close Date</th>
      			<th>Lots</th>					
      			<th>Spread</th>	
      			<th>Expire</th>
      			<th>Stock Open</th>	
      			<th>Stock Close</th>										
      			<th>Stopped</th>									
      			<th>Profit</th>
      			<th>Balance</th>											
      		</tr>
      	</thead>
      	
      	<tbody>
      		<tr ng-repeat="row in trades track by $index">
      			<td ng-bind="row.BackTestTradesOpen | date:'M/d/yyyy'"></td>
      			<td ng-bind="row.BackTestTradesClose | date:'M/d/yyyy'"></td>
            <td ng-bind="row.BackTestTradesLots"></td>
      			<td><span ng-bind="row.BackTestTradesLongLeg1 | number:0"></span> / <span ng-bind="row.BackTestTradesShortLeg1 | number:0"></span></td>	
      			<td ng-bind="row.BackTestTradesExpire1 | date:'M/d/yyyy'"></td>
      			<td>$<span ng-bind="row.BackTestTradesSymStart | number:2"></span></td>
      			<td>$<span ng-bind="row.BackTestTradesSymEnd | number:2"></span></td>					
      			<td ng-bind="row.BackTestTradesStopped"></td>				
      			<td ng-class="{ red: (row.BackTestTradesProfit < 0) }">$<span ng-bind="row.BackTestTradesProfit | number:2"></span></td>
      			<td>$<span ng-bind="row.BackTestTradesBalance | number:2"></span></td>											
      		</tr>					
      	</tbody>	
      </table>
    </div>
	
  </div>
	
</div>