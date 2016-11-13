<?php

use Cloudmanic\Craft2Laravel\Craft2Laravel;
use Dropbox\Client;
use Cloudmanic\LaravelApi\Me;
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
	Route::get('blog/{slug}', 'BlogController@single_slug');
	Route::get('blog/{id}/{slug}', 'BlogController@single');		
	
  // backtests
  Route::get('backtest', 'BacktestsController@index');
  Route::get('backtests/get/{id}', 'BacktestsController@get');  
  Route::get('backtests/get_trades/{id}', 'BacktestsController@get_trades');
  Route::get('backtests/status/{id}', 'BacktestsController@status');
  Route::get('backtests/option-spreads/{hash}', 'BacktestsController@options_spreads');
  Route::post('backtests/run', 'BacktestsController@run');
  Route::post('backtests/setup_backtest', 'BacktestsController@setup_backtest');
	
	// options-broker-picker
	Route::get('options-broker-picker', 'BrokerPickerController@options');	  
	
	// api/v1/newsletter
	Route::post('api/v1/newsletter/create', 'Api\V1\Newsletter@create');	
});

// Special routes for my AH friends.
Route::get('ah/ivr', 'AhController@ivr');

// Apple Safari stuff.
Route::post('v1/log', 'AppleController@collect_logs');
Route::post('v1/pushPackages/web.cloudmanic.stockpeer', 'AppleController@push_package');
Route::post('v1/devices/{device}/registrations/web.cloudmanic.stockpeer', 'AppleController@store_device');

// Random routes
//Route::get('random/bt_cl', 'RandomController@bt_cl');
  
/* End File */