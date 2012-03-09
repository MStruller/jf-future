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
 * $Id: ContentElement.php 247 2012-02-10 11:16:55Z geraint $
 * @package joomfish
 * @subpackage Models
 *
 */
// Don't allow direct linking
defined('_JEXEC') or die('Restricted access');

include_once(dirname(__FILE__) . DS . "ContentElementTable.php");

/**
 * Content element class based on the xml file
 *
 * @package joomfish
 * @subpackage administrator
 * @copyright 2003 - 2012, Think Network GmbH, Munich
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Revision: 1543 $
 * @author Alex Kempkens
 */
class ContentElement
{

	private $_xmlFile;
	public $checked_out = false;
	public $Name = '';
	public $Author = '';
	public $Version = '';
	public $Description = '';
	public $PrimaryKey = "id";
	public $Storage = "joomfish";
	public $referenceInformation;
	/** 	field (if any) that keyword	filters apply to */
	private $_keywordFilter = null;
	private $_categoryFilter = null;
	private $_authorFilter = null;
	private $_defaultlang = null;

	public $Treatment = null;
	
	/** Standard constructor, which loads already standard information
	 * for easy and direct access
	 */
	public function __construct($xmlDoc)
	{	
		$jf = JoomFishManager::getInstance();
		$this->_defaultlang = $jf->getDefaultLanguage();
		
		$this->_xmlFile = $xmlDoc;

		if (isset($this->_xmlFile))
		{
			$valueElement = $this->_xmlFile->getElementsByTagName('name')->item(0);
			$this->Name = trim($valueElement->textContent);

			$valueElement = $this->_xmlFile->getElementsByTagName('author')->item(0);
			$this->Author = trim($valueElement->textContent);

			$valueElement = $this->_xmlFile->getElementsByTagName('version')->item(0);
			$this->Version = trim($valueElement->textContent);

			$valueElement = $this->_xmlFile->getElementsByTagName('description')->item(0);
			$this->Description = trim($valueElement->textContent);

			$this->Storage = $this->getTarget();

		}

	}

	/** Type of reference
	 */
	public function getReferenceType()
	{
		if (!isset($this->referenceInformation["type"]) && isset($this->_xmlFile))
		{
			$tableElement = $this->_xmlFile->getElementsByTagName('reference')->item(0);
			$type = trim($tableElement->getAttribute('type'));
			$this->referenceInformation["type"] = $type;
		}

		return $this->referenceInformation["type"];

	}

	/**
	 * Public function to return array of filters included in contentelement file
	 */
	public function getAllFilters()
	{
		$allFilters = array();
		if (isset($this->_xmlFile))
		{
			$fElement = $this->_xmlFile->getElementsByTagName('translationfilters')->item(0);
			if (!isset($fElement) || !$fElement->hasChildNodes())
			{
				return $allFilters;
			}
			foreach ($fElement->childNodes as $child)
			{
				$type = $child->nodeName;
				$filter = "_$type" . "Filter";
				$this->$filter = $child->textContent;
				$allFilters[$type] = trim($this->$filter);
			}
		}
		return $allFilters;

	}

