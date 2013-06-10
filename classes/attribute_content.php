<?php
/**
 * Created by JetBrains PhpStorm.
 * User: radek
 * Date: 24.05.13
 * Time: 09:20
 * To change this template use File | Settings | File Templates.
 */

class Attribute_content {
	public static $INITIAL_STEP = 1;
	public static $ENDED_STEP = 4;

	protected $params;
	protected $http; //dosn't work
	protected $module; //eZModule
	public $parameters;
	public $data;
	protected $errors;
	protected $content_object_attribute_post_key;
	protected $storage_path;
	protected $file_content;

	/**
	 * @param $params
	 * @param bool $http
	 * @param bool $alien_use_flag - true for use from external script
	 */
	public function __construct ($params, $http = false, $alien_use_flag = false) {
		if (!$alien_use_flag){
			if (!$this->set_own_attributes ($params, $http) ){
				//eZLog::write('xxx', 'alogg_file.log');
				return false;
			}
		}
		else{
			$this->fetch_variable_parameters_from_xml_file();
		}


		if ($this->module->isCurrentAction ('get_attributes_list') and $this->parameters['step'] == 2) {
			$this->fetch_attributes_list();
		}
		elseif ($this->module->isCurrentAction ('get_attribute') and $this->parameters['step'] == 3) {
			$this->fetch_attribute ();
		}
		elseif ($this->module->isCurrentAction ('change_attribute_content') and $this->parameters['step'] == 4) {
			$this->change_attribute_content ();
		}
		elseif ($this->module->isCurrentAction ('back_step') and $this->parameters['step'] > (self::$INITIAL_STEP + 1) ){
			$this->set_to_back_step ();
		}
		elseif ($this->module->isCurrentAction ('cancel') ){
			$this->start();
		}
		else{
			$this->do_default ();
		}
		var_dump($this->parameters);

	}


	protected function set_own_attributes ($params_, $http_ = ''){
		if (!$params_){
			eZDebug::writeError( __METHOD__. ' line: '. __LINE__.' : $params_ is missing.');
			return false;
		}
		$this->params = $params_;
		$this->module = $params_['Module'];

		if (!is_object ($http_) ) {
			$this->http = eZHTTPTool::instance();
		}
		else{
			$this->http = $http_;
		}
		$this->storage_path = str_replace ('\\', '/', eZSys::rootDir() ).'/'. eZSys::storageDirectory(). '/'.
			$this->module->currentModule(). '/';

		$this->parameters = array ();
		$this->fetch_session_variable_parameters();

		if ( (empty ($this->parameters['step']) ) or $this->parameters['step'] < self::$INITIAL_STEP){
			$this->start ();
		}
		else{
			$this->parameters['step']++;
		}
		return true;
	}

	protected function start(){
		$this->parameters = array();
		$this->parameters['step'] = self::$INITIAL_STEP;
		$this->parameters['first_step_id'] = self::$INITIAL_STEP;

		$this->parameters['form']['action']['url_alias'] = $this->module->currentModule (). '/'.
			$this->module->Functions['attributecontent']['custom_view_parameters']['default']['url_alias'];

		$this->store_session_variable_parameters();
	}
	protected function do_default(){
		if ( $this->parameters['step'] > self::$INITIAL_STEP){
			$this->parameters['step']--;
		}
		$this->store_session_variable_parameters();

	}

	public function get_parameters (){
		return $this->parameters;
	}

	protected function set_to_next_step (){
		//$this->parameters['step']++;
		$this->parameters['form']['action']['url_alias'] = $this->module->currentModule (). '/'.
			$this->module->Functions['attributecontent']['custom_view_parameters'][$this->module->currentAction ()]['url_alias'];
		$this->store_session_variable_parameters();
	}
	protected function set_to_repeat_step (){
		$this->parameters['form']['action']['url_alias'] = $this->module->currentModule (). '/'.
			$this->module->Functions['attributecontent']['custom_view_parameters'][$this->module->currentAction ()]['url_alias'];
		$this->parameters['step']--;
		$this->store_session_variable_parameters();
	}

