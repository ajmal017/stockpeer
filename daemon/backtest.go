package main

import ( 
  "fmt"
  "time"
  "math"
  "errors"
)

type Backtest struct {
  Balance float32
}

// OptionsEod Table
type OptionsEodRow struct {
  OptionsEodSymbolLast float32
  OptionsEodExpiration string
  OptionsEodStrike float32
  OptionsEodLast float32
  OptionsEodBid float32
  OptionsEodAsk float32 
} 

// CreditSpreadScreens
type CreditSpreadScreens struct {
  SellLegType string
  SellLegStrike float32
  SellLegExpiration string
  BuyLegAsk float32
  BuyLegType string
  BuyLegStrike float32
  BuyLegExpiration string
  BuyLegBid float32
} 

// Screens 
type Screens struct {
  Date string
  Credit float32
  MidPoint float32
  BuyStrike float32
  BuyExpire string
  SellStrike float32
  SellExpire string
}

// Screener return
type ScreenerReturn struct {
  Date string
  Chain []OptionsEodRow
  Screens []Screens
}

//
// Run a backtest.
//
func (t *Backtest) run() {
  
  // Set Balance
  t.Balance = 66.66
  
  // Load the option chains and screen for trades into memory
  screens, dates := t.load_chain_and_trades()
  
    




  for _, row := range dates {
    
    for _, row2 := range screens[row].Screens {

      println("Strikes: ", fmt.Sprintf("%g", row2.BuyStrike), " / ", fmt.Sprintf("%g", row2.SellStrike), " Expire: ", row2.BuyExpire, " Date: ", row2.Date, " Credit $", fmt.Sprintf("%g", row2.Credit), " Midpoint $", fmt.Sprintf("%g", row2.MidPoint))

    }
    
  }  


    
    
    
}

//
// Load option chains and trades into memory
//
func (t *Backtest) load_chain_and_trades() (map[string]ScreenerReturn, []string) {
  
  // Channels
  var screen_channel = make(chan []Screens)
  
  // Screen the different options by quote date and get back a list of possible options to trade.
  screens, dates := t.get_options_chain_by_date()
  
  // Loop through the dates and find possible trades
  for _, row := range dates {
    go t.screen_for_trades(row, screens[row], screen_channel)
  }
  
  // Wait for all the goroutines to come back with results.
  count := len(dates)
  
  for {
    
    rt := <- screen_channel
    
    if len(rt) > 0 {
      
      // hack for bug: http://stackoverflow.com/questions/24221616/golang-assignment-of-mapstringstruct-error
      tmp := screens[rt[0].Date]
      tmp.Screens = rt
      screens[rt[0].Date] = tmp
      
    }
    
    count--
    
    if count <= 0 {
      break
    }
    
  }
  
  // Return data.
  return screens, dates
}

//
// Screen trades. Loop through the chain and return a list of possible trades
//
func (t *Backtest) screen_for_trades(date string, screen ScreenerReturn, screen_channel chan []Screens) {
    
  width := 2
  var rt []Screens
  
  // Get the percent away
  diff := (screen.Chain[0].OptionsEodSymbolLast * 0.04)
  put_strike_away := math.Floor(float64(screen.Chain[0].OptionsEodSymbolLast) - float64(diff))
      
  for _, row := range screen.Chain {
    
    // Skip unwanted trades by strike
    if float64(row.OptionsEodStrike) > put_strike_away {
      continue
    }

    // Get the option that is away from this strike by our width
    buy_leg, err := t.get_option_by_strike_expire((row.OptionsEodStrike - float32(width)), row.OptionsEodExpiration, screen)
    
    if err != nil {
      continue
    }
    
    // Figure out the credit spread amount.
    credit := row.OptionsEodBid - buy_leg.OptionsEodAsk;
    buy_cost := row.OptionsEodAsk - buy_leg.OptionsEodBid;
    mid_point := (credit + buy_cost) / 2	

    // See if we get enough credit to make it worth it.
    if credit <= 0.15 {
      continue
    }    
    
    //println("Strike: $", fmt.Sprintf("%g", buy_leg.OptionsEodStrike), " Expire: ", buy_leg.OptionsEodExpiration, " Date: ", date, " Credit $", fmt.Sprintf("%g", credit), " Midpoint $", fmt.Sprintf("%g", mid_point))
    
    //println("Last: $", fmt.Sprintf("%g", row.OptionsEodSymbolLast), " Strike: $", fmt.Sprintf("%g", row.OptionsEodStrike))

    // Build struct to return
    tmp := Screens{
      Date: date,
      Credit: credit,
      MidPoint: mid_point,
      BuyStrike: buy_leg.OptionsEodStrike,
      BuyExpire: buy_leg.OptionsEodExpiration,
      SellStrike: row.OptionsEodStrike,
      SellExpire: row.OptionsEodExpiration}

    // Add to return array.
    rt = append(rt, tmp)
  }
  
  // Return found tades
  screen_channel <- rt
}

