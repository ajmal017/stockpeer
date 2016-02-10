<?php

namespace App\Models;
 
use DB;
use App;
use Queue;
use DateTime;
use Carbon\Carbon;
use Cloudmanic\LaravelApi\Me;

class TradeGroups extends \Cloudmanic\LaravelApi\Model
{
	public $table = 'TradeGroups';
  public $only_open_positions = false; 
  private $positions_model = null;
  
  //
  // Construct.
  //
  public function __construct(App\Models\Positions $positions_model)
  {
    parent::__construct();
    $this->positions_model = $positions_model;
  }
  
  //
  // Only include open positions.
  //
  public function set_only_open_positions()
  {
    $this->only_open_positions = true;
  }  
  
	// 
	// Get completed trades by year.
	//
	public function get_completed_trades_by_year($year)
	{
		$this->set_col('TradeGroupsStatus', 'Closed');
		$this->set_col('TradeGroupsEnd', "$year-12-31", '<=');
		$this->set_col('TradeGroupsEnd', "$year-01-01", '>=');	
		return $this->get();		
	}   

  //
  // Here we pass in an order and see if we should be closing a position / tradegroup
  //
  public function close_position($order)
  {
    $positions_model = App::make('App\Models\Positions');
    
    // We are only looking at orders that have filled.
    if($order['OrdersStatus'] != 'Filled')
    {
      return false;
    }  
    
    // Close out single stock trades.
    if($order['OrdersClass'] == 'Equity')
    {
      $this->_close_stock_position($order['OrdersSymbol'], $order['OrdersQty'], $order['OrdersSide'], $order['OrdersFilledPrice']);
    }
    
    // Close out single option trades.
    if($order['OrdersClass'] == 'Option')
    {
      $this->_close_option_position($order['OrdersSymbol'], $order['OrdersQty'], $order['OrdersSide'], $order['OrdersFilledPrice']);
    }
    
    // Find the positions for option Spreads
    if($order['OrdersStrategy'] == 'Spread')
    { 
      // Leg #1
      if(! empty($order['OrdersLeg1OptionSymbol']))
      {
        $this->_close_option_position($order['OrdersLeg1OptionSymbol'], $order['OrdersLeg1Qty'], $order['OrdersLeg1Side'], $order['OrdersLeg1FilledPrice']);
      }
     
      // Leg #2      
      if(! empty($order['OrdersLeg2OptionSymbol']))
      {
        $this->_close_option_position($order['OrdersLeg2OptionSymbol'], $order['OrdersLeg2Qty'], $order['OrdersLeg2Side'], $order['OrdersLeg2FilledPrice']);
      }
      
      // Leg #3      
      if(! empty($order['OrdersLeg3OptionSymbol']))
      {
        $this->_close_option_position($order['OrdersLeg3OptionSymbol'], $order['OrdersLeg3Qty'], $order['OrdersLeg3Side'], $order['OrdersLeg3FilledPrice']);
      }
      
      // Leg #4      
      if(! empty($order['OrdersLeg4OptionSymbol']))
      {
        $this->_close_option_position($order['OrdersLeg4OptionSymbol'], $order['OrdersLeg4Qty'], $order['OrdersLeg4Side'], $order['OrdersLeg4FilledPrice']);
      }                
    }
    
    // Return Happy.
    return true;          
  }

