<?php
  
namespace App\Http\Controllers;  

use Auth;
use View;
use Session;
use Response;

class AppController extends Controller 
{
	//
	// Template.
	//
	public function template()
	{
		return View::make('template.app');	
	}
	
  //
  // Service Worker
  //
  public function service_worker()
  {
    $contents = file_get_contents('./app/workers/service-worker.js');
    $contents = str_replace('{UsersId}', Auth::user()->UsersId, $contents);
    $response = Response::make($contents, 200);
    $response->header('Content-Type', 'application/javascript');
    return $response;   
  }
}

/* End File */