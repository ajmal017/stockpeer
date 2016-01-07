<?php
//
// By: Spicer Matthews
// Date: 2/5/2015
// Description: Library to help screen scrape the Eoption website.
//
	
namespace App\Libraries;

use \App;
use \pQuery;
use \GuzzleHttp\Client;

class Eoption
{
	private $_http = null;
	private $_login_url_submit = 'https://etrading.eoption.com/Modules/Login/loginSubmit.php';
	private $_login_url = 'https://etrading.eoption.com/Modules/Login/login.php';
	private $_order_status_url = 'https://etrading.eoption.com/Modules/Trading/OrderStatus/combinedOrderStatus.php';
	
	//
	// Construct.
	//
	public function __construct()
	{
		$this->_http = new Client();
	}
	
	//
	// Login & set session.
	//
	public function login($username, $password)
	{
		// Setup Post data.
		$post = [ 
							'cookies' => true,
							'body' => [
								'loginId' => $username,
								'password' => $password,
								'rememberLogin' => 'Y',
								'args' => '',
								'destination' => ''
							]
						];
		
		// Get the php session setup by visiting the login page.
		$request = $this->_http->get($this->_login_url, [ 'cookies' => true ]);
			
		// Make a post to login.		
		$request = $this->_http->post($this->_login_url_submit, $post);
		
		return true;
	}
	
	//
	// Get order status data.
	//
	public function get_order_status()
	{
		$orders = [];

		// Send Request to get the html from the order's screen
		$request = $this->_http->get($this->_order_status_url, [ 'cookies' => true ]);
	
		// Prase Html	
		$html = (string) $request->getBody();		
		$dom = pQuery::parseStr($html);
		
		// Get the order table node.
		$nodes = $dom->query('#combinedOrderStatusTable tbody tr');
		
		// Loop through the table.
		foreach($nodes AS $key => $row)
		{
			$order = [];
			
			foreach($row->query('td') AS $key2 => $row2)
			{
				$order[] = $row2->text();
			}
			
			$orders[] = [
				'type' => $order[1],
				'action' => $order[2],
				'order_qty' => $order[3],
				'remaining_qty' => $order[4],
				'security' => $order[5],
				'ticker' => str_ireplace('...', '', $order[6]),
				'price' => $order[7],
				'time_in_force' => (isset($order[8])) ? $order[8] : '',
				'open_date_time' => (isset($order[10])) ? $order[10] : '',
				'status' => (isset($order[11])) ? $order[11] : '',
				'spread' => 0
			]; 
		}
		
		// Clean up orders data.
		foreach($orders AS $key => $row)
		{
			if(empty($row['time_in_force']))
			{
				$orders[$key]['time_in_force'] = $orders[$key-1]['time_in_force'];
				$orders[$key]['spread'] = $key - 1;				
			}
			
			if(empty($row['open_date_time']))
			{
				$orders[$key]['open_date_time'] = $orders[$key-1]['open_date_time'];			
			}
			
			if(empty($row['status']))
			{
				$orders[$key]['status'] = $orders[$key-1]['status'];			
			}	
			
			if(empty($row['price']))
			{
				$orders[$key]['price'] = $orders[$key-1]['price'];			
			}									
		}
		
		// Return data.
		return $orders;
	}
	
