<?php

class MA_XML_File {
	protected $sxml;
	protected $sxml_flag;
	protected $xml_str_hun_rle;
	protected $storage_path;
	protected $data_arr;
	protected $errors = array();
	protected $file_content;
	protected $file_name;


	public function __construct ($_data_arr, $_path = '', $_file_name = 'massaction'){
		$this->sxml_flag = false;

		if ($this->set_data_arr ($_data_arr)){
			$this->create_sxml ($this->data_arr, $this->sxml);

			$this->file_name = $_file_name;

			$this->make_xml_human_redable ();
		}
		elseif ($_path){
			if (!$this->set_storage_path ($_path)){
				return false;
			}

			$this->file_name = $_file_name;

			$this->fetch_file ();
		}
		else{
			return false;
		}
	}


	public function set_data_arr ($_data_arr){
		if (!$_data_arr){
			return false;
		}
		elseif (is_numeric ($_data_arr) or is_string ($_data_arr)){
			$this->data_arr = array ($_data_arr);
		}
		elseif (!is_array ($_data_arr)){
			return false;
		}
		else{
			$this->data_arr = $_data_arr;
		}

		return true;
	}

	public function get_data_arr (){
		return $this->data_arr;
	}

	/**
	 * @param $_path
	 * @return bool
	 */
	public function set_storage_path ($_path){
		if (!trim ($_path)){
			return false;
		}
		$this->storage_path = rtrim ($_path, ' /'). '/';

		if (!is_dir (rtrim ($this->storage_path, '/') ) ){
			if (!eZDir::mkdir ($this->storage_path, 0776, true) ){
				$this->errors[] = 'Cannot create file or directory no permission, path: '. $this->storage_path;
				eZDebug::writeError (__METHOD__. ' '.__LINE__. ': '. $this->errors[0]);

				$this->storage_path = '';

				return false;
			}
		}

		return true;
	}

	protected function make_xml_human_redable (){
		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = FALSE;
		$dom->loadXML($this->sxml->asXML());
		$dom->formatOutput = TRUE;

		$this->xml_str_hun_rle = $dom->saveXML();
	}
	protected function create_sxml ($data, &$data_xml){
		if (!$this->sxml_flag){
			$this->sxml = new SimpleXMLElement('<root/>');
			$this->sxml_flag = true;

			$this->create_sxml ($data, $this->sxml);
		}
		else{
			foreach ($data as $key => $value){
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
		}
		//$sxml = new SimpleXMLElement();


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

	/**
	 * @deprecated - please use store_file()
	 * @param string $_path
	 * @param string $_file_name
	 * @return bool
	 */
	public function store_file_2 ($_path = '', $_file_name = 'massaction'){
		if (!$_path){
			return false;
		}

		$_file_full_name = $_file_name. '.xml';
		$_path = rtrim ($_path, '/'). '/';

		if (!is_dir (rtrim ($_path, '/') ) ){
			if (!eZDir::mkdir ($_path, 0776, true) ){
				$this->errors[] = 'Cannot create file or directory, path: '. $_path;
				eZDebug::writeError (__METHOD__. ' '.__LINE__. ': '. $this->errors[0]);
				return false;
			}
		}

		$file_hendler = fopen ($_path. $_file_full_name, 'w');
		fwrite ($file_hendler, $this->xml_str_hun_rle, strlen ($this->xml_str_hun_rle) );
		fclose ($file_hendler);

		return true;
	}

	public function store_file (){
		if (!$this->xml_str_hun_rle){
			if (!$this->data_arr){
				return false;
			}
			$this->create_sxml($this->data_arr, $this->sxml);
			$this->make_xml_human_redable();

			return true;
		}
		$file_hendler = fopen ($this->storage_path. $this->file_name. '.xml', 'w');
		fwrite ($file_hendler, $this->xml_str_hun_rle, strlen ($this->xml_str_hun_rle) );
		fclose ($file_hendler);

		return true;
	}

	public function fetch_file (){
		//$file = fopen ($this->storage_path. $file_full_name, 'w+');
		$_file_full_name = $this->file_name. '.xml';

		if (!file_exists ($this->storage_path. $_file_full_name) ){
			$this->errors[] = 'File doesn\'t exist in path: '. $this->storage_path. $_file_full_name;
			eZDebug::writeWarning (__METHOD__. ' '.__LINE__. ': '. $this->errors[0]);
			return false;
		}
		$handle = fopen ($this->storage_path. $_file_full_name, "r");
		if (!$handle){
			$this->errors[] = 'No permission to read file: '. $this->storage_path. $_file_full_name;
			eZDebug::writeError (__METHOD__. ' '.__LINE__. ': '. $this->errors[0]);

			return false;
		}
		$this->file_content = fread ($handle, filesize ($this->storage_path. $_file_full_name));

		fclose ($handle);

		$this->data_arr = (array) simplexml_load_string ($this->file_content);
		$this->make_xml_human_redable();

		return true;
	}

	public function get_file_content (){
		return $this->file_content;
	}

	public function get_error (){
		return $this->errors[0];
	}

}
