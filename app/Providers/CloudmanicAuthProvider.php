<?php 
  
namespace App\Providers;

use App\User;
use App\Library\CloudmanicAuth;
use Illuminate\Support\ServiceProvider;

class CloudmanicAuthProvider extends ServiceProvider 
{
  //
  // Bootstrap the application services.
  //
  public function boot()
  {
    $this->app['auth']->extend('cloudmanic',function()
    {
      return new CloudmanicAuth();
    });
  }

  //
  // Register the application services.
  //
  public function register()
  {
    //
  }
}

/* End File */