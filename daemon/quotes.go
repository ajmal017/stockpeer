package main

import (
  "time"
  "fmt"
  "strings"  
  "net/http"       
  "encoding/json" 
  "database/sql"
  "io/ioutil"   
  _ "github.com/go-sql-driver/mysql"
)

type Quotes struct {
  DB *sql.DB
  List []string
}

type QuoteSend struct {
  Type string `json:"type"`
  Timestamp string `json:"timestamp"`
  Data QuoteStruct `json:"data"`
}

type QuotesStruct struct {
  Quotes QuotesQuote `json:"quotes"` 
}

type QuotesQuote struct {
  Quote []QuoteStruct `json"quote"`
}

type QuoteStruct struct {
  Symbol string `json:"symbol"`
  Last json.Number `json:"last"`
  Bid json.Number `json:"bid"`
  Ask json.Number `json:"ask"`
  Description string `json:"description"`
  ChangePercentage json.Number `json:"change_percentage"`    
}

type RateLimitsStruct struct {
  Used string `json:"used"`
  Expire string `json:"expire"`
  Allowed string `json:"allowed"`
  Available string `json:"available"`    
}

//
// Get quotes from Tradier
//
func (t *Quotes) DoQuotes(UsersId int, channel_websocket chan string) {
  
  // Get the tradier API key for this user.
  api_key, _ := t.get_tradier_api_key(UsersId) 
  
  // Open db connection.
  t.start_mysql_connection()
  defer t.DB.Close()   

  // Loop through getting quotes over and over again.
  for { 
    
    // No need to get quotes if the market is closed.
    open := is_market_open();
    if ! open {
      time.Sleep(2 * time.Second)
      continue
    }
      
    // Build a list of symbols we want quotes on
    symbs := t.get_symbols(UsersId)
    
    // Create URL request.
    url := fmt.Sprintf("https://api.tradier.com/v1/markets/quotes?symbols=%s", symbs)
    
    // Setup http client
    client := &http.Client{}    
    
    // Setup api request
    req, _ := http.NewRequest("GET", url, nil)
    req.Header.Set("Accept", "application/json")
    req.Header.Set("Authorization", fmt.Sprint("Bearer ", api_key)) 

    res, err := client.Do(req)
        
    if err != nil {
      println("do_quote: client.Do(req)")
      println(err)
      time.Sleep(time.Second * 3)
      continue     
    }        
    
    // Make sure the api responded with a 200
    if res.StatusCode != 200 {
      time.Sleep(time.Second * 3)
      continue
    }    
       
    // Read the data we got.
    body, err := ioutil.ReadAll(res.Body)
    
    if err != nil {
      println("do_quote: ReadAll")
      println(err)
    }     
  
    // UnJsonize the data. We do this mainly to not send too much information down the wire.
    // Also we send each quote one at a time instead of all the data together 
    // (not really sure why we do this....should be rethought)
    var data QuotesStruct
    json.Unmarshal(body, &data)

    // Loop through and send the quptes.
    for _, quote := range data.Quotes.Quote { 
            
      s := QuoteSend {
        Type: "Quotes:get_quote",
        Timestamp: time.Now().Format("01/02/06 3:04:05 pm"),
        Data: quote}
      
      b, err := json.Marshal(s)

      if err != nil {
        println("do_quote: json.Marshal")
        println(err)
      }       
      
      // Send to the channel for processing.  
      channel_websocket <- string(b)
      
    }
    
    // Close body.
    res.Body.Close()
    
    // Sleep then do it again.
    time.Sleep(time.Second * 2)
  }  
}

//
// Start mysql connection
//
func (t *Quotes) start_mysql_connection() () {
  
  var err error

  cont := fmt.Sprint(Config.Mysql.Username, ":", Config.Mysql.Password, "@/", Config.Mysql.Database, "?charset=utf8")
  t.DB, err = sql.Open("mysql", cont)
  
  if err != nil {
    panic(err)
  }  
  
}

