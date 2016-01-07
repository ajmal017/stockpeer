package main

import ( 
  //"os"
  "fmt" 
  "runtime"
  "net/http"
  "database/sql"
  _ "github.com/go-sql-driver/mysql"    
)

// Global variable to share it between main and goroutines
var db *sql.DB

//
// Main....
//
func main() {  
  
  // Setup CPU stuff.
  runtime.GOMAXPROCS(runtime.NumCPU())
  
  // Setup vars
  var u Queue
  var q Quotes
  var o Orders  
  var t Timer
  var p Positions    
  var w Websockets
  var websocket_users_channel = make(map[int] chan string)
  
  // Stuff we have to load / do before anything else.
  load_configs()
  
  // Setup the DB connection 
  setup_db()
  
  // Close DB.
  defer db.Close()

  // Get all the users in our multitenant system.  
  users := db_get_users()

  // Manage the state or our app.
  go do_market_state()

  // Create threads per user
  for _, row := range users { 
    
    // Setup channels for this user.
    websocket_users_channel[row.UsersId] = make(chan string, 5000)
    
    // Setup the websocket sending channel for this user.
    go w.do_websocket_sending(websocket_users_channel[row.UsersId], row.UsersId)
       
    // Setup the goroutine for getting quotes
    go q.DoQuotes(row.UsersId, websocket_users_channel[row.UsersId])
    
    // Setup the goroutine for getting orders
    go o.DoOrders(row.UsersId, row.UsersTradierAccountId, websocket_users_channel[row.UsersId])    	

    // Setup the goroutine for getting positions
    go p.DoPositions(row.UsersId, row.UsersTradierAccountId, websocket_users_channel[row.UsersId])
    
    // Setup the goroutine for websocket timers. - 60 seconds
    go t.Do60Seconds(row.UsersId, row.UsersTradierAccountId, websocket_users_channel[row.UsersId])
        
  }  
  
  // Create threads for listening to the message queue
  go u.do_queue(websocket_users_channel)  
   
  // Setup websocket
	http.HandleFunc("/ws/core", w.websocket_connection)
	
	err := http.ListenAndServe(":8080", nil)
	
	if err != nil {
		panic("ListenAndServe: " + err.Error())
	}
}  

//
// Setup the Global mysql connection.
//
func setup_db() {
  
  // Setup mysql connection 
  var err error  
  db, err = sql.Open("mysql", fmt.Sprint(Config.Mysql.Username, ":", Config.Mysql.Password, "@/", Config.Mysql.Database, "?charset=utf8"))   
   
  if err != nil {
    panic(err.Error())
  }

  // Don't go nuts with database connections.
  db.SetMaxOpenConns(50)

  // Make sure our database connection is good.
  err = db.Ping() 
  
  if err != nil {
    panic(err.Error())
  }
  
}

/* End File */