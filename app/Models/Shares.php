<?php

namespace App\Models;
 
use DB;
use App;
use Cloudmanic\LaravelApi\Me;

class Shares extends \Cloudmanic\LaravelApi\Model
{
  //
  // Insert.
  //
  public function insert($data)
  {
		// Figure out the cost per share. 
		$count = DB::table('Shares')->where('SharesAccountId', Me::get_account_id())->sum('SharesCount');
		$total = DB::table('Assets')->where('AssetsAccountId', Me::get_account_id())->sum('AssetsValue');			
		$price_per = $total / $count;    
    
    // Clean things up.
    if(isset($data['SharesDate']))
    {
      $data['SharesDate'] = date('Y-m-d', strtotime($data['SharesDate']));
    }
    
    // Figure out count.
    if(isset($data['SharesPrice']))
    {
      $data['SharesCount'] = floor($data['SharesPrice'] / $price_per);
      
      if($data['SharesPrice'] < 0)
      {
        $data['SharesCount'] = $data['SharesCount'] * -1;
      }
    }
    
    return parent::insert($data);
  }

}

/* End File */