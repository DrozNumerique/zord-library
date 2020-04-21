         				<div id="admin-import-publish">
         					<table class="admin-table" style="margin:auto;">
         						<thead>
               						<tr>
                       					<th style="width:auto;">
                       						<span class="sort" data-column="0">
                       							<?php echo $locale->tab->context->name; ?>
                       							<i class="fa fa-sort fa-fw" title="<?php echo $locale->tab->publish->select; ?>"></i>
                       						</span>
                       					</th>
                       					<th style="width:30px;">
                       						<i class="fa fa-book fa-fw" title="<?php echo $locale->tab->publish->select; ?>"></i>
                       					</th>
                       				</tr>
         						</thead>
         						<tbody>
                                <?php foreach(Zord::getConfig('context') as $name => $config) { ?>
                                <?php   if ($user->hasRole('admin', $name)) { ?>
                                    <tr class="sort">
                                    	<td><?php echo $name; ?></td>
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
