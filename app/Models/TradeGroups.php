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
}

/* End File */