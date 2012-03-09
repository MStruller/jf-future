<?php
/**
 * Joom!Fish - Multi Lingual extention and translation manager for Joomla!
 * Copyright (C) 2003 - 2012, Think Network GmbH, Munich
 *
 * All rights reserved. The Joom!Fish project is a set of extentions for
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,USA.
 *
 * The "GNU General Public License" (GPL) is available at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -----------------------------------------------------------------------------
 * $Id: TranslateParams.php 225M 2011-05-26 16:40:14Z (local) $
 * @package joomfish
 * @subpackage Models
 *
 */
defined('_JEXEC') or die('Restricted access');

class TranslateFormsCategories extends TranslateForms
{
	function __construct($fields=null,$contentElement = null)
	{
		parent::__construct($fields,$contentElement);
		$this->component = 'com_categories';
	}

	public function setFields($jfFields,$formFields)
	{
		//
		
		foreach($jfFields as $field)
		{
			switch($field->Name)
			{
				case 'extension':
					//FB::dump($field,'field');
					//$this->trans_form->bind(array($field->Name => $field->translationContent->value));
					$this->orig_form->setFieldAttribute('parent_id','extension',$field->originalValue);
					$this->trans_form->setFieldAttribute('parent_id','extension',$field->originalValue);
					
				break;
				
				case 'parent_id':
					//$this->orig_form->setFieldAttribute('parent_id','value',$field->originalValue);
					//$this->trans_form->setFieldAttribute('parent_id','value',$field->originalValue);
					//FB::dump($field->originalValue,'parent_id');
					//FB::dump(JRequest::getVar('id'));
					JRequest::setVar('id',$field->translationContent->id);//originalValue);
					$this->orig_form->setFieldAttribute('parent_id','parent','true');
					$this->trans_form->setFieldAttribute('parent_id','parent','true');
				break;
			}
		}
		
		
	}


	protected function _initForms()
	{
		$this->loadLangComponent();
		
		// for J2.5 beta2 and J2.5 RC1 and J2.5  we need to set the table
		// The Model load this and without nothing found
		JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_categories'.DS.'tables');
		
		$cid = JRequest::getVar('cid', array(0));
		//$oldcid = $cid;
		$translation_id = 0;
		$contentid = 0;
		$language_id = 0;
		if (strpos($cid[0], '|') !== false)
		{
			list($translation_id, $contentid, $language_id) = explode('|', $cid[0]);
		}
		
		// if we have an existing translation then load this directly and have we not we load the reference
		// all fields we want not from an existing reference on new we handle in TrannslateParamsContent
		$translation_id = $translation_id ? $translation_id : $contentid;
		
		/*
		JRequest::setVar("cid", array($contentid));
		JRequest::setVar("edit", true);

		// model's populate state method assumes the id is in the request object!
		$oldid = JRequest::getInt("article_id", 0);
		// Take care of the name of the id for the item
		JRequest::setVar("article_id", $contentid);
		*/
		
		//JLoader::import('models.JFContentModelItem', JOOMFISH_ADMINPATH);
		//JLoader::import('models.TranslateModelContent', JoomfishExtensionHelper::getExtraPath('base'));
		JLoader::import('TranslateModelCategories', JoomfishExtensionHelper::getExtraPath('models'));
		
		// NOW GET THE TRANSLATION - IF AVAILABLE
		//$this->trans_model = new JFContentModelItem();
		//$this->orig_model = new JFContentModelItem();
		$this->trans_model = new TranslateModelCategories(array('ignore_request'=>true));
		$this->orig_model = new TranslateModelCategories(array('ignore_request'=>true));
		//array('ignore_request'=>true)
		
		//$model->setState('category.extension','');
		/*
		$result->parent_id = $this->getState('category.parent_id');
		$result->extension = $this->getState('category.extension')
		
		*/
		//$this->trans_model->setState('article.id', $translation_id);
		
		//$this->orig_model->setState('article.id', $contentid);
		
		$this->setForms($translation_id, $contentid);
		
		
		//need for J2.5
		//$this->orig_form->setFieldAttribute('catid','extension','com_content');
		//$this->trans_form->setFieldAttribute('catid','extension','com_content');
		
		/*
		if we want for new translation an empty value on an field see TranslateParamsContent
		
		
		foreach($this->fields as $field)
		{
			switch($field->Name)
			{
				case the fieldname to change value:
					$this->forms->trans_form->bind(array($field->Name => json_decode($field->translationContent->value)));
				break;
			}
		}
		
		*/
		
		/*<!-- TODO this must go to other place -->*/
		$this->setStyle();

	}
}

?>
