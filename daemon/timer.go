//
// Maybe this library is silly. But it makes life 
// easier on the website front. We just send pings
// on a certain loop. This allows us to not do settimeouts 
// within the web app. With ajax based page loading, and laptops
// that go to sleep having the timer via the websocket is cleaner.
// Lastly, this is nice so all devices ask for data at the same time.
// So if an ipad and a desktop were next to each other both would have
// the same looking chart (within reason).
//

package main

import (
  "time"
  "encoding/json"  
)

type Timer struct {}

type TimerSend struct {
  Type string `json:"type"`
  Timestamp string `json:"timestamp"`
  Data string `json:"data"`
}

//
// Get orders from Tradier
//
func (t *Timer) Do60Seconds(UsersId int, UsersTradierAccountId string, channel_websocket chan string) {
  
   for {
      
    // Sleep then do it again.
    time.Sleep(time.Second * 60)
    
    // Send ping
    s := TimerSend {
      Type: "Timmer:60seconds",
      Timestamp: time.Now().Format("01/02/06 3:04:05 pm"),
      Data: ""}
    
    b, err := json.Marshal(s)
    
    if err != nil {
      println("TimerSend: json.Marshal")
      println(err)
    } 
        
    // Send to the channel for websocket processing.  
    channel_websocket <- string(b)    
    
  }
}

/* End File */