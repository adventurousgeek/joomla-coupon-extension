<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 * Originally created by Stanislav Scholtz, RuposTel.com
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );


if(empty($this->image_raw)) {
	echo 'Not found';
	JFactory::getApplication()->close(); 
}

Header('Content-Type: image/'.$this->extension);
echo $this->image_raw;  
JFactory::getApplication()->close(); 
