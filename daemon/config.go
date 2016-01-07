package main

import (
  "fmt"
  "os"
  "encoding/json"  
)

var Config Configs

type Configs struct {
  Mysql Mysql  
  Users []User
}

type Mysql struct {
  Host string
  Database string
  Username string
  Password string
}

type User struct {
  UsersId int
  UsersFirst string
  UsersLast string
  UsersEmail string
  UsersTradierToken string
}

//
// Read our conf.json file and load our settings.
//
func load_configs() {
  file, _ := os.Open("conf.json")
  decoder := json.NewDecoder(file)
  
  Config = Configs{}
  
  err := decoder.Decode(&Config)
  
  if err != nil {
    fmt.Println("Config.go Error:", err)
  }   
}

/* End File */