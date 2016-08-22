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

class ImportEodOptions extends Command 
{
	protected $name = 'stockpeer:importeodoptions';
	protected $description = 'Import from deltaneutral.com the EOD optiosn data.';

  private $eod_option_daily_symbols = [ 'spy' ];

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
  
    // FTP to deltaneutral.com and get the data we do not already have.
    $this->download_data();

    $this->info('Ending Import'); 
    
    // Log action as successful. With healthchecks.io
    if(env('HEALTHCHECKS_HOOK_IMPORTEODOPTIONS'))
    {
      file_get_contents(env('HEALTHCHECKS_HOOK_IMPORTEODOPTIONS'));   
    }
	}
	
	//
	// Download the data.
	//
	public function download_data()
	{
  	$storage = '/tmp/';
  	
    // Setup dropbox connection
    $client = new Client(env('DROPBOX_ACCESS_TOKEN'), env('DROPBOX_APP_KEY'));
    $adapter = new DropboxAdapter($client);
    $db_filesystem = new Filesystem($adapter);  	
  	
    // Setup the FTP connection to Delta Neutral 
    $filesystem = new Filesystem(new Ftp([
      'host' => 'eodftp.deltaneutral.com',
      'username' => env('DELTANEUTRALUSER'),
      'password' => env('DELTANEUTRALPASSWORD'),
      'port' => 21,
      'root' => '/dbupdate',
      'passive' => true,
      'ssl' => false,
      'timeout' => 300,
    ]));  
      
    // Get the contents of the directory so we can loop over it downloading it.
    $list = $filesystem->listContents('/'); 
    
    // Load up files we skip.
    $skip = $this->get_files_to_skip();
    
    // Loop over the contents and process the data.
    foreach($list AS $key => $row)
    {
      // We only want files.
      if($row['type'] != 'file')
      {
        continue;
      }
        
      // We only want daily data.
      if(! stripos($row['path'], 'ptions_'))
      {
        continue;
      }
      
      // We only want zip files. 
      if($row['extension'] != 'zip')
      {
        continue;
      }

      // Some files we just do not want.
      if(in_array($row['path'], $skip))
      {
        continue;
      }

      $this->info('Downloading: ' . $row['path']);
      
      // Tmp file
      $tmp_file = $storage . $row['path'];
      
      // Download file and put it in our storage area.
      file_put_contents($tmp_file, $filesystem->read($row['path']));
      
      // Import the file into our database.
      $filesystem_zip = new Filesystem(new ZipArchiveAdapter($tmp_file));
      $list = $filesystem_zip->listContents('/');  
      
      // File the file we want to import.
      foreach($list AS $key2 => $row2)
      {
        // Just want the file with the options data.
        if(strpos($row2['path'], 'ptions_'))
        {
          // Write the CSV file to the tmp dir.
          $this->info('Importing: ' . $row2['path']);
          file_put_contents('/tmp/' . $row2['path'], $filesystem_zip->read($row2['path']));
          
          // Now that we have the CSV lets start the import.
          $this->_import_from_daily_csv_file('/tmp/' . $row2['path']);
        }
      }      
      
      // Save the file to dropbox
      $db_filesystem->write('/data/AllOptions/Daily/' . $row['path'], file_get_contents($tmp_file));
      
      // Deleting tmp file.
      unlink($tmp_file);
    }
	}
	
  //
  // Import just one day's data from the unziped CSV file.
  //
  private function _import_from_daily_csv_file($file)
  {
		// Setup our models.
		$symbols_model = App::make('App\Models\Symbols');
		
		// Lets get an index of all the underlying symbols.
		$symbol_index = $symbols_model->get_index(); 
    
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
  // Return a list of files we ignore as they are part of data we already imported 
  // When we purchased the past data.
  //
  public function get_files_to_skip()
  {
    $files = [
      'options_20151001.zip',
      'options_20151002.zip',
      'options_20151005.zip',
      'options_20151006.zip',
      'options_20151007.zip',
      'options_20151008.zip',
      'options_20151009.zip',
      'options_20151012.zip',
      'options_20151013.zip',
      'options_20151014.zip',
      'options_20151015.zip',
      'options_20151016.zip',
      'options_20151019.zip',
      'options_20151020.zip',
      'options_20151021.zip',
      'options_20151022.zip',
      'options_20151023.zip',
      'options_20151026.zip',
      'options_20151027.zip',
      'options_20151028.zip',
      'options_20151029.zip',
      'options_20151030.zip',
      'options_20151102.zip',
      'options_20151103.zip',
      'options_20151104.zip',
      'options_20151105.zip',
      'options_20151106.zip',
      'options_20151109.zip',
      'options_20151110.zip',
      'options_20151111.zip',
      'options_20151112.zip',
      'options_20151113.zip',
      'options_20151116.zip',
      'options_20151117.zip',
      'options_20151118.zip',
      'options_20151119.zip',
      'options_20151120.zip',
      'options_20151123.zip',
      'options_20151124.zip',
      'options_20151125.zip',
      'options_20151127.zip',
      'options_20151130.zip',
      'options_20151201.zip',
      'options_20151202.zip',
      'options_20151203.zip',
      'options_20151204.zip',
      'options_20151207.zip',
      'options_20151208.zip',
      'options_20151209.zip',
      'options_20151210.zip',
      'options_20151211.zip'
    ];
    
    // Query dropbox and list files we already know about.
    $client = new Client(env('DROPBOX_ACCESS_TOKEN'), env('DROPBOX_APP_KEY'));
    $adapter = new DropboxAdapter($client);
    $db_filesystem = new Filesystem($adapter);   	
  	$list = $db_filesystem->listContents('/', true);   
  	
    foreach($list AS $key => $row)
    {
      $files[] = $row['basename'];
    }
    
    return $files;    
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
