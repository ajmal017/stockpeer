<?php 
namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Crypt;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MarkAssets extends Command 
{
	protected $name = 'stockpeer:markassets';
	protected $description = 'Make API calls to mark assets.';

  //
  // Create a new command instance.
  // 
	public function __construct()
	{
		parent::__construct();
	}

  //
  // Execute the console command.
  //
	public function fire()
	{
    $this->info('Starting marking of assets');
    
    // Get the assets that need to be marked
    $assets = DB::table('Assets')->where('AssetsAutoUpdate', 'Yes')->get();
    
    foreach($assets AS $key => $row)
    {
      // Log user in.
      Auth::loginUsingId($row->AssetsAccountId);
      
      // Call a function based on the type.
      switch($row->AssetsBroker)
      {
        case 'Tradier':
          $this->_process_tradier($row);
        break;
        
/*
        case 'Robinhood':
          $this->_process_robinhood($row);
        break;
*/
        
        case 'Coinbase':
          $this->_process_coinbase($row);
        break;                
      }
      
      // Logout
      Auth::logout();
    }
    
    $this->info('Done marking of assets');    
	}

	//
	// Process Coinbase Account.
	//
	private function _process_coinbase($row)
	{
    // Set the assets model
    $Assets_Model = App::make('App\Models\Assets');
    
    // Make API call to Coinbase  	
    list($key, $secret) = explode('::', Crypt::decrypt($row->AssetsAccountToken));
    $configuration = Configuration::apiKey($key, $secret);
    $coinbase = Client::create($configuration);
    $balance = $coinbase->getAccounts();
    
    // Update database.
    if($balance->all()[0]->getNativeBalance()->getAmount())
    {
       $Assets_Model->update([ 'AssetsValue' => $balance->all()[0]->getNativeBalance()->getAmount()], $row->AssetsId);
    }
	}
	
	//
	// Process Robinhood Account.
	//
	private function _process_robinhood($row)
	{
    // Set the assets model
    $Assets_Model = App::make('App\Models\Assets');
    
    // Make API call to Robinhood  	
    $token = \Cloudmanic\RobinHood\Api::set_token(Crypt::decrypt($row->AssetsAccountToken));
    $acts = \Cloudmanic\RobinHood\Api::get_accounts();
    $act = \Cloudmanic\RobinHood\Api::get_account($acts['results'][0]['url']);
    $port = \Cloudmanic\RobinHood\Api::get_portfolio_summery($acts['results'][0]['portfolio']);
    
    if(isset($port['last_core_equity']))
    {
      $Assets_Model->update([ 'AssetsValue' => $port['last_core_equity'] ], $row->AssetsId);
    }
	}
	
	//
	// Process the Trader Account.
	//
  private function _process_tradier($row)
  {    
    // Set the assets model
    $Assets_Model = App::make('App\Models\Assets');
    
    // Get data from Tradier's API.
    $tradier = App::make('App\Library\Tradier');
    $tradier->set_token(Crypt::decrypt($row->AssetsAccountToken));
    $data = $tradier->get_account_balances(Auth::user()->UsersTradierAccountId);
    
    if(isset($data['total_equity']))
    {
      $Assets_Model->update([ 'AssetsValue' => $data['total_equity'] ], $row->AssetsId);
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
