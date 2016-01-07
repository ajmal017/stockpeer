<?php

namespace App\Models;

use \DB;
use \PDO;
use \Config;
use Carbon\Carbon;
use Cloudmanic\System\Libraries\Me;
use Cloudmanic\System\Libraries\Events;
use Cloudmanic\System\Models\Accounts\Users;
use Cloudmanic\System\Models\Accounts\Accounts;
use Cloudmanic\System\Models\Accounts\AcctUsersLu;
use Cloudmanic\System\Models\Accounts\Applications;

class FiveMinSpy extends \Cloudmanic\LaravelApi\Model
{	
	public $no_account = true;
	
	//
	// Return the most recent chain.
	//
	public function get_recent_chain()
	{
		$rt = [ 'last' => 0, 'vix' => 0, 'symbol' => 'spy', 'date' => '', 'call' => [], 'put' => [] ];
		
		// Get the most recent timestamp.
		$timestamp = $this->get_recent_timestamp();
		
		// Change connection mode so we get an array.
		DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
		
		// Now get all the data from this timestamp.
		$chain = DB::table('5MinSpy')->where('5MinSpyTimeStamp', $timestamp)->orderBy('5MinSpyStrike', 'asc')->get();
		
		foreach($chain AS $key => $row)		
		{
			$rt[$row['5MinSpyType']][] = $row;
		}		
		
		// Set connection mode back to default.
		DB::connection()->setFetchMode(Config::get('database.fetch'));
		
		// Set last.
		$rt['last'] = $rt['call'][0]['5MinSpyStockLast'];

		// Set date.
		$rt['date'] = date('Y-m-d', $rt['call'][0]['5MinSpyTimeStamp']);
		
		// TODO: add the vix value. I have to setup a feed to get the vix every 5 mins or something. 
		
		// Return chain.
		return $rt;
	}
	
	//
	// Each chain has its own timestamp. This will return the most recent timestamp.
	//
	public function get_recent_timestamp()
	{
		$data = DB::table('5MinSpy')->select('5MinSpyTimeStamp')->orderBy('5MinSpyTimeStamp', 'desc')->first();
		return (isset($data->{'5MinSpyTimeStamp'})) ? $data->{'5MinSpyTimeStamp'} : 0;
	}
}

/* End File */