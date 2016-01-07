<?php
  
// We do this because Laravel 5 thinks they own all function names.
require_once '../../vendor/cloudmanic/cloudmanic-cms/src/codeigniter/helpers/url_helper.php';

require '../../vendor/autoload.php';

// Detect ENV.
if(isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] == 'stockpeer.dev'))
{
	CMS::set_env('local');
}

CMS::config_file('cms.php');
CMS::framework('laravel5', '../../app');
CMS::load_configuration_from_export('./config.php');
require CMS::boostrap('../../vendor');