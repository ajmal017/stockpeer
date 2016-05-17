<?php

namespace App\Http\Controllers;

use Cache;
use View;

class AhController extends Controller 
{
  // 
  // Return the cached IVR
  //
  public function ivr($days)
  {
    $vix_rank = Cache::get('Quotes.SnP500.Rank.' . $days);
    return $vix_rank;
  }
}

/* End File */