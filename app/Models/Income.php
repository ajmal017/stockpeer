<?php

namespace App\Models;

use DB;
use Auth;
use App;
use Queue;
use Cloudmanic\LaravelApi\Me;

class Income extends \Cloudmanic\LaravelApi\Model
{
  //
  // Format get.
  //
  public function _format_get(&$data)
  {
    $assets_model = App::make('App\Models\Assets');    
    $symbols_model = App::make('App\Models\Symbols');
    $tradegroups_model = App::make('App\Models\TradeGroups'); 
    
    // If this a trade group trade?
    if(isset($data['IncomeTradeGroupId']) && ($data['IncomeTradeGroupId'] > 0))
    {
      $tradegroups_model->set_select([ 'TradeGroupsId', 'TradeGroupsTitle', 'TradeGroupsType', 'TradeGroupsStatus' ]);
      $data['TradeGroup'] = $tradegroups_model->get_by_id($data['IncomeTradeGroupId']);
    } else
    {
      $data['TradeGroup'] = [];
    }
    
    // If this a Symbols?
    if(isset($data['IncomeSymbolsId']) && ($data['IncomeSymbolsId'] > 0))
    {
      $symbols_model->set_select([ 'SymbolsId', 'SymbolsShort', 'SymbolsFull', 'SymbolsType', 'SymbolsNameAlt1' ]);
      $data['Symbol'] = $symbols_model->get_by_id($data['IncomeSymbolsId']);
    } else
    {
      $data['Symbol'] = [];
    }
    
    // If this an asset?
    if(isset($data['IncomeTradierAssetId']) && ($data['IncomeTradierAssetId'] > 0))
    {
      $assets_model->set_select([ 'AssetsId', 'AssetsName', 'AssetsBroker' ]);
      $data['Asset'] = $assets_model->get_by_id($data['IncomeTradierAssetId']);
    } else
    {
      $data['Asset'] = [];
    }        
  }
}

/* End File */