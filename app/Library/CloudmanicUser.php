<?php 

namespace App\Library;

use Illuminate\Contracts\Auth\Authenticatable;

class CloudmanicUser implements Authenticatable 
{
	protected $attributes;

  //
  // Create a new generic User object.
  //
	public function __construct(array $attributes)
	{
    \Cloudmanic\LaravelApi\Me::set_account([ 'AccountsId' => $attributes['UsersId'] ]);
  	\Cloudmanic\LaravelApi\Me::set($attributes);
		$this->attributes = $attributes;
	}

  //
  // Get the unique identifier for the user.
  //
	public function getAuthIdentifier()
	{
		return $this->attributes['UsersId'];
	}

  //
  // Get the password for the user.
  //
	public function getAuthPassword()
	{
		return $this->attributes['password'];
	}

  //
  // Get the "remember me" token value.
  //
	public function getRememberToken()
	{
		return $this->attributes[$this->getRememberTokenName()];
	}

  //
  // Set the "remember me" token value.
  //
	public function setRememberToken($value)
	{
		$this->attributes[$this->getRememberTokenName()] = $value;
	}

  //
  // Get the column name for the "remember me" token.
  //
	public function getRememberTokenName()
	{
		return 'remember_token';
	}

  //
  // Dynamically access the user's attributes.
  //
	public function __get($key)
	{
		return $this->attributes[$key];
	}

  //
  // Dynamically set an attribute on the user.
  //
	public function __set($key, $value)
	{
		$this->attributes[$key] = $value;
	}

  //
  // Dynamically check if a value is set on the user.
  //
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

  //
  // Dynamically unset a value on the user.
  //
	public function __unset($key)
	{
		unset($this->attributes[$key]);
	}
}

/* End File */