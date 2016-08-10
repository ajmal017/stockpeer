package main

import (
  _ "github.com/go-sql-driver/mysql"
  "database/sql"
  "fmt"
)

// Users Table
type UsersRow struct {
  UsersId int
  UsersFirst string
  UsersLast string
  UsersEmail string
  UsersWatchList string
  UsersTradierAccountId string   
}

// Positions Table
type PositionsRow struct {
  SymbolsShort string
  PositionsId int
  PositionsTradeGroupId int
  PositionsAssetId int
  PositionsBrokerId string
  PositionsSymbolId int
  PositionsType string   
} 

//
// Get positions by account id
//
func db_get_positions_by_account(id int) ([]PositionsRow) {

  var Result []PositionsRow
 
  cont := fmt.Sprint(Config.Mysql.Username, ":", Config.Mysql.Password, "@/", Config.Mysql.Database, "?charset=utf8")
  
  db, err := sql.Open("mysql", cont)
    
  if err != nil {
    panic(err)
  }
  
  // Close DB.
  defer db.Close()   
  
  // query
  query := fmt.Sprintf("SELECT SymbolsShort, PositionsId, PositionsTradeGroupId, PositionsAssetId, PositionsBrokerId, PositionsSymbolId, PositionsType FROM Positions LEFT JOIN Symbols ON PositionsSymbolId = SymbolsId WHERE  PositionsStatus='Open' AND PositionsAccountId=%d", id)
  rows, err := db.Query(query)
  
  if err != nil {
    panic(err)
  }
  
  // Loop through the rows of the result
  for rows.Next() {
    var r PositionsRow

    err = rows.Scan(&r.SymbolsShort, &r.PositionsId, &r.PositionsTradeGroupId, &r.PositionsAssetId, &r.PositionsBrokerId, &r.PositionsSymbolId, &r.PositionsType)
  
    if err != nil {
      panic(err)
    }
    
    Result = append(Result, r)   
  }
  
  // Return the data.
  return Result
    
}

// 
// Get and return all the rows in the Users Table
//
func db_get_users() ([]UsersRow) {
 
  var UsersResult []UsersRow
 
  cont := fmt.Sprint(Config.Mysql.Username, ":", Config.Mysql.Password, "@/", Config.Mysql.Database, "?charset=utf8")
  
  db, err := sql.Open("mysql", cont)
    
  if err != nil {
    panic(err)
  }
  
  // Close DB.
  defer db.Close()   
  
  // query
  rows, err := db.Query("SELECT UsersId, UsersFirst, UsersLast, UsersEmail, UsersWatchList, UsersTradierAccountId FROM Users")
  
  if err != nil {
    panic(err)
  }

  // Loop through the rows of the result
  for rows.Next() {
    var r UsersRow

    err = rows.Scan(&r.UsersId, &r.UsersFirst, &r.UsersLast, &r.UsersEmail, &r.UsersWatchList, &r.UsersTradierAccountId)
  
    if err != nil {
      panic(err)
    }
    
    UsersResult = append(UsersResult, r)   
  } 
  
  // Return the data.
  return UsersResult
  
}

//
// Get one user.
//
func db_get_user_by_id(id int) (UsersRow) {
 
  var r UsersRow
 
  cont := fmt.Sprint(Config.Mysql.Username, ":", Config.Mysql.Password, "@/", Config.Mysql.Database, "?charset=utf8")
  
  db, err := sql.Open("mysql", cont)
    
  if err != nil {
    panic(err)
  }
  
  // Close DB.
  defer db.Close()  
  
  // query
  query := fmt.Sprintf("SELECT UsersId, UsersFirst, UsersLast, UsersEmail, UsersWatchList FROM Users WHERE UsersId=%d", id)
  
  rows, err := db.Query(query)
  
  if err != nil {
    panic(err)
  }

  // Loop through the rows of the result
  for rows.Next() {

    err = rows.Scan(&r.UsersId, &r.UsersFirst, &r.UsersLast, &r.UsersEmail, &r.UsersWatchList)
  
    if err != nil {
      panic(err)
    }
    
    return r
  }

  
  // Return with no data.
  return r  
}

/* End File */