{$emailBodyStart}

{foreach from=$widgets key=key item=widget}

{if $widget.type == 'section.break'}===== {$widget.name} =====
{else}
{$widget.name|trim,':'}:
	
{foreach from=$widget.values item=value}
{$value.value}

{/foreach}

{/if}
{/foreach}

{$emailViewLink}


{$emailEditLink}