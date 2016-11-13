<?php

namespace App\Http\Controllers;

use DB;
use App;
use Input;

class RandomController extends Controller 
{
/*
  //
  // Backtest Futures - CL
  //
  public function bt_cl()
  {
    $bt = App::make('App\Backtesting\FuturesCL1Min');
    
    if(Input::get('start_date'))
    {
      $start_date = Input::get('start_date');
    } else
    {
      $start_date = '2016-01-01';
    }
  
    if(Input::get('end_date'))
    {
      $end_date = Input::get('end_date');
    } else
    {
      $end_date = '2016-12-31';
    }
  
    if(Input::get('cash'))
    {
      $cash = Input::get('cash');
    } else
    {
      $cash = 3000;
    }
    
    $bt->run([
      'start_date' => $start_date,
      'end_date' => $end_date,
      'cash' => $cash
    ]);
    
    return $bt->return_html();
  }
*/
}

/*
Route::get('bt', function () {
  
  $bt = new App\Backtesting\PutCreditSpreads;
  
  $symbol = (Input::get('symbol')) ? Input::get('symbol') : 'spy';
  $start = (Input::get('start')) ? Input::get('start') : '2011-01-01';
  $end = (Input::get('end')) ? Input::get('end') : '2015-12-31';
  
  $bt->run([
    'symbol' => $symbol,
    'cash' => 10000,
    'start_date' => $start,
    'end_date' => $end,
    'signals' => [
      
      'buy' => [
  			'symbol' => 'spy',
				'type' => 'precent-away',
				'value' => 4,
				'action' => 'credit-spread',
				'spread_width' => 2,
				'min_credit' => 0.18,	
				'max_days_to_expire' => 45,
				'min_days_to_expire' => 0
		  ]		
		  
    ]
  ]);
  
  return $bt->return_html();
  
});


Route::get('boxspreads-show', function () {
  
  $log_file = storage_path('logs/boxspreads.json');
  
  if(file_exists($log_file))
  {
    $log = json_decode(file_get_contents($log_file), true);
  } else
  {    
    $log = [];
  }  
  
  echo "<table width=\"70%\">";
  
  echo "<tr>
    <th>Timestamp</th>
    <th>Trade</th>
    <th>Last</th>
    <th>Width</th>
    <th>Cost</th>
    <th>Diff</th>
    <th>Box</th>            
  </tr>";
  
  foreach($log AS $key => $row)
  {
    $yes_no = ($row['Diff'] > 0) ? 'Yes' : 'No';
    
    if($yes_no == 'No')
    {
      continue;
    }
    
    $color = ($yes_no == 'Yes') ? ' color: green; ' : '';
    
    echo "<tr>
      <td style=\"text-align: center;" . $color . "\">" . $row['Timestamp'] . "</td>
      <td style=\"text-align: center;" . $color . "\">" . $row['Trade'] . "</td>
      <td style=\"text-align: center;" . $color . "\">" . $row['Last'] . "</td>
      <td style=\"text-align: center;" . $color . "\">" . $row['Width'] . "</td>
      <td style=\"text-align: center;" . $color . "\">" . $row['Cost'] . "</td>
      <td style=\"text-align: center;" . $color . "\">" . $row['Diff'] . "</td>
      <td style=\"text-align: center;" . $color . "\">" . $yes_no . "</td>             
    </tr>";    
  }
  
  echo "</table>";
  
  return '';
});
*/

//Route::get('tradier', function () {


/*
    // Stock example
    $order = [
      'class' => 'equity',
      'symbol' => 'AAPL',
      'duration' => 'day',
      'side' => 'buy',
      'quantity' => '100',
      'type' => 'limit',
      'preview' => 'true',
      'price' => '80.00'
    ];
*/
    
/*
    // Credit spread example.
    $order = [
      'class' => 'multileg',
      'symbol' => 'SPY',
      'duration' => 'day',
      'side' => 'buy',
      'type' => 'credit',
      'preview' => 'false',
      'price' => '0.50',
      'side' => [
        'buy_to_open',
        'sell_to_open'
      ],
      'option_symbol' => [
        'SPY150717P00193000',
        'SPY150717P00195000'
      ],
      'quantity' => [ '2', '2' ]
    ];
*/      	

/*
  $t = [];
    
  $tradier = App::make('App\Library\Tradier');
  $tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken));
  //$tradier->set_sandbox();
  
  //$data = $tradier->get_quotes([ 'xlf', 'spy' ]);
  //$data = $tradier->get_timesales('spy', '1min', null, null, 'all');
  //$data1 = $tradier->get_timesales('ivv', '1min', null, null, 'all');   
  //$data = $tradier->get_historical_pricing('spy', 'monthly', null, null);
  //$data = $tradier->get_option_chain('spy', '2015-06-12');
  //$data = $tradier->get_user_balances();
  //$data = $tradier->get_streaming_session();
  //$data = $tradier->get_intraday_status();
  //$data = $tradier->get_option_expiration_dates('spy');
  //$data = $tradier->get_option_strike_prices('spy', '2015-06-19');
  //$data = $tradier->get_account_positions(Auth::user()->UsersTradierAccountId);
  //$data = $tradier->get_account_positions(Auth::user()->UsersTradierAccountId, true);  
  //$data = $tradier->get_account_orders(Auth::user()->UsersTradierAccountId);
  //$data = $tradier->get_account_orders(Auth::user()->UsersTradierAccountId, true);
  //$data = $tradier->get_account_order(Auth::user()->UsersTradierAccountId, 44016);
  //$data = $tradier->get_account_history(Auth::user()->UsersTradierAccountId);
  //$data = $tradier->get_account_balances(Auth::user()->UsersTradierAccountId); 
  //$data = $tradier->get_account_positions_group_by_type(Auth::user()->UsersTradierAccountId);
  //$data = $tradier->place_order(Auth::user()->UsersTradierAccountId, $order);   


  //echo '<pre>' . print_r($data, TRUE) . '</pre>';

  //Cloudmanic\LaravelApi\Me::set_account([ 'AccountsId' => 1 ]);


/*
  //echo $tradier->get_last_error();
  
  foreach($data AS $key => $row)
  {
    $t[$row['time']] = [ $row['time'], $row['close'], $data1[$key]['close'] ];
  }

download_send_headers("data_export_" . date("Y-m-d") . ".csv");
echo array2csv($t);
die();

  echo '<pre>' . print_r($t, TRUE) . '</pre>';
*/
  
  
  
/*
  echo '<pre>' . print_r($syms, TRUE) . '</pre>';
  
  $quotes = $tradier->get_quotes($syms);
  
  echo '<pre>' . print_r($quotes, TRUE) . '</pre>';
  
  
  echo '<pre>' . print_r($data, TRUE) . '</pre>';

  
  return 'success';
});

*/

/* End File */