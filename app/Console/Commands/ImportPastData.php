<?php 
namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Crypt;
use Cache;
use Coinbase;
use SplFileObject;
use Dropbox\Client;
use League\Csv\Reader;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ImportPastData extends Command 
{
	protected $name = 'stockpeer:importpastdata';
	protected $description = 'Import data from the past (rebuild db). Really should only be run once.';

  private $eod_option_daily_symbols = [ 'spy' ];

  private $eod_option_summary_files = [
    'data/OptionsEndOfDay/IWM/IWM_2005.csv',
    'data/OptionsEndOfDay/IWM/IWM_2006.csv',
    'data/OptionsEndOfDay/IWM/IWM_2007.csv',  
    'data/OptionsEndOfDay/IWM/IWM_2008.csv',
    'data/OptionsEndOfDay/IWM/IWM_2009.csv',
    'data/OptionsEndOfDay/IWM/IWM_2010.csv',  
    'data/OptionsEndOfDay/IWM/IWM_2011.csv',
    'data/OptionsEndOfDay/IWM/IWM_2012.csv',
    'data/OptionsEndOfDay/IWM/IWM_2013.csv',
    'data/OptionsEndOfDay/IWM/IWM_2014-11.11.2014.csv',      

    'data/OptionsEndOfDay/SPY/SPY_2005.csv',
    'data/OptionsEndOfDay/SPY/SPY_2006.csv',
    'data/OptionsEndOfDay/SPY/SPY_2007.csv',  
    'data/OptionsEndOfDay/SPY/SPY_2008.csv',
    'data/OptionsEndOfDay/SPY/SPY_2009.csv',
    'data/OptionsEndOfDay/SPY/SPY_2010.csv',  
    'data/OptionsEndOfDay/SPY/SPY_2011.csv',
    'data/OptionsEndOfDay/SPY/SPY_2012.csv',
    'data/OptionsEndOfDay/SPY/SPY_2013.csv',
    'data/OptionsEndOfDay/SPY/SPY_2014.csv',     
    'data/OptionsEndOfDay/SPY/SPY_2015-12.10.2015.csv'      
  ];

  //
  // Create a new command instance.
  // 
	public function __construct()
	{
		parent::__construct();
		
    // No DB logging.
    DB::connection()->disableQueryLog();    
	}

  //
  // Execute the console command.
  //
	public function fire()
	{
    $this->info('Starting Import');
  
    // Import data.
    $this->_import_eod_options_data();
 
    $this->info('Ending Import');    
	}
	
	//
	// Import end of day options data.
	//
	private function _import_eod_options_data()
	{
    // Clear database.
    DB::statement('TRUNCATE TABLE OptionsEod');  	
  	
		// Setup our models.
		$symbols_model = App::make('App\Models\Symbols');
		$optionseod_model = App::make('App\Models\OptionsEod');
		
		// Lets get an index of all the underlying symbols.
		$symbol_index = $symbols_model->get_index();  	
  	  	
    // Loop through the different files and import
    foreach($this->_get_option_eod_summary_files() AS $file)
    {
      $this->info('Importing ' . $file);
      
      // Batch var
      $batch = [];      
      
      // Do the CSV magic.
			$reader = Reader::createFromFileObject(new SplFileObject($file));
			
			foreach($reader->query() AS $key => $row) 
			{	
				// Skip of the row is empty.
				if((! isset($row[0])) || (! isset($row[1])))
				{
					continue;
				}
			
				// Skip the first row.
				if($row[0] == 'underlying')
				{
					continue;
				}

				// See if we have an entry in the Symbols table for this symbol
				if(! isset($symbol_index[strtoupper($row[0])]))
				{
					$sym_id = $symbols_model->insert([ 'SymbolsShort' => strtoupper($row[0]) ]);
					$symbol_index = $symbols_model->get_index();
				} else
				{
					$sym_id = $symbol_index[strtoupper($row[0])];
				}
				
        // We insert in batches.
        $batch[] = [
  			  'OptionsEodSymbolId' => $sym_id,
  			  'OptionsEodSymbolLast' => $row[1],
  			  'OptionsEodType' => $row[5],
  			  'OptionsEodExpiration' => date('Y-m-d', strtotime($row[6])),
  			  'OptionsEodQuoteDate' => date('Y-m-d', strtotime($row[7])),
  			  'OptionsEodStrike' => $row[8],
  			  'OptionsEodLast' => $row[9],
  			  'OptionsEodBid' => $row[10],
  			  'OptionsEodAsk' => $row[11],
  			  'OptionsEodVolume' => $row[12],
  			  'OptionsEodOpenInterest' => $row[13],
  			  'OptionsEodImpliedVol' => $row[14],
  			  'OptionsEodDelta' => $row[15],
  			  'OptionsEodGamma' => $row[16],
  			  'OptionsEodTheta' => $row[17],
  			  'OptionsEodVega' => $row[18],
  			  'OptionsEodCreatedAt' => date('Y-m-d H:i:s')																																
  			];
			
				// Insert the data into the OptionsEod table. We insert 1000 at a time.
				if(count($batch) >= 1000)
				{
				  DB::table('OptionsEod')->insert($batch);
				  $batch = [];	
        }
  		}
  		
      // Catch any left over.
      if(count($batch) > 0)
      {
        DB::table('OptionsEod')->insert($batch);
      }	
    }
    
    // Now import all the data we collected daily.
    $this->_import_daily_eod_options_data($symbol_index);
	}
	
	//
	// Import data we have gotten on a daily baises from EOD Options.
	//
	private function _import_daily_eod_options_data($symbol_index)
	{
    // Query dropbox and list files.
    $client = new Client(env('DROPBOX_ACCESS_TOKEN'), env('DROPBOX_APP_KEY'));
    $adapter = new DropboxAdapter($client);
    $db_filesystem = new Filesystem($adapter);   	
  	$files = $db_filesystem->listContents('/', true);
  	
    foreach($files AS $key => $row)
    {
      $toast = false;
      
      // Only want files.
      if($row['type'] != 'file')
      {
        continue;
      }
      
      // Only want zipfiles with daily
      if($row['extension'] != 'zip')
      {
        continue;
      }
      
      // Only want a certain type of file.
      if(! strpos($row['path'], 'alloptions/Daily/options_'))
      {
        continue;
      }
      
      // See if we need to download it or not.
      if(is_file('/Users/spicer/Dropbox/Apps/Stockpeer/' . $row['path']))
      {
        $file = '/Users/spicer/Dropbox/Apps/Stockpeer/' . $row['path'];
      } else
      {
        $toast = true;
        $file = '/tmp/' . basename($row['path']);
        $this->info('Dropbox: Downloading ' . $row['path']);
        file_put_contents($file, $db_filesystem->read($row['path']));
      }
  	
      // Unzip the file.
      $filesystem = new Filesystem(new ZipArchiveAdapter($file));
      $list = $filesystem->listContents('/');  
      
      // File the file we want to import.
      foreach($list AS $key => $row)
      {
        // Just want the file with the options data.
        if(strpos($row['path'], 'ptions_'))
        {
          // Write the CSV file to the tmp dir.
          $this->info('Importing: ' . $row['path']);
          file_put_contents('/tmp/' . $row['path'], $filesystem->read($row['path']));
          
          // Now that we have the CSV lets start the import.
          $this->_import_from_daily_csv_file('/tmp/' . $row['path'], $symbol_index);
        }
      }
      
      // Toast temp file
      if($toast)
      {
        unlink($file);
      }
    }
	}
	
  //
  // Import just one day's data from the unziped CSV file.
  //
  private function _import_from_daily_csv_file($file, $symbol_index)
  {
    $batch = [];
    
    // Do the CSV magic.
    $reader = Reader::createFromFileObject(new SplFileObject($file));
    
    // symbols model
    $symbols_model = App::make('App\Models\Symbols');
    
    // Loop through the option data.
    foreach($reader->query() AS $key => $row) 
    {	
      // Only care about some symbols
      if(! in_array(strtolower($row[0]), $this->eod_option_daily_symbols))
      {
        continue;
      }
      
      // See if we have an entry in the Symbols table for this symbol
      if(! isset($symbol_index[strtoupper($row[0])]))
      {
        $sym_id = $symbols_model->insert([ 'SymbolsShort' => strtoupper($row[0]) ]);
        $symbol_index = $symbols_model->get_index();
      } else
      {
        $sym_id = $symbol_index[strtoupper($row[0])];
      }      
      
      // We insert in batches.
      DB::table('OptionsEod')->insert([
        'OptionsEodSymbolId' => $sym_id,
        'OptionsEodSymbolLast' => $row[1],
        'OptionsEodType' => $row[5],
        'OptionsEodExpiration' => date('Y-m-d', strtotime($row[6])),
        'OptionsEodQuoteDate' => date('Y-m-d', strtotime($row[7])),
        'OptionsEodStrike' => $row[8],
        'OptionsEodLast' => $row[9],
        'OptionsEodBid' => $row[10],
        'OptionsEodAsk' => $row[11],
        'OptionsEodVolume' => $row[12],
        'OptionsEodOpenInterest' => $row[13],
        'OptionsEodImpliedVol' => $row[14],
        'OptionsEodDelta' => $row[15],
        'OptionsEodGamma' => $row[16],
        'OptionsEodTheta' => $row[17],
        'OptionsEodVega' => $row[18],
        'OptionsEodCreatedAt' => date('Y-m-d H:i:s')																																
      ]);
    }
         
    // Delete tmp file.
    unlink($file);   
  }
	
	//
	// Return a list of files with eod data.
	//
  private function _get_option_eod_summary_files()
  {
    $files = [];
    
    if(is_dir('/Users/spicer/Dropbox/Apps/Stockpeer'))
    {
      $base = '/Users/spicer/Dropbox/Apps/Stockpeer/';
    } else
    {
      $base = '/tmp/market_data/';
      $this->_get_data_from_dropbox();
    }
    
    foreach($this->eod_option_summary_files AS $key => $row)
    {
      $files[] = $base . $row; 
    }
    
    return $files;
  }
  
  //
  // Download files from Dropbox.
  //
  private function _get_data_from_dropbox()
  {
    $tmp = '/tmp/market_data/';
    
    // tmp/market_data/
    if(! is_dir($tmp))
    {
      mkdir($tmp);
    }

    // data/optionsendofday/IWM
    if(! is_dir($tmp . '/data/OptionsEndOfDay/IWM'))
    {
      mkdir($tmp . '/data/OptionsEndOfDay/IWM', 0777, true);
    }

    // data/optionsendofday/SPY
    if(! is_dir($tmp . '/data/OptionsEndOfDay/SPY'))
    {
      mkdir($tmp . '/data/OptionsEndOfDay/SPY', 0777, true);
    }
    
    // Query dropbox and list files.
    $client = new Client(env('DROPBOX_ACCESS_TOKEN'), env('DROPBOX_APP_KEY'));
    $adapter = new DropboxAdapter($client);
    $filesystem = new Filesystem($adapter); 

    // Loop through and download the data
    foreach($this->eod_option_summary_files AS $key => $row)
    {
      if(is_file($tmp . $row))
      {
        $this->info('Dropbox Already Stored: ' . $row . ' ' . ($key+1) . ' of ' . count($this->eod_option_summary_files));
        continue;
      }
      
      // Download and store.
      file_put_contents($tmp . $row, $filesystem->read($row));
      $this->info('Dropbox Stored: ' . $row . ' ' . ($key+1) . ' of ' . count($this->eod_option_summary_files));     
    }
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

/*
		// CSV Format
    [0] => underlying
    [1] => underlying_last
    [2] =>  exchange
    [3] => optionroot
    [4] => optionext
    [5] => type
    [6] => expiration
    [7] => quotedate
    [8] => strike
    [9] => last
    [10] => bid
    [11] => ask
    [12] => volume
    [13] => openinterest
    [14] => impliedvol
    [15] => delta
    [16] => gamma
    [17] => theta
    [18] => vega
    [19] => optionalias
*/

/* End File */
