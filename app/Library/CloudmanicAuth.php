<?php 

namespace App\Library;

use DB;
use Hash;
use App\Library\CloudmanicUser;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class CloudmanicAuth implements UserProvider 
{
  //
  // Get the user by id.
  //
  public function retrieveById($id)
  {
    $user = DB::table('Users')->where('UsersId', $id)->first();
    return $this->getGenericUser($user);
  }

  //
  // Get by Token.
  //
  public function retrieveByToken($identifier, $token)
  {
    //echo "Failed: retrieveByToken";
  }

  //
  // Update remember Token.
  //
  public function updateRememberToken(UserContract $user, $token)
  {
    //echo "Failed: updateRememberToken";
  }

  //
  // Log a user in.
  // 
  public function retrieveByCredentials(array $credentials)
  {
    $user = DB::table('Users')->where('UsersEmail', $credentials['email'])->first();    
    return $this->getGenericUser($user);
  }

  //
  // Validate password.
  //
  public function validateCredentials(UserContract $user, array $credentials)
  {
    return Hash::check($credentials['password'], $user->UsersPassword);      
  }
    
  // -------------- Private Helper Functions ----------------- //
  
  //
  // Get the generic user
  //
  private function getGenericUser($user)
  {
    if($user !== null)
    {
      return new CloudmanicUser((array) $user);
    }
  }    
}

/* End File */