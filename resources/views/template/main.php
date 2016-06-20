<!DOCTYPE html>
<html class="no-js" lang="en" ng-app="app">
<head>
	<meta charset="UTF-8">
	<title><?=$header['title']?> - Learn Options Trading</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="shortcut icon" type="image/png" href="/app/images/favicon.png" />
	<meta name="description" content="<?=$header['description']?>" />
	<meta name="twitter:card" content="summary" />
	<meta name="twitter:site" content="@stockpeer" />
	<meta name="twitter:title" content="<?=$header['title']?> - Learn Stock and Options Trading" />
	<meta name="twitter:description" content="<?=$header['description']?>" />
	<meta name="twitter:image" content="<?=$header['thumb']?>" />
	<meta name="og:site_name" content="Stockpeer - Learn Stock and Options Trading" />		
	<meta name="og:title" content="<?=$header['title']?> - Learn Stock and Options Trading" />	
	<meta name="og:url" content="<?=Request::url()?>" />
	<meta name="og:description" content="<?=$header['description']?>" />
	<meta name="og:image" content="<?=$header['image']?>" />		
	<meta name="fb:app_id" content="<?=Config::get('site.facebook_app_id')?>" />
	<meta property="og:type" content="article" />
	
	<link rel="alternate" type="application/rss+xml" title="Stockpeer Feed" href="<?=URL::to('blog/rss')?>" />
	<link href='//fonts.googleapis.com/css?family=Poiret+One|PT+Serif|Open+Sans:400,300' rel='stylesheet' type='text/css'>
	<link href="/assets/css/bootstrap.min.css" rel="stylesheet" />
	<link href="/assets/css/bootstrap-responsive.min.css" rel="stylesheet" />
	<link href="/assets/css/socialicons.css" rel="stylesheet" />
	<link href="/assets/css/glyphicons.css" rel="stylesheet" />
	<link href="/assets/css/halflings.css" rel="stylesheet" />
	<link href="/assets/css/template.css" rel="stylesheet" />
	<link href="/assets/css/colors/color-red.css" rel="stylesheet" id="colorcss" />
	<link href="/assets/css/style.css" rel="stylesheet" />

	<script>
		var site = {
			env: '<?=App::environment()?>',
			ws_url: '<?=env('APP_WS_URL')?>'
		}
	</script>

	<script src="/assets/js/modernizr.js"></script>
	<script src="/assets/js/jquery-1.9.1.js"></script>
	<script src="/assets/js/bootstrap.min.js"></script>	
	<script src="/app/bower/angular/angular.min.js"></script>
	<script src="/app/bower/highcharts/highcharts.js"></script>
	
	<script src="/app/controllers/public.js"></script>
	<script src="/app/filters/date.js"></script>
	<script src="/app/controllers/public-backtest.js"></script>
	
  <script src="/assets/js/bootstrap.js"></script>
  <script src="/assets/js/tinynav.js"></script>
  <script src="/assets/js/template.js"></script>	
</head>
<body ng-controller="SiteWideCtrl">

<div class="container">

	<div class="masthead clearfix">
		<a href="<?=URL::to('')?>">
			<img id="logo" src="/assets/img/wwlogo@2x.png" alt="Stockpeer">
		</a>
		<ul id="nav" class="nav ww-nav pull-right hidden-phone">
		  <li class="active">
		  	<a href="<?=URL::to('')?>">Home</a>
			</li>
		  
		  <li>
				<a href="<?=URL::to('backtest')?>">Backtest</a>
			</li>		  
		  
		  <li>
				<a href="<?=URL::to('options-broker-picker')?>">Broker Finder</a>
			</li>
		  
		  <li>
				<a href="<?=URL::to('about')?>">About</a>
			</li>
		</ul>
	</div>

	<hr>
	
	<div class="row main-content">
  	<div class="alert alert-warning hide" role="alert" id="ws-server-reconnect">Reconnecting to the server...</div>
		<?=$body?>
		<?=View::make('template.newsletter')?>
	</div>
	

	<div class="row well">	
		<div class="copyright span8">
			Sponsored by <a href="http://cloudmanic.com/?utm_campaign=stockpeer.com">Cloudmanic Labs</a>
		</div>
				
		<div class="pull-right">
			<a href="<?=URL::to('blog/rss')?>"><i class="smicon-rss"></i></a>
			<a href="http://twitter.com/stockpeer"><i class="smicon-twitter"></i></a>
			<a href="https://www.facebook.com/stockpeer"><i class="smicon-facebook"></i></a>
			<a href="https://google.com/+Stockpeer"><i class="smicon-google"></i></a>
		</div>
		
			
	</div>
	
