<?php

class MA_XML_File {
	protected $sxml;
	protected $xml_str_hun_rle;
	protected $storage_path;
	protected $data_arr;
	protected $errors = array();
	protected $file_content;


	public function __construct ($_data_arr, $_path = '', $file_name = ''){
		if (is_array ($data_arr)){
			$this->data_arr = $_data_arr;
			$this->sxml = new SimpleXMLElement('<root/>');
			$this->create_sxml ($this->data_arr, $this->sxml);

			$this->make_xml_human_redable ();

		}
		elseif ($_path){
			$this->fetch_file ($_path);

		}

		return false;
	}

	protected function make_xml_human_redable (){
		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = FALSE;
		$dom->loadXML($this->sxml->asXML());
		$dom->formatOutput = TRUE;

		$this->xml_str_hun_rle = $dom->saveXML();
	}
	protected function create_sxml ($data, &$data_xml){
		//$sxml = new SimpleXMLElement();
		foreach($data as $key => $value) {
			if (is_array ($value) ) {
				if (!is_numeric ($key) ){
					$subnode = $data_xml->addChild ("$key");
					$this->create_sxml ($value, $subnode);
				}
				else{
					$this->create_sxml ($value, $data_xml);
				}
			}
			elseif (is_numeric ($key) ) {
				$data_xml->addChild ("_key_$key","$value");
			}
			else{
				$data_xml->addChild ("$key","$value");
			}
		}

		/*
		foreach ($data as $key => $value){
			if (is_numeric ($key)){
				$key__ = $key;
				$key = '_key_'. $key;
				$data[$key] = $value;
				unset ($data[$key__]);
			}
			if (is_array ($value) ){
				$data[$key] = $this->reindex_an_array_to_assoc_array ($value);
			}

		}
		return $data;
		*/
	}
	public function store_file ($file_name = 'default_name', $_path = ''){
		if (!$_path){
			return false;
		}

		$file_full_name = $file_name. '.xml';

		if (!is_dir (rtrim ($_path, '/') ) ){
			if (!eZDir::mkdir (rtrim ($_path, '/'), 0776, true) ){
				$this->errors[] = 'Cannot create file or directory, path: '. rtrim ($_path, '/');
				eZDebug::writeError (__METHOD__. ' '.__LINE__. ': '. $this->errors[0]);
				return false;
			}
		}

		$file_hendler = fopen ($_path. $file_full_name, 'w');
		fwrite ($file_hendler, $this->xml_str_hun_rle, strlen ($this->xml_str_hun_rle) );
		fclose ($file_hendler);

		return true;
	}

	public function fetch_file ($_path, $file_name = ''){
		//$file = fopen ($this->storage_path. $file_full_name, 'w+');
		$file_full_name = $file_name. '.xml';

		if (!file_exists ($_path. $file_full_name) ){
			$this->errors[] = 'File doesn\'t exist in path: '. $_path. $file_full_name;
			eZDebug::writeWarning (__METHOD__. ' '.__LINE__. ': '. $this->errors[0]);
			return false;
		}
		$handle = fopen ($_path. $file_full_name, "r");
		$this->file_content = fread ($handle, filesize ($_path. $file_full_name));

		fclose ($handle);

		return true;
	}

	public function get_file_content (){
		return $this->file_content;
	}

}
