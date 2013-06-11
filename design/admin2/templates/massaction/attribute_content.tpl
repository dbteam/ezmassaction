{*ezcss_require(
	array(
		'mass_action.css'
	)
)*}
{* require
	mass_action.css
	mass_action.js
	load files should be set in design.ini.append.php into ext or siteaccess or loaded using ezcss_require(), ezscript_require() in template
*}
<div class="mass-action">

	{*
	<div>
		{$params.Module|attribute(show)}
	</div>
	*}

	<div class="content">
		{if is_set($persistent_variable.errors.0)}
		<div class="message-error">
			{foreach $persistent_variable.errors as $text}
				<p>{$text|i18n("extension/massaction/content")|strip_tags}</p>
			{/foreach}

		</div>
		{/if}

		{if is_set($persistent_variable.warnings.0)}

		{/if}

		<form id="attribute-content" action="{$persistent_variable.parameters.form.action.url_alias|ezurl('no')}" method="post" name="mass_action_attribute_content">
			<fieldset>
				<legend>Change attribute value</legend>
				<ul class="parameters">
					<li class="parameter">{*if is_unset($persistent_variable.parameters.parents_nodes_ids.0)} turn-off{/if*}
						<ul class="nodes">
							<li class="label">
								<label>Parents nodes of sub trees*: </label>
							</li>

							<li class="nodes{if is_unset($persistent_variable.parameters.parents_nodes_ids.0)} turn-off{/if}">
								<ul class="labels">
									{if is_set($persistent_variable.parameters.parents_nodes_ids.0)}
										{def $node_=hash()}

										{foreach $persistent_variable.parameters.parents_nodes_ids as $node_id}
											{set $node_=fetch('content', 'node', hash('node_id', $node_id) )}

									<li class="Node-{$node_.node_id}">
										{$node_.name}
									</li>
										{/foreach}

										{undef $node_}
									{/if}
								</ul>
							</li>

							<li class="nodes{if is_unset($persistent_variable.parameters.parents_nodes_ids.0)} turn-off{/if}">
								<ul class="values">
									{if is_set($persistent_variable.parameters.parents_nodes_ids.0)}
										{foreach $persistent_variable.parameters.parents_nodes_ids as $node_id}
									<li>
										<input name="Nodes_IDs[]" id="Node-{$node_id}" type="checkbox" min="1" value="{$node_id}" checked="checked" onclick="mass_action_delete_unchecked_nodes(this); return false;">
									</li>
										{/foreach}
									{/if}
								</ul>
							</li>

						</ul>
					</li>

					<li class="parameter">
						<ul class="section">
							<li class="label">
								<label for="sections">Sections: </label>
							</li>

							{def $sections=fetch('content', 'section_list')}
							<li class="values">
								<select name="Section" id="sections" size="1">
									<option value="">Not selected</option>
									{foreach $sections as $section}
									<option value="{$section.id}"{if is_set($persistent_variable.parameters.section_id)}{if $section.id|eq($persistent_variable.parameters.section_id)}selected="selected"{/if}{/if}>{$section.name}</option>
									{/foreach}
								</select>
							</li>
							{undef $sections}
						</ul>
					</li>

					<li class="parameter">
						<ul class="class">
							<li class="label">
								<label for="classes">Classes*: </label>
							</li>

							{def
								$classes=fetch('class', 'list', hash() )
							}
							<li class="values">
								<select name="Class" id="classes" size="1">
									<option value="">Not selected</option>

									{foreach $classes as $class_}
									<option value="{$class_.id}"{if is_set($persistent_variable.parameters.class_id)}{if $class_.id|eq($persistent_variable.parameters.class_id)}selected="selected"{/if}{/if}>
										{$class_.name}
									</option>

									{/foreach}
								</select>
							</li>
							{undef $classes}
						</ul>
					</li>

					<li class="parameter">
						<ul class="locale">
							<li class="label">
								<label for="locales_list">Objects locales*: </label>
							</li>

							{def $locales=fetch('content', 'translation_list')}
							<li class="values">
								<select name="Locales[]" id="locales_list" size="{count($locales)}" multiple="multiple">
									{foreach $locales as $locale}
									<option value="{$locale.locale_full_code}"{if $persistent_variable.parameters.locales_codes|contains($locale.locale_full_code)} selected="selected"{/if}>
										{$locale.locale_full_code}
									</option>
									{/foreach}
								</select>
							</li>
							{undef $locales}
						</ul>
					</li>

					<li class="parameter{if or(is_unset($persistent_variable.parameters.class_id), $persistent_variable.parameters.step|lt($persistent_variable.parameters.first_step_id|sum(1))} turn-off{/if}">
						<ul class="attributes">
							{if and(is_set($persistent_variable.parameters.class_id), $persistent_variable.parameters.step|gt($persistent_variable.parameters.first_step_id) )}
							<li class="label">
								<label for="attributes_list">Attributes*: </label>
							</li>

							{def
								$attributes=fetch('content', 'class_attribute_list', hash('class_id', $persistent_variable.parameters.class_id) )
							}
							<li class="values">
								<select name="AttributeID" id="attributes_list" size="1">
								{if count($attributes)|gt(0)}
									<option value="">Not selected</option>
									{foreach $attributes as $attribute}
									<option value="{$attribute.id}"{if $persistent_variable.parameters.attribute_id|eq($attribute.id)} selected="selected"{/if}>
										{$attribute.name} [{$attribute.data_type_string}]
									</option>

									{/foreach}
								{/if}

								</select>
							</li>

								{undef $attributes}
						{/if}
						</ul>
					</li>

					<li class="attribute{if or(is_unset($persistent_variable.parameters.attribute_id), $persistent_variable.parameters.step|lt($persistent_variable.parameters.first_step_id|sum(2)) )} turn-off{/if}">

					{if and($persistent_variable.parameters.attribute_id|gt(0), $persistent_variable.parameters.step|gt($persistent_variable.parameters.first_step_id|sum(1)) )}
						{def
							$node__ = fetch(
								'content', 'list',
								hash(
									'parent_node_id', 2,
									'limit', 1,
									'depth', 11,
									'class_filter_type', 'include',
									'class_filter_array', array($persistent_variable.parameters.class_identifier)
								)
							).0
						}
						{if is_set($node__.name)}
						<div class="object-content-attribute">
							{attribute_edit_gui attribute=$node__.data_map[$persistent_variable.parameters.attribute_identifier]}
						</div>
						{else}
							<div class="messages">
								<p>
									Object selected class not founded.
								</p>
							</div>
						{/if}
					{/if}
					</li>
				</ul>

				<div class="buttons">
					<input type="submit" name="RestartButton" value="Clear form" class="button" />
					{*<input type="submit" name="SetSubtrees" value="Set sub trees" class="button" />
					*}
					{if $persistent_variable.parameters.step|le($persistent_variable.parameters.first_step_id)}
					<input type="submit" name="GetAttributesList" value="Get attributes list" class="button" />
					{else}

					<input type="submit" name="PreviousButton" value="Back" class="button" />
						{if $persistent_variable.parameters.step|eq($persistent_variable.parameters.first_step_id|sum(1))}
					<input type="submit" name="GetAttribute" value="Get attribute" class="button" />
						{elseif $persistent_variable.parameters.step|eq($persistent_variable.parameters.first_step_id|sum(2) ) }
					<input type="submit" name="ChangeAttributeContent" value="Change attribute content" class="button" />
						{/if}
					{/if}

				</div>

			</fieldset>
		</form>
	</div>

	<div>
		{*$params|attribute( show, 4 )*}
	</div>
</div>
{undef $sections $classes $locales}