	public function _getTreatment()
	{
		$xpath = new DOMXPath($this->_xmlFile);

		$treatment = array();
		$treatments = $xpath->query('//reference/treatment')->item(0);
		if($treatments && $treatments->hasChildNodes())
		{
			foreach ($treatments->childNodes as $node)
			{
				if($node->nodeType == XML_ELEMENT_NODE)
				{
					$add = array();
					if($node->hasAttributes())
					{
						$attributes = $node->attributes;
						if(!is_null($attributes))
						{
							foreach ($attributes as $index => $attribute)
							{
								$add[$attribute->name] = $attribute->value;
							}
						}
					}
					if(count($add) > 0)
					{
						$treatment[$node->nodeName] = array('value'=>$node->nodeValue,'attributes'=>$add);
					}
					else
					{
						$treatment[$node->nodeName] = $node->nodeValue;
					}
				}
			}
		}
		$this->Treatment = $treatment;
	}
	public function getTreatment()
	{
		if(isset($this->Treatment) && $this->Treatment)
		{
			return $this->Treatment;
		}
		$this->Treatment = array();
		if (isset($this->_xmlFile))
		{
			//$joomfishManager = JoomFishManager::getInstance();
			//$treatment = $joomfishManager->getTreatment($this->_xmlFile,$this->getTableName());
			//if(count($treatment))
			//{
			//	$this->Treatment = $treatment;
			//}
			$this->_getTreatment();
		}
		return $this->Treatment;
		//return null;
	}
	/**
	 * function that returns target that is used to decide where the translation is saved - choices are joomfish (default) or joomla
	 *
	 */
	public function getTarget()
	{
		if (isset($this->_xmlFile))
		{
			$treatment = $this->getTreatment();
			if(count($treatment) > 0)
			{
				if(isset($treatment['target']))
				{
					return $treatment['target'];
				}
			}
			/*
			$xpath = new DOMXPath($this->_xmlFile);
			$targetElement = $xpath->query('//reference/treatment/target')->item(0);
			if (!isset($targetElement))
			{
				return 'joomfish';
			}
			$target = trim($targetElement->textContent);
			return $target;
			*/
		}
		return 'joomfish';

	}

	/*
	 * get the translation object class for the table and make sure the source file is loaded


	MS:
	changed this function
	
	we have 3 ways to get the $className:
	1. over $treatment['translationObject']
	2. over 'TranslationObject'.ucfirst($this->getTableName())
	3. the base class 'TranslationObject'
	
	we can have different folder to search:
	1. $includePath.DS.'objects'
		in include path we can have
		
		<includePath>administrator/components/com_content/joomfish</includePath>
		return an path like JPATH_ROOT/administrator/components/com_content/joomfish
		or 
		<includePath extension="1" site="0">jf</includePath>
		<extension>com_content</extension>
		return an path like JPATH_ADMINISTRATOR/components/com_content/jf
		
		<includePath extension="1" site="1">jf</includePath>
		<extension>com_content</extension>
		return an path like JPATH_ROOT/components/com_content/jf
		
	
	2. JoomfishExtensionHelper::getExtraPath('objects')
	for the path i have created JOOMFISH_ADMINPATH.DS.'models'.DS.'jf'....

	 */
	public function getTranslationObjectClass(){
		
		JLoader::import('TranslationObject', JoomfishExtensionHelper::getExtraPath('objects'));
		if (isset($this->_xmlFile))
		{
			$treatment = $this->getTreatment();
			if(count($treatment) > 0)
			{
				$includePath = JoomfishExtensionHelper::getTreatmentIncludePath($treatment);
				if(isset($treatment['translationObject']))
				{
					$className = $treatment['translationObject'];
				}
				else
				{
					$className = 'TranslationObject'.ucfirst($this->getTableName());
				}
				if(isset($includePath) && isset($className))
				{
					if($file = JPath::find($includePath.DS.'objects', $className.'.php'))
					{
						include_once($file);
					}
				}
				elseif(isset($className))
				{
					//if($file = JPath::find(JOOMFISH_ADMINPATH.DS.'models', $className.'.php'))
					if($file = JPath::find(JoomfishExtensionHelper::getExtraPath('objects'),$className.'.php'))
					{
						include_once($file);
					}
				}
				if(isset($className) && class_exists($className))
				{
					return $className;
				}
			}
		}
		return 'TranslationObject';
	}
	
