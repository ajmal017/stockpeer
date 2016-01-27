<?php

use Dropbox\Client;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;


/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

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

Route::get('tradier', function () {


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
*/
  
  return 'success';
});

// API V1 (NO Authed)
Route::group([ 'prefix' => 'api/v1', 'middleware' => [ 'force.ssl' ] ], function() {

  // api/v1/optionseod
  Route::get('optionseod', 'Api\V1\OptionsEod@get');	  

  // api/v1/activity
  Route::get('activity/gcm_fetch', 'Api\V1\Activity@gcm_fetch');	
   		
});

// API V1 (Authed)
Route::group([ 'prefix' => 'api/v1', 'middleware' => [ 'force.ssl', 'auth' ] ], function() {

  // api/v1/activity
  Route::get('activity', 'Api\V1\Activity@get');

  // api/v1/me
  Route::get('me', 'Api\V1\Me@get');
  Route::get('me/ping', 'Api\V1\Me@ping');
  Route::get('me/get_watchlist', 'Api\V1\Me@get_watchlist');
  Route::post('me/update_settings', 'Api\V1\Me@update_settings');
  Route::post('me/get_websocket_key', 'Api\V1\Me@get_websocket_key');
  
  // api/v1/positions
	Route::get('positions', 'Api\V1\Positions@get');
	Route::get('positions/get_by_types', 'Api\V1\Positions@get_by_types');

	// api/v1/autotrade
	Route::get('autotrade/spy_percent_away', 'Api\V1\Autotrade@spy_percent_away');
	Route::get('autotrade/spy_weekly_percent_away', 'Api\V1\Autotrade@spy_weekly_percent_away');
	
  // api/v1/quotes
  Route::get('quotes/get_account_quotes', 'Api\V1\Quotes@get_account_quotes');
  Route::get('quotes/get_snp_500_rank/{days}', 'Api\V1\Quotes@get_snp_500_rank');
  Route::get('quotes/timesales', 'Api\V1\Quotes@timesales');		
	
	// api/v1/eodquote
	Route::get('eodquote/p_l_rsi_based', 'Api\V1\EodQuote@p_l_rsi_based');	
	Route::get('eodquote/stock_movement_ranges', 'Api\V1\EodQuote@stock_movement_ranges');	
	
  // api/v1/assets
  Route::get('assets', 'Api\V1\Assets@get');	
  Route::post('assets/update/{id}', 'Api\V1\Assets@update');	  
  
  // api/v1/marks
  Route::get('marks', 'Api\V1\Marks@get');	

  // api/v1/TradierHistory
  Route::get('tradierhistory', 'Api\V1\TradierHistory@get');	

  // api/v1/income
  Route::get('income', 'Api\V1\Income@get');	

  // api/v1/expenses
  Route::get('expenses', 'Api\V1\Expenses@get');
  Route::get('expenses/get_vendors', 'Api\V1\Expenses@get_vendors');	
  Route::get('expenses/get_categories', 'Api\V1\Expenses@get_categories');
  Route::post('expenses/create', 'Api\V1\Expenses@create');	

  // api/v1/reports
  Route::get('reports/income_statement', 'Api\V1\Reports@income_statement');	

  // api/v1/shares
  Route::get('shares', 'Api\V1\Shares@get');
  Route::post('shares/create', 'Api\V1\Shares@create');  

  // api/v1/backtest
  Route::get('backtests', 'Api\V1\Backtests@get');	
  Route::get('backtests/{id}', 'Api\V1\Backtests@id');	  
  Route::post('backtests/run', 'Api\V1\Backtests@run');	
  Route::post('backtests/setup_backtest', 'Api\V1\Backtests@setup_backtest');	  
  
  // api/v1/blogtrades
  Route::any('blogtrades/insert_by_tradeid', 'Api\V1\BlogTrades@insert_by_tradeid');
  
  // api/v1/trades
  Route::get('trades', 'Api\V1\Trades@get');
  Route::get('trades/pl_by_year/{year}', 'Api\V1\Trades@pl_by_year'); 
  Route::post('trades', 'Api\V1\Trades@create');
  Route::post('trades/update/{id}', 'Api\V1\Trades@update');
  Route::post('trades/preview_trade', 'Api\V1\Trades@preview_trade');
  
  // api/v1/tradegroups
  Route::get('tradegroups', 'Api\V1\TradeGroups@get');  
  
  // api/v1/orders
  Route::get('orders', 'Api\V1\Orders@get'); 
  Route::get('orders/get_open', 'Api\V1\Orders@get_open');
  
  // api/v1/usertodevice   
  Route::post('usertodevice/create', 'Api\V1\UserToDevice@create');    		
});

// Application (Authed)
Route::group([ 'middleware' => [ 'force.ssl', 'auth' ] ], function() {

  // Service workers
  Route::get('service-worker', 'AppController@service_worker');

	// a/dashboard
	Route::get('a', 'AppController@template');
	Route::get('a/dashboard', 'AppController@template');

	// a/screener
	Route::get('a/screener/credit-spreads', 'AppController@template');

	// a/accounting
	Route::get('a/accounting/assets', 'AppController@template');
	Route::get('a/accounting/income', 'AppController@template');	
	Route::get('a/accounting/expenses', 'AppController@template');
	Route::get('a/accounting/expenses/add', 'AppController@template');
	Route::get('a/accounting/shares', 'AppController@template');	
	Route::get('a/accounting/shares/add', 'AppController@template');
	Route::get('a/accounting/shares/remove', 'AppController@template');

	// a/reports
	Route::get('a/reports/orders', 'AppController@template');
	Route::get('a/reports/activity', 'AppController@template');
	Route::get('a/reports/performance', 'AppController@template');
	Route::get('a/reports/income-statement', 'AppController@template');
	Route::get('a/reports/tradier-history', 'AppController@template');
	
	// a/backtest
	Route::get('a/backtest/option-spreads', 'AppController@template');
	Route::get('/a/backtest/option-spreads/{id}', 'AppController@template');	
	
	// a/trades
	Route::get('a/trades', 'AppController@template');	
	
	// a/trade-groups
	Route::get('a/trade-groups', 'AppController@template');		

	// a/settings
	Route::get('a/settings', 'AppController@template');	
		
});

// Public site.
Route::group([ 'middleware' => 'force.ssl' ], function() {
	
	// Index
	Route::get('/', 'BlogController@index');
	
	// Login
	Route::any('login', 'AuthController@login');	
	Route::any('logout', 'AuthController@logout');		
	
	// Pages
	Route::get('about', 'PagesController@about');
	
	// /blog
	Route::get('blog', 'BlogController@index');	
	Route::get('blog/rss', 'BlogController@rss');
	Route::get('blog/{id}/{slug}', 'BlogController@single');		
	
  // backtests/option-spreads/{hash}
  Route::get('backtests/option-spreads/{hash}', 'BacktestsController@options_spreads');
	
	// options-broker-picker
	Route::get('options-broker-picker', 'BrokerPickerController@options');	
	
	// api/v1/newsletter
	Route::post('api/v1/newsletter/create', 'Api\V1\Newsletter@create');	
	
	// a/backtester
	Route::get('a/backtester', 'AppController@template');	
});

// Apple Safari stuff.
Route::post('v1/log', 'AppleController@collect_logs');
Route::post('v1/pushPackages/web.cloudmanic.stockpeer', 'AppleController@push_package');
Route::post('v1/devices/{device}/registrations/web.cloudmanic.stockpeer', 'AppleController@store_device');
  
/* End File */