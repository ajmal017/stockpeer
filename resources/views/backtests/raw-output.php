<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/pure/0.6.0/pure-min.css">
    
    <style>
      .cont { width: 90%; margin: 15px auto; }
      .summary { padding: 15px 0; }
      .summary table { width: 400px; }
      .summary table td { text-align: right; padding: 5px; }
    </style>		
		
	</head>
	<body>

    <div class="cont">
      
      <div class="summary">
        <table>
          
          <tr>
            <td><b>Trade Count:</b></td>
            <td><?=$trade_count?></td>
          </tr>          

          <tr>
            <td><b>Win Rate:</b></td>
            <td><?=$win_rate?>%</td>
          </tr>  

          <tr>
            <td><b>Start Date:</b></td>
            <td><?=date('n/j/Y', strtotime($start_date))?></td>
          </tr>
          
          <tr>
            <td><b>End Date:</b></td>
            <td><?=date('n/j/Y', strtotime($end_date))?></td>
          </tr>

          <tr>
            <td><b>Start Cash:</b></td>
            <td>$<?=number_format($start_cash, 2)?></td>
          </tr>
          
          <tr>
            <td><b>End Cash:</b></td>
            <td>$<?=number_format($end_cash, 2)?></td>
          </tr>

          <tr>
            <td><b>CAGR:</b></td>
            <td><?=number_format($cagr, 2)?>%</td>
          </tr> 

          <tr>
            <td><b>Profit:</b></td>
            <td>$<?=number_format($profit, 2)?> (<?=$profit_precent?>%)</td>
          </tr>          
                    
        </table>
      </div>

      <div class="table">
        
        <table class="pure-table">
          <thead>
            <tr>
              <th>Symbol</th>
              <th>Type</th>
              <th>Qty</th>
              <th>Open Date</th> 
              <th>Close date</th>
              <th>Open Time</th>
              <th>Close Time</th>
              <th>Open Price</th> 
              <th>Close Price</th>
              <th>Profit / Share</th> 
              <th>Profit</th> 
              <th>Cash</th>                                                
            </tr>
          </thead>
          
          <tbody>
            
            <?php foreach($trades AS $key => $row) : ?>
            <tr>
              <td><?=strtoupper($row['symbol'])?></td>
              <td><?=$row['type']?></td>
              <td><?=$row['qty']?></td>
              <td><?=$row['open_date']?></td>
              <td><?=$row['close_date']?></td>
              <td><?=$row['open_time']?></td>                                
              <td><?=$row['close_time']?></td>    
              <td>$<?=number_format($row['open_price'], 2)?></td>    
              <td>$<?=number_format($row['close_price'], 2)?></td>    
              <td>$<?=number_format($row['profit_share'], 2)?></td>  
              <td <?php if($row['profit'] < 0) echo 'style="color: red;"'; ?>>$<?=number_format($row['profit'], 2)?></td>   
              <td>$<?=number_format($row['cash'], 2)?></td>                                    
            </tr>
            
            <?php endforeach; ?>
            
          </tbody>
        </table>
        
      </div>
    
    </div>

	</body>
</html>