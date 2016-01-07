<div class="offset1 span10 zone-content">
	<div class="blog-posts cont">
		<div class="published"><?=date('F j, Y', strtotime($post->BlogDate))?></div> 
		<h1><?=$post->BlogTitle?></h1>
		<p><?=App\Library\Parse::instance()->run($post->BlogBody)?></p>
				<div class="social-share well">
			<p>Help support this blog, please share.</p>
			<ul class="clearfix">
				<li class="first">

					<div class="fb-share-button" data-href="<?=Request::url()?>" data-layout="button"></div>
				</li>
				
				<li>
					<a href="https://twitter.com/share" class="twitter-share-button" data-via="stockpeer" data-count="none">Tweet</a>				
				</li>
				
				<li>
					<div class="g-plus" data-action="share" data-annotation="none"></div>	
				</li>
				
				<li>
					<a href="http://stocktwits.com/widgets/share" id="stocktwits-share-button">
						<img src="https://stocktwits.com/assets/widget/stocktwits_share.png" alt="Share on StockTwits"/>
					</a>
				</li>
			</ul>
		</div>
		
	</div>
	
	<div class="blog-single-comments">
		<div id="disqus_thread"></div>
	</div>
 
	<script src="https://apis.google.com/js/platform.js" async defer></script>
 
	<div id="fb-root"></div>
	<script>(function(d, s, id) {
	  var js, fjs = d.getElementsByTagName(s)[0];
	  if (d.getElementById(id)) return;
	  js = d.createElement(s); js.id = id;
	  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&appId=865449586799704&version=v2.0";
	  fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));</script>
 
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script> 
 
	<script src="https://stocktwits.com/addon/button/share.min.js"></script>
 
 <script type="text/javascript">
 	var disqus_shortname = 'stockpeer';
 	var disqus_identifier = '<?=$post->BlogId?>';
 	
 	(function() {
 	    var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
 	    dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
 	    (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
 	})();
 </script>	
</div>