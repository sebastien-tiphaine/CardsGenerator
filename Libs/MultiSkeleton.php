<?php

require_once(__DIR__.'/CardSkeleton.php');
require_once(__DIR__.'/StoreAbstract.php');

class MultiSkeleton extends StoreAbstract implements Iterator, Countable{

	// list stored skeletons
	protected $_arrSkeletonStorage = array();

	// list of skeletons vars
	protected $_arrVars = array();

	// common lock state
	protected $_intIsLocked = false;

	// pos of iterator
	protected $_intIteratePos = 0;

	// stores a MultiSkeleton object
	public static function storeMulti($strId, $oMulti){
		return parent::storeObject($strId, $oMulti);
	}

	// returns true if MultiSkeleton $strId exists
	public static function hasMulti($strId){
		return parent::hasObject($strId);
	}

	//returns skeleton id
	public static function getMulti($strId){
		return parent::getObject($strId);
	}

	// constructor
	public function __construct($mSkeleton = false){

		// do we have one or more skeleton to add
		if($mSkeleton){
			// yes
			$this->addSkeleton($mSkeleton);
		}

		// init iterator value
		$this->_intIteratePos = 0;

		return $this;
	}

	public function count(){
		return count($this->_arrSkeletonStorage);
	}

	// iterator
	public function rewind() {
        $this->_intIteratePos = 0;
    }

    public function current() {
        return $this;
    }

    public function key() {
        return $this->_intIteratePos;
    }

    public function next() {
        ++$this->_intIteratePos;
    }

    public function valid() {
        return isset($this->_arrSkeletonStorage[$this->_intIteratePos]);
    }
	// /iterator

	// add a skeleton to the list
	public function addSkeleton($mSkeleton){

		// adds many skeletons at once
		if(is_array($mSkeleton) && !empty($mSkeleton)){
			foreach($mSkeleton as $oSkelItem){
				$this->addSkeleton($oSkelItem);
			}
			// done
			return $this;
		}

		// checking name
		if(is_string($mSkeleton)){
			$mSkeleton = CardSkeleton::getSkeleton($mSkeleton);
		}

		if(!$mSkeleton instanceof CardSkeleton){
			throw new Exception('Skeleton object expected');
		}

		// adding skeleton to the list
		$this->_arrSkeletonStorage[] = array(
				'skeleton' => clone $mSkeleton,
				'strRenderKey' => false
		);

		// done
		return $this;
	}

	// sets a fixed value that will be used to render the skeletons
	public function setVar($strName, $mValue){

		// checking var name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('String expected');
		}

		// adding var value
		$this->_arrVars[$strName] = $mValue;

