<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:bibo="http://purl.org/ontology/bibo/"
         xmlns:dc="http://purl.org/dc/terms/"
         xmlns:foaf="http://xmlns.com/foaf/0.1/"
         xmlns:address="http://schemas.talis.com/2005/address/schema#">
  <bibo:Book rdf:about="<?php echo $baseURL.'/'.$models['metadata']['ean']; ?>">
    <dc:title><?php echo Library::title($models['metadata']); ?></dc:title>
<?php if (isset($models['metadata']['description'])) { ?>
<?php   if (is_array($models['metadata']['description'])) { ?>
<?php     foreach ($models['metadata']['description'] as $lang => $description) { ?>
    <dc:abstract xml:lang="<?php echo $lang; ?>"><?php echo $description; ?></dc:abstract>
<?php     }?>
<?php   } else if (is_string($models['metadata']['description'])) { ?>
    <dc:abstract><?php echo $models['metadata']['description']; ?></dc:abstract>
<?php   }?>
<?php } ?>
<?php if (isset($models['metadata']['publisher'])) { ?>
    <dc:publisher>
      <foaf:Organization>
<?php if (isset($models['metadata']['pubplace'])) { ?>
        <address:localityName><?php echo $models['metadata']['pubplace']; ?></address:localityName>
<?php   }?>
        <foaf:name><?php echo $models['metadata']['publisher']; ?></foaf:name>
      </foaf:Organization>
    </dc:publisher>
<?php } ?>
<?php if (isset($models['metadata']['date'])) { ?>
    <dc:date><?php echo $models['metadata']['date']; ?></dc:date>
<?php } ?>
<?php if (isset($models['metadata']['language'])) { ?>
    <dc:language><?php echo $models['metadata']['language']; ?></dc:language>
<?php } ?>
<?php if (isset($models['metadata']['isbn'])) { ?>
    <bibo:isbn13><?php echo $models['metadata']['isbn']; ?></bibo:isbn13>
<?php } ?>
<?php if (isset($models['metadata']['uri'])) { ?>
    <bibo:uri><?php echo $models['metadata']['uri']; ?></bibo:uri>
<?php } ?>
<?php if (isset($models['metadata']['type'])) { ?>
    <dc:type><?php echo $models['metadata']['type']; ?></dc:type>
<?php } ?>
<?php if (isset($models['metadata']['rights'])) { ?>
    <dc:rights><?php echo $models['metadata']['rights']; ?></dc:rights>
<?php } ?>
<?php if (isset($models['metadata']['format'])) { ?>
    <dc:format><?php echo $models['metadata']['format']; ?></dc:format>
<?php } ?>
<?php if (isset($models['metadata']['relation'])) { ?>
    <dc:isPartOf>
      <bibo:Series>
        <dc:title><?php echo $models['metadata']['relation']; ?></dc:title>
<?php if (isset($models['metadata']['collection_number'])) { ?>
        <bibo:number><?php echo $models['metadata']['collection_number']; ?></bibo:number>
<?php } ?>
      </bibo:Series>
    </dc:isPartOf>
<?php } ?>
<?php if (isset($models['metadata']['page'])) {?>
    <bibo:numPages rdf:datatype="http://www.w3.org/2001/XMLSchema#integer"><?php echo $models['metadata']['page']; ?></bibo:numPages>
<?php } ?>
<?php if (isset($models['metadata']['creator'])) { ?>
<?php   foreach ($models['metadata']['creator'] as $creator) { ?>
<?php     $name = explode(',',$creator); ?>
    <dc:creator>
      <foaf:Person>
        <foaf:surname><?php echo $name[0]; ?></foaf:surname>
<?php     if (count($name) > 0) { ?>
        <foaf:givenname><?php echo $name[1]; ?></foaf:givenname>
<?php     } ?>
      </foaf:Person>
    </dc:creator>
<?php   } ?>
<?php } ?>
<?php if (isset($models['metadata']['editor'])) { ?>
<?php   foreach ($models['metadata']['editor'] as $editor) { ?>
<?php     $name = explode(',',$editor); ?>
    <bibo:editor>
      <foaf:Person>
        <foaf:surname><?php echo $name[0]; ?></foaf:surname>
<?php     if (count($name) > 0) { ?>
        <foaf:givenname><?php echo $name[1]; ?></foaf:givenname>
<?php     } ?>
      </foaf:Person>
    </bibo:editor>
<?php   } ?>
<?php } ?>
  </bibo:Book>
</rdf:RDF>
