<?php

namespace App\Models;

use DB;
use App;
use Queue;
use Cloudmanic\LaravelApi\Me;

class Assets extends \Cloudmanic\LaravelApi\Model
{
  //
  // Update
  //
  public function update($data, $id)
  {
    // Set Date.
    if(! isset($data['AssetsLastMark']))
    {
      $data['AssetsLastMark'] = date('Y-m-d');
    }
    
    $rt = parent::update($data, $id);  
    
    // Mark the entire port.
    if(isset($data['AssetsValue']))
    {
      $ass = $this->get_by_id($id);
      $AssetMarks = App::make('App\Models\AssetMarks');
      
      // Delete any marks for today (one mark per day).
      $AssetMarks->set_col('AssetMarksAssetId', $ass['AssetsId']);
      $AssetMarks->set_col('AssetMarkDate', date('Y-m-d'));
      $AssetMarks->delete_all();
      
      // Insert mark
      $AssetMarks->insert([
        'AssetMarksAssetId' => $ass['AssetsId'],
        'AssetMarksValue' => $ass['AssetsValue'],
        'AssetMarkDate' => date('Y-m-d')
      ]);
      
      // Update total marks.
      $Marks = App::make('App\Models\Marks');
      $Marks->mark();
    }
    
    // Tell websockets this happened
    $data = $this->get_by_id($id);
    $data['UsersId'] = Me::get_account_id();
    Queue::pushOn('stockpeer.com.websocket', 'Assets:update', $data);
    
    return $rt;      
  }
  
  //
  // Format get.
  //
  public function _format_get(&$data)
  {
    // Date format
    if(isset($data['AssetsLastMark']))
    {
      $data['AssetsLastMark_df1'] = date('n/j/Y', strtotime($data['AssetsLastMark']));
      $data['AssetsLastMark_df2'] = date('n/j/y', strtotime($data['AssetsLastMark']));      
    }   
  }

}

/* End File */