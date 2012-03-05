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
	
	
	/**
	 * Loading of specific XML files
	*/
	private function _loadContentElement($tablename) {
		$file = JOOMFISH_ADMINPATH .'/contentelements/'.$tablename.".xml";
		if (file_exists($file)){
			unset($xmlDoc);
			$xmlDoc = new DOMDocument();
			if ($xmlDoc->load( $file)) {
				$element = $xmlDoc->documentElement;
				if ($element->nodeName == 'joomfish') {
					if ( $element->getAttribute('type')=='contentelement' ) {
						return $xmlDoc;
						
						$nameElements = $element->getElementsByTagName('name');
						$nameElement = $nameElements->item(0);
						$name = strtolower( trim($nameElement->textContent) );
						$contentElement = new ContentElement( $xmlDoc );
						$this->_contentElements[$contentElement->getTableName()] = $contentElement;
						return $contentElement;
					}
				}
			}
		}
		return null;
	}
	
	
	public function getFilters($row = null,$contentElement,$language_id = null)
	{
		$doc = JFactory::getDocument();
		static $translationMapFilterScript;
		static $filters = array();
		if (!isset($translationMapFilterScript))
		{
			$script = <<<SCRIPT
var jfFilters = new Array();
//var fields = new Array();
SCRIPT;
			$doc->addScriptDeclaration($script);
			$translationMapFilterScript = true;
			$catid = $contentElement->getTableName();
			$xml = $this->_loadContentElement($catid);
			if($xml)
			{
				$xpath = new DOMXPath($xml);
				$filterElement = $xpath->query('//reference/treatment/filters');
				if($filterElement && $filterElement->item(0)) 
				{
					$filterElement = $filterElement->item(0);
					$allFilters = $filterElement->getElementsByTagName('filter');

					foreach ($allFilters as $filter)
					{
						$filters[] = trim($filter->textContent);
					}
				}
			}
		}
		/*
		if(!row)
		{
			$translationClass = $contentElement->getTranslationObjectClass();
			$translationObject = new $translationClass( $language_id, $contentElement );
		}
		*/
		if(count($filters))
		{
			//$rowFromId = $row->loadFromContentID($row->id);
			//$contentElement = $row->getContentElement();
			$fields = array();
			foreach($filters as $filter)
			{
				if(isset($contentElement->referenceInformation['table']->IndexedFields[$filter]->originalValue))
				{
					$fields[$filter] = $contentElement->referenceInformation['table']->IndexedFields[$filter]->originalValue;
				}
				
			}
			
			if(count($fields))
			{
				//$new_array = array_map(create_function('$key, $value', 'return "fields[\"".$key."\"]=\"".$value."\";";'), array_keys($fields), array_values($fields));
				//$new_array = array_map(create_function('$key, $value', 'return "fields[fields.length] = {\"".$key."\":\"".$value."\"};";'), array_keys($fields), array_values($fields));
				$new_array = array_map(create_function('$key, $value', 'return "jfFilters[\"'.$row->id.'\"] = [{\"".$key."\":\"".$value."\"}];";'), array_keys($fields), array_values($fields));
				$fields=implode($new_array);
				$script = <<<SCRIPT
//fields.empty();
$fields
//fields["test"] = "test";
//jfFilters["$row->id"] = fields;


SCRIPT;
				$doc->addScriptDeclaration($script);
			}
		}
	}
	
	
	public function checkTranslationMapInComponent()
	{
		
		$db = JFactory::getDbo();
		$transMapButton = '';
		$joomfishManager = JoomFishManager::getInstance();
		$catid = str_replace('com_','',JRequest::getCmd('option'));
		switch($catid)
		{
			case 'menus':
				$catid = 'menu';
			break;
		}
		
		$contentElement = $joomfishManager->getContentElement($catid);
		if (!$contentElement)
		{
			$catid = "content";
			$contentElement = $joomfishManager->getContentElement($catid);
		}
		
		//TODO from contentElement
		$reference_id = JRequest::getInt("id");
		JLoader::import('models.TranslationFilter', JOOMFISH_ADMINPATH);
		$doc = JFactory::getDocument();
			/*
			here set filters?
			from treatment
			
			*/
			$tranFilters = getTranslationFilters($catid, $contentElement);
			//only show column orig language if we have an language filter
			//and treatment target is native
			//
			if($tranFilters && array_key_exists('language',$tranFilters) && $contentElement->getTarget() == 'native')
			{
				$showOrgLanguage = true;
				$table = JTable::getInstance($contentElement->getTableClass());
				$table->load(intval($reference_id));
				$lang = $joomfishManager->getLanguageByCode($table->language);
				if($lang)
				{
					$language_id = $lang->lang_id;
					$db->setQuery($contentElement->createContentSQL($language_id, $reference_id, 0, 0, $tranFilters));
					$rows = $db->loadObjectList();
					if ($db->getErrorNum())
					{
						JError::raiseWarning(200, JTEXT::_('No valid database connection: ') . $db->stderr());
						// should not stop the page here otherwise there is no way for the user to recover
						$rows = array();
					}

					// Manipulation of result based on further information
					for ($i = 0; $i < count($rows); $i++)
					{
						$translationClass = $contentElement->getTranslationObjectClass();
						$translationObject = new $translationClass( $language_id, $contentElement );
						$translationObject->readFromRow($rows[$i]);
						$rows[$i] = $translationObject;
					}
					if($rows)
					{
						$row = $rows[0];
						//echo 
						
						$doc = JFactory::getDocument();
						$this->getFilters($row,$contentElement);//,$language_id);
						$transMapButton = $this->checkTranslationMap( $reference_id,$language_id,$row->org_language_id, $contentElement); //,true );
						//TODO 
						if(!$transMapButton)
						{
							//show buttons for edit if we have translations?
							//or show new button?
							
							$contentTable = $contentElement->getTable();
							
							$db->setQuery('select * from #__jf_translationmap where reference_table="' . $contentTable->Name . '" AND translation_id=' . $reference_id);
							$translations = $db->loadObjectList();
							$original = 0;
							if (count($translations) > 0)
							{
								$original = $translations[0]->reference_id;
							}
							if($original)
							{
								
								
								$link = '';
								$link = '<span id="transMapButtonHolder'.$reference_id.'"></span>';
								$script = <<<SCRIPT
window.addEvent('load', function() {
var transMapButton = new Element('input');
transMapButton.set('type','text');
transMapButton.set('class','readonly');
transMapButton.set('id','showTransMapValue$reference_id');
transMapButton.set('value','Original ID: $original');
var transMapButtonHolder = document.id('transMapButtonHolder$reference_id');
transMapButtonHolder.appendChild(transMapButton);


});
SCRIPT;
								$doc->addScriptDeclaration($script);
								$transMapButton .= $link;
								//return $link;
								
								
							}
							
						}
						
			$script = <<<SCRIPT
window.addEvent('domready', function() {
	var langselect = $('jform_language');
	var transMapButtonContent = '$transMapButton'.trim();
	if (langselect && transMapButtonContent){
		var transMapButton = new Element("div");
		transMapButton.set('id','transMapButton');
		
		transMapButton.set('html','$transMapButton');
		var langselectli = langselect.getParent()
			
		var jflanglabel = new Element("label",{id:'jftranslationMap-lbl', for:'transMapButton'});
		jflanglabel.appendText("TranslationMap");
					
		// new li row
		var li = new Element('li');
		li.set('id','transmapButtonLi');
		li.appendChild(jflanglabel);
		li.appendChild(transMapButton);
		
		// insert it after the lang selector
		li.inject( langselectli,'after');
		
		langselect.addEventListener("change", function(){
			if(langselect.value=="*"){
				//jflangselect.set('value', 0);
				//jflangselect.getParent().style.display="none";
			}
			else {
				//jflangselect.set('value', 1);
				//jflangselect.getParent().style.display="block";
			}
			
		});
	}
});
SCRIPT;
			$doc->addScriptDeclaration($script);
					}
				}
			}
		return $transMapButton;
	}
	
	public function checkTranslationMapInJoomfish( $content_id,$language_id,$org_language_id, $contentElement)
	{
	
		$return = $this->checkTranslationMap( $content_id,$language_id,$org_language_id, $contentElement); //,true );
		
		if($return)
		{
			$translationClass = $contentElement->getTranslationObjectClass();
			$translationObject = new $translationClass( $language_id, $contentElement );
			//$translationObject->readFromRow($rows[$i]);
			//$row = 
			$translationObject->loadFromContentID($content_id);
			$contentElement = $translationObject->getContentElement();
			$this->getFilters($translationObject,$contentElement);//,$language_id);
		}
		
		
		return $return;
	}
	
	
	public function checkTranslationMap( $content_id,$language_id,$org_language_id, $contentElement)
	{
		//we need the id from the item
		//so we can get the language from item?
		if($org_language_id == $language_id)
		{
		
		//set script only once
		
		$doc = JFactory::getDocument();
		static $translationMapScript;
		if (!isset($translationMapScript))
		{
			JHTML::_('behavior.modal');
			$uri = JURI::base();
			$script = <<<SCRIPT
function removeTransmapButton() {
	var transmapButtonLi = $('transmapButtonLi');
	if(transmapButtonLi)
	{
		transmapButtonLi.destroy();
	}
	else
	{
		window.parent.document.adminForm.submit();
	}
}
function showTransmapEditor(fieldId, language_id, reference_id, catid) {
	var field = document.getElementById(fieldId);
	
	var url = '$uri'+'index.php?option=com_joomfish&task=translationmap.edit&tmpl=component&catid='+catid+'&language_id='+language_id+'&reference_id='+reference_id;
	if(jfFilters)
	{
		//alert('jfFilters');
	}
	if(jfFilters)
	{
		jfFilters.each(function(items, index){
			if(index == reference_id)
			{
				items.each(function(item, index2){
					//alert(index2 + " = " + item);
					//alert(item);
					var keys = Object.keys(item);
					keys.each(function(key, index3){
						//alert(index3 + " = " + key);
						//alert(item[key]);
						url = url + "&filters["+key+"]="+item[key];
					});
				});
			}
		});
	}
	SqueezeBox.initialize();
	SqueezeBox.fromElement(field, {
		handler: 'iframe',
		url: url,
		size: {x: 750, y: 500}
	});
}
SCRIPT;
			$doc->addScriptDeclaration($script);

			$translationMapScript = true;
		}
		
		
			//$joomfishManager = JoomFishManager::getInstance();
			//$lang = $joomfishManager->getLanguageByID($language_id);
			$db = JFactory::getDBO();

			$referencefield = "id";

			$referencefield = $contentElement->PrimaryKey;
			$tablename = $contentElement->referenceInformation['tablename'];
				
			$more1 = "\nSELECT tm4.reference_id from #__jf_translationmap as tm4 WHERE tm4.reference_table=".$db->quote($tablename);
			$more2 = "\nSELECT tm5.translation_id from #__jf_translationmap as tm5 WHERE tm5.reference_table=".$db->quote($tablename);
			$more = " ( ".$db->quote( $content_id). " IN (".$more2." ) OR ".$db->quote( $content_id). " IN (".$more1." )) ";

			$query = $db->getQuery(true);
			$query->select('*');
			$query->from('#__' . $tablename . ' as c');
			$query->where($more);
			$db->setQuery($query);
			$transmap = $db->loadObjectList();
				
			if(!count($transmap))
			{
				//$language_id
				//$link = '<a href="#" id="showTransMapValue'. $content_id.'" onClick="showTransmapEditor(\'showTransMap'.$content_id.'\', \''.$lang->lang_id.'\', \''.$content_id.'\', \''.$tablename.'\');">';
				$link = '';
				$link = '<span id="transMapButtonHolder'.$content_id.'"></span>';
				
				$image = JHTML::_('image.administrator', 'menu/icon-16-config.png', '/images/', null, null, JText::_( 'EDIT' ));
				$script = <<<SCRIPT
window.addEvent('load', function() {
var transMapButton = new Element('a');
transMapButton.set('href','#');
transMapButton.set('id','showTransMapValue$content_id');
transMapButton.set('onclick','showTransmapEditor(\'showTransMap$content_id\', \'$language_id\', \'$content_id\', \'$tablename\');');
transMapButton.set('html','$image');
var transMapButtonHolder = document.id('transMapButtonHolder$content_id');
transMapButtonHolder.appendChild(transMapButton);


});
SCRIPT;
			$doc->addScriptDeclaration($script);
				return $link;
				/*
				$link .= '<a '.($hide ? 'style="display:none;" ' : '').'href="#" id="showTransMapValue'. $content_id.'" onClick="showTransmapEditor(\'showTransMap'.$content_id.'\', \''.$language_id.'\', \''.$content_id.'\', \''.$tablename.'\');">';

					$link .= JHTML::_('image.administrator', 'menu/icon-16-config.png', '/images/', null, null, JText::_( 'EDIT' ));
				$link .= '</a>';

				return $link;
				*/
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
	
	
	
	//MS: add for translationmap
	function getContentMap($contentElement,$exclude_language_id = null,$filters = array(0),$order = null,$orderDir = 'ASC',$sqlFields = null)
	{
		$joomfishManager = JoomFishManager::getInstance();
		$db =& JFactory::getDBO();
		
		$query = $db->getQuery(true);
		
		$contentTable = $contentElement->getTable();
		$referencefield = "id";
		foreach ($contentTable->Fields as $tableField)
		{

			switch($tableField->Type)
			{
				case "referenceid":
				$referencefield = $tableField->Name;
					$sqlFields[] = 'c.' . $tableField->Name . ' as id';
				break;
				case "titletext":
					$sqlFields[] = 'c.' . $tableField->Name . ' as title';
				break;
			}
		}
		$sqlFields[] = 'c.*';
		$sqlFields[] = 'c.language as language';
		
		$sqlFields[] = "l.lang_id as orig_language_id";
		
		$sqlFields[] = 'tm.language as transmap_language';
		$sqlFields[] = 'tm.reference_id';
		$sqlFields[] = 'tm.translation_id';
		
		$sqlFields[] = 'ct.language as trans_language';
		$sqlFields[] = 'co.language as orig_language';
		
		$query->select(implode(', ', $sqlFields));
		$query->from('#__' . $contentTable->Name . ' as c');
		$lang = null;
		if($exclude_language_id)
		{
			$lang = $joomfishManager->getLanguageByID($exclude_language_id);
		}
		
		$query->leftJoin('#__jf_translationmap as tm ON (tm.reference_id=c.' . $referencefield .' OR tm.translation_id=c.' . $referencefield .') AND tm.reference_table='.$db->quote($contentTable->Name));
		
		$query->leftJoin('#__' . $contentTable->Name . ' as co ON (( co.'.$referencefield.'=tm.reference_id AND tm.translation_id<>co.'.$referencefield.' AND tm.reference_table='.$db->quote($contentTable->Name).') OR ( (c.language=\'*\' ) AND co.'.$referencefield.'=c.'.$referencefield.' ))');
		
		$query->leftJoin('#__' . $contentTable->Name . ' as ct ON ct.'.$referencefield.'=tm.translation_id AND ct.'.$referencefield.'=c.'.$referencefield);
		
		$query->leftJoin('#__languages as l ON l.lang_code=c.language');
		
		if($exclude_language_id)
		{
			$query->where('c.language <> '.$db->quote($lang->lang_code));
		}
		
		
		
		/*foreach ($filters as $filter)
			{
				$sqlFilter = $filter->createFilter($this);
				if ($sqlFilter != "")
				{
					$where[] = $sqlFilter;
					$whereFilter[] = $sqlFilter;
				}
			}
		
		*/
		if ($contentTable->Filter != '')
		{
			$query->where($contentTable->Filter);
		}
		foreach($filters as $filter)
		{
			if($filter)
			{	
				$query->where($filter);
			}
		}

		$query->order(($order ? $order : 'c.language, c.' . $referencefield).' '.$orderDir);
		$db->setQuery($query);
		$transmap = $db->loadObjectList();
		return $transmap;
		
	}
	
	function getTranslationMap($contentElement,$content_id,$exclude_language_id = null,$filters = array(0),$order = null,$orderDir = 'ASC',$sqlFields = null)
	{
		$joomfishManager = JoomFishManager::getInstance();
		$db =& JFactory::getDBO();
		
		$query = $db->getQuery(true);
		
		$contentTable = $contentElement->getTable();
		$referencefield = "id";
		foreach ($contentTable->Fields as $tableField)
		{

			switch($tableField->Type)
			{
				case "referenceid":
				$referencefield = $tableField->Name;
					$sqlFields[] = 'c.' . $tableField->Name . ' as id';
				break;
				case "titletext":
					$sqlFields[] = 'c.' . $tableField->Name . ' as title';
				break;
			}
		}
		$sqlFields[] = 'c.*';
		$sqlFields[] = 'c.language as language';
		
		$sqlFields[] = "l.lang_id as orig_language_id";
		
		$sqlFields[] = 'tm.language as transmap_language';
		$sqlFields[] = 'tm.reference_id';
		$sqlFields[] = 'tm.translation_id';
		
		$sqlFields[] = 'ct.language as trans_language';
		$sqlFields[] = 'co.language as orig_language';
		
		$query->select(implode(', ', $sqlFields));
		$query->from('#__' . $contentTable->Name . ' as c');
		$lang = null;
		if($exclude_language_id)
		{
			$lang = $joomfishManager->getLanguageByID($exclude_language_id);
		}
		//TODO get all translations with reference_id = $content_id or translation_id = $content_id
		$query->leftJoin('#__jf_translationmap as tm ON (tm.reference_id=c.' . $referencefield .' OR tm.translation_id=c.' . $referencefield .') AND tm.reference_table='.$db->quote($contentTable->Name));
		
		$query->leftJoin('#__' . $contentTable->Name . ' as co ON (( co.'.$referencefield.'=tm.reference_id AND tm.translation_id<>co.'.$referencefield.' AND tm.reference_table='.$db->quote($contentTable->Name).') OR ( (c.language=\'*\' ) AND co.'.$referencefield.'=c.'.$referencefield.' ))');
		
		$query->leftJoin('#__' . $contentTable->Name . ' as ct ON ct.'.$referencefield.'=tm.translation_id AND ct.'.$referencefield.'=c.'.$referencefield);
		
		$query->leftJoin('#__languages as l ON l.lang_code=c.language');
		
		if($exclude_language_id)
		{
			$query->where('c.language <> '.$db->quote($lang->lang_code));
		}
		
		
		
		if ($contentTable->Filter != '')
		{
			$query->where($contentTable->Filter);
		}
		foreach($filters as $filter)
		{
			if($filter)
			{	
				$query->where($filter);
			}
		}

		$query->order(($order ? $order : 'c.language, c.' . $referencefield).' '.$orderDir);
		$db->setQuery($query);
		$transmap = $db->loadObjectList();
		return $transmap;
		
	}
}

?>
