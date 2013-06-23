<?php
/**
 * Created by JetBrains PhpStorm.
 * User: radek
 * Date: 17.06.13
 * To change this template use File | Settings | File Templates.
 */

class MA_Content_Object_Tree_Nodes_List extends eZContentObjectTreeNode {

	protected $parent_node;
	protected $parent_node_id;
	protected $nodes_tree_list;
	protected $nodes_tree_list_count;
	protected $nodes_tree_list_count_step;
	protected $nodes_tree_list_to_change;
	protected $nodes_tree_list_changed;
	protected $last_node_to_change;
	protected $result;

	protected $section_identifier;
	protected $section_id;

	protected $class_identifier;
	protected $class_id;

	protected $attribute_identifier;
	protected $attribute_content;

	protected $languages_codes;

	protected $depth;
	protected $offset;
	protected $limit;

	protected $count;

	protected $nodes_name_pattern;
	protected $nodes_count_per_floor_pattern;

	protected $errors_list;
	protected $has_error;
	protected $cron_flag;

	protected $error;

	protected $db;


	public function __construct ($_parent_node, $_section, $_class, $_languages, $_depth = 0){
		$this->has_error = false;
		$this->cron_flag = false;
		$this->error = MA_Error::get_instance ();

		$this->set_parent_node ($_parent_node);
		$this->set_section ($_section);
		$this->set_class ($_class);
		$this->set_languages ($_languages);
		$this->set_depth ($_depth);

		//$this->
		//$this->set_offset ($_offset);
		//$this->set_limit ($_limit);

		if ($this->error->has_error()){
			return false;
		}

	}

