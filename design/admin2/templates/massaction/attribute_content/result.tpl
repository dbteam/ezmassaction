<form action="{$persistent_variable.parameters.form.action.url_alias|ezurl('no')}" method="post">
	{if is_set($persistent_variable.errors.0)}
		<div class="message-error">
			{foreach $persistent_variable.errors as $text}
				<p>{$text|i18n("extension/massaction/content")|strip_tags}</p>
			{/foreach}

		</div>
	{/if}

	{if is_set($persistent_variable.warnings.0)}
	{/if}

	<div class="context-block">
		{* DESIGN: Header START *}
		<div class="box-header">
			<div class="box-tc">
				<div class="box-ml">
					<div class="box-mr">
						<div class="box-tl">
							<div class="box-tr">
								<h1 class="context-title">{'Wizard finished'|i18n( 'extension/dbattributeconverter')|wash}</h1>

								{* DESIGN: Mainline *}
								<div class="header-mainline">

								</div>
								{* DESIGN: Header END *}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>


		<div class="box-ml">
			<div class="box-mr">
				<div class="box-content">
					{* DESIGN: Content START *}
					<div class="block">
						{if $persistent_variable.parameters.cli_flag}
							{if $persistent_variable.parameters.scheduled_script_id|gt(0)}
								<p>
									{'The process has been scheduled to run in the background, and will be started automatically. Please do not edit the class again until the process has finished. You can monitor the progress of the background process here:'|i18n( 'design/admin/class/view' )} <br />
									<b>
										<a href={concat('scriptmonitor/view/',$persistent_variable.parameters.scheduled_script_id)|ezurl}>
											{'Background process monitor'|i18n( 'design/admin/class/view' )}
										</a>
									</b>
								</p>
							{else}
								<h2>Run the following script into Command Line to change attribute content.</h2>
								<label>php extension/ezmassaction/bin/cli/content_changer.php -s <admin_siteaccess> --filename-part={$persistent_variable.parameters.file_name}</label>
								<p>php extension/ezmassaction/bin/cli/content_changer.php -s site_admin --filename-part={$persistent_variable.parameters.file_name}</p>
							{/if}
						{else}
							<h2>Finished!</h2>
							<p>Total contents of eZContentAttribute changed: {*$total_attribute_count*}</p>
						{/if}
					</div>
					{* DESIGN: Content END *}
				</div>
			</div>
		</div>


		<div class="controlbar">
			{* DESIGN: Control bar START *}
			<div class="box-bc">
				<div class="box-ml">
					<div class="box-mr">
						<div class="box-tc">
							<div class="box-bl">
								<div class="box-br">
									<div class="block">
										<input type="submit" name="PreviousButton" value="Back" class="button" />
										<input class="button" type="submit" name="RestartButton" value="{'Restart'|i18n( 'dbattributeconverter/wizard' )}" title="{'Restart wizard.'|i18n( 'extension/ezattributeconverter' )}" />
									</div>

								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			{* DESIGN: Control bar END *}
		</div>

	</div>

</form>
