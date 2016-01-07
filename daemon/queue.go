package main

import (
  //"log"
  "fmt"
  "os/exec" 
  "encoding/json" 
  "github.com/iwanbk/gobeanstalk"
)

type Queue struct {}

type QueueJob struct {
  Job string `json:"job"`
  Data QueuePayload `json:"data"`
}

type QueuePayload struct {
  UsersId int `json:"UsersId,string"`
  PayLoad map[string]interface{} `json:"Payload"`
}

/*
  Expected Queue format for input.
{
  "job": "Backtesting:progress",
  "data": {
    "UsersId": 1,
    "Payload": {}
  } 
}
*/

//
// Listen to the beanstalk queue.
//
func (t *Queue) do_queue(websocket_users_channel map[int] chan string) {
  conn, err := gobeanstalk.Dial("localhost:11300")
  
  if err != nil {
    panic(err)
  }
  
  conn.Watch("stockpeer.com")
  conn.Watch("stockpeer.com.websocket")  
  
  for {
    
    j, err := conn.Reserve()
    
    if err != nil {
      panic(err)
    }
    
    //log.Printf("id:%d, body:%s\n", j.ID, string(j.Body))
    
    // Get the job from json Just so we can get the action and user id.
    var data QueueJob
    err = json.Unmarshal(j.Body, &data)  
    
    if err != nil {
      panic(err)
    }    
    
    // Some jobs we do special things with.
    switch data.Job {
      
      // Fork and run the backtest.
      case "Backtesting:start":
        go t.run_backtest(data.Data.PayLoad["BackTestsId"].(string));
        
       // Send job out the websocket to the server.
      default:
        websocket_users_channel[data.Data.UsersId] <- string(j.Body)
      
    }
    
    // Delete job from queue
    err = conn.Delete(j.ID)
    
    if err != nil {
      panic(err)
    }
    
  }  
}

//
// Run a backtest.
//
func (t *Queue) run_backtest(id string) {
  
  println("Running Backtest Id #1 (stockpeer:backtest ", id, ")")
  
  // Run command
  output, err := exec.Command("php", "../artisan", "-vvv", "stockpeer:backtest", id).Output();
  //err := cmd.Run()
  
  if err != nil {
    panic(err)
  } 
  
  // Print Output to console
  fmt.Printf("%s", output)
    
}

/* End File */