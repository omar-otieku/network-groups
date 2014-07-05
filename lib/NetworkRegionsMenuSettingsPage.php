<?php

class NetworkRegionsMenuSettingsPage extends NetworkRegions
{
	
	public function __construct()
	{
		add_action('network_admin_menu', array(&$this, 'addNetworkRegionSettingsPage'));
	}
	
	public function addNetworkRegionSettingsPage()
	{
		add_submenu_page('settings.php', 'Network Regions', 'Network Regions', 'Super Admin', 'network_regions_settings', array(&$this, 'buildSettingsPage'));
	}
	
	public function buildSettingsPage()
	{
		$regions = $this->getNetworkRegions();
		
		if(!$regions):
		?>
		
		<?php else:  ?>
		
		<?php
		endif;
	}

}
