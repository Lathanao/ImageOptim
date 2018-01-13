

  <a href="{$href|escape:'html':'UTF-8'}" class="edit btn btn-default {if $disable}disabled{/if}" title="{$action}" >
  	<i class="icon-exchange"></i> {$action}
  </a>
	<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
		<i class="icon-caret-down"></i>&nbsp;
	</button>
	<ul class="dropdown-menu">
	<li>
		<a href="{$link}" target="_blank" title="View image">
			<i class="icon-eye-open"></i> View image
		</a>
	</li>
	</ul>
