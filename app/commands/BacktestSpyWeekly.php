<?php 
  
namespace App\Commands;

use App;
use Queue;
use Carbon\Carbon;
use App\Commands\Command;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldBeQueued;

class BacktestSpyWeekly extends Command implements SelfHandling, ShouldBeQueued 
{
	use InteractsWithQueue, SerializesModels;

  public $backtest_id = null;

  //
  // Create a new command instance.
  //
	public function __construct($backtest_id)
	{
    $this->backtest_id = $backtest_id;           
  }

  //
  // Execute the command.
  //
	public function handle()
	{  	
    echo "\n ********** Starting BacktestSpyWeekly Backtest **************** \n";
  	  	
    echo "\n Backtest Id: $this->backtest_id \n\n";    
  	
  	// Run backtest
    $WeeklySpy = App::make('App\Backtesting\WeeklySpy');
    $data = $WeeklySpy->run($this->backtest_id);
    
  	// Tell websockets we are done.
    $order['UsersId'] = 1;
    Queue::pushOn('stockpeer.com.websocket', 'Backtesting:done', $order);    
    
    echo "\n ********** Ending BacktestSpyWeekly Backtest **************** \n";     
    
/*
    // -------- Do something with the results. ------- //
  
    $html = '';
    $diffs = [];
    $diffs_abs = [];  
    $profit = 0.00;
    $winners = 0;
    $orders = $data['orders'];
    $balance = $data['balance'];
    $start_balance = $data['start_balance'];        
    
    $html .= '<table width="80%" border="1">';
    $html .= '<tr>
    <th>Open Date</th>
    <th>Close Date</th>
    <th>Spread</th> 
    <th>Expire</th>    
    <th>Lots</th>  
    <th>Open</th>
    <th>Close</th>
    <th>Diff</th>
    <th>Vix</th>
    <th>Touched</th>  
    <th>Costs</th> 
    <th>Credit</th>    
    <th>Profit</th> 
    <th>Balance</th>       
    </tr>';
    
    foreach($orders AS $key => $row)
    {    
      $html .= "<tr>";
      $html .= "<td>" . $row['open_day'] . "</td>"; 
      $html .= "<td>" . $row['close_day'] . "</td>"; 
      $html .= "<td>" . $row['spread'] . "</td>"; 
      $html .= "<td>" . $row['expire'] . "</td>";                
      $html .= "<td>" . $row['lots'] . "</td>";
      $html .= "<td>$" . $row['open'] . "</td>";
      $html .= "<td>$" . $row['close'] . "</td>"; 
      
      $diffs_abs[] = $row['prct_diff'];
      if($row['open'] > $row['close'])
      {
        $diffs[] = $row['prct_diff'] * -1;
        $html .= "<td>-" . round($row['prct_diff'], 2) . "%</td>";    
      } else
      {
        $diffs[] = $row['prct_diff'];
        $html .= "<td>" . round($row['prct_diff'], 2) . "%</td>";      
      }
  
      $html .= "<td>" . number_format($row['vix'], 2, '.', ',') . "</td>"; 
      $html .= "<td>" . $row['touched'] . "</td>";    
      $html .= "<td>$" . number_format($row['commissions'], 2, '.', ',') . "</td>";     
      $html .= "<td>$" . number_format($row['credit'], 2, '.', ',') . "</td>"; 
      
      $html .= "<td>$" . number_format($row['profit'], 2, '.', ',') . "</td>"; 
      $html .= "<td>$" . number_format($row['balance'], 2, '.', ',') . "</td>";                    
      $html .= "</tr>";
      
      if($row['profit'] > 0)
      {
        $winners++;
      }
      
      if(is_null($row['close']))
      {
        //$html .= '<pre>' . print_r($row, TRUE) . '</pre>';
      } else
      {
        $profit += $row['profit'];
      }
    }
    
    $years = date('Y', strtotime($this->end_date)) - date('Y', strtotime($this->start_date));
    
    if($years > 0)
    {
      $CAGR = (pow(($balance / $start_balance), (1 / $years)) - 1) * 100;
    } else
    {
      $CAGR = (pow(($balance / $start_balance), (1 / 1)) - 1) * 100;
    }
    
    $html .= '</table>';
  
    $html .= '<h1> Start Balance: $' . number_format($start_balance, 2, '.', ',')  . '</h1>';
    
    $html .= '<h1> End Balance: $' . number_format($balance, 2, '.', ',')  . '</h1>';
    
    $html .= '<h1> Profit: $' . number_format($profit, 2, '.', ',') . '</h1>';
  
    $html .= '<h1> Mean Change: ' . round(array_sum($diffs_abs) / count($diffs_abs), 2) . '%</h1>';
   
    $html .= '<h1> Max Change: ' . round(max($diffs), 2) . '%</h1>';
  
    $html .= '<h1> Min Change: ' . round(min($diffs), 2) . '%</h1>';    
  
    $html .= '<h1> Trades: ' . count($orders) . '</h1>';
  
    $html .= '<h1> Success: ' . $winners . ' / ' . count($orders) . ' (' . round(($winners / count($orders)) * 100, 2) . '%)</h1>';  
  
    $html .= '<h1>CAGR: ' . round($CAGR, 2) . '%</h1>'; 
    
    // Write html out to the cache file.
    file_put_contents(public_path() . "/cache/$this->start_date" . "_" . $this->end_date . "_BacktestSpyWeekly.html", $html);
    
    // Echo out the url
    echo "\n cache/$this->start_date" . "_" . $this->end_date . "_BacktestSpyWeekly.html \n";
    
    echo "\n ********** Ending BacktestSpyWeekly Backtest **************** \n"; 
*/   
	}

}

/* End File */