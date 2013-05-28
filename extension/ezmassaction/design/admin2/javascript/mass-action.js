/**
 * Created with JetBrains PhpStorm.
 * User: Radosław Zadroga
 * Date: 23.05.13
 * Time: 09:33
 * To change this template use File | Settings | File Templates.
 */


(function(){
	var node = {};

	function create_label_list_element(){
		return'<li class="Node-'+node['node_id']+'">'+node['name']+'</li>';
	}
	function create_value_list_element(){
		return'<li><input name="Nodes_IDs[]" id="Node-'+node['node_id']+'" type="checkbox" min="2" value="'+node['node_id']+'" checked="checked" onclick="mass_action_delete_unchecked_nodes(this); return false;"></li>';
	}

	function is_node_on_the_list(parent_tree){
		if(jQuery(parent_tree+' li input[value="'+node['node_id']+'"]').length){
			return true;
		}
		return false;
	}

	function add_to_form(parent_tag){
		if(is_node_on_the_list(parent_tag + ' ul.values') != true){
			var label = create_label_list_element();
			jQuery(parent_tag + ' ul.labels').append(label);

			var content = create_value_list_element();
			jQuery(parent_tag + ' ul.values').append(content);
		}
	}
	function show_list(tag){
		if(jQuery(tag).length > 0){
			jQuery(tag).parent().parent().removeClass('turn-off');
		}
		else{
			jQuery(tag).parent().parent().addClass('turn-off');
		}
	}
	function get_node_name(){
		var selector = '#content_tree_menu li#n'+node['node_id']+' > a.image-text span';
		node['name'] = jQuery(selector).text();
	}

	//interface
	function _add_node_id_to_form(){
		// CurrentSubstituteValues - file admin2 ezpopupmenu.js
		node['node_id'] = CurrentSubstituteValues['%nodeID%']
		get_node_name();

		add_to_form('#attribute-content ul.nodes');

		show_list('#attribute-content ul.nodes ul.labels li');
		show_list('#attribute-content ul.nodes ul.values li');

	}
	function _delete_unchecked_nodes(elem){
		var element = jQuery(elem);
		console.log('id jQ: '+element.attr('id'));

		if(element.attr('checked') != true){
			jQuery('#attribute-content ul.nodes ul.labels li.'+element.attr('id')).remove();
			element.remove();

			show_list('#attribute-content ul.nodes ul.labels li');
			show_list('#attribute-content ul.nodes ul.values li');
		}
	}

	window.ezpopmenu_addNodeToForm = _add_node_id_to_form;
	window.mass_action_delete_unchecked_nodes=_delete_unchecked_nodes;
/*
	jQuery('form ul.nodes ul.value input[type=checkbox]').on('click', function(event){
		console.log('function start');
		var element = jQuery(this);
		if(element.attr('checked') != true){
			console.log('not checked');
			jQuery('#attribute-content ul.nodes ul.labels li.Node_'+element.attr('value')).remove();
			element.remove();
		}
		else{
			console.log('yes checked');
		}
	});
*/


})();



