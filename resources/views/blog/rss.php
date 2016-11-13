<?php echo '<?xml version="1.0" encoding="UTF-8" ?>'; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title><?=$header['title']?></title>
		<link><?=URL::to('blog')?></link>
		<atom:link href="<?=URL::to('blog/rss')?>" rel="self" type="application/rss+xml" />
		<description><?=$header['description']?></description>
		<copyright>Cloudmanic Labs, LLC</copyright>
		<ttl>30</ttl>

		<?php foreach($posts AS $key => $row) : ?>
			<item>
				<title><?=$row->title?></title>
				<description><?=htmlspecialchars(App\Library\Parse::instance()->run($row->field_blogSummary))?></description>
				<link><?=URL::to('blog/' . $row->slug)?></link>
				<guid isPermaLink="true"><?=URL::to('blog/' . $row->slug)?></guid>
				<pubDate><?=date('D, d M y H:i:s O', strtotime($row->postDate))?></pubDate>
			</item>
		<?php endforeach; ?>
	</channel>
</rss>