package main

import (
  //"bufio"
  //"strings"
  //"net/url"
  "fmt"   
  "net/http"
  "encoding/json"
  "database/sql" 
  _ "github.com/go-sql-driver/mysql" 
  "github.com/gorilla/websocket" 
)

type Websockets struct {
  Connections []WebsocketStruct
}

type WebsocketStruct struct {
  UsersId int
  TimeSales string
  Connection *websocket.Conn
}

//
// Check Origin
//
func (t *Websockets) check_origin(r *http.Request) bool {
  
  origin := r.Header.Get("Origin")
  
  if origin == "http://127.0.0.1:8080" {
    return true;
  }

  if origin == "http://localhost:8080" {
    return true;
  }

  if origin == "https://stockpeer.dev" {
    return true;
  }

  if origin == "https://stockpeer.com" {
    return true;
  }
  
  return false;
}


//
// Handle new connections to the app.
//
func (t *Websockets) websocket_connection(w http.ResponseWriter, r *http.Request) {

  // setup upgrader
  var upgrader = websocket.Upgrader{
    ReadBufferSize:  1024,
    WriteBufferSize: 1024,
    CheckOrigin: t.check_origin,
  }

  // Upgrade connection
  conn, err := upgrader.Upgrade(w, r, nil)

  if err != nil {
    fmt.Println(err)
    return
  }

  println("New Websocket Connection")
  
  // Add the connection to our connection array
  var r_con WebsocketStruct
  r_con.Connection = conn
  r_con.UsersId = 0
  t.Connections = append(t.Connections, r_con)
  
  // Send a message that we are connected.
  conn.WriteMessage(websocket.TextMessage, []byte("{\"type\":\"Status:connected\"}"))
  
  // Start a goroutine for handling reading messages from the client.
  go t.do_websocket_reading(conn, &t.Connections[len(t.Connections)-1])

}

//
// Do reading message.
//
func (t *Websockets) do_websocket_reading(conn *websocket.Conn, obj *WebsocketStruct) {  
  
  for {
    
    // Block waiting for a message to arrive
    _, message, err := conn.ReadMessage()
		
    if err != nil {
			println("read:", err)
      break
    }
    
    // Json decode message.
    var data map[string]interface{}
    if err := json.Unmarshal(message, &data); err != nil {
			println("json:", err)
      break      
    }
    
    // Is this a ping?
    if data["type"] == "ping" {
      conn.WriteMessage(websocket.TextMessage, []byte("{\"type\":\"pong\"}"))
    }

    // Is this a ws-key? Use the key to get the user Id.
    if data["type"] == "ws-key" {        
      obj.UsersId = t.get_user_id_by_websocket_key(data["data"].(string))
    }
    
  }
  
}

//
// Send data out the websocket
//
func (t *Websockets) do_websocket_sending(channel_websocket chan string, user_id int) {
  
  for {

    message := <-channel_websocket
    
    for _, row := range t.Connections {
      
      // We only send data for the user we passed in.
      if row.UsersId == user_id {
        row.Connection.WriteMessage(websocket.TextMessage, []byte(message));
      }

    }

  }
	  
}

// 
// Get UserId by websocket key
//
func (t *Websockets) get_user_id_by_websocket_key(key string) (int) {
  
  cont := fmt.Sprint(Config.Mysql.Username, ":", Config.Mysql.Password, "@/", Config.Mysql.Database, "?charset=utf8")
  
  db, err := sql.Open("mysql", cont)
    
  if err != nil {
    panic(err)
  }
  
  // Close DB.
  defer db.Close()    
  
  // query
  query := fmt.Sprintf("SELECT UsersId FROM Users WHERE UsersWebSocketKey=\"%s\"", key)
  rows, err := db.Query(query)
  
  if err != nil {
    panic(err)
  }
  
  // Loop through the rows of the result
  var UsersId int  
  
  // Loop through the rows of the result
  for rows.Next() {

    err = rows.Scan(&UsersId)
  
    if err != nil {
      panic(err)
    }
    
    return UsersId
  }  
  
  
  // Error
  return UsersId
}

/* End File */