	protected function set_to_back_step (){
		$this->parameters['step'] -= 2;
		$this->store_session_variable_parameters();

		return true;
	}

	public function fetch_attributes_list () {
		if (!$this->validate_data__fetch_attributes_list ()){
			$this->set_to_repeat_step();

			return false;
		}
		$this->set_to_next_step();

		return true;
	}
	protected function validate_data__fetch_attributes_list (){
		$this->errors = array ();

		if (count ($this->module->actionParameter ('parents_nodes_ids') ) < 1){
			$this->errors = array ('Required data is either missing or is invalid');
		}
		else{
			$this->parameters['parents_nodes_ids'] = $this->module->actionParameter ('parents_nodes_ids');
			foreach ($this->parameters['parents_nodes_ids'] as $key => $node_id){
				$this->parameters['parents_nodes_ids'][$key] = (int) $node_id;
			}
		}

		if ($this->module->hasActionParameter ('section_id') ) {
			$this->parameters['section_id'] = (int) $this->module->actionParameter ('section_id');
		}

		if ($this->module->actionParameter ('class_id') < 1){
			$this->errors = array ('Required data is either missing or is invalid');
		}
		else{
			$this->parameters['class_id'] = (int) $this->module->actionParameter ('class_id');
		}

		if (count ($this->module->actionParameter ('locales_codes') ) < 1){
			$this->errors = array ('Required data is either missing or is invalid');
		}
		else{
			$this->parameters['locales_codes'] = $this->module->actionParameter ('locales_codes');
		}

		if (isset ($this->errors[0]) ){
			return false;
		}

		return true;
	}

	public function fetch_attribute (){
		if (!$this->validate_data__fetch_attribute() ){
			$this->set_to_repeat_step();

			return false;
		}
		$this->set_var_parameters_attr_identifier();
		$this->set_var_parameters_class_identifier();

		$this->set_to_next_step ();

		return true;
	}
	protected function validate_data__fetch_attribute (){
		$this->errors = array ();

		if (!$this->module->hasActionParameter ('attribute_id') ){
			$this->errors = array ('Required data is either missing or is invalid');
			return false;
		}

		if ($this->module->actionParameter ('attribute_id') < 1){
			$this->errors = array ('Required data is either missing or is invalid');
			return false;
		}
		$this->parameters['attribute_id'] = (int) $this->module->actionParameter ('attribute_id');

		return true;
	}
	protected function set_var_parameters_attr_identifier (){
		$this->parameters['attribute_identifier'] = eZContentClassAttribute::classAttributeIdentifierByID ($this->parameters['attribute_id']);
	}
	protected function set_var_parameters_class_identifier (){
		$this->parameters['class_identifier'] = eZContentClass::classIdentifierByID ($this->parameters['class_id']);
	}

	public function change_attribute_content (){
		if (!$this->search_attribute_in_post () ){
			return false;
		}
		$this->set_var_parameters_attribute_content ();

		$this->store_variable_parameters_in_xml_file ();
		$this->store_session_variable_parameters();

		return true;
	}
	protected function search_attribute_in_post (){
		$post_keys = array_keys ($_POST);

		/*
		$post_keys = array (//test data
			'bla_bla',
			'ContentObjectAttribute_data_text_585',
			'ContentObjectAttribute_data_enhancedbinaryfilename_1209600'
		);
		*/
		$pattern = '/^ContentObjectAttribute_[[:alnum:]_]{4,}$/u';
		$founded_keys = preg_grep ($pattern, $post_keys, 0);
		/* $founded_keys
		 * 1 => 'ContentObjectAttribute_data_text_585',
		 * 2 => 'ContentObjectAttribute_data_enhancedbinaryfilename_1209600'
		 */

		if (!count ($founded_keys) ){
			$this->errors[] = 'Do not founded attribute.';
			eZDebug::writeWarning ('Module: '. $this->module->currentModule(). '/'. $this->module->currentView().
				' '. __METHOD__. ' '.__LINE__. ': '. $this->errors[0]);

			return false;
		}
		$founded_keys = array_values ($founded_keys);
		// array reindex to set first founded element to the index 0
		$this->content_object_attribute_post_key = $founded_keys[0];

		return true;
	}
	protected function set_var_parameters_attribute_content (){
		$this->parameters['attribute_content'] = $_POST[$this->content_object_attribute_post_key];
	}

