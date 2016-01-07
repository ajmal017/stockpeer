<?php

namespace App\Http\WebSockets;

use DB;
use App;
use Auth;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\Http\HttpServerInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Guzzle\Http\Message\RequestInterface;
  
class Core implements MessageComponentInterface 
{
  use Orders;
  use Queue;  
  use Quotes;
  use Timesales;
  use Autotrade;
  use Positions;  
  
  protected $clients;
  protected $server = null;

  //
  // Construct...
  //
  public function __construct($server) 
  {
    $this->server = $server;
    $this->clients = new \SplObjectStorage;
  }  
    
  //
  // On Open.
  //
  public function onOpen(ConnectionInterface $conn) 
  {    
    $this->clients->attach($conn);
    $conn->send(json_encode([ 'timestamp' => date('n/j/y g:i:s a'), 'type' => 'Status:connected' ]));
    $this->server->info("New connection! ({$conn->resourceId})");
  }

  //
  // On Message
  //
  public function onMessage(ConnectionInterface $from, $msg) 
  {
    $msg = json_decode($msg, true);
    
    // Was this a ping request.
    if($msg['type'] == 'ping')
    {
      $from->last_ping = time();
      $from->send(json_encode([ 'type' => 'pong' ]));  
    }  
    
    // Was this an websocket api key?
    if(($msg['type'] == 'ws-key') && (! empty($msg['data'])))
    {
      // Get the user.
      $user = DB::table('Users')->where('UsersWebSocketKey', $msg['data'])->first();

      // Store the user in the array.
      $from->user = $user;
      
      // Clear the UsersWebSocketKey so we do not double use it.
      DB::table('Users')->where('UsersId', $user->UsersId)->update([ 'UsersWebSocketKey' => '']);
    }
    
    // Register to get timesales data.
    if($msg['type'] == 'timesales')
    {
      $from->timesales = $msg['data'];
      $this->get_timesales();
    }
  }

  //
  // On Close.
  //
  public function onClose(ConnectionInterface $conn) 
  {
    $this->clients->detach($conn);
    $this->server->info("Connection {$conn->resourceId} has disconnected");    
  }

  //
  // On Error.
  //
  public function onError(ConnectionInterface $conn, \Exception $e) 
  {
    $this->server->error("Connection: An error has occurred: {$e->getMessage()}");
    $conn->close();
  }
}

/* End File */