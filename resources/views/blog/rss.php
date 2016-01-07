<?php echo '<?xml version="1.0" encoding="UTF-8" ?>'; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title><?=$header['title']?></title>
		<link><?=URL::to('blog')?></link>
		<atom:link href="<?=URL::to('blog/rss')?>" rel="self" type="application/rss+xml" />
		<description><?=$header['title']?></description>
		<copyright><?=URL::to('description')?></copyright>
		<ttl>30</ttl>

		<?php foreach($posts AS $key => $row) : ?>
			<item>
				<title><?=$row->BlogTitle?></title>
				<description><?=htmlspecialchars($row->BlogSummary)?></description>
				<link><?=URL::to('blog/' . $row->BlogId . '/' . str_slug($row->BlogTitle))?></link>
				<guid isPermaLink="true"><?=URL::to('blog/' . $row->BlogId . '/' . str_slug($row->BlogTitle))?></guid>
				<pubDate><?=date('D, d M y H:i:s O', strtotime($row->BlogDate))?></pubDate>
			</item>
		<?php endforeach; ?>
	</channel>
</rss>