<?php

namespace App\Models;

use DB;
use Auth;
use App;
use Queue;
use Cloudmanic\LaravelApi\Me;

class Activity extends \Cloudmanic\LaravelApi\Model
{
  //
  // Insert a new activity. Just Insert with more options.
  //
  public function record($type, $t_id, $msg, $push = false)
  {
    // Insert into the db.
    $id = $this->insert([
      'ActivityType' => trim($type),
      'ActivityTypeId' => $t_id,
      'ActivityText' => trim($msg)     
    ]);
    
    // Do we pust this activity out?
    if($push)
    {
      $this->google_send_push_notifications();
      $this->apple_send_push_notifications(trim($type), trim($msg));
    }
  }
  
  //
  // Google Send push notifications.
  //
  public function google_send_push_notifications()
  {
    $devices = [];
    
    $devs = DB::table('UserToDevice')
              ->where('UserToDeviceType', 'GCM Browser')
              ->where('UserToDeviceAccountId', Me::get_account_id())
              ->orderBy('UserToDeviceId', 'asc')
              ->get();
    
    foreach($devs AS $key => $row)
    {
      $devices[] = str_ireplace('https://android.googleapis.com/gcm/send/', '', $row->UserToDeviceGcmEndPoint);
    }
    
    // Maybe some day the message will not be ignored - 
    // http://stackoverflow.com/questions/30335749/sending-data-payload-to-the-google-chrome-push-notification-with-javascript
    if(count($devices) > 0)
    {
      $gcpm = new App\Library\GCMPushMessage(env('GOOGLE_SERVER_KEY'));
      $gcpm->setDevices($devices);
      $response = $gcpm->send('Message is ignored', []); 
      
      $msg = json_decode($response, true);
    }
  }
  
  //
  // Send notifications to apple.
  //
  public function apple_send_push_notifications($type, $msg)
  {
    $push = App::make('App\Library\ApplePush');
    
    $devs = DB::table('UserToDevice')
              ->where('UserToDeviceType', 'Apple Push')
              ->where('UserToDeviceAccountId', Me::get_account_id())
              ->orderBy('UserToDeviceId', 'asc')
              ->get();
    
    foreach($devs AS $key => $row)
    {
      $push->send($row->UserToDeviceAppleToken, 'Stockpeer.com - ' . $type, $msg, 'View');
    }
     
    // Return happy.
    return true;
  }
}

/* End File */