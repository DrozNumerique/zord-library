"<?php echo str_replace('"', '""', $metadata['title']); ?>",<?php echo Store::isbn($metadata['ean']); ?>,,,,,,,,<?php echo $metadata['uri']; ?>,<?php echo explode(',', $metadata['creator'][0])[0]; ?>,<?php echo $metadata['ean']; ?>,,fulltext,,"<?php echo $metadata['publisher']; ?>",monograph,<?php echo $metadata['date']; ?>,<?php echo $metadata['publication']; ?>,,,<?php echo explode(',', $metadata['editor'][0])[0]; ?>,,,P
