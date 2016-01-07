<?php
return array(
    // Core CMS Configs.
    'site_name' => 'Stockpeer.com',
    'assets_base' => CMS::base_url() . 'assets',
		'public_base' => (CMS::get_env() == 'local') ? 'http://stockpeer.dev/' : 'http://stockpeer.com/',
    'cp_base' => '',
    'cp_base_seg' => 1, 
    'cp_force_ssl' => FALSE,
    'cp_login_type' => 'default',
    'cp_home' => 'blocks',
    'cp_tmp_dir' => '/tmp', // no trailing slash.
    'cp_thumb_width' => '300',
    'cp_thumb_height' => '300',
    'cp_clear_ci_page_cache' => TRUE,
    'app-header-head' => '',
    'app-footer-body' => '',
    'app-header-files' => [],
    'app-footer-files' => [],
		'edit-continue-button' => 'Save_And_Continue_Editing', // Save_And_Continue or Save_And_Continue_Editing or leave blank
    'status-default' => 'Active',
    'status-options' => [
        'Active' => 'Active', 
        'Disabled' => 'Disabled'
    ],  

    // Uploading media.
    'cp_media_driver' => 'amazon-web-services-s3', // local-files / amazon-web-services-s3 / rackspace-cloud-files,
    'cp_media_file_types' => 'gif|jpg|jpeg|png|pdf|mov|avi|mp4',
    'cp_media_file_max_size' => 102400, // kilobytes
    'cp_image_resize' => '1200',

    // Local file config.
    'cp_media_local_path' => 'uploads/',
    'cp_media_local_dir' => 'uploads/', // must be from document root
    'cp_media_local_url' => CMS::base_url(), 
    'cp_media_local_ssl_url' => CMS::base_url(), 

    // Amazon config
    'cp_media_amazon_s3_access_key' => 'AKIAJHURH6FZ3Q6IQ47Q',
    'cp_media_amazon_s3_secret_key' => '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',
    'cp_media_amazon_s3_container' => 'stockpeer',
    'cp_media_amazon_s3_path' => 'blog/',
    'cp_media_amazon_s3_url' => 'https://dukitbr4wfrx2.cloudfront.net/', // trailing slash
    'cp_media_amazon_s3_ssl_url' => 'https://dukitbr4wfrx2.cloudfront.net/', // trailing slash

    // Rackspace config 
    'cp_media_rackspace_username' => '',
    'cp_media_rackspace_key' => '',
    'cp_media_rackspace_container' => '',
    'cp_media_rackspace_path' => 'cms/',
    'cp_media_rackspace_url' => '',
    'cp_media_rackspace_ssl_url' => ''
);  