	protected function store_session_variable ($variable_name, $variable){
		if (!$variable_name){
			return false;
		}
		eZSession::set(
			'massive_action', array (
				'attribute_content' => array (
					$variable_name => $variable
				)
			)
		);

		return true;
	}

	public function get_session_variable ($name){
		if ($name){
			$massive_action = eZSession::get('massive_action');

			if ($massive_action['attribute_content'][$name]){
				return $massive_action['attribute_content'][$name];
			}
			return false;
		}

		return true;
	}
	public function store_session_variable_parameters (){
		//$this->parameters['step'] = $this->parameters['next_step'];
		eZSession::set (
			'massive_action', array (
				'attribute_content' => array (
					'parameters' => $this->parameters
				)
			)
		);
	}

	public function fetch_session_variable_parameters (){
		//$massive_action = $this->http->sessionVariable('massive_action');
		$massive_action = eZSession::get( 'massive_action' );

		if ( is_array ($massive_action) ){
			$this->parameters = $massive_action['attribute_content']['parameters'];
		}
	}


	/** Method could change on static if external script require it
	 *
	 */
	public function fetch_variable_parameters_from_xml_file (){
		$this->fetch_file ($this->module->surrentView(). '.xml');

	}
	protected function fetch_file ($file_full_name = 'default.txt'){
		//$file = fopen ($this->storage_path. $file_full_name, 'w+');
		if (!file_exists ($this->storage_path. $file_full_name) ){
			$this->errors[] = 'File doesn\'t exist in path: '. $this->storage_path. $file_full_name;
			eZDebug::writeWarning (__METHOD__. ' '.__LINE__. ': '. $this->errors[0]);
			return false;
		}
		$handle = fopen ($this->storage_path. $file_full_name, "r");
		$this->file_content = fread ($handle, filesize ($this->storage_path. $file_full_name));

		fclose ($handle);

		return true;
	}

	public function store_variable_parameters_in_xml_file (){
		$sxml = new SimpleXMLElement('<root/>');
		$this->create_sxml ($this->parameters, $sxml);

		$xml_str_hun_rle = $this->make_xml_human_redable ($sxml->asXML());
	//	var_dump ('result: <br />');
	//	var_dump ($xml_str_hun_rle);

		$this->store_file ($this->module->currentView().'.xml', $xml_str_hun_rle);
	}
	protected function store_file ($file_full_name = 'default.txt', $data = 'blad'){
		if (!is_dir (rtrim ($this->storage_path, '/') ) ){
			if (!eZDir::mkdir (rtrim ($this->storage_path, '/'), 0772, true) ){
				$this->errors[] = 'Cannot create or file directory, path: '. rtrim ($this->storage_path, '/');
				eZDebug::writeError ('Module: '. $this->module->uri(). '/'. $this->currentView().' '.
					__METHOD__. ' '.__LINE__. ': '. $this->errors[0]);
				return false;
			}
		}

		$file_hendler = fopen ($this->storage_path. $file_full_name, 'w');
		fwrite ($file_hendler, $data, strlen ($data) );
		fclose ($file_hendler);

		return true;
	}
	protected function make_xml_human_redable ($data){
		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = FALSE;
		$dom->loadXML($data);
		$dom->formatOutput = TRUE;
		return $dom->saveXML();
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

	protected function add_to_session_variable_parameters ($variable, $name = ''){
		if (is_array ($variable) ){
			$this->parameters = array_merge ($this->parameters, $variable);
		}
		else{
			$this->parameters = array_merge ($this->parameters, array ($name, $variable) );
		}
	}

	public function get_errors (){
		return $this->errors;
	}

}
