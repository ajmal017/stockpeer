<?php

namespace App\Models;
 
use DB;
use App;
use Cloudmanic\LaravelApi\Me;

class Marks extends \Cloudmanic\LaravelApi\Model
{
  //
  // Mark Port.
  //
  public function mark()
  {
    $total = DB::table('Assets')->where('AssetsAccountId', Me::get_account_id())->sum('AssetsValue');
    return $this->insert([ 'MarksValue' => $total, 'MarksDate' => date('Y-m-d') ]);
  }
  
  //
  // Insert.
  //
  public function insert($data)
  {
    // Mark the entire port.
    if(isset($data['MarksValue']) && isset($data['MarksDate']))
    {
      // Delete any marks for today (one mark per day).
      $this->set_col('MarksDate', $data['MarksDate']);
      $this->delete_all();
    }

    // Figure out the current total number of shares.
    $data['MarksShares'] = DB::table('Shares')->where('SharesAccountId', Me::get_account_id())->sum('SharesCount');

    return parent::insert($data);
  }  
  
  //
  // Format get.
  //
  public function _format_get(&$data)
  {
    // Number format
    if(isset($data['MarksValue']))
    {
      $data['MarksValue_df1'] = number_format($data['MarksValue'], 2, '.', ',');
    }
    
    // Share format
    if(isset($data['MarksShares']))
    {
      $data['MarksShares_df1'] = number_format($data['MarksShares'], 0, '.', ',');
    }
    
    // Date format
    if(isset($data['MarksDate']))
    {
      $data['MarksDate_df1'] = date('n/j/Y', strtotime($data['MarksDate']));
    } 
    
    // Figure out price per share.
    if(isset($data['MarksValue']) && isset($data['MarksShares']))
    {
      $data['PricePerShare'] = number_format($data['MarksValue'] / $data['MarksShares'], 3, '.', ',');
    }       
  }
}

/* End File */