<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1">
    <head>
        <meta name="dtb:uid" content="urn:uuid:<?php echo $models['metadata']['uuid']; ?>"/>
        <meta name="dtb:totalPageCount" content="0"/>
        <meta name="dtb:maxPageNumber" content="0"/>
    </head>
    <docTitle>
    	<text><?php echo htmlspecialchars(Library::title($models['metadata'])); ?></text>
    </docTitle>
    <navMap>
<?php $index = 1; ?>
<?php foreach($models['navbar'] as $point) { ?>
		<navPoint id="n-<?php echo $index; ?>" playOrder="<?php echo $index++; ?>">
            <navLabel>
            	<text><?php echo htmlspecialchars($point['text']); ?></text>
            </navLabel>
            <content src="<?php echo $point['part']; ?>.xhtml#<?php echo $point['id']; ?>"/>
		</navPoint>
<?php } ?>
    </navMap>
</ncx>