	protected function set_parent_node ($_parent_node){
		if (!$_parent_node){
			$this->error->set_error('$_parent_node missing.', __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}

		if (is_numeric ($_parent_node)){
			$this->parent_node_id = (int) $_parent_node;
		}
		else{
			$this->parent_node = $_parent_node;
			$this->parent_node_id = $this->parent_node->attribute ('node_id');
		}

		return true;
	}
	protected function set_section ($_section = 'standard'){
		if (!$_section){
			//$this->add_error ('Section missing.'. __METHOD__. ' '. __LINE__);
			//$this->error->set_error('$_section missing. ', __METHOD__, __LINE__, MA_Error::NOTICE);
			//return false;
		}

		if (is_numeric ($_section)){
			$this->section_id = $_section;

			$_section = eZFunctionHandler::execute(
				'section', 'object',
				array (
					'section_id' => $this->section_id
				)
			);

			$this->section_identifier = $_section->attribute ('identifier');
		}
		else{
			$this->section_identifier = $_section;
		}

		return true;
	}
	protected function set_class ($_class){
		if (!$_class){
			$this->error->set_error('Class missing.', __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}

		if (is_numeric ($_class)){
			$this->class_id = $_class;

			$_class = eZFunctionHandler::execute(
				'content', 'class',
				array (
					'class_id' => $this->class_id
				)
			);

			$this->class_identifier = $_class->attribute ('identifier');
		}
		else{
			$this->class_identifier = $_class;
		}

		return true;
	}
	protected function set_languages ($_languages){
		if (count ($_languages) < 1){
			$this->error->set_error('Languages missing.', __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}

		$this->languages_codes = $_languages;

		return true;
	}
	protected function set_depth ($_depth = 2){
		if (!is_numeric ($_depth)){
			$this->error->set_error('Depth is not a number.', __METHOD__, __LINE__, MA_Error::ERROR);

			return false;
		}

		$this->depth = $_depth;

		return true;
	}

	/*
	public function set_own_parameters ($_parent_node, $_section, $_class, $_languages, $_depth){
		$this->has_error = false;
		$this->cron_flag = false;
		
		$this->set_parent_node ($_parent_node);
		$this->set_section ($_section);
		$this->set_class ($_class);
		$this->set_languages ($_languages);
		$this->set_depth ($_depth);

		//$this->
		//$this->set_offset ($_offset);
		//$this->set_limit ($_limit);


		if ($this->has_error){

			return false;
		}
	}
	*/

	protected function set_attribute_identifier ($_attribute_identifier){
		if (!$_attribute_identifier){
			//$this->add_error ('$_attribute_identifier missing. '. __METHOD__. ' '. __LINE__);
			$this->error->set_error('Attribute identifier missing.', __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}
		$this->attribute_identifier = $_attribute_identifier;
		return true;
	}
	protected function set_offset ($_offset = 0){
		if (!is_numeric ($_offset)){
			//$this->add_error ('$_offset is not a number. '. __METHOD__. ' '. __LINE__);
			$this->error->set_error('Offset is not a number.', __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}
		else{
			$this->offset = (int) $_offset;
		}

		return true;
	}
	protected function set_limit ($_limit = 0){
		if (!$_limit){
			$_limit = 50;
		}
		if (!is_numeric ($_limit)){
			//$this->add_error ('$_count is not a number. '. __METHOD__. ' '. __LINE__);
			$this->error->set_error('Count is not a number.', __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}
		else{
			$this->limit = (int) $_limit;
		}
		return true;
	}
	protected function set_attribute_content ($_attribute_content){
		if (!$_attribute_content){
			//$this->add_error ('$_attribute_content missing. '. __METHOD__. ' '. __LINE__);
			$this->error->set_error('Attribute content missing.', __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}
		$this->attribute_content = $_attribute_content;
		return true;
	}
	protected function set_cron_flag ($_cron_flag = false){
		if ($_cron_flag){
			$this->cron_flag = true;
		}
	}
	public function set_to_change_nodes_tree_attribute_content ($_attribute_identifier, $_attribute_content, $_cron_flag, $_offset, $_limit = 0){
		$this->set_attribute_identifier ($_attribute_identifier);

		$this->set_attribute_content ($_attribute_content);

		$this->set_cron_flag ($_cron_flag);

		if ($this->cron_flag){
			$this->set_offset ($_offset);
			$this->set_limit ($_limit);
		}

		$this->nodes_tree_list_changed = array ();
		$this->set_all_nodes_tree_count();
		$this->fetch_last_node_to_change ();

		$this->result = array ();
		$this->result['limit'] = $this->limit;
		$this->result['parent_node_id'] = $this->parent_node_id;
		$this->result['counter'] = 0;
		$this->result['count'] = $this->nodes_tree_list_count;

		if ($this->error->has_error()){
			return false;
		}
		return true;
	}
	protected function set_change_result (){
		//$this->result['counter'] = count ($this->nodes_tree_list_changed);

		if ($this->last_node_to_change->attribute ('mode_id') == end ($this->nodes_tree_list_changed)->attribute ('node_id')){
			$this->result['end_flag'] = true;
		}
		else{
			$this->result['end_flag'] = false;
		}
	}
	public function get_change_result (){
		return $this->result;
	}
	public function change_nodes_tree_attribute_content (){
		$this->fetch_nodes_tree_list ();
		$this->change_nodes_tree_attribute_content_ ();
		$this->set_to_next_use ();
		$this->set_change_result ();

		return true;
	}
	protected function change_nodes_tree_content_now (){

	}
	protected function get_changed_nodes_list (){
		return $this->nodes_tree_list_changed;
	}

	protected function change_nodes_tree_attribute_content_ ($_transaction_flag = false){
		/*
		$knodek = eZContentObjectTreeNode::fetch(11);
		$knodek->store()
		$kobject = $knodek->object();
		$kobject->store()
		$kobject->expireAllViewCache();

		$kdata_map = $kobject->fetchDataMap();
		$kdata_map->
	*/
		//eZContentObjectAttribute::create()

		if ($_transaction_flag){
			$this->db = eZDB::instance();
			$this->db->begin();
		}
		foreach ($this->nodes_tree_list as $_key => $_node){
			$_object = $_node->object();
			$_avalaible_languages = $_object->availableLanguages ();
			foreach ($this->languages_codes as $_code){
				if (in_array ($_code, $_avalaible_languages)){
					$_datamap = $_object->fetchDataMap (false, $_code);
					$_datamap[$this->attribute_identifier]->setContent ($this->attribute_content);

					$_datamap[$this->attribute_identifier]->store ();
				}
			}
			//$_node->store();
			$this->nodes_tree_list_changed[] = $_node->attribute ('node_id');
			$this->result['counter']++;

			$_object->expireAllViewCache ();

			//eZContentCacheManager::clearContentCache();
		}
		if ($_transaction_flag){
			$this->db->commit();
		}

	}

	protected function set_to_next_use (){
		if ($this->cron_flag and $this->nodes_tree_list_count_step >= $this->limit){
			$this->set_offset ($this->offset + $this->limit);
		}
	}
	protected function fetch_nodes_tree_list (){
		if (!$this->cron_flag){
			$_function_parameters = array (
				'parent_node_id' => $this->parent_node_id,
				//'sort_by' => array ('path', false()),
				'sort_by' => array ('published', true),
				'class_filter_type' => array ('include'),
				'class_filter_array' => array ($this->class_identifier),
				'as_object' => true,
				'depth' => $this->depth,
				'ignore_visibility' => false,
				'load_data_map' => true
			);

			$this->nodes_tree_list = eZFunctionHandler::execute (
				'content', 'list',
				$_function_parameters
			);
		}
		else{
			$_function_parameters = array (
				'parent_node_id' => $this->parent_node_id,
				'class_filter_type' => array ('include'),
				'class_filter_array' => array ($this->class_identifier),
				'as_object' => true,
				'sort_by' => array ('published', true),
				'offset' => $this->offset,
				'limit' => $this->limit,
				'depth' => $this->depth,
				'ignore_visibility' => false,
				'load_data_map' => true
			);

			$this->nodes_tree_list = eZFunctionHandler::execute (
				'content', 'list',
				$_function_parameters
			);

			$this->nodes_tree_list_count_step = count ($this->nodes_tree_list);
		}

	}
	protected function set_all_nodes_tree_count (){
		$_function_parameters = array (
			'parent_node_id' => $this->parent_node_id,
			//'sort_by' => array ('path', false()),
			//'sort_by' => array ('published', true),
			'class_filter_type' => array ('include'),
			'class_filter_array' => array ($this->class_identifier),
			'as_object' => true,
			'depth' => $this->depth,
			'ignore_visibility' => false,
			'load_data_map' => false
		);

		$this->nodes_tree_list_count = count (
			eZFunctionHandler::execute (
				'content', 'list',
				$_function_parameters
			)
		);
	}

	protected function fetch_last_node_to_change (){
		$_function_parameters = array (
			'parent_node_id' => $this->parent_node_id,
			'class_filter_type' => array ('include'),
			'class_filter_array' => array ($this->class_identifier),
			'as_object' => true,
			'sort_by' => array ('published', false),
			//'offset' => $this->offset,
			'limit' => 1,
			'depth' => $this->depth,
			'ignore_visibility' => false,
			'load_data_map' => false
		);
		$this->last_node_to_change = eZFunctionHandler::execute (
			'content', 'list',
			$_function_parameters
		);
	}


	protected function add_error ($_message){
		$this->errors_list[] = $_message;
		$this->has_error = true;
	}
	public function get_error (){
		return $this->errors_list[0];
	}
}