		// done
		return $this;
	}

	// returns true if object is locked
	public function isLocked(){
		return $this->_intIsLocked;
	}

	// lock all skeletons
	public function setLocked(){

		$this->_intIsLocked = true;
		
		foreach($this->_arrSkeletonStorage as $arrSkelProp){
			$arrSkelProp['skeleton']->setLocked();
		}

		return $this;
	}

	// returns skeleton at index $intIndex
	public function getSkeleton($intIndex = false){

		// getting validated index
		$intIndex = $this->_getValidatedIndex($intIndex, false);

		// setting value
		return $this->_arrSkeletonStorage[$intIndex]['skeleton'];
	}

	// returns index if valid or throws an exception
	protected function _getValidatedIndex($intIndex, $intAllowAll = false){

		// checking when no intAll is not set
		if(!$intAllowAll && is_numeric($intIndex) && $intIndex < 0){
			// no
			throw new Exception('invalid index value given');
		}

		// do we have all index set
		if($intAllowAll && $intIndex == -1){
			// yes
			return -1;
		}

		// do we have a key
		if(!is_numeric($intIndex)){
			// no. using iterator pos as object key
			return $this->_intIteratePos;
		}

		// does the index exists
		if(!isset($this->_arrSkeletonStorage[$intIndex])){
			// no
			throw new Exception('getValue : index out of range : '.$intIndex);
		}

		return $intIndex;
	}

	// returns rendering key of skeleton at index $intIndex
	public function getRenderingKey($intIndex = false){

		// getting validated index
		$intIndex = $this->_getValidatedIndex($intIndex, false);

		// setting value
		return $this->_arrSkeletonStorage[$intIndex]['strRenderKey'];
	}

	// sets a value over all skeleton
	public function setValueForAll($strKey, $mValue = false){

		foreach($this->_arrSkeletonStorage as $arrSkelProp){

			// can we set the value
			if(!$arrSkelProp['skeleton']->hasValue($strKey, $arrSkelProp['strRenderKey'])){
				// no
				continue;
			}

			// setting the value	
			$arrSkelProp['skeleton']->setValue($strKey, $mValue, $arrSkelProp['strRenderKey']);
		}

		return $this;
	}

	// sets a value over all skeleton if $intIndex == -1
	// or to a specific index if a numerical value is given
	// if $intIndex === false, current iterate index is used
	public function setValue($strKey, $mValue = false, $intIndex = false){

		// do we have a list of values
		if(is_array($strKey) && !empty($strKey)){
			// yes
			// extracting values
			$arrValues = $strKey;
			
			foreach($arrValues as $strKey => $mValue){
				$this->setValue($strKey, $mValue, $intIndex);
			}
			// done
			return $this;
		}

		// getting validated index
		$intIndex = $this->_getValidatedIndex($intIndex, false);

		// setting value
		$this->_arrSkeletonStorage[$intIndex]['skeleton']->setValue($strName, $mValue, $this->_arrSkeletonStorage[$intIndex]['strRenderKey']);
		// done
		return $this;
	}

	// returns true if skeleton on index $intIndex
	public function hasValue($strName, $intIndex = false){

		// getting validated index
		$intIndex = $this->_getValidatedIndex($intIndex, false);

		// yes
		return $this->_arrSkeletonStorage[$intIndex]['skeleton']->hasValue($strName, $this->_arrSkeletonStorage[$intIndex]['strRenderKey']);
	}

	// returns the value of skeleton on index $intIndex
	public function getValue($strName, $intIndex = false){

		// getting validated index
		$intIndex = $this->_getValidatedIndex($intIndex, false);

		// yes
		return $this->_arrSkeletonStorage[$intIndex]['skeleton']->getValue($strName, $this->_arrSkeletonStorage[$intIndex]['strRenderKey']);
	}

	// prepare objects for rendering
	public function prepareRendering($mBaseScale = false){

		// updating lock state
		$this->_intIsLocked = true;

		foreach($this->_arrSkeletonStorage as $intKey => $arrSkelProp){
			$this->_arrSkeletonStorage[$intKey]['strRenderKey'] = $arrSkelProp['skeleton']->prepareRendering($mBaseScale);
		}

		return $this;
	}

	// sets a value over all skeleton
	public function setBaseScaleSvgForAll($mBaseScale){

		foreach($this->_arrSkeletonStorage as $arrSkelProp){
			$arrSkelProp['skeleton']->setBaseScaleSvg($mBaseScale, $arrSkelProp['strRenderKey']);
		}
		
		return $this;
	}

	// sets the baseScaleSvg for all skeletons
	public function setBaseScaleSvg($mBaseScale, $intIndex = false){

		// getting validated index
		$intIndex = $this->_getValidatedIndex($intIndex, false);

		$this->_arrSkeletonStorage[$intIndex]['skeleton']->setBaseScaleSvg($mBaseScale, $this->_arrSkeletonStorage[$intIndex]['strRenderKey']);
		
		return $this;
	}

	// return current base scale image
	public function getBaseScaleSvg($intIndex = false){

		// getting validated index
		$intIndex = $this->_getValidatedIndex($intIndex, false);

		return $this->_arrSkeletonStorage[$intIndex]['skeleton']->getBaseScaleSvg($this->_arrSkeletonStorage[$intIndex]['strRenderKey']);
	}

	// returns all skeletons
	protected function _getSkeletons(){

		$arrResult = false;

		foreach($this->_arrSkeletonStorage as $arrSkelProp){
				$arrResult[] = $arrSkelProp['skeleton'];
		}
		
		return $arrResult;
	}

	// renders all skeletons
	public function render($arrValues = false, $arrVars = false, $mBaseScale = false){

		// setting given values
		if(is_array($arrValues) && !empty($arrValues)){			
			$this->setValue($arrValues, false, -1);
		}

		// do we have to ensure that $arrVars is an array
		if(!is_array($arrVars) && is_array($this->_arrVars) && !empty($this->_arrVars)){
			// yes
			$arrVars = array();
		}

		// do we have vars locally
		if(is_array($this->_arrVars) && !empty($this->_arrVars)){
			// yes
			$arrVars = array_merge($this->_arrVars, $arrVars);
		}

		foreach($this->_arrSkeletonStorage as $arrSkelProp){
			$arrSkelProp['skeleton']->render($arrSkelProp['strRenderKey'], $arrVars, $mBaseScale);
		}

		// done
		return 'fakeRenderingKey';
	}

	// returns all rendered sketeltons
	public function getRenderedTemplate(){

		$arrResult = array();

		foreach($this->_arrSkeletonStorage as $arrSkelProp){
			$arrResult[] = $arrSkelProp->getRenderedTemplate($arrSkelProp['strRenderKey']);
		}

		return $arrResult;
	}

	// render all the skeletons with the given parameters and returns an array
	public function toArray($arrValues = false, $arrVars = false, $mBaseScale = false){

		// render the skeleton with the given values
		$this->render($arrValues, $arrVars, $mBaseScale);
		// return an array of the rendered skeleton
		return $this->getRenderedTemplate();
	}

	public function insertAsTemplate($oGenerator, $arrValues = false, $arrVars = false, $mBaseScale = false){

		// getting an array rendered version of the skeleton
		$arrCards = $this->toArray($arrValues, $arrVars, $mBaseScale);

		foreach($arrCards as $arrCard){
			// adding template to the generator
			call_user_func_array(array($oGenerator, 'addCardTemplate'), $arrCard);
		}

		// done
		return $this;
	}
}
