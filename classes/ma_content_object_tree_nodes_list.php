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
	protected $attribute_post_key;

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

	protected $http;
	protected $attribute_base;

	protected $log;
	protected $error;

	protected $db;


	public function __construct ($_parent_node = null, $_section = null, $_class = null, $_languages = null, $_depth = null){
		$this->has_error = false;
		$this->cron_flag = false;
		$this->error = MA_Error::get_instance ();
		$this->log = MA_Log::get_instance();

		$this->set_parent_node ($_parent_node);
		$this->set_section ($_section);
		$this->set_class ($_class);
		$this->set_languages ($_languages);
		$this->set_depth ($_depth);
		$this->pre_set_result ();

		//$this->
		//$this->set_offset ($_offset);
		//$this->set_limit ($_limit);

		if ($this->error->has_error()){
			return false;
		}
	}

	protected function set_parent_node ($_parent_node = null){
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
	protected function set_section ($_section = null){
		if (!$_section){
			$_section = 'standard';
			//$this->error->set_error('$_section missing. ', __METHOD__, __LINE__, MA_Error::Error);
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
	protected function set_class ($_class = null){
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
		if (!reset ($_languages) and empty ($_languages)){
			$this->error->set_error('Languages missing.', __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}

		$this->languages_codes = $_languages;

		return true;
	}
	protected function set_depth ($_depth = null){
		$_depth = (($_depth > 0)? $_depth: 2 );
		if (!is_numeric ($_depth)){
			$this->error->set_error('Depth is not a number.', __METHOD__, __LINE__, MA_Error::ERROR);

			return false;
		}

		$this->depth = $_depth;

		return true;
	}
	protected function pre_set_result (){
		$this->result = array ();
		$this->result['objects']['langs']['counter'] = 0;
		$this->result['nodes']['fetched'] = 0;
	}


	public function set_to_change_nodes_tree_attribute_content ($attribute_identifier, $attribute_content,
		$cron_flag = false, $offset = null, $limit = null
	){
		$this->set_attribute_identifier ($attribute_identifier);
		//$this->set_attribute_id ($attribute_id);
		$this->set_attribute_content ($attribute_content);
		$this->set_cron ($cron_flag, $offset, $limit);

		$this->attribute_base = 'ContentObjectAttribute';
		$this->http = eZHTTPTool::instance();
		$this->attribute_post_key = $attribute_post_key;
		$this->http->setPostVariable($this->attribute_post_key, $this->attribute_content);


		/**
		 * it dosen't show how many nodes were changed, it show only how many nodes are of the class in the tree (fetched nodes in all languages).
		 */
		//$this->result['count'] = $this->nodes_tree_list_count;
		if ($this->error->has_error()){
			return false;
		}
		return true;
	}
	protected function set_attribute_identifier ($_attribute_identifier){
		if (!$_attribute_identifier){
			//$this->add_error ('$_attribute_identifier missing. '. __METHOD__. ' '. __LINE__);
			$this->error->set_error('Attribute identifier missing.', __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}
		$this->attribute_identifier = $_attribute_identifier;
		return true;
	}
	protected function set_offset ($_offset = null){
		if (!$_offset or ($_offset < 1)){
			$_offset = 2;
		}
		//		$_offset = (($_offset > 0)? $_offset: 0);
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
	protected function set_limit ($_limit = null){
		if (!$_limit or ($_limit < 1)){
			$_limit = 10;
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
	protected function set_attribute_content ($_attribute_content = false){
		if (!$_attribute_content){
			//$this->add_error ('$_attribute_content missing. '. __METHOD__. ' '. __LINE__);
			$this->error->set_error('Attribute content missing.', __METHOD__, __LINE__, MA_Error::ERROR);
			return false;
		}
		$this->attribute_content = $_attribute_content;
		return true;
	}
	protected function set_cron ($_cron_flag = false, $_offset = null, $_limit = null){
		if ($_cron_flag){
			$this->cron_flag = true;

			$this->set_offset ($_offset);
			$this->set_limit ($_limit);
		}
	}

	public function change_nodes_tree_attribute_content (){
		$this->fetch_nodes_tree_list ();

		if (!$this->change_nodes_tree_attribute_content_ ()){
			return false;
		}
		$this->set_to_next_use ();
		$this->set_change_result ();
		return true;
	}
	protected function set_change_result (){
		//$this->result['counter'] = count ($this->nodes_tree_list_changed);
		$this->result['limit'] = $this->limit;
		$this->result['offset'] = $this->offset;
		$this->result['parent_node_id'] = $this->parent_node_id;
		//$this->result['nodes']['counter_fetched'];
		$this->result['nodes']['counter'] = count ($this->nodes_tree_list_changed['nodes_ids']);
		//$this->result['nodes']['nodes'] = $this->nodes_tree_list_changed;
		//$this->result['objects']['langs']['changed']['counter'] = 0;

		if ($this->last_node_to_change->attribute ('node_id') == end ($this->nodes_tree_list_changed['nodes_ids'])){
			$this->result['end_flag'] = true;
		}
		else{
			$this->result['end_flag'] = false;
		}
	}
	public function get_change_result (){
		return $this->result;
	}
	protected function change_nodes_tree_attribute_content_ ($_transaction_flag = false){
		/*
		$knodek = eZContentObjectTreeNode::fetch(11);
		$knodek->store()
		$kobject = $knodek->object();
		$kobject->store();
		$kobject->expireAllViewCache();

		$kdata_map = $kobject->fetchDataMap();
		$kdata_map->
	*/
		//eZContentObjectAttribute::create()

		$key_4 = 0;

		if ($_transaction_flag){
			$this->db = eZDB::instance();
			$this->db->begin();
		}
		foreach ($this->nodes_tree_list as $_key => $node){
		//	echo 'Node: <br />';
		//	var_dump($node);
		//	echo '<br />';

			$object = $node->object();
			$object_current = $object->currentVersion();
			$avalaible_languages = $object->availableLanguages ();
			foreach ($this->languages_codes as $code){
				if (in_array ($code, $avalaible_languages)){
					$datamap = $object->fetchDataMap (false, $code);

					$content_attribute = $datamap[$this->attribute_identifier];
					//var_dump($datamap);
					//var_dump($content_attribute);

					if (!$content_attribute){
						$this->error->set_error('No attribute, huge error.', __METHOD__, __LINE__, MA_Error::ERROR);
						//$this->log->write($this->error->get_error());
						return false;
					}

					//$this->result['objects']['langs']['list'][$object->attribute ('id')]['atttribute']['content']['previous']
					//	= $datamap[$this->attribute_identifier]->content();
					$this->log->write (
						"\n".
						'Node id: '. $node->attribute ('node_id'). " ". 'Object id: '. $node->attribute ('contentobject_id'). " ". 'Language: '. $code. "\n".
						'Content previous: '. $content_attribute->content(). "\n"
					);

					//echo get_class($content_attribute);
					//die();
					//$content_attribute->fetchInput ($this->http, $this->attribute_base);
					$content_attribute->setContent ($this->attribute_content);
					$content_attribute->fromString ($this->attribute_content);
					//$content_attribute->setHTTPValue ($this->attribute_content);
					//$content_attribute->setContent ($this->attribute_content);
					/*
					if ($content_attribute->isSimpleStringInsertionSupported()){
						echo 'abc <br />';
						$arr__ = array('xx');

						//$content_attribute->insertSimpleString ($object, eZContentObjectVersion::STATUS_PUBLISHED, $code,
						//	$content_attribute, $this->attribute_content, $arr__);
					}
					//else{
						//to do
					//}
					//$content_attribute->setContent ($this->attribute_content);
					*/

					$content_attribute->store ();

					if (!(in_array ($node->attribute ('node_id'), $this->nodes_tree_list_changed['nodes_ids']))){
						$this->nodes_tree_list_changed['nodes_ids'][$key_4] = $node->attribute ('node_id');
						$key_4++;
					}
					$this->result['objects']['langs']['counter']++;
				}
			}
			//$node->store();
			$this->result['nodes']['counter_fetched'] = $_key;

			$object->expireAllViewCache ();
			//eZContentCacheManager::clearContentCache();
		}
		if ($_transaction_flag){
			$this->db->commit();
		}
		return true;
	}

	protected function set_to_next_use (){
		if ($this->cron_flag and $this->nodes_tree_list_count_step >= $this->limit){
			$this->set_offset ($this->offset + $this->limit);
		}
	}
	protected function fetch_nodes_tree_list (){
		$this->set_all_nodes_tree_count();
		$this->fetch_last_node_to_change ();
		if (!$this->cron_flag){
			$_function_parameters = array (
				'parent_node_id' => $this->parent_node_id,
				//'sort_by' => array ('path', false()),
				'sort_by' => array ('published', true),
				'class_filter_type' => 'include',
				'class_filter_array' => array ($this->class_identifier),
				'as_object' => true,
				//'ignore_visibility' => false,
				'depth' => $this->depth,
				'load_data_map' => true
			);
			$this->nodes_tree_list = eZFunctionHandler::execute(
				'content', 'list',
				$_function_parameters
			);
		}
		else{
			$_function_parameters = array (
				'parent_node_id' => $this->parent_node_id,
				'class_filter_type' => 'include',
				'class_filter_array' => array ($this->class_identifier),
				'as_object' => true,
				'sort_by' => array ('published', true),
				'offset' => $this->offset,
				'limit' => $this->limit,
				'depth' => $this->depth,
				//'ignore_visibility' => false,
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
		$this->nodes_tree_list_changed = array ();

		if (!$this->cron_flag){
			$this->nodes_tree_list_count = count ($this->nodes_tree_list);
		}
		else{
			$_function_parameters = array (
				'parent_node_id' => $this->parent_node_id,
				//'sort_by' => array ('path', false()),
				//'sort_by' => array ('published', true),
				'class_filter_type' => 'include',
				'class_filter_array' => array ($this->class_identifier),
				'as_object' => true,
				'depth' => $this->depth,
			//	'ignore_visibility' => false,
				'load_data_map' => false
			);

			$this->nodes_tree_list_count = count (
				eZFunctionHandler::execute (
					'content', 'list',
					$_function_parameters
				)
			);
		}

	}
	protected function fetch_last_node_to_change (){
		$_function_parameters = array (
			'parent_node_id' => $this->parent_node_id,
			'class_filter_type' => 'include',
			'class_filter_array' => array ($this->class_identifier),
			'as_object' => true,
			'sort_by' => array ('node_id', false),
			//'offset' => $this->offset,
			'limit' => 1,
			'depth' => $this->depth,
			//'ignore_visibility' => false,
			'load_data_map' => false
		);
		$result = eZFunctionHandler::execute (
			'content', 'list',
			$_function_parameters
		);
		$this->last_node_to_change = reset ($result);

	}

	protected function change_nodes_tree_content_now (){

	}
	protected function get_changed_nodes_list (){
		return $this->nodes_tree_list_changed;
	}

	/**
	 * Use MA_Error object
	 * @deprecated
	 * @param $_message
	 */
	protected function add_error ($_message){
	//	$this->errors_list[] = $_message;
	//	$this->has_error = true;
	}
	public function get_error (){
	//	return $this->errors_list[0];
	}
}
