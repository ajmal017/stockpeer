<?php

namespace App\Http\Controllers;

use DB;
use View;
use Config;
use Response;
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
			'title' => 'Stockpeer',
			'image' => '',
			'thumb' => '',			
			'description' => ''
		];
	}
	
	//
	// Landing page.
	//
	public function index()
	{
		// Get entries from craft
		$craft2laravel = new Craft2Laravel('craft');
		$posts = $craft2laravel->get_entries('blog');		
							
		$this->_data['posts'] = $posts;
							
		return View::make('template.main', $this->_data)->nest('body', 'blog.index', $this->_data);
	}
	
	//
	// Single post page.
	//
	public function single($id, $slug)
	{
		$post = DB::table('Blog')
							->leftJoin('CMS_Media', 'CMS_MediaId', '=', 'BlogImage')
							->where('BlogId', $id)
							->where('BlogStatus', 'Active')					
							->first();	
			
		$this->_data['post'] = $post;	
		
		// Setup header.
		$this->_data['header']['image'] = Config::get('site.aws_url') . $post->CMS_MediaPath . $post->CMS_MediaFile;	
		$this->_data['header']['thumb'] = Config::get('site.aws_url') . $post->CMS_MediaPathThumb . $post->CMS_MediaFileThumb;	
		$this->_data['header']['title'] = $post->BlogTitle;	
		$this->_data['header']['description'] = $post->BlogDescription;		
							
		return View::make('template.main', $this->_data)->nest('body', 'blog.single', $this->_data);
	}
	
	//
	// RSS Feed.
	//
	public function rss()
	{
		$posts = DB::table('Blog')
							->where('BlogStatus', 'Active')
							->orderBy('BlogDate', 'desc')
							->orderBy('BlogId', 'desc')							
							->get();
							
		$this->_data['posts'] = $posts;
							
		return response()->view('blog.rss', $this->_data)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
	}	
}

/* End File */