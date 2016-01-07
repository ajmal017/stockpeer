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
  // Update positions if they have changed on the Tradier side.
  //
  public function update_position($row)
  {
    // First we see if we have already logged this position. 
    $this->set_col('PositionsStatus', 'Open');
    $this->set_col('PositionsBrokerId', $row['id']);
    if($p = $this->get())
    {
      // See if the PositionsDateAcquired has changed.
      if($p[0]['PositionsDateAcquired'] != $row['date_acquired'])
      {
        $this->update([ 'PositionsDateAcquired' => $row['date_acquired'] ], $p[0]['PositionsId']);
      }
      
      // See if the qty has changed.
      if($p[0]['PositionsQty'] != $row['quantity'])
      {
        $this->update([ 'PositionsQty' => $row['quantity'] ], $p[0]['PositionsId']);
      }
    
      // See if the PositionsCostBasis has changed.
      if(floatval($p[0]['PositionsCostBasis']) != floatval(round($row['cost_basis'], 2)))
      {          
        $this->update([ 'PositionsCostBasis' => $row['cost_basis'] ], $p[0]['PositionsId']);
      }     
    
      // Return true.
      return true;
    }
    
    // Nothing updated
    return false;    
  }
  
  //
  // Add a new postion to the database from the Tradier API.
  //
  public function add_new_position($row, $trade_group_id)
  {     
    $symbols_model = App::make('App\Models\Symbols');
    $assets_model = App::make('App\Models\Assets');
    $activity_model = App::make('App\Models\Activity'); 
    $tradegroups_model = App::make('App\Models\TradeGroups');    
    
    // Get the Tradier asset
    $assets_model->set_col('AssetsName', 'Tradier');
    if(! $asset = $assets_model->get())
    {
      return false;
    } else
    {
      $asset = $asset[0];
    }    
    
    // We do a quick check to make sure this position is not already closed.
    // This is common when we have closed something on our end but it is still 
    // open on the Tradier side. This is common with options we let expired 
    // because they do not close until the next day.
    $this->set_col('PositionsStatus', 'Closed');
    $this->set_col('PositionsBrokerId', $row['id']);
    $this->set_col('PositionsDateAcquired', $row['date_acquired']);
    if($this->get())    
    {
      return false;
    }
    
    // Setup a trade group. Since we are adding this trade we know this is part of a new trade group.
    // This is sort of buggy. It assumes we are checking for new positions all the time. 
    // If there is ever a gap in checking for new positions this could group a bunch of positions
    // into one group. Since we place orders from Stockpeer this should not be too much of an issue.
    if(is_null($trade_group_id))
    {      
      $trade_group_id = $tradegroups_model->insert([ 
        'TradeGroupsTitle' => 'New Trade', 
        'TradeGroupsStart' => date('Y-m-d H:i:s'),
        'TradeGroupsStatus' => 'Open'
      ]);
    }
    
    // Now we get the Symbols
    if(! $sym_id = $symbols_model->get_symbol_id($row['symbol']))
    {
      if($row['quote']['type'] == 'option')
      {
        $sym_id = $symbols_model->insert([
          'SymbolsShort' => strtoupper($row['symbol']),
          'SymbolsFull' => $row['quote']['description'],
          'SymbolsExpire' => date('Y-m-d', strtotime($row['quote']['expiration_date'])),  		
          'SymbolsUnderlying' => strtoupper($row['quote']['underlying']), 
          'SymbolsStrike' => $row['quote']['strike'],         		
          'SymbolsType' => 'Option',
          'SymbolsOptionType' => ucfirst(strtolower($row['quote']['option_type']))           
        ]);
      } else
      {
        $sym_id = $symbols_model->insert([
          'SymbolsShort' => strtoupper($row['symbol']),
          'SymbolsFull' => $row['quote']['description'],        		
          'SymbolsType' => 'Stock'           
        ]);          
      }
    } 
    
    // Must be a new position lets log it.
    $this->insert([
      'PositionsAssetId' => $asset['AssetsId'],
      'PositionsTradeGroupId' => $trade_group_id,
      'PositionsBrokerId' => $row['id'],
      'PositionsSymbolId' => $sym_id,
      'PositionsType' => ($row['quote']['type'] == 'option') ? 'Option' : 'Stock',
      'PositionsQty' => $row['quantity'],
      'PositionsOrgQty' => $row['quantity'],
      'PositionsCostBasis' => $row['cost_basis'],
      'PositionsAvgPrice' => ($row['cost_basis'] / $row['quantity']),
      'PositionsStatus' => 'Open',
      'PositionsDateAcquired' => $row['date_acquired']
    ]);
    
    // Get the stats on the trade group so we can set the stats.
    if(! is_null($trade_group_id))
    {
      $stats = $tradegroups_model->get_stats($trade_group_id); 
      
      // Update tradegrop with Summary
      $tradegroups_model->update([
        'TradeGroupsTitle' => $stats['title'],
        'TradeGroupsOpen' => $stats['cost_base'],
        'TradeGroupsOpenCommission' => $stats['open_comm'],
        'TradeGroupsType' => $stats['type'],
        'TradeGroupsRisked' => $stats['risked']                     
      ], $trade_group_id);         
    }
    
    // Return the trade group id.
    return $trade_group_id;
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