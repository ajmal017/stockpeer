<?php
  
namespace App\Http\Controllers;  

use Auth;
use View;
use Input;
use Session;

class AuthController extends Controller 
{
	//
	// Construct.
	//
	public function __construct()
	{
		$this->_data['header'] = [
			'title' => 'Stockpeer',
			'image' => '',
			'thumb' => '',			
			'description' => ''
		];
	}	  
  
  //
  // Login.
  //
  public function login()
  {
    // Am I already logged in.
    if(Auth::check())
    {
      return redirect('/a');
    }
    
    // Did we post??
    if(Input::get('email'))
    {
      // Try to login.
      if(Auth::attempt([ 'email' => Input::get('email'), 'password' => Input::get('password') ]))
      {
        return redirect('/a');
      } else
      {
        $this->_data['failed'] = true;
      }
    }
    
    // Show view.
    return View::make('template.main', $this->_data)->nest('body', 'auth.login', $this->_data);	
  }
  
  //
  // Logout.
  //
  public function logout()
  {
    Auth::logout();
    return redirect('/');
  }  
}

/* End File */