<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">
    <head>
		<title><?php echo htmlspecialchars(Library::title($models['metadata'])); ?></title>
     </head>
    <body>
    	<section>
    		<header>
    			<h1>Sommaire</h1>
    		</header>
    		<nav xmlns:epub="http://www.idpf.org/2007/ops" epub:type="toc" id="toc">
<?php $level = 0; ?>    	
<?php $last = end($models['navbar']); ?>		
<?php foreach($models['navbar'] as $point) { ?>
<?php     if ($point['level'] > $level) { ?>
<?php         for ($i = $level ; $i < $point['level'] ; $i++) { ?>
				<ol>
<?php         } ?>
<?php         $level = $point['level']; ?>
<?php     } else if ($point['level'] < $level) { ?>
					</li>
<?php         for ($i = $level ; $i > $point['level'] ; $i--) { ?>
				</ol></li>
<?php         } ?>
<?php         $level = $point['level']; ?>
<?php     } else if ($level != 0) { ?>
					</li>
<?php     } ?>
    				<li>
    					<a data-level="<?php echo $point['level']; ?>" href="<?php echo $point['part']; ?>.xhtml#<?php echo $point['id']; ?>"><?php echo htmlspecialchars($point['text']); ?></a>
<?php     if ($point == $last) { ?>
					</li>
<?php         for ($i = $level ; $i > 1 ; $i--) { ?>
				</ol></li>
<?php         } ?>
<?php     }?>

<?php } ?>
				</ol>
    		</nav>
    	</section>
    </body>
</html>