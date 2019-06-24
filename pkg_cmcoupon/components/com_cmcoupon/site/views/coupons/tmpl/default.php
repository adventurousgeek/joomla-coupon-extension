<?php 
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

 
// no direct access
defined('_JEXEC') or die('Restricted access');

?>

<script language="javascript" type="text/javascript">
function tableOrdering( order, dir, task ) {
        var form = document.adminForm;

        form.filter_order.value = order;
        form.filter_order_Dir.value = dir;
        document.adminForm.submit( task );
}
</script>
<?php if ($this->params->get( 'show_page_title', 1)) : ?>
	<h2><?php echo $this->escape($this->params->get('page_title')); ?></h2>
<?php endif; ?>




<form id="adminForm" name="adminForm" action="<?php echo JRoute::_( 'index.php?option=com_cmcoupon&view=coupons' );?>" method="post">
<div class="contentpane<?php echo $this->params->get( 'pageclass_sfx' ) ?>">
	<div><?php echo $this->pageNav->getResultsCounter(); ?></div>
	<?php if(!empty($this->rows)) { ?>
		<table class="table" cellspacing="0" cellpadding="0" border="0" width="90%">
		<thead>
		<tr>
			<th class="sectiontableheader"><?php echo JHTML::_('grid.sort', 'COM_CMCOUPON_CP_COUPON_CODE', 'coupon_code', $this->lists['order_Dir'], $this->lists['order'] ); ?></th>
			<th class="sectiontableheader"><?php echo JHTML::_('grid.sort', 'COM_CMCOUPON_CP_VALUE_TYPE', 'coupon_value_type', $this->lists['order_Dir'], $this->lists['order'] ); ?></th>
			<th class="sectiontableheader"><?php echo JHTML::_('grid.sort', 'COM_CMCOUPON_CP_VALUE', 'coupon_value', $this->lists['order_Dir'], $this->lists['order'] ); ?></th>
			<th class="sectiontableheader"><?php echo JText::_('COM_CMCOUPON_GC_BALANCE'); ?></th>
			<th class="sectiontableheader"><?php echo JHTML::_('grid.sort', 'COM_CMCOUPON_CP_DATE_START', 'startdate', $this->lists['order_Dir'], $this->lists['order'] ); ?></th>
			<th class="sectiontableheader"><?php echo JHTML::_('grid.sort', 'COM_CMCOUPON_CP_EXPIRATION', 'expiration', $this->lists['order_Dir'], $this->lists['order'] ); ?></th>
		</tr></thead>
		<tfoot><tr><td colspan="12"><?php echo $this->pageNav->getListFooter(); ?></td></tr></tfoot>
		<tbody>
		<?php 
		$i=-1;
		foreach($this->rows as $row) {
			$i++;
			$price = '';
			if ( ! empty( $row->coupon_value ) ) {
				$price = 'amount' == $row->coupon_value_type
					? AC()->storecurrency->format( $row->coupon_value )
					: round( $row->coupon_value ) . '%';
			}
			?>
			<tr class="sectiontableentry<?php echo ($i%2)+1;?>">
				<td><?php 
						echo $row->coupon_code; 
						if(!empty($row->filename)) 
							echo ' <a class="modal" rel="{handler: \'iframe\', size: {x:600, y: 550}}" href="'.JRoute::_('index.php?option=com_cmcoupon&tmpl=component&view=coupons&layout=image&coupon_code='.$row->coupon_code).'">
										<img src="'.JURI::root(true).'/components/com_cmcoupon/assets/images/icon_view.png" style="height:20px;" >
									</a>';
					?></td>
				<td><?php 
					switch ( $row->function_type ) {
						case 'shipping':
							echo JText::_( 'COM_CMCOUPON_CP_SHIPPING' );
							break;
						case 'giftcert':
							echo JText::_( 'COM_CMCOUPON_GC_GIFTCERT' );
							break;
						default:
							echo JText::_( 'COM_CMCOUPON_CP_COUPON' );
					}
				?></td>
				<td><?php echo $price; ?></td>
				<td><?php echo isset($row->balance) ? $row->str_balance : '---'; ?></td>
				<td><?php echo empty($row->startdate) ? '' : AC()->helper->get_date($row->startdate); ?></td>
				<td><?php echo empty($row->expiration) ? '' : AC()->helper->get_date($row->expiration); ?></td>
			</tr>
		<?php } ?>
		</tbody>
		</table>
	<?php } ?>
</div>

<input type="hidden" name="option" value="com_cmcoupon" />
<input type="hidden" name="view" value="coupons" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
<input type="hidden" name="filter_order_Dir" value="" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>