	/*
	MS:
	add this function to work with params
	
	we have 3 ways to get the $className:
	1. over $treatment['translateParams']
	2. over 'TranslateParams'.ucfirst($this->getTableName())
	3. the base class 'TranslateParams'
	
	we can have different folder to search:
	1. $includePath.DS.'params'
	2. JoomfishExtensionHelper::getExtraPath('params')
	for the path i have created JOOMFISH_ADMINPATH.DS.'models'.DS.'jf'....
	
	*/
	function getTranslateParamsClass()
	{
		JLoader::import( 'TranslateParams',JoomfishExtensionHelper::getExtraPath('params'));
		if (isset($this->_xmlFile))
		{
			$treatment = $this->getTreatment();
			if(count($treatment) > 0)
			{
				$includePath = JoomfishExtensionHelper::getTreatmentIncludePath($treatment);
				if(isset($treatment['translateParams']))
				{
					$className = $treatment['translateParams'];
				}
				else
				{
					$className = 'TranslateParams'.ucfirst($this->getTableName());
				}
				//if(isset($treatment['translateParamsPath']) && isset($className))
				if(isset($includePath) && isset($className))
				{
					if($file = JPath::find($includePath.DS.'params', $className.'.php'))
					{
						include_once($file);
					}
				}
				elseif(isset($className))
				{
					//if($file = JPath::find(JOOMFISH_ADMINPATH.DS.'models', $className.'.php'))
					if($file = JPath::find(JoomfishExtensionHelper::getExtraPath('params'), $className.'.php'))
					{
						include_once($file);
					}
				}
				if(isset($className) && class_exists($className))
				{
					return $className;
				}
			}
			//JLoader::import( 'models.TranslateParams',JOOMFISH_ADMINPATH);
			$className = "TranslateParams".ucfirst($this->getTableName());
			if (!class_exists($className)){
				$className = "TranslateParams";
			}

			return $className;
		}
		//JLoader::import( 'models.TranslationObject',JOOMFISH_ADMINPATH);
		return 'TranslateParams';
	}
	

	/*
	MS:
	add this function to work with JForm not only in TranslateParams
	TranslateParams is use this to
	
	we have 3 ways to get the $className:
	1. over $treatment['translateForms']
	2. over 'TranslateForms'.ucfirst($this->getTableName())
	3. the base class 'TranslateForms'
	
	we can have different folder to search:
	1. $includePath.DS.'forms'
	2. JoomfishExtensionHelper::getExtraPath('forms')
	for the path i have created JOOMFISH_ADMINPATH.DS.'models'.DS.'jf'....
	
	*/
	
	function getTranslateFormsClass()
	{
		JLoader::import( 'TranslateForms',JoomfishExtensionHelper::getExtraPath('forms'));
		if (isset($this->_xmlFile))
		{
			$treatment = $this->getTreatment();
			if(count($treatment) > 0)
			{
				$includePath = JoomfishExtensionHelper::getTreatmentIncludePath($treatment);
				if(isset($treatment['translateForms']))
				{
					$className = $treatment['translateForms'];
				}
				else
				{
					$className = 'TranslateForms'.ucfirst($this->getTableName());
				}
				//if(isset($treatment['translateParamsPath']) && isset($className))
				if(isset($includePath) && isset($className))
				{
					if($file = JPath::find($includePath.DS.'forms', $className.'.php'))
					{
						include_once($file);
					}
				}
				elseif(isset($className))
				{
					//if($file = JPath::find(JOOMFISH_ADMINPATH.DS.'models', $className.'.php'))
					if($file = JPath::find(JoomfishExtensionHelper::getExtraPath('forms'), $className.'.php'))
					{
						include_once($file);
					}
				}
				if(isset($className) && class_exists($className))
				{
					return $className;
				}
			}
			//JLoader::import( 'models.TranslateParams',JOOMFISH_ADMINPATH);
			$className = "TranslateForms".ucfirst($this->getTableName());
			if (!class_exists($className)){
				$className = "TranslateForms";
			}

			return $className;
		}
		//JLoader::import( 'models.TranslationObject',JOOMFISH_ADMINPATH);
		return 'TranslateForms';
	}

	
	public function getPublishedField(){
		if (isset($this->_xmlFile))
		{
			$treatment = $this->getTreatment();
			if(count($treatment) > 0)
			{
				if(isset($treatment['publishedfield']))
				{
					return $treatment['publishedfield'];
				}
			}
			/*
			$xpath = new DOMXPath($this->_xmlFile);
			$publishedfield = $xpath->query('//reference/treatment/publishedfield')->item(0);
			if (!isset($publishedfield))
			{
				return 'published';
			}
			$result = trim($publishedfield->textContent);
			return $result;
			*/
		}
		return 'published';
		
	}
	
