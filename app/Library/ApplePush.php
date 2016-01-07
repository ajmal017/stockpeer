<?php

namespace App\Library;

use ZipArchive;

class ApplePush 
{
  public $p_file = '../apple/push_package/stockpeer.p12';
  public $pem_file = '../apple/web.cloudmanic.stockpeer.pem';
  public $base_dir = '../apple/push_package/';
  public $auth_token = '';
  
  //
  // Construct...
  //
  public function __construct()
  {
    $this->p_file = app_path('../apple/push_package/stockpeer.p12');
    $this->pem_file = app_path('../apple/web.cloudmanic.stockpeer.pem');
    $this->base_dir = app_path('../apple/' . env('APPLE_PUSH_PACKAGE', 'push_package') . '/');
  }
  
  //
  // Send Message
  //
  public function send($deviceToken, $title, $body, $button = 'View')
  {    
    $payload['aps']['alert'] = [
      'title' => $title,
      'body' => $body,
      'action' => $button
    ];
    
    $payload['aps']['url-args'] = [ '' ];	
    $payload = json_encode($payload);
    $apnsHost = 'gateway.push.apple.com';
    $apnsPort = 2195;
    $apnsCert = $this->pem_file;
    $streamContext = stream_context_create();
    stream_context_set_option($streamContext, 'ssl', 'local_cert', $apnsCert);
    $apns = stream_socket_client('ssl://' . $apnsHost . ':' . $apnsPort, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);
    $apnsMessage = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $deviceToken)) . chr(0) . chr(strlen($payload)) . $payload;
    fwrite($apns, $apnsMessage);
    fclose($apns); 
    
    // Return Happy.
    return true;
  }
  
  // --------------- Build Package ---------------- //
  
  //
  // Return raw files.
  //
  public function raw_files() 
  {
    return [
      'icon.iconset/icon_16x16.png',
      'icon.iconset/icon_16x16@2x.png',
      'icon.iconset/icon_32x32.png',
      'icon.iconset/icon_32x32@2x.png',
      'icon.iconset/icon_128x128.png',
      'icon.iconset/icon_128x128@2x.png',
      'website.json'
    ];
  }

  //
  // Creates the manifest by calculating the SHA1 hashes for all of the raw files in the package.
  //
  public function create_manifest($package_dir) 
  {
    // Obtain SHA1 hashes of all the files in the push package
    $manifest_data = array();
    
    foreach($this->raw_files() as $raw_file) 
    {
      $manifest_data[$raw_file] = sha1(file_get_contents("$package_dir/$raw_file"));
    }
    
    file_put_contents("$package_dir/manifest.json", json_encode((object)$manifest_data));
  }
  
  //  
  // Creates a signature of the manifest using the push notification certificate.
  //
  public function create_signature($package_dir, $cert_path, $cert_password) 
  {
    // Load the push notification certificate
    $pkcs12 = file_get_contents($cert_path);
    $certs = array();
    if(!openssl_pkcs12_read($pkcs12, $certs, $cert_password)) {
        return;
    }
    
    $signature_path = "$package_dir/signature";
    
    // Sign the manifest.json file with the private key from the certificate
    $cert_data = openssl_x509_read($certs['cert']);
    $private_key = openssl_pkey_get_private($certs['pkey'], $cert_password);
    openssl_pkcs7_sign("$package_dir/manifest.json", $signature_path, $cert_data, $private_key, array(), PKCS7_BINARY | PKCS7_DETACHED);
    
    // Convert the signature from PEM to DER
    $signature_pem = file_get_contents($signature_path);
    $matches = array();
    if (!preg_match('~Content-Disposition:[^\n]+\s*?([A-Za-z0-9+=/\r\n]+)\s*?-----~', $signature_pem, $matches)) {
        return;
    }
    $signature_der = base64_decode($matches[1]);
    file_put_contents($signature_path, $signature_der);
  }
 
  // 
  // Zips the directory structure into a push package, and returns the path to the archive.
  //
  public function package_raw_data($package_dir, $name) 
  {
    $zip_path = $package_dir . "/$name.zip";
    
    // Package files as a zip file
    $zip = new ZipArchive();
    if (!$zip->open($zip_path, ZIPARCHIVE::CREATE)) 
    {
      error_log('Could not create ' . $zip_path);
      return;
    }
    
    $raw_files = $this->raw_files();
    $raw_files[] = 'manifest.json';
    $raw_files[] = 'signature';
    foreach($raw_files as $raw_file) 
    {
      $zip->addFile("$package_dir/$raw_file", $raw_file);
    }
    
    $zip->close();
    return $zip_path;
  }  
 
  // 
  // Copies the raw push package files to $package_dir.
  //
  public function copy_raw_push_package_files($package_dir) 
  {
    global $id;
    mkdir($package_dir . '/icon.iconset');
    
    foreach($this->raw_files() as $raw_file) 
    {
      copy($this->base_dir . $raw_file, "$package_dir/$raw_file");
      if($raw_file == "website.json") 
      {
        $wjson = file_get_contents("$package_dir/$raw_file");
        unlink("$package_dir/$raw_file");
        $ff = fopen("$package_dir/$raw_file", "x");
        fwrite($ff, str_replace("{AUTHTOKEN}", "authenticationToken_" . $this->auth_token, $wjson)); // we have to add "authenticationToken_" because it has to be at least 16 for some reason thx apple
        fclose($ff);
      }
    }
  }  
  
  // 
  // Creates the push package, and returns the path to the archive.
  //
  public function create_push_package() 
  {
    global $certificate_path, $certificate_password, $id;
    
    // Create a temporary directory in which to assemble the push package
    $package_dir = '/tmp/pushPackage' . time();
    if(! mkdir($package_dir)) 
    {
      unlink($package_dir);
      die;
    }
    
    $this->copy_raw_push_package_files($package_dir, $id);
    $this->create_manifest($package_dir);
    $this->create_signature($package_dir, $this->p_file, '');
    $package_path = $this->package_raw_data($package_dir, 'stockpeer');
    
    return $package_path;
  }  
      
}

/* End File */