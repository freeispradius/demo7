<option value=''>{$_L['Select_Routers']}</option>
{if $_c['radius_enable']}
    <option value="radius">Radius</option>
{/if}
{foreach $d as $ds}
	<option value="{$ds['name']}">{$ds['name']}</option>
{/foreach}