//
// Get option by strike and expire
//
func (t *Backtest) get_option_by_strike_expire(strike float32, expire string, screen ScreenerReturn) (OptionsEodRow, error) {
  
  for _, row := range screen.Chain {
    
    // Skip unwanted trades by expire
    if row.OptionsEodExpiration != expire {
      continue
    }

    // Look for the strike we are after
    if row.OptionsEodStrike == strike {
      return row, nil
    }
  
  }  
  
  var r OptionsEodRow
  return r, errors.New("Option not found")
}

//
// Get options data by date.
//
func (t *Backtest) get_options_chain_by_date() (map[string]ScreenerReturn, []string) {
  
  var dates []string
  var screener_channel = make(chan ScreenerReturn)
  
  // A map to keep track of the screened results.
  screens := make(map[string]ScreenerReturn)
  
  // Here we get all the dates we are considering
  rows, err := db.Query("SELECT OptionsEodQuoteDate FROM OptionsEod WHERE OptionsEodQuoteDate >= \"2012-01-01\" AND OptionsEodQuoteDate <= \"2015-12-31\" AND OptionsEodSymbolId = 1 GROUP BY OptionsEodQuoteDate") 
  
  // Loop through the rows of the result
  for rows.Next() {
    var date string

    err = rows.Scan(&date)
  
    if err != nil {
      panic(err)
    }
  
    // Start a goroutine and screen for options that meet our filter. 
    go t.screen_for_options(screener_channel, date)
    
    // Date count
    dates = append(dates, date)
  }     
  
  //println(len(dates))
  
  // Keep track of the number of daily options we return.
  var returns int
  returns = 0
  
  // We know how many screen_for_options should return. Here we 
  // loop getting data back from the goroutines until we get all the 
  // daily option data we need.
  for {
    rt := <- screener_channel

    // Add the return to the dates map
    screens[rt.Date] = rt

    returns++

    //println(rt.Date)
    //println(returns, " of ", len(dates))
    
    // Did we get back all the data we were expecting?
    if(returns >= len(dates)) {
      break
    } 
  }
  
  // return data.
  return screens, dates 
   
}

//
// Screen for options - Here we screen for options that meet our filter requirments per day.
//
func (t *Backtest) screen_for_options(channel chan ScreenerReturn, date string) {  
  
  var Result []OptionsEodRow
  
  // We do not need expire dates too far out.
  now, _ := time.Parse("2006-01-02", date)
  future := time.Hour * 24 * 45 // 45 days
  diff := now.Add(future)
  max_expire := diff.Format("2006-01-02")
  
  // query
  rows, err := db.Query(fmt.Sprintf("SELECT OptionsEodSymbolLast, OptionsEodExpiration, OptionsEodStrike, OptionsEodLast, OptionsEodBid, OptionsEodAsk FROM OptionsEod WHERE OptionsEodType=\"put\" AND OptionsEodQuoteDate=\"%s\" AND OptionsEodExpiration <= \"%s\" AND OptionsEodSymbolId=1", date, max_expire))
  
  if err != nil {
    panic(err)
  }
  
  // Loop through the rows of the result
  for rows.Next() {
    var r OptionsEodRow

    err = rows.Scan(&r.OptionsEodSymbolLast, &r.OptionsEodExpiration, &r.OptionsEodStrike, &r.OptionsEodLast, &r.OptionsEodBid, &r.OptionsEodAsk)
  
    if err != nil {
      panic(err)
    }
    
    Result = append(Result, r)   
  }
  
  // Setup the return
  rt := ScreenerReturn{
    Date: date,
    Chain: Result}
  
  // Return the data out the channel
  channel <- rt
  
  //println(date)
  //fmt.Println(Result)

}  

/* End File */