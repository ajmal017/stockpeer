<?php
  
namespace App\Http\Controllers;  

use App;
use View;
use Input;

class BrokerPickerController extends Controller 
{
	private $_data = [];
	private $_brokers = [
		'E*Trade' => [ 'ticket_charge' => 9.99, 'per_option' => 0.75, 'per_leg' => false, 'url' => 'http://etrade.com?utm_campaign=stockpeer.com' ],
		'TradeKing' => [ 'ticket_charge' => 4.95, 'per_option' => 0.65, 'per_leg' => false, 'url' => 'http://bit.ly/1yMaWU9' ],	
		'eOptions' => [ 'ticket_charge' => 3.00, 'per_option' => 0.15, 'per_leg' => true, 'url' => 'http://eoption.com?utm_campaign=stockpeer.com' ],
		'just2trade' => [ 'ticket_charge' => 2.50, 'per_option' => 0.50, 'per_leg' => true, 'url' => 'http://just2trade.com?utm_campaign=stockpeer.com' ],
		'Ameritrade' => [ 'ticket_charge' => 9.99, 'per_option' => 0.75, 'per_leg' => false, 'url' => 'http://tdameritrade.com?utm_campaign=stockpeer.com' ],	
		'Tastyworks *' => [ 'ticket_charge' => 0.00, 'per_option' => 0.50, 'per_leg' => false, 'url' => 'https://www.tastyworks.com?utm_campaign=stockpeer.com' ],		
		'Interactive Brokers' => [ 'ticket_charge' => 0.00, 'per_option' => 0.70, 'per_leg' => false, 'url' => 'https://www.interactivebrokers.com?utm_campaign=stockpeer.com' ],
		'Options House' => [ 'ticket_charge' => 4.95, 'per_option' => 0.50, 'per_leg' => false, 'url' => 'https://optionshouse.com?utm_campaign=stockpeer.com' ],
		'Tradier Brokerage **' => [ 'ticket_charge' => 0.00, 'per_option' => 0.35, 'per_leg' => false, 'min_reg' => 5.00, 'min_leg' => 7.00, 'url' => 'https://brokerage.tradier.com' ],									
	];	
	
	//
	// Construct.
	//
	public function __construct()
	{
		$this->_data['header'] = [
			'title' => 'Find The Cheapest Options Broker For Your Strategy | Stockpeer',
			'image' => 'https://dukitbr4wfrx2.cloudfront.net/blog/find-the-best-options-broker_25.png',
			'thumb' => 'https://dukitbr4wfrx2.cloudfront.net/blog/find-the-best-options-broker_25_thumb.png',			
			'description' => 'Using our tool you can search for the cheapest options broker for your strategy. Since options strategies are complex one broker might be better than another for your style of trading.'
		];
	}	
	
	//
	// Options broker picker.
	//
	public function options()
	{
		if(Input::get('strategy'))
		{
			$this->_data['brokers'] = $this->_order_brokers();
		} else
		{
			$this->_data['brokers'] = [];
		}
			
		return View::make('template.main', $this->_data)->nest('body', 'broker-picker.options', $this->_data);	
	}
	
	// -------------------- Private Helper Functions -------------------- //
	
	//
	// Return the brokers in order of price.
	//
	private function _order_brokers()
	{
		$rt = [];
		$price_index = [];		
		$strategy = Input::get('strategy');
		$lots = Input::get('lots');
		
		// Figure out leg information.
		switch($strategy)
		{
			case 'buy-options':
			case 'write-options':			
				$legs = 1;
			break;
			
			case 'vertical-spread':	
				$legs = 2;
			break;
			
			case 'iron-condor':	
				$legs = 4;
			break;						
		}	
		
		// Figure out price per broker.
		foreach($this->_brokers AS $key => $row)
		{
			// Figure out price based on strategy
			if($row['per_leg'])
			{
				$this->_brokers[$key]['ticket_charge'] = ($row['ticket_charge'] * $legs); 
				$this->_brokers[$key]['per_option'] = ($row['per_option'] * ($lots * $legs)); 				
				$price_index[$key] = ($row['ticket_charge'] * $legs) + ($row['per_option'] * ($lots * $legs));
			} else
			{
				$this->_brokers[$key]['per_option'] = ($row['per_option'] * ($lots * $legs)); 				
				$price_index[$key] = $row['ticket_charge'] + ($row['per_option'] * ($lots * $legs));				
			}
			
			// Special case: Interactive Brokers
			if(($key == 'Interactive Brokers') && ($price_index[$key] < 1))
			{
				$price_index[$key] = 1.00;
			}
			
			// Special case: Tradier Brokerage
			if($key == 'Tradier Brokerage **')
			{	
  			if(($price_index[$key] < 5) && ($legs == 1))
  			{
				  $price_index[$key] = 5.00;
        }
        
  			if(($price_index[$key] < 7) && ($legs >= 2))
  			{
				  $price_index[$key] = 7.00;
        }        
			}			
		}

		// Sort of lowest to highest.		
		asort($price_index);

		// Build object to pass to the view. 
		foreach($price_index AS $key => $row)
		{
			$rt[] = [
				'name' => $key,
				'ticket_charge' => number_format($this->_brokers[$key]['ticket_charge'], 2),
				'per_option' => number_format($this->_brokers[$key]['per_option'], 2),		
				'url' => $this->_brokers[$key]['url'],
				'total_cost' => number_format($row, 2)
			];
		}
		
		// Return sorted data.
		return $rt;
	}
}

/* End File */