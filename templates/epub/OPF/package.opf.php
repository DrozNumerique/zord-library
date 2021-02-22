<?php $metadata = $models['metadata']; ?>
<package version="3.0" unique-identifier="uid" xmlns="http://www.idpf.org/2007/opf">
	<metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
		<meta name="cover" content="cover-image"/>
		<meta property="dcterms:modified"><?php echo date("Y-m-d\Th:i:s\Z"); ?></meta>
		<meta property="schema:accessModeSufficient">textual,visual</meta>
		<meta property="schema:accessModeSufficient">textual</meta>
		<meta property="schema:accessMode">textual</meta>
		<meta property="schema:accessibilityFeature">alternativeText</meta>
		<meta property="schema:accessibilityHazard">none</meta>
		<meta property="schema:accessibilitySummary" xml:lang="fr">Cette publication est conforme au niveau AA des spécifications d'accessibilité EPUB.</meta>
		<meta property="schema:accessibilitySummary" xml:lang="en">This publication conforms to the EPUB Accessibility specification at WCAG Level AA.</meta>
		<dc:type>text</dc:type>
		<dc:date><?php echo explode('-', $metadata['date'])[0]; ?></dc:date>
		<dc:identifier id="ean"><?php echo $metadata['epub']; ?></dc:identifier>
		<dc:identifier id="uri"><?php echo $metadata['uri']; ?></dc:identifier>
		<dc:identifier id="uid">urn:uuid:<?php echo $metadata['uuid']; ?></dc:identifier>
		<dc:title><?php echo htmlspecialchars(Library::title($metadata, null, null, ', ')); ?></dc:title>
<?php if (isset($metadata['creator']) && is_array($metadata['creator'])) { ?>
<?php     foreach($metadata['creator'] as $creator) { ?>
		<dc:creator><?php echo htmlspecialchars($creator); ?></dc:creator>
<?php     } ?>
<?php } ?>
<?php if (isset($metadata['editor']) && is_array($metadata['editor'])) { ?>
<?php     foreach($metadata['editor'] as $editor) { ?>
		<dc:contributor><?php echo htmlspecialchars($editor); ?></dc:contributor>
<?php     } ?>
<?php } ?>
<?php if (isset($metadata['description']) && is_array($metadata['description'])) { ?>
<?php     foreach($metadata['description'] as $lang => $description) { ?>
		<dc:description xml:lang="<?php echo $lang; ?>"><?php echo htmlspecialchars($description); ?></dc:description>
<?php     } ?>
<?php } ?>
		<dc:publisher><?php echo htmlspecialchars($metadata['publisher']); ?></dc:publisher>
		<dc:format><?php echo $metadata['format']; ?></dc:format>
		<dc:source><?php echo $metadata['ean']; ?></dc:source>
		<dc:rights><?php echo $metadata['rights']; ?></dc:rights>
		<dc:language><?php echo $metadata['language']; ?></dc:language>
<?php if (isset($metadata['relation']) && !empty($metadata['relation'])) { ?>
		<dc:relation><?php echo $metadata['relation']; ?></dc:relation>
<?php } ?>
	</metadata>
	<manifest>
		<item id="ncx" href="navigation.ncx" media-type="application/x-dtbncx+xml"/>
		<item id="nav" href="navigation.xhtml" media-type="application/xhtml+xml" properties="nav"/>
<?php foreach ($models['styles'] as $css) { ?>
		<item id="<?php echo $css; ?>-css" href="css/<?php echo $css; ?>.css" media-type="text/css"/>
<?php } ?>
		<item id="cover-page" href="cover.xhtml" media-type="application/xhtml+xml"/>
		<item id="cover-image" href="medias/cover.jpg" media-type="image/jpeg"/>
<?php foreach ($models['items'] as $path => $info) { ?>
<?php     $type = Zord::value('content', strtolower(pathinfo($path, PATHINFO_EXTENSION))); ?>
<?php     if (!empty($type)) { ?>
		<item id="<?php echo $info['id']; ?>" href="<?php echo $path; ?>" media-type="<?php echo $type; ?>"/>
<?php     } ?>
<?php } ?>
	</manifest>
	<spine toc="ncx">
		<itemref idref="cover-page" linear="yes"/>
<?php foreach ($models['items'] as $path => $info) { ?>
<?php     if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) == 'xhtml') {?>
		<itemref idref="<?php echo $info['id']; ?>" linear="yes"/>
<?php     } ?>
<?php } ?>
	</spine>
	<guide>
		<reference href="cover.xhtml" title="Cover" type="text"/>
<?php foreach ($models['items'] as $path => $info) { ?>
<?php     if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) == 'xhtml') {?>
		<reference href="<?php echo $path; ?>" title="<?php echo htmlspecialchars($info['title']); ?>" type="text"/>
<?php     } ?>
<?php } ?>
	</guide>
</package>