package main

import ( 
  "fmt"
  "time"
  "sync"
  "runtime"
  "net/http"
  "io/ioutil"
  "encoding/json" 
)

var market_open bool = false

type ApiClock struct {
  Clock ApiClockObj `json:clock`
}

type ApiClockObj struct {
  State string `json:state`
  Date string `json:date`
  Timestamp int `json:timestamp`
  NextState string `json:"next_state"`
  NextChange string `json:"next_change"`
  Description string `json:description`
}

//
// Check to see if the market is open.
//
func do_market_state() {
  
  var mutex = &sync.Mutex{}
  
  // Get the tradier API key for this user. TODO: (for now we just use the first users)
  api_key, _ := get_tradier_api_key(1)  
  
  // Setup http client
  client := &http.Client{} 
  
  // Check to see if the market is open every 20 seconds.
  for {
    
    // Setup and send api request
    req, _ := http.NewRequest("GET", "https://api.tradier.com/v1/markets/clock", nil)
    req.Header.Set("Accept", "application/json")
    req.Header.Set("Authorization", fmt.Sprint("Bearer ", api_key))     
    
    res, err := client.Do(req)
        
    if err != nil {
      println("do_market_state: client.Do")
      println(err)
      time.Sleep(time.Second * 3)
      continue      
    } 
    
    // Make sure the api responded with a 200
    if res.StatusCode != 200 {
      time.Sleep(time.Second * 3)
      continue
    }         
    
    body, err := ioutil.ReadAll(res.Body)
    
    if err != nil {
      println("do_market_state: ReadAll")
      println(err)
    }     
  
    var data ApiClock
    json.Unmarshal(body, &data)
    
    // Set the flag
    mutex.Lock()
  
    if data.Clock.State == "closed" {
      market_open = false
    } else {
      market_open = true
    }
    
    mutex.Unlock()
    
    runtime.Gosched()
    
    // Wait for next checking time.
    time.Sleep(20 * time.Second)
    
  }
  
}

//
// Is the market open?
//
func is_market_open() (bool) {  
    
  var mutex = &sync.Mutex{}
  
  mutex.Lock()
  open := market_open
  mutex.Unlock()
  
  return open
}

//
// Get the tradier API key. If we ever make this a multi tenant app we need to make this smarter.
//
func get_tradier_api_key(user_id int) (string, string) {
  
  // Loop through the users in the config to find the key
  for _, row := range Config.Users {
    if row.UsersId == user_id {
      return row.UsersTradierToken, ""
    }
  }
  
  // Return with error
  return "", "key not found"
}

/* End File */