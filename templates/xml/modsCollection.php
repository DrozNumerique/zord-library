<modsCollection xmlns="http://www.loc.gov/mods/v3"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-2.xsd">
<?php 
    	foreach ($models['books'] as $isbn) {
    	    $metadata = Library::data($isbn, 'meta', 'array');
?>
	<mods>
		<typeOfResource>text</typeOfResource>
		<genre authority="local">book</genre>
		<genre authority="marcgt">book</genre>
<?php 
			if (isset($metadata['title'])) {
			    $title = $metadata['title'];
			    if (isset($metadata['subtitle'])) {
			        $title .= '. '.$metadata['subtitle'];
			    }
			    $title = Library::xmlspecialchars($title);
?>
		<titleInfo>
			<title><?php echo $title; ?></title>
		</titleInfo>
<?php
			}
			if (isset($metadata['description'])) {
			    if (is_array($metadata['description'])) {
			        foreach($metadata['description'] as $lang => $description) {
?>
		<abstract lang="<?php echo $lang; ?>"><?php echo Library::xmlspecialchars($description); ?></abstract>
<?php 
			        }
			    } else {
?>
		<abstract><?php echo Library::xmlspecialchars($metadata['description']); ?></abstract>
<?php
			    }
			}
		    if (isset($metadata['publisher'])) {
?>
		<originInfo>
<?php 
		        if (isset($metadata['pubplace'])) {
?>
			<place>
				<placeTerm type="text"><?php echo Library::xmlspecialchars($metadata['pubplace']); ?></placeTerm>
			</place>	
<?php
		        }
?>
			<publisher><?php echo Library::xmlspecialchars($metadata['publisher']); ?></publisher>
<?php 
		        if (isset($metadata['date'])) {
?>
			<copyrightDate><?php echo explode('-', $metadata['date'])[0]; ?></copyrightDate>
<?php
		        }
?>
			<issuance>monographic</issuance>
		</originInfo>
<?php
		    }
		    if (isset($metadata['isbn'])) {
?>
		<identifier type="isbn"><?php echo Library::xmlspecialchars($metadata['isbn']); ?></identifier>
<?php
		    }
		    if (isset($metadata['uri'])) {
?>
		<location>
			<url usage="primary display"><?php echo $metadata['uri']; ?></url>
		</location>
<?php
		    }
		    if (isset($metadata['language'])) {
?>
		<language>
			<languageTerm type="text"><?php echo $metadata['language']; ?></languageTerm>
		</language>
<?php
		    }
		    if (isset($metadata['rights'])) {
?>
		<accessCondition type="restrictionOnAccess"><?php echo Library::xmlspecialchars($metadata['rights']); ?></accessCondition>
		<?php
		    }
		    if (isset($metadata['relation'])) {
?>
		<relatedItem type="series">
			<titleInfo>
				<title><?php echo Library::xmlspecialchars($metadata['relation']); ?></title>
			</titleInfo>
<?php 
		        if (isset($metadata['collection_number'])) {
?>
			<part>
				<detail type="volume">
					<number><?php echo Library::xmlspecialchars($metadata['collection_number']); ?></number>
				</detail>
			</part>
<?php
		        }
?>
		</relatedItem>
<?php
		    }
		    if (isset($metadata['pages'])) {
?>
		<physicalDescription>
			<extent><?php echo Library::xmlspecialchars($metadata['pages']).' p.'; ?></extent>
		</physicalDescription>
<?php
		    }
		    foreach(['creator' => 'aut', 'editor' => 'edt'] as $key => $value) {
		        if (isset($metadata[$key])) {
		            foreach($metadata[$key] as $actor) {
			            $name = explode(',',$actor);
?>
		<name type="personal">
			<namePart type="family"><?php echo Library::xmlspecialchars($name[0]); ?></namePart>
<?php 
			            if (isset($name[1])) {
?>
			<namePart type="given"><?php echo Library::xmlspecialchars($name[1]); ?></namePart>
<?php
		                }
?>
			<role>
				<roleTerm type="code" authority="marcrelator"><?php echo $value; ?></roleTerm>
			</role>
		</name>
<?php
			        }
			    }
		    }
?>
	</mods>
<?php } ?>
</modsCollection>