	public function getTableClass()
	{
		if (isset($this->_xmlFile))
		{
			$treatment = $this->getTreatment();
			if(count($treatment) > 0)
			{
				if(isset($treatment['tableclass']))
				{
					return $treatment['tableclass'];
				}
			}
			/*
			$xpath = new DOMXPath($this->_xmlFile);
			$targetElement = $xpath->query('//reference/treatment/tableclass')->item(0);
			if (!isset($targetElement))
			{
				return false;
			}
			$tableclass = trim($targetElement->textContent);
			return $tableclass;
			*/
		}
		return false;

	}


	public function getTablePrefix()
	{
		$treatment = $this->getTreatment();
		if($treatment && isset($treatment['tableprefix']))
		{
			return $treatment['tableprefix'];
		}
		return 'JTable';
	}
	
	
	public function getTablePath()
	{
		$treatment = $this->getTreatment();
		if($treatment && isset($treatment['tablepath']))
		{
			return $treatment['tablepath'];
		}
		return null;
	}




	/**
	 * function that returns filter string and handles getting filter info from xmlfile if needed
	 *
	 */
	public function getFilter($type)
	{
		$filter = "_$type" . "Filter";
		if (!isset($this->$filter) && isset($this->_xmlFile))
		{
			$xpath = new DOMXPath($this->_xmlFile);
			$fElement = $xpath->query('//translationfilters/' . $type);
			if (!isset($fElement))
			{
				$this->$filter = false;
				return $this->$filter;
			}
			$this->$filter = trim($fElement->textContent);
		}
		return $this->$filter;

	}

	/**
	 * returns translation filter keyword field (if any)
	 */
	public function getKeywordFilter()
	{
		return $this->_getFilter("keyword");

	}

	/**
	 * returns category filter fieldname (if any)
	 */
	public function getCategoryFilter()
	{
		return $this->_getFilter("category");

	}

	/**
	 * returns author filter fieldname (if any)
	 */
	public function getAuthorFilter()
	{
		return $this->_getFilter("author");

	}

	/** 
	MS: add Name of the contentelement
	
	*/
	public function getElementName($xmlDoc)
	{
		if (!$xmlDoc)
		{
			return null;
		}
		$element = $xmlDoc->documentElement;
		if ($element->nodeName == 'joomfish') 
		{
			if ( $element->getAttribute('type')=='contentelement' ) 
			{
				$xpath = new DOMXPath($this->_xmlFile);
				$reference = $xpath->query('//reference')->item(0);
				$referenceName = trim($reference->getAttribute('name'));
				if($referenceName)
				{
					$this->referenceInformation["referencename"] = strtolower($referenceName);
				}
				else
				{
					$nameElements = $element->getElementsByTagName('name');
					$nameElement = $nameElements->item(0);
					$this->referenceInformation["referencename"] = strtolower( trim($nameElement->textContent) );
				}
			}
		}
		return $this->referenceInformation["referencename"];
	}


	/** 
	MS: add Name of the extension
	
	*/
	public function getExtensionName()
	{
		$treatment = $this->getTreatment();
		if($treatment && isset($treatment['extension']))
		{
			return $treatment['extension'];
		}
		return null;
	}

	/** Name of the refering table
	 */
	public function getTableName()
	{
		if (!isset($this->referenceInformation["tablename"]) && isset($this->_xmlFile))
		{
			$xpath = new DOMXPath($this->_xmlFile);
			$tableElement = $xpath->query('//reference/table')->item(0);

			$tableName = trim($tableElement->getAttribute('name'));
			$this->referenceInformation["tablename"] = strtolower($tableName);
		}

		return $this->referenceInformation["tablename"];

	}

	/**
	 * Name of reference id (in other words the primary key)
	 */
	public function getReferenceId()
	{
		if (isset($this->referenceInformation["tablename"]) && isset($this->_xmlFile))
		{
			$xpath = new DOMXPath($this->_xmlFile);
			$tableElement = $xpath->query('//reference/table')->item(0);
			$tableFields = $tableElement->getElementsByTagName('field');

			foreach ($tableFields as $field)
			{
				if (trim($field->getAttribute('type')) == "referenceid")
				{
					$refid = trim($field->getAttribute('name'));
					if ($refid != null)
						return $refid;
					else
						return "id";
				}
			}
		}
		return "id";

	}

