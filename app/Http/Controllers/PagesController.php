<?php

namespace App\Http\Controllers;

use View;

class PagesController extends Controller 
{
	private $_data = [];
	
	//
	// Construct.
	//
	public function __construct()
	{
		$this->_data['header'] = [
			'title' => 'Stockpeer',
			'image' => '',
			'thumb' => '',			
			'description' => ''
		];
	}
	
	//
	// About page.
	//
	public function about()
	{
		$this->_data['header']['title'] = 'Stockpeer Everything You Need To Know About Stock and Options Trading';
		$this->_data['header']['description'] = "I don’t remember exactly when I made my first trade, but I do know I got hooked in August 2004. That was the month I took off work and read McMillan on Options by Lawrence G. McMillan cover to cover—and then spent the rest of the stint fervently writing a software program to scan the options market and media websites to identify trading opportunities (#goodtimes). Since then I have spent countless hours tinkering with the software to facilitate options trading.";
		$this->_data['header']['image'] = 'https://dukitbr4wfrx2.cloudfront.net/blog/38550_1506771996770_6369406_n_5.jpg';
		$this->_data['header']['thumb'] = 'https://dukitbr4wfrx2.cloudfront.net/blog/38550_1506771996770_6369406_n_5_thumb.jpg';
		return View::make('template.main', $this->_data)->nest('body', 'pages.about', $this->_data);
	}
}

/* End File */