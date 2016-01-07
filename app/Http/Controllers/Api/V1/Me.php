<?php

namespace App\Http\Controllers\Api\V1;

use DB;
use App;
use Auth;
use Input;
use Request;
use App\Autotrading\PutCreditSpread;

class Me extends \Cloudmanic\LaravelApi\Controller 
{ 
	public $validation_create = [];
	public $validation_update = [];	

  //
  // Return me.
  //
  public function get()
  {
    $user = (array) DB::table('Users')
            ->select('UsersId', 'UsersFirst', 'UsersLast', 'UsersEmail', 'UsersDefaultPutCreditSpreadCloseCredit', 'UsersDefaultPutCreditSpreadLots')
            ->where('UsersId', Auth::user()->UsersId)
            ->first();
    return $this->api_response($user);
  }

  //
  // Just ping to make sure we are still here.
  //
  public function ping()
  {
    return $this->api_response();
  }

  //
  // Return the watch list.
  //
  public function get_watchlist()
  {
    $user = (array) DB::table('Users')->select('UsersWatchList')->where('UsersId', Auth::user()->UsersId)->first(); 
    
    $wl = json_decode($user['UsersWatchList'], true);
    
    return $this->api_response($wl);
  }

  //
  // Create a temp key for websockets.
  //
  public function get_websocket_key()
  {
    $key = str_random(50);
    
    // Update the database with this random string.
    DB::table('Users')->where('UsersId', Auth::user()->UsersId)->update([ 'UsersWebSocketKey' => $key ]);
    
    return $this->api_response([ 'key' => $key ]);
  }
}

/* End File */