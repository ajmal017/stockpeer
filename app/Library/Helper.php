<?php

//
// Just a helper class with random helper functions. 
// No real logic behind what goes into this class.
// Functions should be called as static.
//

namespace App\Library;

class Helper 
{
	//
	// Figure out if a date is a weekend
	//
	public static function is_weekend($date) 
	{
		return (date('N', strtotime($date)) >= 6);
	}
}

/* End File */