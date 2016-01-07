<?php

namespace App\Http\Controllers\Api\V1;

use DB;
use App;
use Input;
use Request;

class Expenses extends \Cloudmanic\LaravelApi\Controller 
{ 
	public $validation_create = [];
	public $validation_update = [];
	
  //
  // Return categories.
  //
  public function get_categories()
  {
    $d = DB::select("SHOW COLUMNS FROM Expenses WHERE Field = 'ExpensesCategory'");
    
    // Get the categories
    $type = $d[0]->Type;
    preg_match("/^enum\(\'(.*)\'\)$/", $type, $matches);
    $enums = explode("','", $matches[1]);    
    sort($enums);
    
    // Return happy.
    return $this->api_response($enums);
  }
  
  //
  // Return vendors.
  //
  public function get_vendors()
  {
    $d = DB::select("SHOW COLUMNS FROM Expenses WHERE Field = 'ExpensesVendor'");
    
    // Get the categories
    $type = $d[0]->Type;
    preg_match("/^enum\(\'(.*)\'\)$/", $type, $matches);
    $enums = explode("','", $matches[1]);    
    sort($enums);
    
    // Return happy.
    return $this->api_response($enums);
  }  
}

/* End File */