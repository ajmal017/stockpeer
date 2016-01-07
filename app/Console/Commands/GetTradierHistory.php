<?php 
namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Crypt;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GetTradierHistory extends Command 
{
  private $_tradier = null;  
	protected $name = 'stockpeer:gettradierhistory';
	protected $description = 'Make a Tradier API call and download all history.';

  //
  // Create a new command instance.
  // 
	public function __construct()
	{
		parent::__construct();
		
    // No DB logging.
    DB::connection()->disableQueryLog();
    
    // Log user in as spicer
    Auth::loginUsingId(1);
    
    // Setup Tradier
    $this->_tradier = App::make('App\Library\Tradier');
    $this->_tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken));  
	}

  //
  // Execute the console command.
  //
	public function fire()
	{
    $this->info('[' . date('n-j-Y g:i:s a') . '] Starting Import of Tradier history.');

    // Setup models    
    $tradierhistory_model = App::make('App\Models\TradierHistory');

    // Loop through the accounts of all users.
    $users = DB::table('Users')->where('UsersTradierToken', '!=', '')->get();
    
    foreach($users AS $user)
    {
      // Log user in.
      Auth::loginUsingId($user->UsersId);
      
      // Get activity
      $data = $this->_tradier->get_account_history(Auth::user()->UsersTradierAccountId);
      
      foreach($data AS $key => $row)
      {
        // We make up an Id
        $hash = md5(json_encode($row));
        
        // See if we have already inserted this activty.
        $tradierhistory_model->set_col('TradierHistoryHash', $hash);
        if($tradierhistory_model->get())
        {
          continue;
        }
        
        // Insert activity.
        $tradierhistory_model->insert([
          'TradierHistoryHash' => $hash,
          'TradierHistoryType' => ucwords(strtolower($row['type'])),
          'TradierHistoryAmount' => $row['amount'], 
          'TradierHistoryDate' => $row['date'],
          'TradierHistoryDetails' => json_encode($row[$row['type']])
        ]);
      }
  
      // Do the income.
      $this->_do_dividend();
      $this->_do_interest();
    }

    $this->info('[' . date('n-j-Y g:i:s a') . '] Ending Import of Tradier history.');    
	}
	
	//
	// Detect Interest Income.
	//
	public function _do_interest()
	{
    // Setup models
    $assets_model = App::make('App\Models\Assets');    
    $income_model = App::make('App\Models\Income');
    $symbols_model = App::make('App\Models\Symbols');
    $positions_model = App::make('App\Models\Positions');
    $tradierhistory_model = App::make('App\Models\TradierHistory');  	
  	
    // Get the Tradier asset
    $assets_model->set_col('AssetsName', 'Tradier');
    if(! $asset = $assets_model->first())
    {
      return false;
    } 
  	
    // Now loop through the history and see if we have any income to deal with.
    $tradierhistory_model->set_col('TradierHistoryRecorded', 'No');
    $tradierhistory_model->set_col('TradierHistoryType', 'Interest');
    $acts = $tradierhistory_model->get();
    
    foreach($acts AS $key => $row)
    { 
      // Insert into income table.
      $income_model->insert([
        'IncomeTradierAssetId' => $asset['AssetsId'],
        'IncomeDate' => $row['TradierHistoryDate'],
        'IncomeAmount' => $row['TradierHistoryAmount'],
        'IncomeType' => 'Interest',
        'IncomeTradierHistoryId' => $row['TradierHistoryId']
      ]);
      
      // Set this as recorded.
      $tradierhistory_model->update([ 'TradierHistoryRecorded' => 'Yes' ], $row['TradierHistoryId']);      
    }  	
	}
	
	//
	// Detect Dividend.
	//
	public function _do_dividend()
	{
    // Setup models  
    $assets_model = App::make('App\Models\Assets');       
    $income_model = App::make('App\Models\Income');
    $symbols_model = App::make('App\Models\Symbols');
    $positions_model = App::make('App\Models\Positions');
    $tradierhistory_model = App::make('App\Models\TradierHistory');  	
  	
    // Get the Tradier asset
    $assets_model->set_col('AssetsName', 'Tradier');
    if(! $asset = $assets_model->first())
    {
      return false;
    }   	
  	
    // Now loop through the history and see if we have any income to deal with.
    $tradierhistory_model->set_col('TradierHistoryRecorded', 'No');
    $tradierhistory_model->set_col('TradierHistoryType', 'Dividend');
    $acts = $tradierhistory_model->get();
    
    foreach($acts AS $key => $row)
    { 
      // Get the symbol id
      $name = ucwords(strtolower($row['Details']['description']));
      $symbols_model->set_or_col('SymbolsFull', $name);
      $symbols_model->set_or_col('SymbolsNameAlt1', $name);
      if(! $sym = $symbols_model->first())
      {
        $this->info($name . ' Not found.');
        continue;
      }
      
      // Find latest position 
      $positions_model->set_col('PositionsSymbolId', $sym['SymbolsId']);
      $positions_model->set_order('PositionsId', 'desc');
      if($pos = $positions_model->first())
      {
        $tg = $pos['PositionsTradeGroupId'];
      } else
      {
        $tg = 0;
      }
      
      // Insert into income table.
      $income_model->insert([
        'IncomeTradierAssetId' => $asset['AssetsId'],        
        'IncomeDate' => $row['TradierHistoryDate'],
        'IncomeAmount' => $row['TradierHistoryAmount'],
        'IncomeType' => 'Dividend',
        'IncomeTradeGroupId' => $tg,
        'IncomeSymbolsId' => $sym['SymbolsId'],
        'IncomeTradierHistoryId' => $row['TradierHistoryId']
      ]);
      
      // Set this as recorded.
      $tradierhistory_model->update([ 'TradierHistoryRecorded' => 'Yes' ], $row['TradierHistoryId']);
    }  	
	}
	
  //
  // Get the console command arguments.
  //
	protected function getArguments()
	{
		return [];
	}

	//
	// Get the console command options.
	//
	protected function getOptions()
	{
		return [];
	}
}

/* End File */
