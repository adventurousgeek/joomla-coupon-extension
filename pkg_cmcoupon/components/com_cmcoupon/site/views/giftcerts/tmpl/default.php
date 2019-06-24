<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

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


<?php if(AC()->param->get('enable_frontend_balance', 0)==1) { ?>

<form id="adminForm" name="adminForm" action="<?php echo JRoute::_( 'index.php?option=com_cmcoupon&view=giftcerts' );?>" method="post">
	<span><?php echo JText::_('COM_CMCOUPON_GC_ADD'); ?></span>
	<input type="text" name="voucher" />
	<input type="submit" value="<?php echo JText::_('COM_CMCOUPON_GBL_APPLY'); ?>" />
	
	<input type="hidden" name="option" value="com_cmcoupon" />
	<input type="hidden" name="view" value="giftcerts" />
	<input type="hidden" name="task" value="voucher_to_balance" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
<br />


<div><?php echo JText::_('COM_CMCOUPON_GC_GIFTCERT_BALANCE'); ?>: <?php echo AC()->storecurrency->format( $this->balance ); ?></div>
<br />

<?php } ?>





<form id="adminForm" name="adminForm" action="<?php echo JRoute::_( 'index.php?option=com_cmcoupon&view=giftcerts' );?>" method="post">
<div class="contentpane<?php echo $this->params->get( 'pageclass_sfx' ) ?>">
	<div><?php echo $this->pageNav->getResultsCounter(); ?></div>
	<?php if(!empty($this->rows)) { ?>

		<table class="table" cellspacing="0" cellpadding="0" border="0" width="90%">
		<thead>
		<tr>
			<th class="sectiontableheader"><?php echo JText::_('COM_CMCOUPON_GBL_DATE'); ?></th>
			<th class="sectiontableheader"><?php echo JText::_('COM_CMCOUPON_GBL_DESCRIPTION'); ?></th>
			<th class="sectiontableheader"><?php echo JText::_('COM_CMCOUPON_CP_AMOUNT'); ?></th>
		</tr></thead>
		<tfoot><tr><td colspan="12"><?php echo $this->pageNav->getListFooter(); ?></td></tr></tfoot>
		<tbody>
		<?php 
		$i=-1;
		//printrx($this->rows);
		foreach($this->rows as $row) {
			$i++;
			$description = '';
			if($row->type=='credit') $description = JText::_('COM_CMCOUPON_GC_CLAIM').' ('.$row->coupon_code.')';
			elseif($row->type=='debit') $description = JText::_('COM_CMCOUPON_GC_PAYMENT_FOR_ORDER').' ('.$row->order_obj->order_number.')';
			?>
			<tr class="sectiontableentry<?php echo ($i%2)+1;?>">
				<td><?php echo !empty($row->timestamp) ? AC()->helper->get_date( $row->timestamp ) : ''; ?></td>
				<td><?php echo $description; ?></td>
				<td><?php echo AC()->storecurrency->format( $row->type=='debit' ? ($row->amount*-1) : $row->amount ); ?></td>
			</tr>
		<?php } ?>
		</tbody>
		</table>
		
	<?php } ?>
</div>

<input type="hidden" name="option" value="com_cmcoupon" />
<input type="hidden" name="view" value="giftcerts" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="filter_order_Dir" value="" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>
