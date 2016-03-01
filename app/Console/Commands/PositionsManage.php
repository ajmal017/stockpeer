<?php 

namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Crypt;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PositionsManage extends Command 
{
	protected $name = 'stockpeer:managepositions';
	protected $description = 'Check to see if any of our positions need updating in our records.';

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
    $this->info('[' . date('n-j-Y g:i:s a') . '] Starting manage positions.');
    
    // Setup models    
    $orders_model = App::make('App\Models\Orders'); 
    $symbols_model = App::make('App\Models\Symbols');    
    $positions_model = App::make('App\Models\Positions');
    $tradegroups_model = App::make('App\Models\TradeGroups');    
    
    // Loop through the accounts of all users.
    $users = DB::table('Users')->where('UsersTradierToken', '!=', '')->get();
    
    foreach($users AS $user)
    {
      // Log user in.
      Auth::loginUsingId($user->UsersId);
      
      // Update our orders database first.
      $orders_model->log_orders_from_tradier();
      
      // Get positions
      if($data = $this->_tradier->get_account_positions(Auth::user()->UsersTradierAccountId, true))
      {
        // Log positions
        $this->_log_positions($data, $user);
        
        // Loop through the positions.
        foreach($data AS $key => $row)
        {
          // First we check if we have this position in our records
          if(! $pos = $positions_model->get_open_by_symbol($row['symbol']))
          {
            // TODO: check to see if we need to add positions.
            continue;
          }
          
          // See if we have any options expiring worthless today.
          $this->_close_expired_options($row, $pos); 
        }
      
        // See if we have any positions that have closed.
        $this->_close_positions($data);      
      }
      
/*
      // Loop through our orders and see if any of them filled (as in closed).
      $orders_model->manage_postions_from_orders();
*/
    }

    $this->info('[' . date('n-j-Y g:i:s a') . '] Ending manage positions.');  
    
	}
	
	// ------------------- Private Helper Functions ------------ //
	
	//
	// Close Positions
	//
	private function _close_positions($positions)
	{
  	$db_ids = [];  	
  	$broker_ids = [];
  	$positions_model = App::make('App\Models\Positions');
  	
  	// Get a list of positions that are currently open.
    foreach($positions AS $key => $row)
    {
      $broker_ids[] = (int) $row['id'];
    }
  	
    // Get positions that are curently open.
    $positions_model->set_col('PositionsStatus', 'Open');
    foreach($positions_model->get() AS $key => $row)
    {
      $db_ids[] = (int) $row['PositionsBrokerId'];
    }
    
    echo '<pre>' . print_r($db_ids, TRUE) . '</pre>';	
    
    echo '<pre>' . print_r($broker_ids, TRUE) . '</pre>';
    
    // Figure out what ids are not currently in the database.
    $diff_ids = array_diff($db_ids, $broker_ids);	   
    
    echo '<pre>' . print_r($diff_ids, TRUE) . '</pre>'; 
	}
	
  //
  // Log positions
  //
  private function _log_positions($data, $user)
  {   
    $trade_group_id = null;
    $positions_model = App::make('App\Models\Positions');
        
    // Loop through the positions and log them.
    foreach($data AS $key => $row)
    { 
      // See if we are updating or if this is a new position.
      if(! $positions_model->update_position($row))
      {
        // Add the new position to the database.
        if($t = $positions_model->add_new_position($row, $trade_group_id))
        {
          $trade_group_id = $t;
        }
      }
    }      
  }	
	
	//
	// Close expired options
	//
	private function _close_expired_options($row, $pos)
	{    
    // (we only check this after the market closes)
    if(($pos['SymbolsType'] == 'Option') && 
        (strtotime($pos['SymbolsExpire'] . ' 13:05:00') <= strtotime('now')))
    {
      // Setup models      
      $positions_model = App::make('App\Models\Positions');
      $tradegroups_model = App::make('App\Models\TradeGroups');       
      
      // Make sure it expired worthless - Put
      if(($pos['SymbolsOptionType'] == 'Put') && ($row['quote']['bid'] <= 0.03))
      {
        // Close position.
        $positions_model->update([ 
          'PositionsStatus' => 'Closed',
          'PositionsClosed' => date('Y-m-d G:i:s'),
          'PositionsClosePrice' => 0,
          'PositionsQty' => 0,
          'PositionsNote' => 'Expired worthless.' 
        ], $pos['PositionsId']);
      
        // Close Trade Group
        $tradegroups_model->update([
          'TradeGroupsStatus' => 'Closed',
          'TradeGroupsEnd' => date('Y-m-d G:i:s'),
          'TradeGroupsNote' => 'Expired worthless.'
        ], $pos['PositionsTradeGroupId']);
      }
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
