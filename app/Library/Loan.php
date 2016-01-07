<?php

//
// By: Spicer Matthews
// Email: spicer@cloudmanic.com
// Date: 11/9/2015
// Description: Cool library for reviewing a loan.
//

namespace App\Library;

use DateTime;

class Loan
{
  public $principal = 0;
  public $rate = 0;
  public $months = 0;

  // 
  // Construct.
  //  
  public function __construct($principal = 0, $rate = 0, $months = 0)
  {
    $this->set_loan_parms($principal, $rate, $months);
  }

  //
  // Set loan parms.
  //
  public function set_loan_parms($principal, $rate, $months)
  {
    $this->rate = $rate;
    $this->months = $months;     
    $this->principal = $principal;   
  }  
  
  //
  // Get loan payment.
  //
  public function get_loan_payment()
  {
    $r = ($this->rate / 12) * 0.01;    
    $part1 = pow((1 + $r), $this->months);
    $part2 = $r * $part1;
    $part3 = $part1 - 1;
    return round($this->principal * ($part2 / $part3), 2);    
  }
  
  //
  // Get month by month table.
  //
  public function get_month_table()
  {
    $data = [];
    
    $interest_paid = 0;
    
    $principal = $this->principal;
    
    $payment = $this->get_loan_payment();
    
    // Loop through each month
    for($month = 1; $month <= $this->months; $month++)
    {
      $interest = ($principal * ($this->rate / 100)) / 12;
      $interest_paid = $interest_paid + $interest;
      $principal_paid = $payment - $interest;
      $principal = $principal - $principal_paid; 
      
      $data[] = [
        'month' => $month,
        'payment' => $payment,
        'interest' => round($interest, 2),
        'total_interest' => round($interest_paid, 2),
        'principal' => round($principal, 2),
        'principal_paid' => round($principal_paid, 2)
      ];
    }
    
    // Return happy
    return $data;        
  }
}

/* End File */