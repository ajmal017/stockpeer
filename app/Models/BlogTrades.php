<?php

namespace App\Models;

class BlogTrades extends \Cloudmanic\LaravelApi\Model
{
	//
	// Format Get.
	//
	public function _format_get(&$data)
	{
		// Some nicer formatting
		if(isset($data['BlogTradesBuyStrike']))
		{
			$data['BlogTradesBuyStrike_df1'] = str_ireplace('.00', '', $data['BlogTradesBuyStrike']);
			$data['BlogTradesBuyStrike_df1'] = str_ireplace('.50', '.5', $data['BlogTradesBuyStrike_df1']);			
		}
		
		// Some nicer formatting
		if(isset($data['BlogTradesSellStrike']))
		{
			$data['BlogTradesSellStrike_df1'] = str_ireplace('.00', '', $data['BlogTradesSellStrike']);
			$data['BlogTradesSellStrike_df1'] = str_ireplace('.50', '.5', $data['BlogTradesSellStrike_df1']);			
		}
		
		// Set spread width.
		if(isset($data['BlogTradesSellStrike']) && isset($data['BlogTradesBuyStrike']))
		{
			$data['width'] = $data['BlogTradesSellStrike'] - $data['BlogTradesBuyStrike'];
		} else
		{
			$data['width'] = 0;			
		}
		
		// Figure out the profit / loss
		if(isset($data['BlogTradesOpenCredit']) && isset($data['BlogTradesCloseDebit']))
		{
			$y1 = ($data['width'] * 100) - ($data['BlogTradesOpenCredit'] * 100);			
			$y2 = ($data['width'] * 100) - ($data['BlogTradesCloseDebit'] * 100);
			$data['ProfitLoss'] = number_format((($y2 - $y1) / $y1) * 100, 2);
		} else
		{
			$data['ProfitLoss'] = 0;			
		}
				
	}

}

/* End File */