  //
  // Loop through the positions in a group and return stats of the trade.
  //
  public function get_stats($trade_group_id)
  {
    $type = 'Other';
    $open_comm = 0;
    $qty = 0;
    $org_qty = 0;
    $status = 'Closed';
    $close_price = 0;
    $title = 'Trade #' . $trade_group_id;
        
    // Get positions for this tradegroup
    $positions_model = App::make('App\Models\Positions');
    $positions_model->set_col('PositionsTradeGroupId', $trade_group_id);
    $pos = $positions_model->get();
    
    // Set flags
    $stocks = false;
    $options = false;
    $puts = false;
    $calls = false;
    $cost_base = 0;
    $risked = 0;
    
    // Loop through and see what we have.
    foreach($pos AS $key => $row)
    {     
      // Set closed price.
      $close_price = $close_price + $row['PositionsClosePrice'];
      
      // See if the position is open or closed.
      if($row['PositionsStatus'] == 'Open')
      {
        $status = 'Open';
      }
       
      if($row['PositionsType'] == 'Stock')
      {
        $stocks = true;        
      }
      
      if($row['PositionsType'] == 'Option')
      {
        // Set qty
        $qty = $qty + abs($row['PositionsQty']);
        $org_qty = $org_qty + abs($row['PositionsOrgQty']);
                
        $options = true;
        
        if(stripos($row['SymbolsFull'], 'Put'))
        {
          $puts = true;
        }

        if(stripos($row['SymbolsFull'], 'Call'))
        {
          $calls = true;
        }       
      }
      
      $cost_base = $cost_base + $row['PositionsCostBasis'];
      $risked = $cost_base;      
    }
    
    // Generate Title for stocks and options
    if($stocks && $options)
    {
      $type = 'Other';      
      
      $com = $org_qty * 0.35;
      $open_comm = ($com < 7) ? 7.00 : $com; 
      $open_comm = $open_comm + 1; // TODO: make dynamic      
      
      $title = 'Stock and Options Trade #' . $trade_group_id;      
    }
    
    // Generate title for just stocks
    if($stocks && (! $options))
    {
      $open_comm = 1.00; // TODO: make this dynamic
      $type = ($cost_base > 0) ? 'Long Stock Trade' : 'Short Stock Trade';      
      $title = 'Stock Trade #' . $trade_group_id;      
    }    

    // Generate title for Options
    if((! $stocks) && $options)
    {
      $type = ($cost_base > 0) ? 'Long Option Trade' : 'Short Option Trade';
      $title = 'Options Trade #' . $trade_group_id;  
      
      if($puts && (! $calls) && (count($pos) > 1))
      {
        if($cost_base < 0)
        {
          $type = 'Put Credit Spread';
          $title = 'Put Credit Spread Trade #' . $trade_group_id . ' @ $' . ((abs($cost_base) / ($org_qty / count($pos))) / 100);   
          
          // Lets see if this is a weekly put credit spread. A special type for when our trade is just a week long
          $datetime1 = new DateTime(date('Y-m-d', strtotime($row['PositionsCreatedAt'])));
          $datetime2 = new DateTime($row['SymbolsExpire']);
          $interval = $datetime1->diff($datetime2);
          if($interval->days <= 9)
          {
            $type = 'Weekly Put Credit Spread';
            $title = 'Weekly ' . $title;
          }        
        } else
        {
          $type = 'Put Debit Spread';
          $title = 'Put Debit Spread Trade #' . $trade_group_id;          
        }            
      } 
      
      if($calls && (! $puts) && (count($pos) > 1))
      {
        if($cost_base > 0)
        {
          $type = 'Call Debit Spread';          
          $title = 'Call Debit Spread Trade #' . $trade_group_id;           
        } else
        {
          $type = 'Call Credit Spread';          
          $title = 'Call Credit Spread Trade #' . $trade_group_id;          
        }        
      }
      
      // Figure out how much we have risked.
      if(count($pos) > 1)
      {
        $strikes = [];
        
        foreach($pos AS $key => $row)
        {
          $strikes[] = $row['SymbolsStrike'];
        } 
        
        $diff = max($strikes) - min($strikes);
        
        $risked = ($diff * ($org_qty / count($pos)) * 100) + $cost_base;
      }        
      
      // Figure out commission paid to open
      if(count($pos) == 1)
      {
        $com = $org_qty * 0.35;
        $open_comm = ($com < 5) ? 5.00 : $com;         
      } else
      {      
        $com = $org_qty * 0.35;
        $open_comm = ($com < 7) ? 7.00 : $com; 
      }
    } 
    
    // Return the new title.
    return [
      'status' => $status,
      'type' => $type,
      'title' => $title,
      'cost_base' => $cost_base,
      'close_price' => $close_price,
      'open_comm' => $open_comm,
      'risked' => $risked
    ];
  }
  
  //
  // Format Get
  //
  public function _format_get(&$data)
  {
    // Add Positions
    if(isset($data['TradeGroupsId']))
    {
      $this->positions_model->set_col('PositionsTradeGroupId', $data['TradeGroupsId']);
      $this->positions_model->set_order('PositionsId', 'asc');
      
      if($this->only_open_positions)
      {
        $this->positions_model->set_col('PositionsStatus', 'Open');        
      }
      
      $data['Positions'] = $this->positions_model->get();
    }
    
    // Find total commissions paid 
    if(isset($data['TradeGroupsOpenCommission']) && isset($data['TradeGroupsCloseCommission']))
    {
      $data['Commissions_Total'] = $data['TradeGroupsOpenCommission'] + $data['TradeGroupsCloseCommission'];
    } else
    {
      $data['Commissions_Total'] = 0;      
    }    
    
    // Add profit and loss
    if(isset($data['TradeGroupsClose']) && 
        isset($data['TradeGroupsOpen']) && 
        isset($data['TradeGroupsStatus']) && 
        isset($data['TradeGroupsRisked']) &&         
        ($data['TradeGroupsStatus'] == 'Closed'))
    {
      $data['Profit_Loss'] = $data['TradeGroupsClose'] - $data['TradeGroupsOpen'] - $data['Commissions_Total'];

      if($data['TradeGroupsRisked'] > 0)
      {
        $data['Profit_Loss_Precent'] = ((($data['TradeGroupsRisked'] + $data['Profit_Loss']) - $data['TradeGroupsRisked']) / $data['TradeGroupsRisked']) * 100;
      } else
      {
        $data['Profit_Loss_Precent'] = 0;        
      }
    } else
    {
      $data['Profit_Loss'] = 0;
      $data['Profit_Loss_Precent'] = 0;      
    }  
  }
  
