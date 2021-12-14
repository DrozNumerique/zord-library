         				<div id="admin-import-publish">
         					<table class="admin-table">
         						<thead>
               						<tr>
                       					<th colspan="2">
                       						<span><?php echo $locale->tab->import->publish; ?></span>
                       					</th>
                       				</tr>
         						</thead>
         						<tbody>
                                <?php foreach(Zord::getConfig('context') as $name => $config) { ?>
                                <?php   if ($user->hasRole('admin', $name)) { ?>
                                    <tr>
                                    	<td>
                                    		<span><?php echo $config['title'][$lang] ?? $config['title'] ?? $name; ?></span>
                                    	</td>
                                    	<td class="state" data-type="publish">
                      						<input name="<?php echo $name; ?>" data-empty="no" type="hidden" value="no"/>
                      						<i class="display fa hidden fa-fw"></i>
                                    	</td>
                                    </tr>
                   				<?php   } ?>
               					<?php } ?>
          						</tbody>
         					</table>
       					</div>
