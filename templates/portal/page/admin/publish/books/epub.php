<?php if (file_exists(STORE_FOLDER.'epub'.DS.(Library::data($book, 'metadata.json', 'array')['epub'] ?? 'unknown').'.epub')) { ?>
                        		<td class="epub" data-action="epub" data-isbn="<?php echo $book; ?>" data-context="<?php echo $context; ?>">
          							<i class="display fa fa-book fa-fw" title="<?php echo $locale->tab->publish->epub; ?>"></i>
                        		</td>
<?php } else { ?>
								<td class="epub">
          							<i class="display fa fa-book fa-fw" style="text-decoration: line-through red 0.2rem; text-decoration-inset: -0.2rem;"></i>
								</td>
<?php }?>
                        		