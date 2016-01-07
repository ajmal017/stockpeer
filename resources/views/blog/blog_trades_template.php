<?php
//echo '<pre>' . print_r($trades, TRUE) . '</pre>';	
?>

<div class="trade-table">
	<table class="table">
		<thead>
			<tr>
				<th>Ticker</th>
				<th>Type</th>
				<th>Spread</th>
				<th>Expire Date</th>
				<th>Open Date</th>
				<th>Closed Date</th>
				<th>Open Credit</th>
				<th>Closed Debit</th>
				<th>Profit / Loss</th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($trades AS $key => $row) : ?>
			<tr>
				<td><?=$row['BlogTradesTicker']?></td>
				<td><?=$row['BlogTradesType']?></td>
				<td><?=$row['BlogTradesBuyStrike_df1']?> / <?=$row['BlogTradesSellStrike_df1']?></td>
				<td><?=date('n/j/y', strtotime($row['BlogTradesExpireDate']))?></td>
				<td><?=date('n/j/y', strtotime($row['BlogTradesOpenDate']))?></td>
				<td><?=date('n/j/y', strtotime($row['BlogTradesCloseDate']))?></td>
				<td>$<?=$row['BlogTradesOpenCredit']?></td>
				<td>$<?=$row['BlogTradesCloseDebit']?></td>
				<td><?=$row['ProfitLoss']?>%</td>					
			</tr>	
			<?php endforeach; ?>									
		</tbody>
	</table>
</div>