	/** Array of the field elements in the table
	 * @return reference to the table information
	 */
	public function & getTable()
	{
		if (!isset($this->referenceInformation["table"]) && isset($this->_xmlFile))
		{
			$xpath = new DOMXPath($this->_xmlFile);
			$tableElement = $xpath->query('//reference/table')->item(0);

			$this->referenceInformation["table"] = new ContentElementTable($tableElement,$this->_xmlFile);
		}

		return $this->referenceInformation["table"];

	}

	/** Generating the sql statement to retrieve the information
	 * from the database
	 */
	public function createContentSQL($idLanguage=-1, $contentid=null, $limitStart=-1, $maxRows=-1, $filters=array())
	{
		$jf = JoomFishManager::getInstance();
		$lang = $jf->getLanguageByID($idLanguage);
		$db = JFactory::getDBO();
		$sqlFields = null;
		$where = array();
		$order = null;
		$join = null;
		$contentTable = $this->getTable();
		foreach ($filters as $filter)
		{
			$sqlFilter = $filter->createFilter($this);
			if ($sqlFilter != ""){
				$where[] = $sqlFilter;
			}
		}
		if ($this->Storage == "joomfish")
		{
			foreach ($contentTable->Fields as $tableField)
			{
				// Based on the types we might want to have special names ;-)
				switch ($tableField->Type) {
					case "referenceid":
						$contentid_exist = (isset($contentid) && $contentid != -1 );
						if (strtolower($tableField->Name) != "id")
						{
							$sqlFields[] = 'c.' . $tableField->Name . ' as id';
							if ($contentid_exist)
								$where[] = 'c.' . $tableField->Name . '=' . $db->quote($contentid);
						}
						else
						{
							if ($contentid_exist)
								$where[] = 'c.id=' . $contentid;
						}
						$join[] = 'c.' . $tableField->Name . '=jfc.reference_id';
						break;
					case "titletext":
						if (strtolower($tableField->Name) != "title")
						{
							$sqlFields[] = 'c.' . $tableField->Name . ' as title';
						}
						$join[] = "jfc.reference_field='" .$tableField->Name. "'";
						$order[] = 'c.' . $tableField->Name;
						break;
					case "modified_date":
						if (strtolower($tableField->Name) != "modified_date")
						{
							$sqlFields[] = 'c.' . $tableField->Name . ' as modified_date';
						}
						break;
					case "checked_out_by":
						if (strtolower($tableField->Name) != "checked_out")
						{
							$sqlFields[] = 'c.' . $tableField->Name . ' as check_out';
						}
						break;
				}

				// I want to have each field with his original name in the select
				// so the special fields will be only addon's!
				// Reason: To grap the data later it's more easy to refer to the original names of the XML file
				$sqlFields[] = 'c.' . $tableField->Name . '';
			}

			$sqlFields[] = "jfc.id as jfc_id";
			$sqlFields[] = "jfc.value as titleTranslation";
			$sqlFields[] = "jfc.modified as lastchanged";
			$sqlFields[] = 'jfc.published as published';
			$sqlFields[] = 'jfc.language_id';
			$sqlFields[] = 'jfl.title as language';
			$sqlFields[] = "jfc.reference_id as jfc_refid";
			$join[] = "jfc.reference_table='$contentTable->Name'";
			// Now redundant
			/*
			  if( isset($contentid) && $contentid!=-1 ) {
			  $where[] = 'c.id=' .$contentid;
			  }
			 */
			if (isset($idLanguage) && $idLanguage != "" && $idLanguage != -1)
			{
				if ($idLanguage == "NULL")
				{
					$where[] = "jfc.language_id IS NULL";
				}
				else
				{
					$join[] = "jfc.language_id=$idLanguage";
				}
			}

			if ($contentTable->Filter != '')
			{
				$where[] = $contentTable->Filter;
			}

			$sql = "SELECT " . implode(', ', $sqlFields)
					. "\nFROM #__" . $contentTable->Name . ' as c'
					. "\nLEFT JOIN #__jf_content as jfc ON " . implode(' AND ', $join)
					. "\nLEFT JOIN #__languages as jfl ON jfc.language_id=jfl.lang_id"
					. (count($where) ? "\nWHERE " . implode(' AND ', $where) : "")
					. (count($order) ? "\nORDER BY " . implode(', ', $order) : "");

			if ($limitStart != -1 && $maxRows > 0)
			{
				$sql .= "\nLIMIT $limitStart, $maxRows";
			}
			//echo "sql = <pre>$sql</pre><br />";
		}
		else
		{
			$referencefield = "id";

			foreach ($contentTable->Fields as $tableField)
			{
				// Based on the types we might want to have special names ;-)
				if ($tableField->Type == "referenceid")
				{
					$referencefield = $tableField->Name;
					break;
				}
			}

			// TODO set source language
			$where[] = '(c.language="*" OR c.language='. $db->quote($this->_defaultlang).')';

			foreach ($contentTable->Fields as $tableField)
			{
				// Based on the types we might want to have special names ;-)
				switch ($tableField->Type) {
					case "referenceid":
						$contentid_exist = (isset($contentid) && $contentid != -1 );
						if (strtolower($tableField->Name) != "id")
						{
							$sqlFields[] = 'c.' . $tableField->Name . ' as id';
							if ($contentid_exist)
								$where[] = 'c.' . $tableField->Name . '=' . $db->quote($contentid);
						}
						else
						{
							if ($contentid_exist)
								$where[] = 'c.'.$referencefield.'=' . $contentid;
						}
						break;
					case "titletext":
						$sqlFields[] = 'c.' . $tableField->Name . ' as title';
						$sqlFields[] = 'ct.' . $tableField->Name . ' as titleTranslation';
						break;
					case "modified_date":
						$sqlFields[] = 'c.' . $tableField->Name . ' as modified_date';
						break;
					case "checked_out_by":
						$sqlFields[] = 'c.' . $tableField->Name . ' as checked_out';
						break;
				}

				// I want to have each field with his original name in the select
				// so the special fields will be only addon's!
				// Reason: To grap the data later it's more easy to refer to the original names of the XML file
				$sqlFields[] = 'c.' . $tableField->Name . '';
				$sqlFields[] = 'ct.' . $tableField->Name . ' AS jfc_'.$tableField->Name;
			}

			$sqlFields[] = "ct.id as jfc_id";
			// NEW SYSTEM make sure published is a valid field!
			$publishedField = $this->getPublishedField();
			$sqlFields[] = 'ct.'.$publishedField.' as published';
			$sqlFields[] = "ct." . $referencefield . " as jfc_refid";
			// NEW SYSTEM TODO get the last changed from the translation map table - ALSO keep a record of the ORIGINAL record
			//$sqlFields[] = "tm.lastchanged  as lastchanged";
			$sqlFields[] = "'2010-06-11 05:30:30' as lastchanged";

			if ($contentTable->Filter != '')
			{
				$where[] = $contentTable->Filter;
			}

			$transmap = "";
			if (isset($idLanguage) && $idLanguage != "" && $idLanguage != -1)
			{
				$transmap = "\nLEFT JOIN #__jf_translationmap as tm ON  tm.reference_id=c." . $referencefield. " AND tm.reference_table=".$db->quote($contentTable->Name);
				$transmap .= " AND tm.language=" . $db->quote($lang->code);

				$join[] = "tm.translation_id=ct.".$referencefield;
				$sql = "SELECT " . implode(', ', $sqlFields)
						. "\nFROM #__" . $contentTable->Name . ' as c'
						. $transmap
						. "\nLEFT JOIN #__" . $contentTable->Name . " as ct ON " . implode(' AND ', $join)
						. (count($where) ? "\nWHERE " . implode(' AND ', $where) : "")
						. (count($order) ? "\nORDER BY " . implode(', ', $order) : "");
			}



			if ($limitStart != -1 && $maxRows > 0)
			{
				$sql .= "\nLIMIT $limitStart, $maxRows";
			}
			//echo "sql = <pre>" . str_replace("#__", $db->getPrefix(), $sql) . "</pre><br />";
		}
		return $sql;

	}

