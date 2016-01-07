<?php

namespace App\Models;
 
use DB;
use App;
use Queue;
use Cloudmanic\LaravelApi\Me;

class Trades extends \Cloudmanic\LaravelApi\Model
{
	// 
	// Get completed trades by year.
	//
	public function get_completed_trades_by_year($year)
	{
		$this->set_col('TradesStatus', 'Closed');
		$this->set_col('TradesDateEnd', "$year-12-31", '<=');
		$this->set_col('TradesDateEnd', "$year-01-01", '>=');	
		return $this->get();		
	} 
	
	//
	// Insert.
	//
	public function insert($data)
	{
  	// Update date.
  	if(isset($data['TradesDateStart']))
  	{
      $data['TradesDateStart'] = date('Y-m-d', strtotime($data['TradesDateStart']));
  	}
  	
    $id = parent::insert($data);
    
    // Tell websockets this happened
    $data = $this->get_by_id($id);
    $data['UsersId'] = Me::get_account_id();
    Queue::pushOn('stockpeer.com.websocket', 'Trades:insert', $data);
    
    return $id;
	} 
	
	//
	// update.
	//
	public function update($data, $id)
	{  	
    parent::update($data, $id);
    
    // Tell websockets this happened
    $data = $this->get_by_id($id);
    $data['UsersId'] = Me::get_account_id();
    Queue::pushOn('stockpeer.com.websocket', 'Trades:update', $data);
    
    return true;
	} 	 
  
  //
  // Format Get.
  //
  public function _format_get(&$data)
  {    
    // Figure out a summery for this trade
    switch($data['TradesType'])
    {
      case 'Other':
        $data['Title'] = trim($data['TradesAsset']);
      break;
      
      case 'Put Credit Spread':
      case 'Weekly Put Credit':
        $data['TradesStartPrice'] = ($data['TradesSpreadWidth1'] * $data['TradesShares']) - ($data['TradesCredit'] * 100 * $data['TradesShares']); 
        $data['Title'] = $data['TradesStock'] . ' Put Credit Spread ' . $data['TradesLongLeg1'] . ' / ' . $data['TradesShortLeg1'] . ' ' . date('n/j/Y', strtotime($data['TradeExpiration'])) . ' @ ' . $data['TradesCredit'];
      break;
    }
    
    // Format start price
    $data['TradesStartPrice_df1'] = number_format($data['TradesStartPrice'], 2);

    // Format start price
    $data['TradesEndPrice_df1'] = number_format($data['TradesEndPrice'], 2);
    
 		// Set Profit
		if(isset($data['TradesEndPrice']) && ($data['TradesStatus'] == 'Closed'))
		{
  		$data['ProfitRaw'] = ($data['TradesEndPrice'] - $data['TradesStartPrice']) - ($data['TradesStartCommission'] + $data['TradesEndCommission']);
			$data['Profit'] = number_format(($data['TradesEndPrice'] - $data['TradesStartPrice']) - ($data['TradesStartCommission'] + $data['TradesEndCommission']), 2);
		} else
		{
  		$data['ProfitRaw'] = 0;
			$data['Profit'] = '---';
		}
		
		// Percent Gain
		if($data['Profit'] != '---')
		{
			$start = $data['TradesStartPrice'] + $data['TradesStartCommission'];
			$end = $data['TradesEndPrice'] - $data['TradesEndCommission'];
			$data['PercentGain'] = number_format((($end - $start) / $start) * 100, 2);
		}
  }

}

/* End File */