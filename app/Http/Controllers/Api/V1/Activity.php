<?php

namespace App\Http\Controllers\Api\V1;

use DB;
use Auth;
use URL;
use App;
use Input;
use Crypt;
use Request;

class Activity extends \Cloudmanic\LaravelApi\Controller 
{ 
	public $validation_create = [];
	public $validation_update = [];
	
  //
  // Get Next GCM message to push to app.
  //
  public function gcm_fetch()
  {
    // Must have a referer
    if(! $UserId = Input::get('UsersId'))
    {
      return [ 'error' => 'Bad Request' ];
    }
    
    // Get the latest activy for this user.
    $act = (array) DB::table('Activity')
                    ->where('ActivityAccountId', Input::get('UsersId'))
                    ->orderBy('ActivityId', 'desc')
                    ->first();
    if(! $act)
    {
      $rt = [ 
        'UsersId' => $UserId,
        
        'notification' => [
          'tag' => 'error',
          'title' => 'Push Error',
          'icon' => '/assets/img/logo-white-background-192x192.jpg',
          'message' => 'There as been an error with our push notification system.' 
        ]
      ];      
    } else
    {
      $tag = str_ireplace(' ', '-', strtolower($act['ActivityType']));
      
      $rt = [ 
        'UsersId' => $UserId,
        
        'notification' => [
          'tag' => $tag,
          'title' => $act['ActivityType'],
          'icon' => '/assets/img/logo-white-background-192x192.jpg',
          'message' => $act['ActivityText'] 
        ]
      ];       
    }
        
    return $rt;    
  }
}

/* End File */