	/** Generating the sql statement to retrieve the orphans information from the database
	 */
	public function createOrphanSQL($idLanguage=-1, $contentid=null, $limitStart=-1, $maxRows=-1, $filters=array())
	{


		$sqlFields = null;
		$sqlFields[] = "jfc.id as jfc_id";
		$sqlFields[] = "jfc.reference_id as jfc_refid";
		$sqlFields[] = "jfc.value as titleTranslation";
		$sqlFields[] = "jfc.modified as lastchanged";
		$sqlFields[] = 'jfc.published as published';
		$sqlFields[] = 'jfc.language_id';
		$sqlFields[] = 'jfl.title as language';
		$sqlFields[] = 'jfc.original_text as original_text';

		$where = array();
		$order = null;
		$join = null;
		$contentTable = $this->getTable();
		foreach ($filters as $filter)
		{
			$sqlFilter = $filter->createFilter($this);
			if ($sqlFilter != "")
				$where[] = $sqlFilter;
		}
		foreach ($contentTable->Fields as $tableField)
		{
			// Based on the types we might want to have special names ;-)
			switch ($tableField->Type) {
				case "referenceid":
					$contentid_exist = (isset($contentid) && $contentid != -1 );
					if (strtolower($tableField->Name) != "id")
					{
						$sqlFields[] = 'c.' . $tableField->Name . ' as id';
						if ($contentid_exist)
							$where[] = 'c.' . $tableField->Name . '=' . $contentid;
					}
					else
					{
						if ($contentid_exist)
							$where[] = 'c.id=' . $contentid;
					}
					$join[] = 'c.' . $tableField->Name . '=jfc.reference_id ';
					$where[] = 'c.' . $tableField->Name . ' IS NULL ';
					$sqlFields[] = 'c.' . $tableField->Name . '';
					break;
				case "titletext":
					if (strtolower($tableField->Name) != "title")
					{
						$sqlFields[] = 'c.' . $tableField->Name . ' as title';
					}
					//$join[] = "jfc.reference_field='" .$tableField->Name. "'";
					$where[] = "jfc.reference_field='" . $tableField->Name . "'";
					$sqlFields[] = 'c.' . $tableField->Name . '';
					//					$order[] = 'c.' .$tableField->Name;
					break;
			}
		}

		//$join[] = "jfc.reference_table='$contentTable->Name'";
		$where[] = "jfc.reference_table='$contentTable->Name'";
		if (!isset($idLanguage) || ($idLanguage != "" && $idLanguage != -1 ))
		{
			$where[] = "jfc.language_id=$idLanguage";
		}

		$sql = "SELECT " . implode(', ', $sqlFields)
				. "\nFROM #__jf_content as jfc"
				. "\nLEFT JOIN #__" . $contentTable->Name . ' as c ON ' . implode(' AND ', $join)
				. "\nLEFT JOIN #__languages as jfl ON jfc.language_id=jfl.lang_id"
				. (count($where) ? "\nWHERE " . implode(' AND ', $where) : "")
				. (count($order) ? "\nORDER BY " . implode(', ', $order) : "");

		if ($limitStart != -1)
		{
			$sql .= "\nLIMIT $limitStart, $maxRows";
		}
		//echo "orphansql = $sql<br />";

		return $sql;

	}

