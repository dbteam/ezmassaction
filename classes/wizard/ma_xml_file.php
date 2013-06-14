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
	protected $file_full_name;
	protected $file_ext;


	public function __construct ($_data_arr, $_path = '', $_file_name = 'massaction'){
		$this->sxml_flag = false;
		$this->file_ext = '.xml';

		if ($this->set_data_arr ($_data_arr)){
			$this->create_sxml ($this->data_arr, $this->sxml);

			$this->set_file_full_name ($_file_name);

			$this->make_xml_human_redable ();

			if (!$this->set_storage_path ($_path)){
				return false;
			}
		}
		elseif ($_path){
			if (!$this->set_storage_path ($_path)){
				return false;
			}

			$this->set_file_full_name ($_file_name);

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

	public function set_file_full_name ($_file_name = 'massaction'){
		$this->file_name = $_file_name;
		$this->file_full_name = $this->file_name. $this->file_ext;
	}

	/**
	 * @param $_path
	 * @return bool
	 */
	public function set_storage_path ($_path){
		if (!trim ($_path)){
			return false;
		}
		//for server on Windows:
		$this->storage_path = str_replace ('\\', '/', $_path);

		$this->storage_path = rtrim ($this->storage_path, ' /'). '/';

		if (!is_dir (rtrim ($this->storage_path, '/') ) ){
			if (!eZDir::mkdir ($this->storage_path, 0776, true) ){
				$this->errors[] = 'Cannot create directory no permission, path: '. $this->storage_path;
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

		if (!$this->set_storage_path ($_path)){
			return false;
		}

		$this->set_file_full_name ($_file_name);

		if (!$this->store_file ()){
			return false;
		}

		return true;
	}

	public function store_file (){
		if (!$this->xml_str_hun_rle){
			if (!$this->data_arr){
				$this->errors[] = 'No data to store.';

				return false;
			}
			$this->create_sxml($this->data_arr, $this->sxml);
			$this->make_xml_human_redable();

			//return true;
		}

		$_file_full_path = $this->storage_path. $this->file_full_name;

		$counter = 2;
		while (file_exists ($_file_full_path)) {
			$this->set_file_full_name ($this->file_name. '_'. $counter);

			$_file_full_path = $this->storage_path. $this->file_full_name;

			$counter++;
		}

		$file_hendler = fopen ($_file_full_path, 'xt');//xt wt

		if (!$file_hendler){
			$this->errors[] = 'File exist this method cannot rewrite the file. Path: '. $_file_full_path;
			eZDebug::writeError (__METHOD__. ' '.__LINE__. ': '. $this->errors[0]);

			return false;
		}
		else{
			/*
			if (!is_writable ($_file_full_path)){
				$this->errors[] = 'Cannot rewrite the file, no permissions. Path: '. $_file_full_path;
				eZDebug::writeError (__METHOD__. ' '.__LINE__. ': '. $this->errors[0]);

				fclose ($file_hendler);

				return false;
			}
			*/
		}

		fwrite ($file_hendler, $this->xml_str_hun_rle, strlen ($this->xml_str_hun_rle) );
		fclose ($file_hendler);

		return true;
	}

	public function fetch_file (){
		//$file = fopen ($this->storage_path. $file_full_name, 'w+');
		$_file_full_path = $this->storage_path. $this->file_full_name;

		if (!file_exists ($_file_full_path) ){
			$this->errors[] = 'File doesn\'t exist in path: '. $_file_full_path;
			eZDebug::writeWarning (__METHOD__. ' '.__LINE__. ': '. $this->errors[0]);
			return false;
		}

		$handle = fopen ($_file_full_path, "r");
		if (!$handle){
			$this->errors[] = 'No permission to read file: '. $_file_full_path;
			eZDebug::writeError (__METHOD__. ' '.__LINE__. ': '. $this->errors[0]);

			return false;
		}
		$this->file_content = fread ($handle, filesize ($_file_full_path));

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
