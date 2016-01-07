<?php 
  
namespace App\Console\Commands;

use App\Http\WebSockets\Core;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\Http\HttpServerInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Guzzle\Http\Message\RequestInterface;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class WebSockets extends Command 
{
	protected $name = 'stockpeer:websocket';
	protected $description = 'Start the websocket server.';

  //
  // Execute the console command.
  //
	public function fire()
	{
    $this->info("\n*********** Starting Websocket On Port 8080 ***********\n");

    // (Example of how we fired off server side events)
    // Queue::pushOn('stockpeer.com.backtests', 'update', [ 'spicer' => 'matthews' ]);

    // Setup connection handlers.
    $core = new Core($this);

    // Setup event loops to monitor for new server side events.
    $loop = \React\EventLoop\Factory::create();
    $loop->addPeriodicTimer(1, [ $core, 'get_queue_msg' ]);     
    $loop->addPeriodicTimer(60, [ $core, 'get_timesales' ]); 
    $loop->addPeriodicTimer(2, [ $core, 'get_quotes' ]);
    $loop->addPeriodicTimer(5, [ $core, 'get_open_orders' ]);
    $loop->addPeriodicTimer(5, [ $core, 'get_current_positions' ]);         
    $loop->addPeriodicTimer((60 * 4), [ $core, 'get_possible_spy_put_credit_spreads_weeklies' ]);      
    $loop->addPeriodicTimer((60 * 6), [ $core, 'get_possible_spy_put_credit_spreads_45_days_out' ]); 
        
    // Start the server.
    $server = new \Ratchet\App(env('APP_WS_URL'), 8080, '127.0.0.1', $loop);
    $server->route('/ws/core', $core, [ 'stockpeer.com', 'stockpeer.dev' ]);
    $server->run();   
	}

  //
  // Get the console command arguments.
  //
	protected function getArguments()
	{
		return [];
	}

  //
  // Get the console command options.
  //
	protected function getOptions()
	{
		return [];
	}
}
/* End File */