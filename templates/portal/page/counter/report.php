        <table>
            <thead>
                <tr>
                    <th class="counter" colspan="<?php echo count($models['counter']['months']) + 4; ?>">Book Report <?php echo $models['type']; ?> (R4) - <?php echo $models['counter']['reports'][$models['type']]['title']; ?></th>
                </tr>
                <tr>
                    <th class="counter" colspan="<?php echo count($models['counter']['months']) + 4; ?>"><?php echo $models['counter']['scope']?? $locale->all; ?></th>
                </tr>
                <tr>
                    <th class="counter" colspan="<?php echo count($models['counter']['months']) + 4; ?>"><?php echo 'Period covered by the report: '.$models['counter']['range']['start'].' to '.$models['counter']['range']['end']; ?></th>
                </tr>
                <tr>
                    <th class="counter" colspan="<?php echo count($models['counter']['months']) + 4; ?>"><?php echo 'Date run: '.date('Y-m-d') ?></th>
                </tr>
                <tr>
                    <th class="counter"></th>
                    <th>Portals</th>
                    <th>ISBN</th>
                    <?php foreach ($models['counter']['months'] as $month) { ?>
                    <th><?php echo $month; ?></th>
                    <?php } ?>
                    <th>Total</th>
                </tr>
                <tr>
                    <th class="counter">Total for all titles</th>
                    <th><?php echo isset($models['counter']['reports'][$models['type']]['context']) ? implode(", ",$models['counter']['reports'][$models['type']]['context']) : ''; ?></th> <!--All Portals-->
                    <th></th>
<?php foreach ($models['counter']['months'] as $month) { ?>
                    <th><?php echo isset($models['counter']['reports'][$models['type']]['counts']['months'][$month]['total']) && $models['counter']['reports'][$models['type']]['counts']['months'][$month]['total'] != 0 ? $models['counter']['reports'][$models['type']]['counts']['months'][$month]['total'] : ''; ?></th>
<?php } ?>
                    <th><?php echo isset($models['counter']['reports'][$models['type']]['counts']['total']) && $models['counter']['reports'][$models['type']]['counts']['total'] != 0 ? $models['counter']['reports'][$models['type']]['counts']['total'] : ''; ?></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($models['counter']['reports'][$models['type']]['books'] as $book => $context) { ?>
                <tr>
                    <td><?php echo $models['counter']['books'][$book] ?></td>
                    <td><?php echo implode(', ', $context); ?></td>
                    <td><?php echo $book ?></td>
<?php foreach($models['counter']['months'] as $month) { ?>
                    <td> <?php echo isset($models['counter']['reports'][$models['type']]['counts']['months'][$month]['books'][$book]) && $models['counter']['reports'][$models['type']]['counts']['months'][$month]['books'][$book] != 0 ? $models['counter']['reports'][$models['type']]['counts']['months'][$month]['books'][$book] : ''; ?></td>
<?php } ?>
                    <td> <?php echo $models['counter']['reports'][$models['type']]['counts']['books'][$book] != 0 ? $models['counter']['reports'][$models['type']]['counts']['books'][$book] : ''; ?></td>
                </tr>
<?php } ?>
            </tbody>
        </table>
