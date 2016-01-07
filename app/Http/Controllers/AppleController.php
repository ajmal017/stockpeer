<?php

namespace App\Http\Controllers;

use DB;
use App;

class AppleController extends Controller 
{
  //
  // Deliver push package
  //
  public function push_package()
  {
    // Get id.
    $body = file_get_contents("php://input");
    $json = json_decode($body, true);
    $push = App::make('App\Library\ApplePush');
    $push->auth_token = $json['UsersId'];
    $path = $push->create_push_package();
    header("Content-type: application/zip");
    echo file_get_contents($path);
    die;   
  }
  
  //
  // Add a device to our db.
  //
  public function store_device($device)
  {
    // We need a header to continue;    
    if(! isset($_SERVER['HTTP_AUTHORIZATION']))
    {
      return 'failed';
    }
    
    // Get the key from the header.
    $parts = explode('_', $_SERVER['HTTP_AUTHORIZATION']);
    
    // Clear any old entries
    DB::table('UserToDevice')->where('UserToDeviceAppleToken', trim($device))->delete();
    
    // Add entry.
    DB::table('UserToDevice')->insert([
      'UserToDeviceAccountId' => trim($parts[1]),
      'UserToDeviceType' => 'Apple Push', 
      'UserToDeviceAppleToken' => trim($device),
      'UserToDeviceUpdatedAt' => date('Y-m-d H:i:s'),
      'UserToDeviceCreatedAt' => date('Y-m-d H:i:s')
    ]);
    
    return 'success';
  }
  
  //
  // Collect logs from push.
  //
  public function collect_logs()
  {
    $body = file_get_contents("php://input");
    $r = @file_get_contents('/tmp/apple.push.log');
    file_put_contents('/tmp/apple.push.log', $r . $body);
    return 'success';     
  }
}

/* End File */