	private function _get_test_html()
	{
		$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>	
		<script type="text/javascript">downloadTimerStart = new Date().getTime();</script>		<title>Order Status</title>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
		
		<link rel="stylesheet" href="//etrading.eoption.com/Modules/Navigation/styleSet.php?assetCacheID=1420678714" type="text/css" media="all" />
					<link rel="stylesheet" href="//etrading.eoption.com/Modules/Navigation/styleSet.php?assetCacheID=1420678714&amp;set=COMBINED_ORDER_STATUS" type="text/css" media="all" />
				
		<script language="javascript" type="text/javascript" src="//etrading.eoption.com/Modules/Navigation/javaScriptSet.php?assetCacheID=1420678714"></script>
					<script language="javascript" type="text/javascript" src="//etrading.eoption.com/Modules/Navigation/javaScriptSet.php?assetCacheID=1420678714&amp;set=COMBINED_ORDER_STATUS"></script>
		
		<!-- compiled CSS -->
<link rel="stylesheet" type="text/css" href="/Set/app/assets/vendor.css" />
<link rel="stylesheet" type="text/css" href="/CSS/ng-boilerplate-0.3.1.css" />

<!-- compiled JavaScript -->
<script type="text/javascript" src="/Set/app/src/js_deps.min.js"></script>
<script type="text/javascript" src="/Set/app/vendor/angular/angular.min.js"></script>
<script type="text/javascript" src="/Set/app/src/js_no_mangle.min.js"></script>
<script type="text/javascript" src="/Set/app/src/js_bundle.min.js"></script>
		<script type="text/javascript">
			var autoLoadAccount = true;
var shouldReportDownloadTimes = false;
if(typeof(loadHandler)!="undefined"){window.onload=loadHandler;}
if(typeof(svi)=="undefined"){svi={};}
if(typeof(svi.redirects)=="undefined"){svi.redirects={};}
if(typeof(svi.permissions)=="undefined"){svi.permissions={};}
if(typeof(svi.account)=="undefined"){svi.account={};}
svi.redirects.manualLogout = "\/Modules\/Login\/logoutExpand.php";
svi.redirects.sessionExpired = "\/Modules\/Login\/logoutExpand.php?message=sessionExpired";
svi.redirects.noSession = "\/Modules\/Login\/logoutExpand.php?message=noSession";
svi.permissions.symbolLookup = true;
svi.permissions.stockTrading = true;
svi.permissions.optionTrading = true;
svi.permissions.fundTrading = true;
svi.permissions.advancedTrading = true;
svi.permissions.bonusTradesEdit = false;
svi.permissions.analytics = false;
svi.account.currentAccount = "17547918";
svi.account.accountList = ["17547918 - trading account"]
svi.serverTime = 1423163967;
svi.sessionLeft = 14399;
svi.token = "0ef04633aae91410cbd1caddd2881733";
svi.expireMins = 240;			
				//legacy password security stuff
	var b_antikeyCapture = false;
	var b_flashKeypad = false;
	var b_pwdMeter = false;
	
	//variables for updateInfo.js
	Ext.ns("svi.updateInfo");
	svi.updateInfo.skipsRemain = 3;
	svi.updateInfo.display = false;
	svi.updateInfo.loginExpired = false;
	svi.updateInfo.forceLogin = false;
	svi.updateInfo.tradingExpired = false;
	svi.updateInfo.forceTrading = false;
	svi.updateInfo.emailExpired = false;
	
		
			
	if (svi.updateInfo.display) {
		Ext.onReady(function() {
			svi.DynamicLoader.loadJS("UPDATE_INFO_JS");
		});
	}
		</script>
					<!--[if IE]>
			<script type="text/javascript" event="FSCommand(command,args)" for="keyboard">
				keyboard_DoFSCommand(command, args);
			</script>
			<![endif]-->
			</head>
	<body>
				
					<div id="header">
				<a href="//etrading.eoption.com/Modules/Dashboard/dashboard.php">
	<img src="//etrading.eoption.com/Images/eOption/LogoeOption.png" class="logo" />
</a>
<div class="headerShortCuts">
			<a href="#" id="shortcutFastFind">Fast Find</a> <span id="headerSeparator">|</span> 
		<div id="fastFind" class="dialog">
	<h2 class="dialogHeader">
		<a href="#" id="fastFindClose">close</a>
		<div class="dialogTitle">Fast Find</div>
	</h2>
	<div class="dialogContent">
		<ul>
			<li>
				Accounts				<ul>
											<li class="deselected"><a href="//etrading.eoption.com/Modules/Dashboard/dashboard.php">Summary</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/AccountInfo/accountInfo.php">Account Info</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/Balances/balances.php">Balances</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/Positions/positions.php">Positions</a></li>
																															<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/History/history.php">History</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/CostBasis/Unrealized/unrealizedExpandable.php">Gain/Loss</a></li>
																										<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/AssetAllocation/assetAllocation.php">Asset Allocation</a></li>
																																		</ul>
			</li>
							<li>
					Trading					<ul>
													<li class="deselected"><a href="//etrading.eoption.com/Modules/Trading/Trade/Standard/Stock/enter.php">Stocks &#43; ETFs</a></li>
																									<li class="deselected"><a href="//etrading.eoption.com/Modules/Trading/Trade/Standard/Option/enter.php">Options</a></li>
																			<li class="deselected"><a href="//etrading.eoption.com/Modules/Trading/Trade/Standard/Fund/enter.php">Mutual Funds</a></li>
																			<li class="selected"><a href="//etrading.eoption.com/Modules/Trading/OrderStatus/combinedOrderStatus.php">Order Status</a></li>
											</ul>
				</li>
										<li>
					Research					<ul>
													<li class="deselected"><a href="//etrading.eoption.com/Modules/Research/SymbolLookup/symbolLookup.php">Find Symbol</a></li>
																			<li class="deselected"><a href="//etrading.eoption.com/Modules/Research/OptionChain/optionChain.php">Option Chains</a></li>
																											<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/marketOverview.php">Market Overview</a></li>
																						<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/marketMovers.php">Market Movers</a></li>
																						<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/companyProfile.php">Company Profile</a></li>
																						<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/advancedCharts.php">Advanced Charts</a></li>
																						<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/portfolio.php">Portfolios</a></li>
																						<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/alerts.php">Alerts</a></li>
																						<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/screener.php">Stock Screener</a></li>
																								</ul>
				</li>
						<li>
				Tools				<ul>
																					<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/AccountSharing/manage.php">Share Accounts</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/Users/Investor/Preferences/preferences.php">Preferences</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/Communication/MsgCenter/list.php">Message Center</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/MoneyTransfer/ACH/default.php">Transfer Money</a></li>
														</ul>
			</li>
		</ul>
	</div>
</div>
		<a id="headerLogout" href="//etrading.eoption.com/Modules/Login/logoutExpand.php">Log Out</a>
</div>

	<form id="fastQuoteForm">
	<div class="fq-box-l">
		<div class="fq-box-r">
			<div class="fq-box-c">
				<div id="fastQuoteGo" class="rolloverTarget"></div>
				<label id="fastQuoteLabel" class="quoteLabel">Quote:</label>
				<input type="text" name="fastQuoteSymbol" id="fastQuoteSymbol" />
			</div>
		</div>
	</div>
	<div id="fastQuoteLinks" class="slqLink"></div>
</form>	
<div id="fastQuotePanel" class="dialog">
	<h2 class="dialogHeader">
		<a href="#" id="fastQuoteClose">close</a>
		<a href="#" id="fastQuoteRefresh">refresh</a>
		<form id="fastQuoteForm2">
			<input type="text" name="fastQuoteSymbol2" id="fastQuoteSymbol2" />
		</form>
		<div id="fastQuoteLabel2">Symbol:</div>
		<div class="dialogTitle">Quote (<span id="fqTime"></span>)</div>
	</h2>
	<div class="dialogContent rolloverAnchor_quotebox">
		<table id="fastQuoteTable" class="dataTable">
			<thead>
				<tr>
					<th class="tLeft"></th>
					<th class="number">Symbol</th>
					<th class="number">Desc</th>
					<th class="number">Bid</th>
					<th class="number">Ask</th>
					<th class="number">Last</th>
					<th class="number">Change</th>
					<th class="number">% Change</th>
					<th class="number">Volume</th>
											<th class="number tRight"></th>
									</tr>
			</thead>
			<tbody>
									<tr id="fqQuote0">
						<td class="tLeft"></td>
						<td class="number"><span id="fqSymbol0"></span></td>
						<td class="description"><span id="fqDescription0"></span></td>
						<td class="number"><span id="fqBid0"></span></td>
						<td class="number"><span id="fqAsk0"></span></td>
						<td class="number"><span id="fqLast0"></span></td>
						<td class="number"><span id="fqChange0"></span></td>
						<td class="number"><span id="fqPctChange0"></span></td>
						<td class="number"><span id="fqVolume0"></span></td>
													<td class="number tRight">
								<span id="fqAction0">
									<div id="rolloverTarget_quote0" class="rolloverTarget"></div>
								</span>
							</td>
											</tr>
									<tr id="fqQuote1">
						<td class="tLeft"></td>
						<td class="number"><span id="fqSymbol1"></span></td>
						<td class="description"><span id="fqDescription1"></span></td>
						<td class="number"><span id="fqBid1"></span></td>
						<td class="number"><span id="fqAsk1"></span></td>
						<td class="number"><span id="fqLast1"></span></td>
						<td class="number"><span id="fqChange1"></span></td>
						<td class="number"><span id="fqPctChange1"></span></td>
						<td class="number"><span id="fqVolume1"></span></td>
													<td class="number tRight">
								<span id="fqAction1">
									<div id="rolloverTarget_quote1" class="rolloverTarget"></div>
								</span>
							</td>
											</tr>
									<tr id="fqQuote2">
						<td class="tLeft"></td>
						<td class="number"><span id="fqSymbol2"></span></td>
						<td class="description"><span id="fqDescription2"></span></td>
						<td class="number"><span id="fqBid2"></span></td>
						<td class="number"><span id="fqAsk2"></span></td>
						<td class="number"><span id="fqLast2"></span></td>
						<td class="number"><span id="fqChange2"></span></td>
						<td class="number"><span id="fqPctChange2"></span></td>
						<td class="number"><span id="fqVolume2"></span></td>
													<td class="number tRight">
								<span id="fqAction2">
									<div id="rolloverTarget_quote2" class="rolloverTarget"></div>
								</span>
							</td>
											</tr>
									<tr id="fqQuote3">
						<td class="tLeft"></td>
						<td class="number"><span id="fqSymbol3"></span></td>
						<td class="description"><span id="fqDescription3"></span></td>
						<td class="number"><span id="fqBid3"></span></td>
						<td class="number"><span id="fqAsk3"></span></td>
						<td class="number"><span id="fqLast3"></span></td>
						<td class="number"><span id="fqChange3"></span></td>
						<td class="number"><span id="fqPctChange3"></span></td>
						<td class="number"><span id="fqVolume3"></span></td>
													<td class="number tRight">
								<span id="fqAction3">
									<div id="rolloverTarget_quote3" class="rolloverTarget"></div>
								</span>
							</td>
											</tr>
									<tr id="fqQuote4">
						<td class="tLeft"></td>
						<td class="number"><span id="fqSymbol4"></span></td>
						<td class="description"><span id="fqDescription4"></span></td>
						<td class="number"><span id="fqBid4"></span></td>
						<td class="number"><span id="fqAsk4"></span></td>
						<td class="number"><span id="fqLast4"></span></td>
						<td class="number"><span id="fqChange4"></span></td>
						<td class="number"><span id="fqPctChange4"></span></td>
						<td class="number"><span id="fqVolume4"></span></td>
													<td class="number tRight">
								<span id="fqAction4">
									<div id="rolloverTarget_quote4" class="rolloverTarget"></div>
								</span>
							</td>
											</tr>
									<tr id="fqQuote5">
						<td class="tLeft"></td>
						<td class="number"><span id="fqSymbol5"></span></td>
						<td class="description"><span id="fqDescription5"></span></td>
						<td class="number"><span id="fqBid5"></span></td>
						<td class="number"><span id="fqAsk5"></span></td>
						<td class="number"><span id="fqLast5"></span></td>
						<td class="number"><span id="fqChange5"></span></td>
						<td class="number"><span id="fqPctChange5"></span></td>
						<td class="number"><span id="fqVolume5"></span></td>
													<td class="number tRight">
								<span id="fqAction5">
									<div id="rolloverTarget_quote5" class="rolloverTarget"></div>
								</span>
							</td>
											</tr>
													<tr id="fqUlQuote0">
						<td class="tLeft"></td>
						<td class="number"><span id="fqUlSymbol0"></span></td>
						<td class="description"><span id="fqUlDescription0"></span></td>
						<td class="number"><span id="fqUlBid0"></span></td>
						<td class="number"><span id="fqUlAsk0"></span></td>
						<td class="number"><span id="fqUlLast0"></span></td>
						<td class="number"><span id="fqUlChange0"></span></td>
						<td class="number"><span id="fqUlPctChange0"></span></td>
						<td class="number"><span id="fqUlVolume0"></span></td>
												<td class="number tRight">
							<span id="fqUlAction0">
								<div id="rolloverTarget_quoteUl0" class="rolloverTarget"></div>
							</span>
						</td>
											</tr>
									<tr id="fqUlQuote1">
						<td class="tLeft"></td>
						<td class="number"><span id="fqUlSymbol1"></span></td>
						<td class="description"><span id="fqUlDescription1"></span></td>
						<td class="number"><span id="fqUlBid1"></span></td>
						<td class="number"><span id="fqUlAsk1"></span></td>
						<td class="number"><span id="fqUlLast1"></span></td>
						<td class="number"><span id="fqUlChange1"></span></td>
						<td class="number"><span id="fqUlPctChange1"></span></td>
						<td class="number"><span id="fqUlVolume1"></span></td>
												<td class="number tRight">
							<span id="fqUlAction1">
								<div id="rolloverTarget_quoteUl1" class="rolloverTarget"></div>
							</span>
						</td>
											</tr>
									<tr id="fqUlQuote2">
						<td class="tLeft"></td>
						<td class="number"><span id="fqUlSymbol2"></span></td>
						<td class="description"><span id="fqUlDescription2"></span></td>
						<td class="number"><span id="fqUlBid2"></span></td>
						<td class="number"><span id="fqUlAsk2"></span></td>
						<td class="number"><span id="fqUlLast2"></span></td>
						<td class="number"><span id="fqUlChange2"></span></td>
						<td class="number"><span id="fqUlPctChange2"></span></td>
						<td class="number"><span id="fqUlVolume2"></span></td>
												<td class="number tRight">
							<span id="fqUlAction2">
								<div id="rolloverTarget_quoteUl2" class="rolloverTarget"></div>
							</span>
						</td>
											</tr>
									<tr id="fqUlQuote3">
						<td class="tLeft"></td>
						<td class="number"><span id="fqUlSymbol3"></span></td>
						<td class="description"><span id="fqUlDescription3"></span></td>
						<td class="number"><span id="fqUlBid3"></span></td>
						<td class="number"><span id="fqUlAsk3"></span></td>
						<td class="number"><span id="fqUlLast3"></span></td>
						<td class="number"><span id="fqUlChange3"></span></td>
						<td class="number"><span id="fqUlPctChange3"></span></td>
						<td class="number"><span id="fqUlVolume3"></span></td>
												<td class="number tRight">
							<span id="fqUlAction3">
								<div id="rolloverTarget_quoteUl3" class="rolloverTarget"></div>
							</span>
						</td>
											</tr>
									<tr id="fqUlQuote4">
						<td class="tLeft"></td>
						<td class="number"><span id="fqUlSymbol4"></span></td>
						<td class="description"><span id="fqUlDescription4"></span></td>
						<td class="number"><span id="fqUlBid4"></span></td>
						<td class="number"><span id="fqUlAsk4"></span></td>
						<td class="number"><span id="fqUlLast4"></span></td>
						<td class="number"><span id="fqUlChange4"></span></td>
						<td class="number"><span id="fqUlPctChange4"></span></td>
						<td class="number"><span id="fqUlVolume4"></span></td>
												<td class="number tRight">
							<span id="fqUlAction4">
								<div id="rolloverTarget_quoteUl4" class="rolloverTarget"></div>
							</span>
						</td>
											</tr>
									<tr id="fqUlQuote5">
						<td class="tLeft"></td>
						<td class="number"><span id="fqUlSymbol5"></span></td>
						<td class="description"><span id="fqUlDescription5"></span></td>
						<td class="number"><span id="fqUlBid5"></span></td>
						<td class="number"><span id="fqUlAsk5"></span></td>
						<td class="number"><span id="fqUlLast5"></span></td>
						<td class="number"><span id="fqUlChange5"></span></td>
						<td class="number"><span id="fqUlPctChange5"></span></td>
						<td class="number"><span id="fqUlVolume5"></span></td>
												<td class="number tRight">
							<span id="fqUlAction5">
								<div id="rolloverTarget_quoteUl5" class="rolloverTarget"></div>
							</span>
						</td>
											</tr>
							</tbody>
		</table>
		<div id="fastQuoteDetail">
			<h3 id="fqToggleDetail" class="svi-x"><img src="//etrading.eoption.com/Web/Images/Icons/plus.gif" alt="" id="fqDetailImg"> Detail</h3>
			<div id="fqDetailPanel">
				<table class="dataTable" id="fqDetailTable">
					<thead>
						<tr>
							<th>Price History</th>
							<th>Related Data</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<table class="dataTable">
									<tr>
										<th class="svi-x">Open</th>
										<td id="fqOpen" class="number"></td>
									</tr>
									<tr>
										<th class="svi-x">Prev Close</th>
										<td id="fqPrevClose" class="number"></td>
									</tr>
									<tr>
										<th class="svi-x">Day High</th>
										<td id="fqHigh" class="number"></td>
									</tr>
									<tr>
										<th class="svi-x">Day Low</th>
										<td id="fqLow" class="number"></td>
									</tr>
									<tr class="fqOptionOnly" style="display:none">
										<th class="svi-x">Contract High</th>
										<td id="fqContractHigh" class="number"></td>
									</tr>
									<tr class="fqOptionOnly" style="display:none">
										<th class="svi-x">Contract Low</th>
										<td id="fqContractLow" class="number"></td>
									</tr>
									<tr class="fqNonOptionOnly" style="display:none">
										<th class="svi-x">52-Week High</th>
										<td id="fqHigh52" class="number"></td>
									</tr>
									<tr class="fqNonOptionOnly" style="display:none">
										<th class="svi-x">52-Week Low</th>
										<td id="fqLow52" class="number"></td>
									</tr>
								</table>
							</td>
							<td>
								<table class="dataTable">
									<tr>
										<th class="svi-x">Bid Size</th>
										<td id="fqBidSize" class="number"></td>
									</tr>
									<tr>
										<th class="svi-x">Ask Size</th>
										<td id="fqAskSize" class="number"></td>
									</tr>
									<tr class="fqNonOptionOnly">
										<th class="svi-x">Yield</th>
										<td id="fqYield" class="number"></td>
									</tr>
									<tr class="fqNonOptionOnly">
										<th class="svi-x">Price/Earnings Ratio</th>
										<td id="fqPERatio" class="number"></td>
									</tr>
									<tr class="fqNonOptionOnly">
										<th class="svi-x">Earnings per Share</th>
										<td id="fqEarnShare" class="number"></td>
									</tr>
									<tr class="fqOptionOnly">
										<th class="svi-x">Exp Date</th>
										<td id="fqExpDate" class="number"></td>
									</tr>
									<tr class="fqStockOnly">
										<th class="svi-x">Dividend</th>
										<td id="fqDividend" class="number"></td>
									</tr>
									<tr>
										<th class="svi-x">Market</th>
										<td id="fqMarket" class="number"></td>
									</tr>
									<tr class="fqOptionOnly">
										<th class="svi-x">Open Interest</th>
										<td id="fqOpenInterest" class="number"></td>
									</tr>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<ul id="rolloverMenu_quote0" class="rolloverMenuOnTop"></ul>
<ul id="rolloverMenu_quoteUl0" class="rolloverMenuOnTop"></ul>
<ul id="rolloverMenu_quote1" class="rolloverMenuOnTop"></ul>
<ul id="rolloverMenu_quoteUl1" class="rolloverMenuOnTop"></ul>
<ul id="rolloverMenu_quote2" class="rolloverMenuOnTop"></ul>
<ul id="rolloverMenu_quoteUl2" class="rolloverMenuOnTop"></ul>
<ul id="rolloverMenu_quote3" class="rolloverMenuOnTop"></ul>
<ul id="rolloverMenu_quoteUl3" class="rolloverMenuOnTop"></ul>
<ul id="rolloverMenu_quote4" class="rolloverMenuOnTop"></ul>
<ul id="rolloverMenu_quoteUl4" class="rolloverMenuOnTop"></ul>
<ul id="rolloverMenu_quote5" class="rolloverMenuOnTop"></ul>
<ul id="rolloverMenu_quoteUl5" class="rolloverMenuOnTop"></ul>
			</div>
		
					<div id="privateNav">
				<ul id="mainNav">
	<li>
		Accounts		<ul>
							<li class="deselected"><a href="//etrading.eoption.com/Modules/Dashboard/dashboard.php">Summary</a></li>
										<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/AccountInfo/accountInfo.php">Account Info</a></li>
										<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/Balances/balances.php">Balances</a></li>
										<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/Positions/positions.php">Positions</a></li>
										<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/History/history.php">History</a></li>
										<li class="rightCap"><a href="//etrading.eoption.com/Modules/ThirdParty/EDocuments/ADPeDocs.php" target="eDocs">Statements</a></li>
															<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/CostBasis/Unrealized/unrealizedExpandable.php">Gain/Loss</a></li>
																				<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/AssetAllocation/assetAllocation.php">Asset Allocation</a></li>
																				</ul>
	</li>
				<li>
			Trading			<ul>
									<li class="deselected"><a href="//etrading.eoption.com/Modules/Trading/Trade/Standard/Stock/enter.php">Stocks &#43; ETFs</a></li>
																	<li class="deselected"><a href="//etrading.eoption.com/Modules/Trading/Trade/Standard/Option/enter.php">Options</a></li>
													<li class="deselected"><a href="//etrading.eoption.com/Modules/Trading/Trade/Standard/Fund/enter.php">Mutual Funds</a></li>
													<li class="selected"><a href="//etrading.eoption.com/Modules/Trading/OrderStatus/combinedOrderStatus.php">Order Status</a></li>
													<li class="deselected"><a href="//etrading.eoption.com/Modules/Lib/MVC/iframe.php?target=regal.autoTrade">Auto Trade</a></li>
							</ul>
		</li>
				<li>
			Research			<ul>
									<li class="deselected"><a href="//etrading.eoption.com/Modules/Research/SymbolLookup/symbolLookup.php">Find Symbol</a></li>
													<li class="deselected"><a href="//etrading.eoption.com/Modules/Research/OptionChain/optionChain.php">Option Chains</a></li>
				
															<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/marketOverview.php">Market Overview</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/marketMovers.php">Market Movers</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/companyProfile.php?symbol=AAPL">Company Profile</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/advancedCharts.php?symbol=AAPL">Advanced Charts</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/portfolio.php">Portfolios</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/alerts.php">Alerts</a></li>
																<li class="deselected"><a href="//etrading.eoption.com/Modules/ThirdParty/IDC/screener.php">Stock Screener</a></li>
																		                                    <li class="deselected"><a href="//etrading.eoption.com/Modules/OptionsPlay/optionsPlay.php">OptionsPlay</a></li>
                
			</ul>
		</li>
				<li class="rightCap">
			Tools			<ul>
																					<li class="deselected"><a href="//etrading.eoption.com/Modules/Accounts/AccountSharing/manage.php">Share Accounts</a></li>
													<li class="deselected"><a href="//etrading.eoption.com/Modules/Users/Investor/Preferences/preferences.php">Preferences</a></li>
													<li class="deselected"><a href="//etrading.eoption.com/Modules/Communication/MsgCenter/list.php">Message Center</a></li>
													<li class="deselected"><a href="//etrading.eoption.com/Modules/MoneyTransfer/ACH/default.php">Transfer Money</a></li>
											</ul>
		</li>
	            <li class="deselected"><a href="//etrading.eoption.com/Modules/OptionsPlay/optionsPlay.php">OptionsPlay</a></li>
    </ul>			</div>
				
		
		<div id="pageBody">
			<div id="pageTitle">
				<div id="shortcutMenu">
				<a href="#" id="shortcutPrint">print</a>
	 
	 
			<a href="//etrading.eoption.com/Modules/Communication/MsgCenter/list.php" id="shortcutAlerts">alerts<span id="numAlerts"></span></a>
	</div>
									<h1 class="svi-x">Order Status</h1>
				                			</div>

			<div id="message">
			    											</div>

						
			
			<div id="contentTop">
							</div>

			<div id="content">
															<form action="//etrading.eoption.com/Modules/Trading/OrderStatus/combinedOrderStatus.php" method="post" id="orderStatusFilterForm" class="filterForm">
	<dl>
		<dt><label for="currentAccount">Account</label></dt>
		<dd><select id="currentAccount" name="currentAccount">
	<option selected="selected" value="0">17547918 - trading account</option>
</select>
</dd>
	</dl>
	<dl>
		<dt><label for="ordStatus">Status</label></dt>
		<dd>
			<select id="ordStatus" name="ordStatus">
				<option selected="selected" value="ALL">All</option>
<option value="2">Executed</option>
<option value="A">Pending</option>
<option value="0">Open</option>
<option value="4">Cancelled</option>
<option value="8">Rejected</option>
<option value="C">Expired</option>
			</select>
		</dd>
	</dl>
	<dl>
		<dt><label for="securityType">Type</label></dt>
		<dd>
			<select id="securityType" name="securityType">
				<option selected="selected" value="ALL">All</option>
<option value="CS">Stock</option>
<option value="OPT">Option</option>
<option value="MF">Mutual Fund</option>
			</select>
		</dd>
	</dl>	
			<dl>
			<dt><label for="groupType">Filter By</label></dt>
			<dd>
				<select id="groupType" name="groupType">
					<option selected="selected" value="ALL">All Orders</option>
<option value="CO">Contingent Orders</option>
<option value="UCO">Untriggered Contingent Orders</option>
<option value="NCO">All But Contingent Orders</option>
				</select>
			</dd>
		</dl>
	    		<dl>
			<dt><label for="orderType">Order Type</label></dt>
			<dd>
				<select id="orderType" name="orderType">
					<option selected="selected" value="ALL">All</option>
<option value="1">Market</option>
<option value="2">Limit</option>
<option value="3">Stop</option>
<option value="4">Stop Limit</option>
<option value="5">Market on Close</option>
				</select>
			</dd>
		</dl>
		<div class="buttons">
		<input type="submit" id="orderStatusSubmit" class="submit mediumButton" value="Refresh" />	</div>
</form>


<table  class="dataTable orderStatusTable table table-striped table-bordered" id="combinedOrderStatusTable">
	<thead>
		<tr>
			<th class=" tLeft"></th>
<th class=" securityType jsSortable" ext:qtip=""><img class="sortedImage" src="//etrading.eoption.com/Web/Images/clear.gif" /><span class="headerText">Asset Class</span></th>
<th class=" transaction jsSortable" ext:qtip=""><img class="sortedImage" src="//etrading.eoption.com/Web/Images/clear.gif" /><span class="headerText">Action</span></th>
<th class=" orderQty number jsSortable" ext:qtip=""><img class="sortedImage" src="//etrading.eoption.com/Web/Images/clear.gif" /><span class="headerText">Original<br />Quantity</span></th>
<th class=" leavesQty number jsSortable" ext:qtip=""><img class="sortedImage" src="//etrading.eoption.com/Web/Images/clear.gif" /><span class="headerText">Remaining<br />Quantity</span></th>
<th class=" description jsSortable" ext:qtip=""><img class="sortedImage" src="//etrading.eoption.com/Web/Images/clear.gif" /><span class="headerText">Security Name</span></th>
<th class=" symbol jsSortable jsSortedAsc" ext:qtip=""><img class="sortedImage" src="//etrading.eoption.com/Web/Images/clear.gif" /><span class="headerText">Symbol/<br />CUSIP</span></th>
<th class=" ordTypeAndPrice jsSortable" ext:qtip=""><img class="sortedImage" src="//etrading.eoption.com/Web/Images/clear.gif" /><span class="headerText">Price</span></th>
<th class=" timeInForce jsSortable" ext:qtip=""><img class="sortedImage" src="//etrading.eoption.com/Web/Images/clear.gif" /><span class="headerText">Time In Force</span></th>
<th class=" execInst jsSortable" ext:qtip=""><img class="sortedImage" src="//etrading.eoption.com/Web/Images/clear.gif" /><span class="headerText">Instructions</span></th>
<th class=" tradeDate jsSortable" ext:qtip=""><img class="sortedImage" src="//etrading.eoption.com/Web/Images/clear.gif" /><span class="headerText">Date &amp; Time</span></th>
<th class=" ordStatus jsSortable" ext:qtip=""><img class="sortedImage" src="//etrading.eoption.com/Web/Images/clear.gif" /><span class="headerText">Order Status</span></th>
<th class=" lastShares number" ext:qtip="">Executed <br /> Quantity</th>
<th class=" lastPx number jsSortable" ext:qtip=""><img class="sortedImage" src="//etrading.eoption.com/Web/Images/clear.gif" /><span class="headerText">Executed <br /> Price</span></th>
<th class=" action" ext:qtip="">Actions</th>
<th class=" tRight"></th>

		</tr>
	</thead>
	<tbody>
		<tr id="row_0_leg0" class="evenrow"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Buy to Close</td>
<td class="number orderQty">3</td>
<td class="number leavesQty">3</td>
<td class="description">IWM Feb 20 2015 108.00 Put</td>
<td class="symbolext:qtip=&quot;IWM Feb 20 2015 108.00 Put&quot; symbol"><span class="symbolLink marketHoverOver"><span symbol="IWM PUT 108.00 2015/02/20" ext:qtip="IWM PUT 108.00 2015/02/20">IWM...</span></span></td>
<td rowspan="2" class="ordTypeAndPrice sort_450000000030&quot;&gt; ordTypeAndPrice">Debit $0.03</td>
<td rowspan="2" class="timeInForce">GTC</td>
<td rowspan="2" class="execInst"></td>
<td rowspan="2" class="tradeDate sort_1421427632 tradeDate">01/16/2015 12:00 ET</td>
<td rowspan="2" class="ordStatus">Open</td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td rowspan="2" class="action"><div id="rolloverTarget_orderStatus0" class="rolloverTarget"></div><ul id="rolloverMenu_orderStatus0" class="rolloverMenu"><li class="editOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Edit/ComplexOptions/enter.php?orderID=SVI-22429051&groupID=SVI-22429051">Edit</a></li>
<li class="cancelOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Cancel/ComplexOptions/preview.php?orderID=SVI-22429051&groupID=SVI-22429051">Cancel</a></li>
</ul></td>
<td class="tRight"></td></tr><tr id="row_0_leg1" class="evenrow keepWithLast"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Sell to Close</td>
<td class="number orderQty">3</td>
<td class="number leavesQty">3</td>
<td class="description">IWM Feb 20 2015 106.00 Put</td>
<td class="symbolext:qtip=&quot;IWM Feb 20 2015 106.00 Put&quot; symbol"><span class="symbolLink marketHoverOver"><span symbol="IWM PUT 106.00 2015/02/20" ext:qtip="IWM PUT 106.00 2015/02/20">IWM...</span></span></td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td class="tRight"></td></tr><tr id="row_1_leg0" class="oddrow"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Buy to Close</td>
<td class="number orderQty">3</td>
<td class="number leavesQty">3</td>
<td class="description">IWM Feb 27 2015 105.00 Put</td>
<td class="symbolext:qtip=&quot;IWM Feb 27 2015 105.00 Put&quot; symbol"><span class="symbolLink marketHoverOver"><span symbol="IWM PUT 105.00 2015/02/27" ext:qtip="IWM PUT 105.00 2015/02/27">IWM...</span></span></td>
<td rowspan="2" class="ordTypeAndPrice sort_450000000030&quot;&gt; ordTypeAndPrice">Debit $0.03</td>
<td rowspan="2" class="timeInForce">GTC</td>
<td rowspan="2" class="execInst"></td>
<td rowspan="2" class="tradeDate sort_1421427580 tradeDate">01/16/2015 11:59 ET</td>
<td rowspan="2" class="ordStatus">Open</td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td rowspan="2" class="action"><div id="rolloverTarget_orderStatus1" class="rolloverTarget"></div><ul id="rolloverMenu_orderStatus1" class="rolloverMenu"><li class="editOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Edit/ComplexOptions/enter.php?orderID=SVI-22429031&groupID=SVI-22429031">Edit</a></li>
<li class="cancelOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Cancel/ComplexOptions/preview.php?orderID=SVI-22429031&groupID=SVI-22429031">Cancel</a></li>
</ul></td>
<td class="tRight"></td></tr><tr id="row_1_leg1" class="oddrow keepWithLast"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Sell to Close</td>
<td class="number orderQty">3</td>
<td class="number leavesQty">3</td>
<td class="description">IWM Feb 27 2015 103.00 Put</td>
<td class="symbolext:qtip=&quot;IWM Feb 27 2015 103.00 Put&quot; symbol"><span class="symbolLink marketHoverOver"><span symbol="IWM PUT 103.00 2015/02/27" ext:qtip="IWM PUT 103.00 2015/02/27">IWM...</span></span></td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td class="tRight"></td></tr><tr id="row_2_leg0" class="evenrow"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Buy to Close</td>
<td class="number orderQty">4</td>
<td class="number leavesQty">4</td>
<td class="description">IWM Mar 06 2015 108.50 Put</td>
<td class="symbolext:qtip=&quot;IWM Mar 06 2015 108.50 Put&quot; symbol"><span class="symbolLink marketHoverOver"><span symbol="IWM PUT 108.50 2015/03/06" ext:qtip="IWM PUT 108.50 2015/03/06">IWM...</span></span></td>
<td rowspan="2" class="ordTypeAndPrice sort_450000000030&quot;&gt; ordTypeAndPrice">Debit $0.03</td>
<td rowspan="2" class="timeInForce">GTC</td>
<td rowspan="2" class="execInst"></td>
<td rowspan="2" class="tradeDate sort_1423156242 tradeDate">02/05/2015 12:10 ET</td>
<td rowspan="2" class="ordStatus">Open</td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td rowspan="2" class="action"><div id="rolloverTarget_orderStatus2" class="rolloverTarget"></div><ul id="rolloverMenu_orderStatus2" class="rolloverMenu"><li class="editOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Edit/ComplexOptions/enter.php?orderID=SVI-22541697&groupID=SVI-22541697">Edit</a></li>
<li class="cancelOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Cancel/ComplexOptions/preview.php?orderID=SVI-22541697&groupID=SVI-22541697">Cancel</a></li>
</ul></td>
<td class="tRight"></td></tr><tr id="row_2_leg1" class="evenrow keepWithLast"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Sell to Close</td>
<td class="number orderQty">4</td>
<td class="number leavesQty">4</td>
<td class="description">IWM Mar 06 2015 106.50 Put</td>
<td class="symbolext:qtip=&quot;IWM Mar 06 2015 106.50 Put&quot; symbol"><span class="symbolLink marketHoverOver"><span symbol="IWM PUT 106.50 2015/03/06" ext:qtip="IWM PUT 106.50 2015/03/06">IWM...</span></span></td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td class="tRight"></td></tr><tr class=" oddrow oddrow"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Sell to Close</td>
<td class="number orderQty">2</td>
<td class="number leavesQty">2</td>
<td class="description">SPY Feb 06 2015 205.50 Call</td>
<td class="symbol symbol"><span class="symbolLink marketHoverOver"><span symbol="SPY CALL 205.50 2015/02/06" ext:qtip="SPY CALL 205.50 2015/02/06">SPY...</span></span></td>
<td class="ordTypeAndPrice sort_500000001200&quot;&gt; ordTypeAndPrice">Limit $1.20</td>
<td class="timeInForce">Day</td>
<td class="execInst"></td>
<td class="tradeDate sort_1423160558 tradeDate">02/05/2015 13:22 ET</td>
<td class="ordStatus">Open</td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td class="action"><div id="rolloverTarget_orderStatus3" class="rolloverTarget"></div><ul id="rolloverMenu_orderStatus3" class="rolloverMenu"><li class="editOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Edit/Standard/Option/enter.php?orderID=SVI-22542433%2F2">Edit</a></li>
<li class="cancelOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Cancel/Standard/Option/preview.php?orderID=SVI-22542433%2F2">Cancel</a></li>
</ul></td>
<td class="tRight"></td></tr><tr class=" evenrow evenrow"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Sell to Close</td>
<td class="number orderQty">2</td>
<td class="number leavesQty">2</td>
<td class="description">SPY Feb 06 2015 205.50 Call</td>
<td class="symbol symbol"><span class="symbolLink marketHoverOver"><span symbol="SPY CALL 205.50 2015/02/06" ext:qtip="SPY CALL 205.50 2015/02/06">SPY...</span></span></td>
<td class="ordTypeAndPrice sort_500000001100&quot;&gt; ordTypeAndPrice">Limit $1.10</td>
<td class="timeInForce">Day</td>
<td class="execInst"></td>
<td class="tradeDate sort_1423159405 tradeDate">02/05/2015 13:03 ET</td>
<td class="ordStatus">Replaced</td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td class="action"></td>
<td class="tRight"></td></tr><tr class=" oddrow oddrow"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Buy to Open</td>
<td class="number orderQty">2</td>
<td class="number leavesQty">0</td>
<td class="description">SPY Feb 06 2015 205.50 Call</td>
<td class="symbol symbol"><span class="symbolLink marketHoverOver"><span symbol="SPY CALL 205.50 2015/02/06" ext:qtip="SPY CALL 205.50 2015/02/06">SPY...</span></span></td>
<td class="ordTypeAndPrice sort_49&quot;&gt; ordTypeAndPrice">Market</td>
<td class="timeInForce">Day</td>
<td class="execInst"></td>
<td class="tradeDate sort_1423159325 tradeDate">02/05/2015 13:02 ET</td>
<td class="ordStatus">Executed</td>
<td class="number lastShares">2</td>
<td class="number lastPx">$0.8800</td>
<td class="action"></td>
<td class="tRight"></td></tr><tr id="row_6_leg0" class="evenrow"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Buy to Close</td>
<td class="number orderQty">4</td>
<td class="number leavesQty">4</td>
<td class="description">SPY Feb 20 2015 191.50 Put</td>
<td class="symbolext:qtip=&quot;SPY Feb 20 2015 191.50 Put&quot; symbol"><span class="symbolLink marketHoverOver"><span symbol="SPY PUT 191.50 2015/02/20" ext:qtip="SPY PUT 191.50 2015/02/20">SPY...</span></span></td>
<td rowspan="2" class="ordTypeAndPrice sort_450000000030&quot;&gt; ordTypeAndPrice">Debit $0.03</td>
<td rowspan="2" class="timeInForce">GTC</td>
<td rowspan="2" class="execInst"></td>
<td rowspan="2" class="tradeDate sort_1423156050 tradeDate">02/05/2015 12:07 ET</td>
<td rowspan="2" class="ordStatus">Open</td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td rowspan="2" class="action"><div id="rolloverTarget_orderStatus6" class="rolloverTarget"></div><ul id="rolloverMenu_orderStatus6" class="rolloverMenu"><li class="editOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Edit/ComplexOptions/enter.php?orderID=SVI-22541657&groupID=SVI-22541657">Edit</a></li>
<li class="cancelOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Cancel/ComplexOptions/preview.php?orderID=SVI-22541657&groupID=SVI-22541657">Cancel</a></li>
</ul></td>
<td class="tRight"></td></tr><tr id="row_6_leg1" class="evenrow keepWithLast"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Sell to Close</td>
<td class="number orderQty">4</td>
<td class="number leavesQty">4</td>
<td class="description">SPY Feb 20 2015 189.50 Put</td>
<td class="symbolext:qtip=&quot;SPY Feb 20 2015 189.50 Put&quot; symbol"><span class="symbolLink marketHoverOver"><span symbol="SPY PUT 189.50 2015/02/20" ext:qtip="SPY PUT 189.50 2015/02/20">SPY...</span></span></td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td class="tRight"></td></tr><tr id="row_7_leg0" class="oddrow"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Buy to Close</td>
<td class="number orderQty">4</td>
<td class="number leavesQty">4</td>
<td class="description">SPY Feb 27 2015 192.00 Put</td>
<td class="symbolext:qtip=&quot;SPY Feb 27 2015 192.00 Put&quot; symbol"><span class="symbolLink marketHoverOver"><span symbol="SPY PUT 192.00 2015/02/27" ext:qtip="SPY PUT 192.00 2015/02/27">SPY...</span></span></td>
<td rowspan="2" class="ordTypeAndPrice sort_450000000030&quot;&gt; ordTypeAndPrice">Debit $0.03</td>
<td rowspan="2" class="timeInForce">GTC</td>
<td rowspan="2" class="execInst"></td>
<td rowspan="2" class="tradeDate sort_1423156106 tradeDate">02/05/2015 12:08 ET</td>
<td rowspan="2" class="ordStatus">Open</td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td rowspan="2" class="action"><div id="rolloverTarget_orderStatus7" class="rolloverTarget"></div><ul id="rolloverMenu_orderStatus7" class="rolloverMenu"><li class="editOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Edit/ComplexOptions/enter.php?orderID=SVI-22541668&groupID=SVI-22541668">Edit</a></li>
<li class="cancelOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Cancel/ComplexOptions/preview.php?orderID=SVI-22541668&groupID=SVI-22541668">Cancel</a></li>
</ul></td>
<td class="tRight"></td></tr><tr id="row_7_leg1" class="oddrow keepWithLast"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Sell to Close</td>
<td class="number orderQty">4</td>
<td class="number leavesQty">4</td>
<td class="description">SPY Feb 27 2015 190.00 Put</td>
<td class="symbolext:qtip=&quot;SPY Feb 27 2015 190.00 Put&quot; symbol"><span class="symbolLink marketHoverOver"><span symbol="SPY PUT 190.00 2015/02/27" ext:qtip="SPY PUT 190.00 2015/02/27">SPY...</span></span></td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td class="tRight"></td></tr><tr id="row_8_leg0" class="evenrow"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Buy to Close</td>
<td class="number orderQty">3</td>
<td class="number leavesQty">3</td>
<td class="description">SPY Feb 20 2015 191.00 Put</td>
<td class="symbolext:qtip=&quot;SPY Feb 20 2015 191.00 Put&quot; symbol"><span class="symbolLink marketHoverOver"><span symbol="SPY PUT 191.00 2015/02/20" ext:qtip="SPY PUT 191.00 2015/02/20">SPY...</span></span></td>
<td rowspan="2" class="ordTypeAndPrice sort_450000000030&quot;&gt; ordTypeAndPrice">Debit $0.03</td>
<td rowspan="2" class="timeInForce">GTC</td>
<td rowspan="2" class="execInst"></td>
<td rowspan="2" class="tradeDate sort_1421428141 tradeDate">01/16/2015 12:09 ET</td>
<td rowspan="2" class="ordStatus">Open</td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td rowspan="2" class="action"><div id="rolloverTarget_orderStatus8" class="rolloverTarget"></div><ul id="rolloverMenu_orderStatus8" class="rolloverMenu"><li class="editOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Edit/ComplexOptions/enter.php?orderID=SVI-22429186&groupID=SVI-22429186">Edit</a></li>
<li class="cancelOrderLink"><a href="//etrading.eoption.com/Modules/Trading/Cancel/ComplexOptions/preview.php?orderID=SVI-22429186&groupID=SVI-22429186">Cancel</a></li>
</ul></td>
<td class="tRight"></td></tr><tr id="row_8_leg1" class="evenrow keepWithLast"><td class="tLeft"></td><td class="securityType">Option</td>
<td class="transaction">Sell to Close</td>
<td class="number orderQty">3</td>
<td class="number leavesQty">3</td>
<td class="description">SPY Feb 20 2015 189.00 Put</td>
<td class="symbolext:qtip=&quot;SPY Feb 20 2015 189.00 Put&quot; symbol"><span class="symbolLink marketHoverOver"><span symbol="SPY PUT 189.00 2015/02/20" ext:qtip="SPY PUT 189.00 2015/02/20">SPY...</span></span></td>
<td class="number lastShares">0</td>
<td class="number lastPx"></td>
<td class="tRight"></td></tr>
	</tbody>
	<tfoot>
		
	</tfoot>
</table>

<ul id="footnotes" class="svi-x">
			</ul>

<div id="below_footnotes">
	</div>												</div>

			<div id="contentBottom">
							</div>

		</div>
					<div id="footer">
				<br/>
<p>Online trading has inherent risks due to loss of online services or delays from system performance,
risk parameters, market conditions, and erroneous or unavailable market data.
Investors should understand these and additional risks before trading.
Options involve risk and are not suitable for all investors.
Prior to trading options, you must be approved for options trading and read the
<a href="http://www.optionsclearing.com/about/publications/character-risks.jsp">Characteristics and Risks of Standardized Options</a>
provided by the Options Clearing Corporation (OCC), available in Adobe Acrobat PDF format.
A copy may also be requested via email at <a href="mailto:support@eoption.com">support@eoption.com</a>
or via mail to eOption, 950 Milwaukee Ave., Ste. 102, Glenview, IL 60025.
If you are considering using margin, you must have sufficient buying power and eligible securities in your account.
It is important that you fully understand the risks involved in trading securities on margin.
<a href="http://www.eoption.com/margin_disclosure.html">Click here</a> for the risks involved with trading securities in a margin account.
</p>
<br/>
<p>
eOption brokerage services are provided by Regal Securities, Inc., member <a href="http://www.finra.org/">FINRA</a> / <a href="http://www.sipc.org/">SIPC</a>,
950 Milwaukee Ave., Suite 102, Glenview, IL, 60025.
Custody and other services provided by FirstSouthwest Company, member <a href="http://www.finra.org/">FINRA</a> / <a href="http://www.sipc.org/">SIPC</a>,
325 North St. Paul Street, Suite 800, Dallas, TX  75201.
</p> 			</div>
				
			<!-- Piwik -->
	<script type="text/javascript">
		var _paq = _paq || [];
		//added to force ajax requests so onload doesn"t get held up by piwik
		_paq.push(["setRequestMethod", "POST"]);
		_paq.push(["trackPageView"]);
		_paq.push(["enableLinkTracking"]);
		(function() {
			var u=(("https:" == document.location.protocol) ? "https" : "http") + "://piwik.scivantage.com/";
			_paq.push(["setTrackerUrl", u+"piwik.php"]);
			_paq.push(["setSiteId", 2]);
			var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
			g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
		})();
	</script>
	<noscript><p><img src="//piwik.scivantage.com/piwik.php?idsite=2" style="border:0;" alt="" /></p></noscript>
	<!-- End Piwik Code -->

		</body>
</html>';

		return $html;
	}
	
}

/* End File */