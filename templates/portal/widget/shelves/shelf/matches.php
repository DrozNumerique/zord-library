<?php foreach ($models['matches'] as $part => $matches) { ?>
							<tr class="matches<?php reset($models['matches']); echo $part == key($models['matches']) ? ' first' : ''; ?>">
								<td colspan="7">
									<div class="part" data-folded="true">
										<header>
											<h3><?php echo $locale->chapter.' : '.$models['parts'][$part]['title']; ?> &#x21e8; <span class="instances"><?php echo count($matches) ?> <?php echo $locale->instances; ?></span></h3>
										</header>
										<div style="display:none;">
<?php   foreach ($matches as $match) { ?>
											<div class="match">
												<div class="snip" data-book="<?php echo $models['isbn']; ?>" data-part="<?php echo $part; ?>" data-match="<?php echo $match['keyword']; ?>" data-index="<?php echo $match['index']; ?>">
													<span class="left"><?php echo $match['left']; ?></span>
													<span class="keyword"><b><?php echo $match['keyword']; ?></b></span>
													<span class="right"><?php echo $match['right']; ?></span>
													<div class="tooltip">
														<span class="left"><?php echo $match['left']; ?>
														<span class="keyword"><b><?php echo $match['keyword']; ?></b></span>		   								
														<span class="right"><?php echo $match['right']; ?></span>	   								
													</div>
												</div>
											</div>
<?php   } ?>
										</div>
									</div>
								</td>
							</tr>
<?php } ?>
<?php if (count($models['matches']) > 0) { ?>
							<tr class="matches last">
								<td colspan="7">&nbsp;</td>
							</tr>
<?php }?>