	/** Generating the sql statement to count the information
	 */
	public function countContentSQL($idLanguage=-1, $filters=array())
	{
		$jf = JoomFishManager::getInstance();
		$lang = $jf->getLanguageByID($idLanguage);

		$contentTable = $this->getTable();

		if ($this->Storage == "joomfish")
		{
			/* Try to simplify the count queries.
			  Check only on original table including the standard filters as we assume that */

			$join = null;
			$where = null;
			$referencefield = "";

			foreach ($contentTable->Fields as $tableField)
			{
				// Based on the types we might want to have special names ;-)
				if ($tableField->Type == "referenceid")
				{
					$join[] = 'c.' . $tableField->Name . '=jfc.reference_id';
					$referencefield = 'c.' . $tableField->Name;
				}
			}

			$sqlFields[] = "COUNT(distinct $referencefield)";
			$join[] = "jfc.reference_table='$contentTable->Name'";
			if (isset($idLanguage) && $idLanguage != -1)
			{
				if ($idLanguage == 'NULL')
				{
					$where[] = "jfc.language_id IS NULL";
				}
				else
				{
					$join[] = "jfc.language_id=$idLanguage";
				}
			}

			foreach ($filters as $filter)
			{
				$sqlFilter = $filter->createFilter($this);
				if ($sqlFilter != "")
					$where[] = $sqlFilter;
			}
			if ($contentTable->Filter != '')
			{
				$where[] = $contentTable->Filter;
			}

			$sql = "SELECT " . implode(', ', $sqlFields)
					. "\nFROM #__" . $contentTable->Name . ' as c'
					. "\nLEFT JOIN #__jf_content as jfc ON " . implode(' AND ', $join)
					. (count($where) ? "\nWHERE " . implode(' AND ', $where) : "");


			//echo "<pre>count-sql = $sql</pre><br />";
			return $sql;
		}
		// else Joomla storage!
		else
		{

			$join = null;
			$where = null;
			$referencefield = "";

			foreach ($contentTable->Fields as $tableField)
			{
				// Based on the types we might want to have special names ;-)
				if ($tableField->Type == "referenceid")
				{
					$referencefield = $tableField->Name;
				}
			}

			$sqlFields[] = "COUNT(distinct c.$referencefield)";
			if (isset($idLanguage) && $idLanguage != -1)
			{
				// TODO we need a source language for the count!
				//$where[] = "language_id='*'";
			}
			else
			{
				// TODO we need a source language for the count!
				$where[] = "(language_id='*' OR language_id=". $db->quote($this->_defaultlang).')';
			}

			foreach ($filters as $filter)
			{
				$sqlFilter = $filter->createFilter($this);
				if ($sqlFilter != "")
					$where[] = $sqlFilter;
			}
			if ($contentTable->Filter != '')
			{
				$where[] = $contentTable->Filter;
			}

			$db = JFactory::getDbo();
			$transmap = "\nLEFT JOIN #__jf_translationmap as tm ON  tm.reference_id=c." . $referencefield. " AND tm.reference_table=".$db->quote($contentTable->Name);
			$transmap .= " AND tm.language=" . $db->quote($lang->code);
			
			$sql = "SELECT " . implode(', ', $sqlFields)
					. "\nFROM #__" . $contentTable->Name . ' as c'
					. $transmap
					."\nLEFT JOIN #__" . $contentTable->Name . ' as ct ON tm.translation_id=ct.' .$referencefield
					. (count($where) ? "\nWHERE " . implode(' AND ', $where) : "");

			//echo "<pre>count-sql = $sql</pre><br />";
			return $sql;
		}

	}

