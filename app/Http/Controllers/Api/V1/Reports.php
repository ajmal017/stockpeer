<?php

namespace App\Http\Controllers\Api\V1;

use App;
use Auth;
use Input;
use Crypt;
use Request;

class Reports extends \Cloudmanic\LaravelApi\Controller 
{ 
  //
  // Income Statement
  //
  public function income_statement()
  {
    $rt = [
      'Income' => [
        'Put_Credit_Spread' => 0,
        'Call_Credit_Spread' => 0,
        'Weekly_Put_Credit' => 0,
        'Long_Stock_Trade' => 0,
        'Weekly_Put_Credit_Spread' => 0,
        'Long_Option_Trade' => 0,
        'Futures_Day_Trade' => 0,
        'Dividends' => 0,
        'Interest' => 0,
        'Loan_Interest' => 0,        
        'Peer_To_Peer_Lending' => 0, 
        'Realty_Shares_Distribution' => 0,                
        'Other' => 0,
        'Total' => 0                
      ],
      
      'Trading_Expenses' => [
        'Put_Credit_Spread_Commissions' => 0,
        'Call_Credit_Spread_Commissions' => 0,        
        'Weekly_Put_Credit_Commissions' => 0,
        'Weekly_Put_Credit_Spread_Commissions' => 0, 
        'Long_Option_Trade_Commissions' => 0,
        'Long_Stock_Trade_Commissions' => 0,
        'Futures_Day_Trade_Commissions' => 0,
        'Other_Commissions' => 0,
        'Total' => 0                
      ],
      
      'Expenses' => [
        'Total' => 0
      ],
      
      'Total' => 0
    ];
    
    // -------------- Get Income from Trade Groups (new way) -------- //
    $tradegroups_models = App::make('App\Models\TradeGroups');
    $tradegroups_models->set_col('TradeGroupsStatus', 'Closed');
    $tradegroups_models->set_order('TradeGroupsEnd', 'asc');

    // Add Start date
    if(Input::get('start'))
    {
      $tradegroups_models->set_col('TradeGroupsEnd', date('Y-m-d', strtotime(Input::get('start'))), '>=');
    }

    // Add End date
    if(Input::get('end'))
    {
      $tradegroups_models->set_col('TradeGroupsEnd', date('Y-m-d', strtotime(Input::get('end'))), '<=');
    }

    // Get trades
    $trades = $tradegroups_models->get();
    
    foreach($trades AS $key => $row)
    {
      $type = str_ireplace(' ', '_', $row['TradeGroupsType']);
      $rt['Income'][$type] += ($row['TradeGroupsClose'] - $row['TradeGroupsOpen']);
      $rt['Trading_Expenses'][$type . '_Commissions'] += ($row['TradeGroupsOpenCommission'] + $row['TradeGroupsCloseCommission']);
      $rt['Income']['Total'] += ($row['TradeGroupsClose'] - $row['TradeGroupsOpen']);
      $rt['Trading_Expenses']['Total'] += ($row['TradeGroupsOpenCommission'] + $row['TradeGroupsCloseCommission']);
    }
    
    // -------------- Get income from trades (old way) -------------- //
    $trades_models = App::make('App\Models\Trades');
    $trades_models->set_col('TradesStatus', 'Closed');
    $trades_models->set_order('TradesDateStart', 'asc');

    // Add Start date
    if(Input::get('start'))
    {
      $trades_models->set_col('TradesDateEnd', date('Y-m-d', strtotime(Input::get('start'))), '>=');
    }

    // Add End date
    if(Input::get('end'))
    {
      $trades_models->set_col('TradesDateEnd', date('Y-m-d', strtotime(Input::get('end'))), '<=');
    }

    // Get trades
    $trades = $trades_models->get();
    
    foreach($trades AS $key => $row)
    {
      $type = str_ireplace(' ', '_', $row['TradesType']);
      $rt['Income'][$type] += ($row['TradesEndPrice'] - $row['TradesStartPrice']);
      $rt['Trading_Expenses'][$type . '_Commissions'] += ($row['TradesStartCommission'] + $row['TradesEndCommission']);
      $rt['Income']['Total'] += ($row['TradesEndPrice'] - $row['TradesStartPrice']);
      $rt['Trading_Expenses']['Total'] += ($row['TradesStartCommission'] + $row['TradesEndCommission']);
    }
    
    // ---- Add in the income ------------ //
    
    $income_model = App::make('App\Models\Income');

    // Add Start date
    if(Input::get('start'))
    {
      $income_model->set_col('IncomeDate', date('Y-m-d', strtotime(Input::get('start'))), '>=');
    }

    // Add End date
    if(Input::get('end'))
    {
      $income_model->set_col('IncomeDate', date('Y-m-d', strtotime(Input::get('end'))), '<=');
    }
    
    // Loop through the income
    foreach($income_model->get() AS $key => $row)
    {  
      $rt['Income']['Total'] += $row['IncomeAmount'];
          
      switch($row['IncomeType'])
      {
        case 'Dividend':
          $rt['Income']['Dividends'] += $row['IncomeAmount'];        
        break;
        
        case 'Interest':
          $rt['Income']['Interest'] += $row['IncomeAmount'];        
        break;  

        case 'Loan Interest':
          $rt['Income']['Loan_Interest'] += $row['IncomeAmount'];        
        break; 

        case 'P2P Interest':
          $rt['Income']['Peer_To_Peer_Lending'] += $row['IncomeAmount'];        
        break;

        case 'Realty Shares Distribution':
          $rt['Income']['Realty_Shares_Distribution'] += $row['IncomeAmount'];        
        break;
        
        default:
          $rt['Income']['Other'] += $row['IncomeAmount'];         
        break;      
      }
    }
    
    // ---- Add in the Expenses From the expenses table. ------------ //  
    
    $expenses_models = App::make('App\Models\Expenses');  
    $expenses_models->set_order('ExpensesCategory', 'asc');
    
    // Add Start date
    if(Input::get('start'))
    {
      $expenses_models->set_col('ExpensesDate', date('Y-m-d', strtotime(Input::get('start'))), '>=');
    }

    // Add End date
    if(Input::get('end'))
    {
      $expenses_models->set_col('ExpensesDate', date('Y-m-d', strtotime(Input::get('end'))), '<=');
    }    
    
    // Loop through and add the expenses
    foreach($expenses_models->get() AS $key => $row)
    {
      if(! isset($rt['Expenses'][$row['ExpensesCategory']]))
      {
        $rt['Expenses'][$row['ExpensesCategory']] = 0;
      }
      
      $rt['Expenses']['Total'] += $row['ExpensesAmount'];
      $rt['Expenses'][$row['ExpensesCategory']] += $row['ExpensesAmount'];
    }
    
    // ----------- Format Number ---------------- //
    
    // Format income
    foreach($rt['Income'] AS $key => $row)
    {
      $rt['Income'][$key] = number_format($rt['Income'][$key], 2, '.', '');
    }    

    // Format trading expenses
    foreach($rt['Trading_Expenses'] AS $key => $row)
    {
      $rt['Trading_Expenses'][$key] = number_format($rt['Trading_Expenses'][$key], 2, '.', '');
    }   

    // Format expenses
    foreach($rt['Expenses'] AS $key => $row)
    {
      $rt['Expenses'][$key] = number_format($rt['Expenses'][$key], 2, '.', '');
    }

    $rt['Total'] = number_format($rt['Income']['Total'] - $rt['Trading_Expenses']['Total'] - $rt['Expenses']['Total'], 2, '.', '');
    
    // Return happy.
    return $this->api_response($rt);
  }
}

/* End File */