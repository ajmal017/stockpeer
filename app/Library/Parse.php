<?php
	
namespace App\Library;

use \App;
use \View;

class Parse
{
	//
	// Get an instance of Parse.
	//
	public static function instance()
	{
		return new Parse();	
	}
	
	//
	// Parse the text we pass in to see if there is any replacing we need to do.
	//
	public function run($str)
	{
		$this->parse_blog_trades($str);
		
		return $str;
	} 
	
	//
	// Parse for blog trades.
	//
	// {{BlogTrades start="11/1/2014" end="11/30/2014"}} 
	//
	public function parse_blog_trades(&$str)
	{
		// See if we have any matches
		preg_match_all('/{{(.+) start=\\"(.+)\\" end=\\"(.+)\\"}}/', $str, $matches);
		
		// Return if we did not find any matches
		if(! $matches)
		{
			return false;
		}
		
		// Get the model up and running.
		$blogtrades_model = App::make('App\Models\BlogTrades');
		
		// Loop through and replace the tag with the html.
		foreach($matches[0] AS $key => $row)
		{
			$start = date('Y-m-d', strtotime($matches[2][$key])); 
			$end = date('Y-m-d', strtotime($matches[3][$key])); 

			// Make database query to get the data.
			$blogtrades_model->set_col('BlogTradesCloseDate', $start, '>=');
			$blogtrades_model->set_col('BlogTradesCloseDate', $end, '<=');
			$blogtrades_model->set_order('BlogTradesCloseDate', 'asc');
			$trades = $blogtrades_model->get();
			
			// Add the data to the template.
			$html = View::make('blog.blog_trades_template', [ 'trades' => $trades ])->render();	
			
			// Replace the tag with the html			
			$str = str_replace($row, $html, $str);
		}
	} 
}

/* End File */