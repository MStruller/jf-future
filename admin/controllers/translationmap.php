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
 * $Id: translate.php 225M 2011-05-26 16:40:14Z (local) $
 * @package joomfish
 * @subpackage translate
 *
 */
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

JLoader::import('helpers.controllerHelper', JOOMFISH_ADMINPATH);

/**
 * The JoomFish Tasker manages the general tasks within the Joom!Fish admin interface
 *
 */
class TranslationmapController extends JController
{

	/** @var string		current used task */
	var $task = null;
	/** @var string		action within the task */
	var $act = null;
	/** @var array		int or array with the choosen list id */
	var $cid = null;
	/** @var string		file code */
	var $fileCode = null;
	/**
	 * @var object	reference to the Joom!Fish manager
	 * @access private
	 */
	var $_joomfishManager = null;

	/**
	 * PHP 4 constructor for the tasker
	 *
	 * @return joomfishTasker
	 */
	function __construct()
	{
		parent::__construct();
		
		//$this->registerDefaultTask('showTranslationmap');

		$this->act = JRequest::getVar('act', '');
		$this->task = JRequest::getVar('task', '');
		$this->cid = JRequest::getVar('cid', array(0));
		if (!is_array($this->cid))
		{
			$this->cid = array(0);
		}
		$this->fileCode = JRequest::getVar('fileCode', '');
		$this->_joomfishManager = JoomFishManager::getInstance();

		$includePath = JRequest::getVar('includePath', '');
		
		$includePath = json_decode(base64_decode($includePath));
		if($includePath)
		foreach($includePath as $include)
		{
			if(isset($include->path))
			$this->_joomfishManager->addIncludePath($include->path ,(isset($include->type) ? $include->type : null ));
		
		}

		//$this->registerTask('translationmap', 'showTranslationmap');
		//$this->registerTask('edit', 'editTranslation');

		// Populate data used by controller
		$this->_catid = JFactory::getApplication()->getUserStateFromRequest('selected_catid', 'catid', '');
		$this->_select_language_id = JFactory::getApplication()->getUserStateFromRequest('selected_lang', 'select_language_id', '-1');
		$this->_language_id = JRequest::getVar('language_id', $this->_select_language_id);
		$this->_select_language_id = ($this->_select_language_id == -1 && $this->_language_id != -1) ? $this->_language_id : $this->_select_language_id;

		// Populate common data used by view
		// get the view
		$this->view = $this->getView("translationmap");
		$model = $this->getModel('translationmap');
		$this->view->setModel($model, true);

		// Assign data for view
		$includePath = JRequest::getVar('includePath', '');
		$this->view->assignRef('includePath', $includePath);
		
		$this->view->assignRef('catid', $this->_catid);
		$this->view->assignRef('select_language_id', $this->_select_language_id);
		$this->view->assignRef('task', $this->task);
		$this->view->assignRef('act', $this->act);

	}

	function save()
	{
		$reference_id = JRequest::getVar('reference_id', '');
		$translation_id = JRequest::getVar('translation_id', '');
		
		$item_id = JRequest::getVar('item_id', ''); //this must load for the ?
		
		$contentElement = $this->_joomfishManager->getContentElement($this->_catid);
		$translationClass = $contentElement->getTranslationObjectClass();
		$translationObject = new $translationClass( $this->_language_id, $contentElement );
		
		$jfm = JoomFishManager::getInstance();
		$language = $jfm->getLanguageByID($this->_language_id);
		$lang_code = $language->code;

		if($success = $translationObject->generateTranslationMapManual($reference_id,$translation_id,$contentElement->getTableName(),$lang_code))
		{
			//we tell the request ajax that is ok
			echo '1';
			exit;
		}
		//we tell the request ajax that is not ok
		echo '0';
		exit;
		echo false;
		return false;
	}


	function edit()
	{
		// get the view
		$this->view = $this->getView("translationmap", "html");

		// Set the layout
		$this->view->setLayout('edit');

		// Assign data for view - should really do this as I go along
		//$this->view->assignRef('showOrgLanguage', $showOrgLanguage);
		/*
		$this->view->assignRef('rows', $rows);
		$this->view->assignRef('search', $search);
		$this->view->assignRef('pageNav', $pageNav);
		$this->view->assignRef('clist', $clist);
		*/
		$reference_id = JRequest::getVar('reference_id', '');
		
		$contentElement = $this->_joomfishManager->getContentElement($this->_catid);
		$translationClass = $contentElement->getTranslationObjectClass();
		$translationObject = new $translationClass( $this->_language_id, $contentElement );
		
		//$translationObject->readFromRow($reference_id);
		$translationObject->loadFromContentID($reference_id);
		//$model = $this->getModel('translate');
		//an array of table objects
		//$transmaprows = $model->getTranslationMap($this->_catid,$this->_language_id);
		/*
		TODO Filter
		*/
		
		//$filters = JFactory::getApplication()->getUserStateFromRequest("com_joomfish.view.translationmap.filters", 'filters', array(0));
		$filters = array(0);
		$filter_language = JFactory::getApplication()->getUserStateFromRequest("com_joomfish.view.translationmap.filter_language", 'filter_language',null);
		if($filter_language)
		$filters['language'] = 'c.language=\''.$filter_language.'\'';
		
		$order = JFactory::getApplication()->getUserStateFromRequest("com_joomfish.view.translationmap.filter_order", 'filter_order', null);
		$orderDir = JFactory::getApplication()->getUserStateFromRequest("com_joomfish.view.translationmap.filter_order_Dir", 'filter_order_Dir', null);
		$this->view->assignRef('order', $order);
		$this->view->assignRef('orderDir', $orderDir);
		$this->view->assignRef('filter_language', $filter_language);
		
		
		$contentmaprows = $contentElement->getContentMap($this->_language_id,$filters,$order,$orderDir);
		$this->view->assignRef('contentmaprows', $contentmaprows);
		
		$this->view->assignRef('contentElement', $contentElement);
		$this->view->assignRef('translationObject', $translationObject);
		//$rows[$i] = $translationObject;
		
		$this->view->assignRef('reference_id', $reference_id);
		/*
		$this->view->assignRef('filterlist', $filterHTML);
		*/
		$this->view->assignRef('language_id', $this->_language_id);

		$this->view->display();
	}


}