//
// Return the symbols from current orders we have in.
//
func (t *Quotes) get_order_symbols(id int, syb_list *[]string) {
  
  // Update the user object so we can use the latest from the watch list.
  query := fmt.Sprintf("SELECT OrdersSymbol, OrdersLeg1OptionSymbol, OrdersLeg2OptionSymbol, OrdersLeg3OptionSymbol, OrdersLeg4OptionSymbol FROM Orders WHERE (OrdersUpdatedAt > TIMESTAMP(DATE_SUB(NOW(), INTERVAL 2 day)) OR OrdersStatus = 'Open') AND OrdersAccountId=%d", id)
  
  rows, err := t.DB.Query(query)
  
  if err != nil {
    panic(err)
  }
  
  // Loop through the rows of the result
  var OrdersSymbol string
  var OrdersLeg1OptionSymbol string
  var OrdersLeg2OptionSymbol string
  var OrdersLeg3OptionSymbol string
  var OrdersLeg4OptionSymbol string
  
  for rows.Next() {
    err = rows.Scan(&OrdersSymbol, &OrdersLeg1OptionSymbol, &OrdersLeg2OptionSymbol, &OrdersLeg3OptionSymbol, &OrdersLeg4OptionSymbol)
  
    if err != nil {
      panic(err)
    }

    if len(OrdersLeg1OptionSymbol) > 0 {
      *syb_list = append(*syb_list, OrdersLeg1OptionSymbol) 
    }
    
    if len(OrdersLeg2OptionSymbol) > 0 {
      *syb_list = append(*syb_list, OrdersLeg2OptionSymbol) 
    }
    
    if len(OrdersLeg3OptionSymbol) > 0 {
      *syb_list = append(*syb_list, OrdersLeg3OptionSymbol) 
    }
    
    if len(OrdersLeg4OptionSymbol) > 0 {
      *syb_list = append(*syb_list, OrdersLeg4OptionSymbol) 
    }
    
    if len(OrdersSymbol) > 0 {
      *syb_list = append(*syb_list, OrdersSymbol) 
    }                
  } 
    
}

//
// Return the watch list for a particular user
//
func (t *Quotes) get_watch_list(id int, syb_list *[]string) {
  
  // Update the user object so we can use the latest from the watch list.
  query := fmt.Sprintf("SELECT UsersWatchList FROM Users WHERE UsersId=%d", id)
  
  rows, err := t.DB.Query(query)
  
  if err != nil {
    panic(err)
  }
  
  // Loop through the rows of the result
  var UsersWatchList string
  
  for rows.Next() {
    err = rows.Scan(&UsersWatchList)
  
    if err != nil {
      panic(err)
    }
  } 
  
  if err := json.Unmarshal([]byte(UsersWatchList), syb_list); err != nil {
    println("json:", err)
  }   
    
}

//
// Return a list of open positions.
//
func (t *Quotes) get_open_positions(id int, syb_list *[]string) {

  // query
  query := fmt.Sprintf("SELECT SymbolsShort FROM Positions LEFT JOIN Symbols ON PositionsSymbolId = SymbolsId WHERE  PositionsStatus='Open' AND PositionsAccountId=%d", id)
  rows, err := t.DB.Query(query)
  
  if err != nil {
    panic(err)
  }
  
  // Loop through the rows of the result
  var SymbolsShort string  
  
  // Loop through the rows of the result
  for rows.Next() {

    err = rows.Scan(&SymbolsShort)
  
    if err != nil {
      panic(err)
    }
    
    *syb_list = append(*syb_list, SymbolsShort)   
  }

}

//
// Get the tradier API key. If we ever make this a multi tenant app we need to make this smarter.
//
func (t *Quotes) get_tradier_api_key(user_id int) (string, string) {
  
  // Loop through the users in the config to find the key
  for _, row := range Config.Users {
    if row.UsersId == user_id {
      return row.UsersTradierToken, ""
    }
  }
  
  // Return with error
  return "", "key not found"
}

//
// Manage the list of quotes we need to get
//
func (t *Quotes) get_symbols(UsersId int) (string) {
  
  var tmp []string
  var syb_list []string
  var seen = map[string]bool{}

  // Get watchlist symbols
  t.get_watch_list(UsersId, &tmp)
      
  // Get a list of symbols that we currently have positions on.
  t.get_open_positions(UsersId, &tmp)
  
  // Get symbols from current orders.
  t.get_order_symbols(UsersId, &tmp)
  
  // Remove any duplicates
  for _, row := range tmp {
    
    if seen[row] == true {
      continue
    } else {
      seen[row] = true
      syb_list = append(syb_list, row)
    }
    
  } 
  
  // Create a list of CSV symbols to pass to the Tradier API.
  symbs := strings.Join(syb_list, ",") 
 
  // Return CSV of symbols
  return symbs  
  
}

/* End File */