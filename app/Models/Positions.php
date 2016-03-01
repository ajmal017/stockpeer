<?php

namespace App\Models;
 
use DB;
use App;
use Queue;
use Cloudmanic\LaravelApi\Me;

class Positions extends \Cloudmanic\LaravelApi\Model
{
	public $table = 'Positions'; 
  
  public $joins = [
    [ 'table' => 'Assets', 'left' => 'PositionsAssetId', 'right' => 'AssetsId' ],    
    [ 'table' => 'Symbols', 'left' => 'PositionsSymbolId', 'right' => 'SymbolsId' ]  
  ];
  
  //
  // Get position by symbol.
  //
  public function get_open_by_symbol($ticker)
  {
    $this->set_col('PositionsStatus', 'Open');
    $this->set_col('SymbolsShort', strtoupper($ticker));
    return $this->first();    
  }
  
  //
  // Format Get
  //
  public function _format_get(&$data)
  {
    if(isset($data['PositionsCostBasis']) && isset($data['PositionsClosePrice']) && isset($data['PositionsStatus']) && ($data['PositionsStatus'] == 'Closed'))
    {
      $data['Profit_Loss'] = $data['PositionsClosePrice'] - $data['PositionsCostBasis'];
    } else
    {
      $data['Profit_Loss'] = 0;
    }
  }  
}

/* End File */