</div>



<?php if(App::environment('production')) : ?>

<script type="text/javascript">(function(e,b){if(!b.__SV){var a,f,i,g;window.mixpanel=b;b._i=[];b.init=function(a,e,d){function f(b,h){var a=h.split(".");2==a.length&&(b=b[a[0]],h=a[1]);b[h]=function(){b.push([h].concat(Array.prototype.slice.call(arguments,0)))}}var c=b;"undefined"!==typeof d?c=b[d]=[]:d="mixpanel";c.people=c.people||[];c.toString=function(b){var a="mixpanel";"mixpanel"!==d&&(a+="."+d);b||(a+=" (stub)");return a};c.people.toString=function(){return c.toString(1)+".people (stub)"};i="disable time_event track track_pageview track_links track_forms register register_once alias unregister identify name_tag set_config reset people.set people.set_once people.increment people.append people.union people.track_charge people.clear_charges people.delete_user".split(" ");
for(g=0;g<i.length;g++)f(c,i[g]);b._i.push([a,e,d])};b.__SV=1.2;a=e.createElement("script");a.type="text/javascript";a.async=!0;a.src="undefined"!==typeof MIXPANEL_CUSTOM_LIB_URL?MIXPANEL_CUSTOM_LIB_URL:"file:"===e.location.protocol&&"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\/\//)?"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js":"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";f=e.getElementsByTagName("script")[0];f.parentNode.insertBefore(a,f)}})(document,window.mixpanel||[]);
mixpanel.init("2f60e9408250fffab49db1781575c6d2");</script>

<script src="//static.getclicky.com/js" type="text/javascript"></script>
<script type="text/javascript">try{ clicky.init(100781521); }catch(e){}</script>
<noscript><p><img alt="Clicky" width="1" height="1" src="//in.getclicky.com/100781521ns.gif" /></p></noscript>

<script type="text/javascript">
  var _paq = _paq || [];
  _paq.push(["setCookieDomain", "*.stockpeer.com"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u=(("https:" == document.location.protocol) ? "https" : "http") + "://piwik.cloudmanic.com/";
    _paq.push(['setTrackerUrl', u+'piwik.php']);
    _paq.push(['setSiteId', 5]);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript';
    g.defer=true; g.async=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<noscript><p><img src="https://piwik.cloudmanic.com/piwik.php?idsite=5" style="border:0;" alt="" /></p></noscript>

<?php else : ?>

<script type="text/javascript">(function(e,b){if(!b.__SV){var a,f,i,g;window.mixpanel=b;b._i=[];b.init=function(a,e,d){function f(b,h){var a=h.split(".");2==a.length&&(b=b[a[0]],h=a[1]);b[h]=function(){b.push([h].concat(Array.prototype.slice.call(arguments,0)))}}var c=b;"undefined"!==typeof d?c=b[d]=[]:d="mixpanel";c.people=c.people||[];c.toString=function(b){var a="mixpanel";"mixpanel"!==d&&(a+="."+d);b||(a+=" (stub)");return a};c.people.toString=function(){return c.toString(1)+".people (stub)"};i="disable time_event track track_pageview track_links track_forms register register_once alias unregister identify name_tag set_config reset people.set people.set_once people.increment people.append people.union people.track_charge people.clear_charges people.delete_user".split(" ");
for(g=0;g<i.length;g++)f(c,i[g]);b._i.push([a,e,d])};b.__SV=1.2;a=e.createElement("script");a.type="text/javascript";a.async=!0;a.src="undefined"!==typeof MIXPANEL_CUSTOM_LIB_URL?MIXPANEL_CUSTOM_LIB_URL:"file:"===e.location.protocol&&"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\/\//)?"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js":"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";f=e.getElementsByTagName("script")[0];f.parentNode.insertBefore(a,f)}})(document,window.mixpanel||[]);
mixpanel.init("493af81382111ff5d50f67a9c006ee09");</script>

<?php endif; ?>


</body>
</html>