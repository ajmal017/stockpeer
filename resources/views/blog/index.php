<div class="offset1 span10 zone-content">

  <ul class="blog-posts">
  	<?php 
  		foreach($posts AS $key => $row) : 
  			$first = ($key == 0) ? 'first' : '';
  			$last = (($key+1) == count($posts)) ? 'last' : '';
  	?>
  	<li class="<?=$last?> <?=$first?>">
  		<article class="blog-post cont">
  			<div class="published"><?=date('F j, Y', strtotime($row->postDate))?></div>     
  			<h1>
  				<a href="<?=URL::to('blog/' . $row->slug)?>"><?=$row->title?></a>
  			</h1>
  			<p>
  				<?=App\Library\Parse::instance()->run($row->field_blogSummary)?>
  				<a href="<?=URL::to('blog/' . $row->slug)?>">Read More...</a>
  			</p>
  		</article>
  	</li>
  	<?php endforeach; ?>
  </ul>

  <div class="blog-pagination">

<?php
/*
	 			<div class="newer">
					<a href="#">« Newer posts</a>
				</div>
				
				<div class="older">
					<a href="#">Older posts »</a>
				</div>	
*/
?>		
  </div>

</div>