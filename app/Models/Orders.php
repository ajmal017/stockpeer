<?php

namespace App\Models;
 
use DB;
use App;
use Auth;
use Crypt;
use Queue;
use Cloudmanic\LaravelApi\Me;

class Orders extends \Cloudmanic\LaravelApi\Model
{
  public $joins = [
    [ 'table' => 'Assets', 'left' => 'OrdersAssetId', 'right' => 'AssetsId' ]
  ];
  
  //
  // Loop through filled orders and see if we need to close any positions.
  //
  public function manage_postions_from_orders()
  {
    $tradegroups_model = App::make('App\Models\TradeGroups');
    
    // Loop through filled orders.
    $this->set_col('OrdersStatus', 'Filled');
    $this->set_col('OrdersReviewed', 'No');
    foreach($this->get() AS $key => $row)
    {      
      // See if any of our orders closed a position.
      $tradegroups_model->close_position($row);
      
      // Mark order as reviewed.
      $this->update([ 'OrdersReviewed' => 'Yes' ], $row['OrdersId']);
    }
    
    // Loop through Canceled orders.
    $this->set_col('OrdersStatus', 'Canceled');
    $this->set_col('OrdersReviewed', 'No');
    foreach($this->get() AS $key => $row)
    {      
      $this->update([ 'OrdersReviewed' => 'Yes' ], $row['OrdersId']);
    }    
  }
  