	/**
	 * Returing the number of elements corresponding with the information of the class
	 * @return total number of elements
	 */
	public function countReferences($idLanguage=-1, $filters=array())
	{
		$db = JFactory::getDBO();

		/*
		  $db->setQuery( $this->countContentSQL($idLanguage, $filters) );
		  $result = $db->loadObjectList();
		  echo $db->getErrorMsg();
		  return count( $result );
		 */

		$db->setQuery($this->countContentSQL($idLanguage, $filters));
		$count = $db->loadResult();
		//echo "count = $count<br/>";
		return $count;

	}

	/**
	 * Returns the component specific information related the UI screen settings, options, ...
	 * The information allow the direct translation module to interact with the admin form and allow instant access
	 * to the specific translation screen
	 * @return	array	of admin form parameters
	 */
	public function getComponentInformation()
	{
		$componentInfo = array();
		if (isset($this->_xmlFile))
		{
			$xpath = new DOMXPath($this->_xmlFile);
			$componentElement = $xpath->query('//reference/component')->item(0);
			if (!isset($componentElement) || !$componentElement->hasChildNodes())
			{
				return $componentInfo;
			}
			$forms = $componentElement->getElementsByTagName('form');
			foreach ($forms as $componentForm)
			{
				$componentInfo[] = $componentForm->textContent;
			}
		}
		return $componentInfo;

	}

}

?>
