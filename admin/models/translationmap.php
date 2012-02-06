<?php

/**
 * Joom!Fish - Multi Lingual extention and translation manager for Joomla!
 * Copyright (C) 2003 - 2012, Think Network GmbH, Munich
 *
 * All rights reserved.  The Joom!Fish project is a set of extentions for
 * the content management system Joomla!. It enables Joomla!
 * to manage multi lingual sites especially in all dynamic information
 * which are stored in the database.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,USA.
 *
 * The "GNU General Public License" (GPL) is available at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -----------------------------------------------------------------------------
 * $Id: translate.php 226 2011-05-27 07:29:41Z alex $
 * @package joomfish
 * @subpackage Models
 *
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

JLoader::register('JFModel', JOOMFISH_ADMINPATH . DS . 'models' . DS . 'JFModel.php');

/**
 * This is the corresponding module for translation management
 * @package		Joom!Fish
 * @subpackage	Translationmap
 */
class TranslationmapModelTranslationmap extends JFModel
{

	protected $_modelName = 'translationmap';

	/**
	 * return the model name
	 */
	public function getName()
	{
		return $this->_modelName;

	}
	
	//must add $this->org_language_id, $this->contentElement
	
	public function checkTranslationMap( $language_id,$org_language_id, $contentElement )
	{
		//we need the id from the item
		//so we can get the language from item?
		if($org_language_id == $language_id)
		{
			$joomfishManager = JoomFishManager::getInstance();
			$lang = $joomfishManager->getLanguageByID($language_id);
			$db = JFactory::getDBO();

			$referencefield = "id";

			$referencefield = $contentElement->PrimaryKey;
			$tablename = $contentElement->referenceInformation['tablename'];
				
			$more1 = "\nSELECT tm4.reference_id from #__jf_translationmap as tm4 WHERE tm4.reference_table=".$db->quote($tablename);
			$more2 = "\nSELECT tm5.translation_id from #__jf_translationmap as tm5 WHERE tm5.reference_table=".$db->quote($tablename);
			$more = " ( ".$db->quote( $this->id). " IN (".$more2." ) OR ".$db->quote( $this->id). " IN (".$more1." )) ";

			$query = $db->getQuery(true);
			$query->select('*');
			$query->from('#__' . $tablename . ' as c');
			$query->where($more);
			$db->setQuery($query);
			$transmap = $db->loadObjectList();
				
			if(!count($transmap))
			{
				$link = '<a href="#" id="showTransMapValue'. $this->id.'" onClick="showTransmapEditor(\'showTransMap'.$this->id.'\', \''.$lang->lang_id.'\', \''.$this->id.'\', \''.$tablename.'\');">';
						$link .= JHTML::_('image.administrator', 'menu/icon-16-config.png', '/images/', null, null, JText::_( 'EDIT' ));
					$link .= '</a>';
				
				$link .= '<script type="text/javascript">';
	$link .= "<![CDATA[
function showTransmapEditor(fieldId, language_id, reference_id, catid) {
	var field = document.getElementById(fieldId);
	SqueezeBox.initialize();
	SqueezeBox.fromElement(field, {
		handler: 'iframe',
		url: '".JURI::base()."index.php?option=com_joomfish&task=translationmap.edit&tmpl=component&catid='+catid+'&language_id='+language_id+'&reference_id='+reference_id,
		size: {x: 750, y: 500}
	});
}
]]>
</script>";
				
				return $link;
			}
		}
		return '';
	}


	
	/*
	MS: add to save translationMap from manuell set from user
	*/
	public function generateTranslationMapManual($originalid, $translationid,$tablename,$language)
	{
		
		$db = JFactory::getDbo();
		$sql = "replace into #__jf_translationmap (reference_id, translation_id, reference_table, language ) values ($originalid, $translationid," . $db->quote($tablename) . "," . $db->quote($language) . ")";
		$db->setQuery($sql);
		$success = $db->query();
		
		if($success)
		{
			return true;
		}
		
		return false;
	}
}

?>
