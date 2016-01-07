<?php

namespace App\Models;
 
use DB;
use App;
use Queue;
use Cloudmanic\LaravelApi\Me;

class UserToDevice extends \Cloudmanic\LaravelApi\Model
{
  public $joins = [];
  
  //
  // Insert.
  //
  public function insert($data)
  {    
    // Duplicates are bad - Check UserToDeviceGcmEndPoint
    if(isset($data['UserToDeviceGcmEndPoint']))
    {
      $this->set_col('UserToDeviceGcmEndPoint', $data['UserToDeviceGcmEndPoint']);
      
      if($d = $this->get())
      {
        return $d[0]['UserToDeviceId']; 
      }
    }
    
    return parent::insert($data);
  }  
}

/* End File */