  //
  // Log orders from Tradier.
  //
  public function log_orders_from_tradier()
  {
    $assets_model = App::make('App\Models\Assets');
    $activity_model = App::make('App\Models\Activity');
    $tradegroups_model = App::make('App\Models\TradeGroups');
    
    // Make API to get the possitions.
    $tradier = App::make('App\Library\Tradier');
    $tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken));     
    if(! $orders = $tradier->get_account_orders(Auth::user()->UsersTradierAccountId, true))
    {
      // get_open_orders: Error getting data from Tradier. (' . $tradier->get_last_error() . ')'
      return false;
    }    
    
    // Get the Tradier asset
    $assets_model->set_col('AssetsName', 'Tradier');
    if(! $asset = $assets_model->get())
    {
      return false;
    } else
    {
      $asset = $asset[0];
    }
    
    // Loop through our orders and process them.
    foreach($orders AS $key => $row)
    {
      // Figure out status
      switch($row['status'])
      {
        case 'filled':
          $status = 'Filled';
        break;
        
        case 'open':
          $status = 'Open';
        break;
        
        case 'partial_filled':
          $status = 'Partial';
        break;
        
        case 'canceled':
          $status = 'Canceled';
        break;
        
        case 'pending':
          $status = 'Pending';
        break;                       
      }
      
      // Figure out the order side.
      switch($row['side'])
      {
        case 'buy':
          $side = 'Buy';
        break;
        
        case 'sell':
          $side = 'Sell';
        break;
        
        case 'buy_to_close':
          $side = 'Buy To Close';
        break;  
        
        case 'sell_to_close':
          $side = 'Sell To Close';
        break;
        
        case 'buy_to_open':
          $side = 'Buy To Open';
        break;  
        
        case 'sell_to_open':
          $side = 'Sell To Open';
        break;                                        
      }
      
      // See if we already have this order logged.
      if($order = $this->get_by_broker_id($asset['AssetsId'], $row['id']))
      {    
        // See if the OrdersPrice has changed
        if(isset($row['price']) && ($order['OrdersPrice'] != $row['price']))
        {
          // Update the order
          $this->update([ 
            'OrdersStatus' => $status,
            'OrdersPrice' => $row['price']
          ], $order['OrdersId']);
         
         // Add activity.
         $activity_model->record('Order Price', $order['OrdersId'], 'Tradier order Id #' . $order['OrdersId'] . ' has changed price to $' . $row['price'] . '.', true); 
        }

        // See if the status has changed
        if($order['OrdersStatus'] != $status)
        {
          // Update the order
          $this->update([ 
            'OrdersStatus' => $status,
            'OrdersFilledPrice' => ($row['avg_fill_price'] < 0) ? ($row['avg_fill_price'] * -1) : $row['avg_fill_price']
          ], $order['OrdersId']);

          // Update option fill prices
          if(($status == 'Filled') && ($row['strategy'] == 'spread'))
          {
            $this->update([ 
              'OrdersLeg1FilledPrice' => (isset($row['leg'][0])) ? $row['leg'][0]['avg_fill_price'] : 0,
              'OrdersLeg2FilledPrice' => (isset($row['leg'][1])) ? $row['leg'][1]['avg_fill_price'] : 0,
              'OrdersLeg3FilledPrice' => (isset($row['leg'][2])) ? $row['leg'][2]['avg_fill_price'] : 0,
              'OrdersLeg4FilledPrice' => (isset($row['leg'][3])) ? $row['leg'][3]['avg_fill_price'] : 0
            ], $order['OrdersId']);                                         
          }
         
         // Add activity.
         $activity_model->record('Order Status', $order['OrdersId'], 'Tradier order Id #' . $order['OrdersId'] . ' has changed status to ' . $status . '.', true); 
        }      
      } else
      {
        // Insert the order
        $id = $this->insert([
          'OrdersAssetId' => $asset['AssetsId'],
          'OrdersBrokerOrderId' => $row['id'],
          'OrdersFilledPrice' => ($row['avg_fill_price'] < 0) ? ($row['avg_fill_price'] * -1) : $row['avg_fill_price'],
          'OrdersSide' => $side,
          'OrdersType' => ucfirst(strtolower($row['type'])),
          'OrdersSymbol' => (isset($row['option_symbol'])) ? strtoupper($row['option_symbol']) : strtoupper($row['symbol']),
          'OrdersPrice' => (isset($row['price'])) ? $row['price'] : 0,
          'OrdersQty' => (isset($row['quantity'])) ? $row['quantity'] : 0,
          'OrdersDuration' => ($row['duration'] == 'day') ? 'Day' : 'GTC',
          'OrdersEntered' => date('Y-m-d G:i:s', strtotime($row['create_date'])),
          'OrdersClass' => (isset($row['class'])) ? ucfirst(strtolower($row['class'])) : '', 
          'OrdersLegs' => (isset($row['num_legs'])) ? $row['num_legs'] : 0, 
          'OrdersStrategy' => (isset($row['strategy'])) ? ucfirst(strtolower($row['strategy'])) : 'Other', 
          'OrdersStatus' => $status,
          
          'OrdersLeg1Symbol' => (isset($row['leg'][0])) ? strtoupper($row['leg'][0]['symbol']) : '',
          'OrdersLeg1OptionSymbol' => (isset($row['leg'][0])) ? strtoupper($row['leg'][0]['option_symbol']) : '',
          'OrdersLeg1Qty' => (isset($row['leg'][0])) ? $row['leg'][0]['quantity'] : 0,
          'OrdersLeg1Side' => (isset($row['leg'][0])) ?  ucwords(strtolower(str_ireplace('_', ' ', $row['leg'][0]['side']))) : '',  
          'OrdersLeg1FilledPrice' => (isset($row['leg'][0])) ? $row['leg'][0]['avg_fill_price'] : 0,

          'OrdersLeg2Symbol' => (isset($row['leg'][1])) ? strtoupper($row['leg'][1]['symbol']) : '',
          'OrdersLeg2OptionSymbol' => (isset($row['leg'][1])) ? strtoupper($row['leg'][1]['option_symbol']) : '',
          'OrdersLeg2Qty' => (isset($row['leg'][1])) ? $row['leg'][1]['quantity'] : 0,
          'OrdersLeg2Side' => (isset($row['leg'][1])) ?  ucwords(strtolower(str_ireplace('_', ' ', $row['leg'][1]['side']))) : '',
          'OrdersLeg2FilledPrice' => (isset($row['leg'][1])) ? $row['leg'][1]['avg_fill_price'] : 0,           
          
          'OrdersLeg3Symbol' => (isset($row['leg'][2])) ? strtoupper($row['leg'][2]['symbol']) : '',
          'OrdersLeg3OptionSymbol' => (isset($row['leg'][2])) ? strtoupper($row['leg'][2]['option_symbol']) : '',
          'OrdersLeg3Qty' => (isset($row['leg'][2])) ? $row['leg'][2]['quantity'] : 0,
          'OrdersLeg3Side' => (isset($row['leg'][2])) ?  ucwords(strtolower(str_ireplace('_', ' ', $row['leg'][2]['side']))) : '', 
          'OrdersLeg3FilledPrice' => (isset($row['leg'][2])) ? $row['leg'][2]['avg_fill_price'] : 0,          
          
          'OrdersLeg4Symbol' => (isset($row['leg'][3])) ? strtoupper($row['leg'][3]['symbol']) : '',
          'OrdersLeg4OptionSymbol' => (isset($row['leg'][3])) ? strtoupper($row['leg'][3]['option_symbol']) : '',
          'OrdersLeg4Qty' => (isset($row['leg'][3])) ? $row['leg'][3]['quantity'] : 0,
          'OrdersLeg4Side' => (isset($row['leg'][3])) ?  ucwords(strtolower(str_ireplace('_', ' ', $row['leg'][3]['side']))) : '',
          'OrdersLeg4FilledPrice' => (isset($row['leg'][3])) ? $row['leg'][3]['avg_fill_price'] : 0          
        ]);
        
        // Add to activity.
        $activity_model->record('New Order', $id, 'New order entered with Tradier. Order Id #' . $id, true);
      }
    }    
  }
    
  //
  // Get order by broker id.
  //
  public function get_by_broker_id($asset, $id)
  {
    $this->set_col('OrdersBrokerOrderId', $id);
    $this->set_col('OrdersAssetId', $asset); 
    $data = $this->get();
    return (isset($data[0])) ? $data[0] : false;
  }
  
  //
  // Formt get.
  //
  public function _format_get(&$data)
  {
    if($data['OrdersClass'] == 'Multileg')
    {
      $data['Qty'] = $data['OrdersLeg1Qty'];
    } else
    {
      $data['Qty'] = $data['OrdersQty'];
    }
  }
}

/* End File */