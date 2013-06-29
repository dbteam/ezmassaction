<?php

class MA_XML_File {
	protected $sxml;
	protected $sxml_flag;
	protected $xml_str_hun_rle;
	protected $storage_path;
	protected $container;
	protected $data_arr;
	//protected $errors;
	protected $file_content;
	protected $file_name;
	protected $file_original_name;
	protected $file_full_name;
	protected $file_full_path;
	protected $file_ext;
	protected $error;


	public function __construct ($_data_arr = null, $_path = '', $_file_name = ''){
		$this->sxml_flag = false;
		$this->xml_str_hun_rle = false;
		$this->file_ext = '.xml';
		$this->error = MA_Error::get_instance ();

		if ($this->set_data_arr ($_data_arr)){
			$this->create_sxml ($this->data_arr, $this->sxml);

			$this->make_xml_human_redable ();

			if (!$this->set_storage_path ($_path)){
			//	return false;
			}
			$this->set_file_name ($_file_name);

			$this->set_file_full_path ();
		}
		elseif ($_path){
			$this->set_storage_path ($_path);
			$this->set_file_name ($_file_name);

			$this->set_file_full_path ();

			$this->fetch_file ();
		}
		else{
			//return false;
		}
	}


	public function set_data_arr ($_data_arr = ''){
		if (!$_data_arr){
			//$this->error->set_error('Missing data'. __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}
		elseif (is_numeric ($_data_arr) or is_string ($_data_arr)){
			$this->data_arr = array ($_data_arr);
		}
		elseif (!is_array ($_data_arr)){
			$this->error->set_error('wrong data'. __METHOD__, __LINE__, MA_Error::ERROR);
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

	public function set_file_name ($_file_name = 'massaction'){
		$this->file_name = str_replace ('\.xml', '', $_file_name);
		if (!$this->file_original_name){
			$this->file_original_name = $this->file_name;
		}

		$this->set_file_full_name();
	}
	protected function set_file_full_name (){
		$this->file_full_name = $this->file_name. $this->file_ext;
	}
	public function set_file_full_path (){
		$this->file_full_path = $this->storage_path. $this->file_full_name;
	}

	/**
	 * @param $_path
	 * @return bool
	 */
	protected function set_storage_path ($_path = 'massaction'){
		if (!trim ($_path)){
			$this->error->set_error('Path missing, path: '. $this->storage_path, __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}
		//for server on Windows:
		// path: /var/www/myweb/var/plain_site/storage/module-dir-name/
		$_path = str_replace ('\\', '/', $_path);

		$_path = rtrim ($_path, ' /');
		$pos = strrpos($_path, "/");

		$this->container = substr ($_path, ($pos + 1));
		$this->storage_path = $_path. '/';
		$this->storage_path = str_replace("//", '/', $this->storage_path);

		$oldumask = @umask( 0 );

		if (!is_dir (rtrim ($this->storage_path, '/') ) ){
			if (!eZDir::mkdir ($this->storage_path, 0776, true) ){
				/**
				 * Use MA_Error singletone object
				 *
				 * @deprecated
				 * @var $this->errors[] = ''
				 */
				//$this->errors[] = 'Cannot create directory no permission, path: '. $this->storage_path;
				$this->error->set_error('Cannot create directory no permission, path: '. $this->storage_path, __METHOD__, __LINE__, MA_Error::ERROR);
				$this->storage_path = '';
				return false;
			}
		}
		@umask( $oldumask );
		return true;
	}
	public function get_container (){
		return $this->container;
	}

	protected function make_xml_human_redable (){
		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($this->sxml->asXML());
		$dom->formatOutput = true;

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
						$subnode = $data_xml->addChild ("_key_$key");
						$this->create_sxml ($value, $subnode);
					}
				}
				elseif (!is_numeric ($key) ) {
					$data_xml->addChild ("$key","$value");
				}
				else{
					$data_xml->addChild ("_key_$key","$value");
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
	 * @deprecated - please to use store_file()
	 * @param string $_path
	 * @param string $_file_name
	 * @return bool
	 */
	public function store_file_2 ($_path = null, $_file_name = null){
		if (!$_path){
			return false;
		}

		if (!$this->set_storage_path ($_path)){
			return false;
		}

		$this->set_file_name ($_file_name);

		$this->set_file_full_path ();

		if (!$this->store_file ()){
			return false;
		}

		return true;
	}

	public function store_file (){
		if (!$this->xml_str_hun_rle){
			if (!$this->data_arr){
				$this->error->set_error('No data to store in the file.', __METHOD__, __LINE__, MA_Error::WARNING);
				return false;
			}
			$this->create_sxml($this->data_arr, $this->sxml);
			$this->make_xml_human_redable();
		}
		$oldumask = @umask( 0 );
		$counter = 2;
		while (file_exists ($this->file_full_path)) {
			$this->set_file_name ($this->file_original_name. '_'. $counter);
			$this->set_file_full_path ();
			$counter++;
		}

		if (file_exists ($this->file_full_path)){
			$this->error->set_error('File exist this method cannot rewrite the file. Path: '. $this->file_full_path, __METHOD__, __LINE__,
				MA_Error::ERROR);
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
		$file_handler = @fopen ($this->file_full_path, 'xt');//xt wt

		@fwrite ($file_handler, $this->xml_str_hun_rle, strlen ($this->xml_str_hun_rle) );
		@fclose ($file_handler);
		@chmod( $this->file_full_path, 0666);
		@umask( $oldumask );
		return true;
	}
	public function rewrite_file (){
		if (!$this->xml_str_hun_rle){
			if (!$this->data_arr){
				$this->error->set_error('No data to store.', __METHOD__, __LINE__, MA_Error::ERROR);
				return false;
			}
			$this->create_sxml($this->data_arr, $this->sxml);
			$this->make_xml_human_redable ();
		}
		$oldumask = @umask(0);
		if (file_exists($this->file_full_path)){
			@chmod( $this->file_full_path, 0666);
		}
		$file_handler = @fopen ($this->file_full_path, 'wt');//xt wt
		if (!$file_handler){
			$this->error->set_error('No permission. Cannot rewrite the file. Path: '. $this->file_full_path, __METHOD__,
				__LINE__, MA_Error::ERROR);
			return false;
		}
		@fwrite ($file_handler, $this->xml_str_hun_rle, strlen ($this->xml_str_hun_rle) );
		@fclose ($file_handler);
		@chmod( $this->file_full_path, 0666);
		@umask( $oldumask );
		return true;
	}

	public function fetch_file (){
		//$file = fopen ($this->storage_path. $file_full_name, 'w+');
		$this->set_file_full_path ();
		$oldumask = @umask( 0 );
		if (!file_exists ($this->file_full_path) ){
			$this->error->set_error('File doesn\'t exist in path: '. $this->file_full_path, __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}
		$handle = fopen ($this->file_full_path, "rt");
		if (!$handle){
			$this->error->set_error('No permission to read file: '. $this->file_full_path, __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}
		$this->file_content = fread ($handle, filesize ($this->file_full_path));
		@fclose ($handle);
		@umask( $oldumask );

		$this->data_arr = simplexml_load_string ($this->file_content);
		$this->affects_sxml_on_arr_reqursive($this->data_arr);
		//$this->make_xml_human_redable();
		return true;
	}

	/**
	 * Reqursive method works probably on 110%
	 *
	 * @param $array array|SimpleXMLElement
	 */
	protected function affects_sxml_on_arr_reqursive (&$array){
		if ($array instanceof SimpleXMLElement){
			$array = (array) $array;
			if (!count ($array)){
				$array = false;
			}
			$this->affects_sxml_on_arr_reqursive($array);
		}
		elseif (is_array($array)){
			foreach ($array as $key => $row){
				if ($row instanceof SimpleXMLElement){
					if (strlen ($key) > 5){
						$count = 1;
						$numeric_key = str_replace ('_key_', '', $key, $count);
						if (is_numeric($numeric_key)){
							$array[$numeric_key] = $row;
							unset($array[$key]);
							unset($row);
							$this->affects_sxml_on_arr_reqursive ($array[$numeric_key]);
						}
						else{
							unset ($row);
							$this->affects_sxml_on_arr_reqursive ($array[$key]);
						}
						$partname_key_ = false;
					}
					else{
						unset ($row);
						$this->affects_sxml_on_arr_reqursive ($array[$key]);
					}
				}
				elseif (strlen ($key) > 5){
					$count = 1;
					$numeric_key = str_replace ('_key_', '', $key, $count);
					if (is_numeric($numeric_key)){
						$array[$numeric_key] = $row;
						unset($array[$key]);
						unset($row);
					}
					$partname_key_ = false;
				}
			}
		}
	}

	public function get_file_name (){
		return $this->file_name;
	}
	public function get_file_content (){
		return $this->file_content;
	}

	public function get_error (){
		return 'xxxx';
	}

}
