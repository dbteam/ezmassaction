
{*if $navigation_part.identifier|compare(ezini('Topmenu_massaction', 'NavigationPartIdentifier', 'menu.ini'))*}
	<script type="text/javascript">
		menuArray['MainMenu'] = {ldelim}{rdelim};
		menuArray['MainMenu']['depth'] = 0;
		menuArray['MainMenu']['elements'] = {ldelim}{rdelim};
		menuArray['MainMenu']['elements']['menu-ezmassaction'] = new Array();
		menuArray['MainMenu']['elements']['menu-ezmassaction']['variable'] = new Array();
		menuArray['MainMenu']['elements']['menu-ezmassaction']['url'] = '%nodeID%';
		//menuArray['ContextMenu']['elements']['menu-ezmassaction']['variable']['node_name'] = '%nodeName%';
		menuArray['MainMenu']['elements']['menu-ezmassaction']['variable']['node_id'] = '%nodeID%';
		//menuArray['ContextMenu']['elements']['menu-ezmassaction'] = {ldelim}  {rdelim};{*"/massaction/add/%nodeID%"|ezurl*}
	</script>

	<hr />

	<a id="menu-ezmassaction" onclick="ezpopmenu_addNodeToForm(); return false;" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' )">
		{'Mass Action'|i18n( 'extension/ezmassaction/content/leftmenu/popup' )}
	</a>

{* /if*}