  // ----------------- Private helper functions ------------------- //
  
  //
  // Close a stock position.
  //
  private function _close_stock_position($symbol, $qty, $side, $filled_price)
  {
    $positions_model = App::make('App\Models\Positions');    
    
    // Make sure this is not an open order
    if(stripos($side, 'To Open'))
    {
      return false;
    }   
    
    // Find the position.
    $positions_model->set_col('PositionsType', 'Stock');
    $positions_model->set_col('PositionsStatus', 'Open');    
    $positions_model->set_col('SymbolsShort', $symbol);
    if($pos = $positions_model->first())
    {      
      // Setup the update.
      $update = [
        'PositionsQty' => $pos['PositionsQty'],
        'PositionsStatus' => 'Open',
        'PositionsClosePrice' => $pos['PositionsClosePrice']
      ];
      
      // Update the qty (short vs. long)
      if($pos['PositionsQty'] > 0)
      {
        $update['PositionsQty'] = $update['PositionsQty'] - $qty;
      } else
      {
        $update['PositionsQty'] = $update['PositionsQty'] + $qty;              
      }
      
      // See if this order is closed.
      if($update['PositionsQty'] == 0)
      {
        $update['PositionsStatus'] = 'Closed';
        $update['PositionsClosed'] = Carbon::now();
      }
      
      // Figure out the PositionsClosePrice
      if($side == 'Buy')
      {
        $update['PositionsClosePrice'] = $update['PositionsClosePrice'] - ($filled_price * $qty);            
      } else
      {
        $update['PositionsClosePrice'] = $update['PositionsClosePrice'] + ($filled_price * $qty);              
      }
      
      // Make query to update the order.
      $positions_model->update($update, $pos['PositionsId']); 
      
      // Update Trade Group stats (we just assume the close commission is the same as the open).
      $stats = $this->get_stats($pos['PositionsTradeGroupId']);
      
      $this->update([
        'TradeGroupsCloseCommission' => 1.00, // TODO: Make less hard coded.
        'TradeGroupsStatus' => $stats['status'],
        'TradeGroupsEnd' => ($stats['status'] == 'Closed') ? date('Y-m-d H:i:s') : '',
        'TradeGroupsClose' => $stats['close_price'],
        'TradeGroupsRisked' => $stats['risked']
      ], $pos['PositionsTradeGroupId']);                        
    }        
  }
  
  //
  // Close an options position.
  //
  private function _close_option_position($symbol, $qty, $side, $filled_price)
  {
    $positions_model = App::make('App\Models\Positions');    
    
    // Make sure this is not an open order
    if(stripos($side, 'To Open'))
    {
      return false;
    }        
    
    // Find the position.
    $positions_model->set_col('PositionsStatus', 'Open');
    $positions_model->set_col('SymbolsShort', $symbol);
    if($pos = $positions_model->first())
    {
      // Setup the update.
      $update = [
        'PositionsQty' => $pos['PositionsQty'],
        'PositionsStatus' => 'Open',
        'PositionsClosePrice' => $pos['PositionsClosePrice']
      ];
      
      // Update the qty (short vs. long)
      if($pos['PositionsQty'] > 0)
      {
        $update['PositionsQty'] = $update['PositionsQty'] - $qty;
      } else
      {
        $update['PositionsQty'] = $update['PositionsQty'] + $qty;              
      }
      
      // See if this order is closed.
      if($update['PositionsQty'] == 0)
      {
        $update['PositionsStatus'] = 'Closed';
        $update['PositionsClosed'] = Carbon::now();
      }
      
      // Figure out the PositionsClosePrice
      if($side == 'Buy To Close')
      {
        $update['PositionsClosePrice'] = $update['PositionsClosePrice'] - (($filled_price * $qty) * 100);            
      } else
      {
        $update['PositionsClosePrice'] = $update['PositionsClosePrice'] + (($filled_price * $qty) * 100);              
      }
                  
      // Make query to update the order.
      $positions_model->update($update, $pos['PositionsId']);
      
      // Update Trade Group stats (we just assume the close commission is the same as the open).
      $stats = $this->get_stats($pos['PositionsTradeGroupId']);
      
      $this->update([
        'TradeGroupsCloseCommission' => $stats['open_comm'],
        'TradeGroupsStatus' => $stats['status'],
        'TradeGroupsEnd' => ($stats['status'] == 'Closed') ? date('Y-m-d H:i:s') : '',
        'TradeGroupsClose' => $stats['close_price'],
        'TradeGroupsRisked' => $stats['risked']
      ], $pos['PositionsTradeGroupId']);                     
    }    
  }
}

/* End File */