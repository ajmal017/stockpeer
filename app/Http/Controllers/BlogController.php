<?php

namespace App\Http\Controllers;

use DB;
use View;
use Config;
use Response;
use App\Library\Parse;
use Cloudmanic\Craft2Laravel\Craft2Laravel;

class BlogController extends Controller 
{
	private $_data = [];
	
	//
	// Construct.
	//
	public function __construct()
	{
		$this->_data['header'] = [
			'title' => 'Best Place For Stock And Options Trading Advice | Stockpeer Blog',
			'image' => 'https://dukitbr4wfrx2.cloudfront.net/blog/find-the-best-options-broker_25.png',
			'thumb' => 'https://dukitbr4wfrx2.cloudfront.net/blog/find-the-best-options-broker_25_thumb.png',			
			'description' => 'The best place for online stock and options trading advice. Learn everything from trading vertical credit spreads to advance stock trades.'
		];
	}
	
	//
	// Landing page.
	//
	public function index()
	{
    return redirect('https://options.cafe/blog', 301);
/*
		// Get entries from craft
		$craft2laravel = new Craft2Laravel('craft');
		$posts = $craft2laravel->get_entries('blog');		
							
		$this->_data['posts'] = $posts;	
							
		return View::make('template.main', $this->_data)->nest('body', 'blog.index', $this->_data);
*/
	}

	//
	// Single post page. - Slug
	//
	public function single_slug($slug)
	{
    return redirect('https://options.cafe/blog/' . $slug, 301);

/*
		// Get entries from craft
		$craft2laravel = new Craft2Laravel('craft');
		$post = $craft2laravel->get_entry_by_slug('blog', $slug);	
	
		$this->_data['post'] = $post;	

		// Setup header - Image
		if(isset($post->field_blogImage[0]))
		{
			$this->_data['header']['image'] = $post->field_blogImage[0]->url;	
		}

		// Setup header - Thumb
		if(isset($post->field_blogThumbnail[0]))
		{
			$this->_data['header']['thumb'] = $post->field_blogThumbnail[0]->url;	
		}
		
		$this->_data['header']['title'] = $post->title;	
		$this->_data['header']['description'] = $post->field_blogDescription;		
							
		return View::make('template.main', $this->_data)->nest('body', 'blog.single', $this->_data);
*/	
	}
	
	//
	// Single post page. - Id and Slug
	//
	public function single($id, $slug)
	{
		return $this->single_slug($slug);
	}
	
	//
	// RSS Feed.
	//
	public function rss()
	{
		// Get entries from craft
		$craft2laravel = new Craft2Laravel('craft');
		$posts = $craft2laravel->get_entries('blog');		
							
		$this->_data['posts'] = $posts;
							
		return response()->view('blog.rss', $this->_data)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
	}	
}

/* End File */