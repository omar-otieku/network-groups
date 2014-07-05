<?php
/**
 * Plugin Name: Network Groups
 * Description: A multisite plugin that allows users to place the blogs in their networks in arbitrary groups.
 * Version: 1.0.0
 * Author: Omar Otieku
 * License: A "Slug" license name e.g. GPL2
 */
 
defined('ABSPATH') or die("No script kiddies please!");

class network_groups {
    
    protected $blogID;
	
	protected $groupsTable;
	
	protected $blogOption = 'blog_group';
	
	protected $networkMenuOption = 'network_group_menu';
    
    public function __construct()
    {

		// This check prevents using this plugin not in a multisite
		if ( function_exists( 'is_multisite' ) && ! is_multisite() && is_super_admin() ) {
			add_filter( 'admin_notices',  array( &$this, 'not_multisite_message' ) );
			return;
		}
		
		global $wpdb;
		$this->groupsTable = $wpdb->base_prefix.'network_groups';
		
		
		// make sure necessary tables are installed
		add_filter('network_admin_menu', array(&$this, 'install_table'));
		
		
		//set the blog ID for the current site
		$this->blogID = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
   
        
        add_blog_option($this->blogID, $this->blogOption, '');
		
		add_blog_option($this->blogID, $this->networkMenuOption, '');
        
        add_action('network_admin_menu', array(&$this, 'add_groups_settings_page'));
		
        
        add_action('admin_post_update_network_groups',  array(&$this, 'update_network_groups'));
		
		
        add_filter('admin_footer', array(&$this, 'add_network_groups_option'), -100);
		
		
		add_filter('check_admin_referer', array(&$this, 'update_blog_group'), -100);
        
        
        add_action('network_groups_updated', array(&$this, 'pipe_update_notice'));
		
    }
	
    
	//add the submenu page to add and edit regions
    public function add_groups_settings_page()
    {
        add_submenu_page('sites.php', 'Network Groups', 'Network Groups', 'Super Admin', 'edit_groups', array(&$this, 'groups_form'));
    }
	
    
	//defines markup for Network Groups form
    public function groups_form()
    {
        $groups = $this->get_network_groups();
        
        if(isset($_GET['updated'])) {
            do_action('network_groups_updated');
        }
		
     	wp_enqueue_style('network_regions', plugins_url( 'css/network_regions.css', __FILE__ ));
     ?>
     
     <form action="<?=admin_url('admin-post.php?action=update_network_groups'); ?>" class="network-regions-form" method="post">
     	<h2>Network Groups</h2>
     	
     	<a href="#" id="add-region-button" class="button button-large">Add Group</a>
     	
         <div id="fieldlist">
	        <?php if(empty($groups)): ?>
	        <div class="field-box">
		     	<label for="option[blog_group][0_new]">Group</label>
		        <input class="group-field" type="text" name="option[blog_group][0_new]">
	        </div>
	        <?php else: foreach($groups as $index => $group): ?>
	        <div class="field-box">
		     	<label for="option[blog_group][<?=$group->id?>]">Group</label>
	        	<input class="group-field" type="text" name="option[blog_group][<?=$group->id?>]" value="<?=$group->title ?>">
	        </div>
	        <?php endforeach; endif; ?>
        </div>
        
        <input type="submit" name="save" value="Submit" class="button button-primary button-large">
     </form> 
     
     <?php
     
     	wp_enqueue_script('network_regions', plugins_url( 'js/network_regions.js', __FILE__ ), array('jquery'), false, true);
    }


	//update site option for network regions on form submit
    public function update_network_groups()
    {
        if(!current_user_can('manage_network_options')) wp_die('FU');
        
        $groups = $_POST['option']['blog_group'];
		
		global $wpdb;
		
		$inserts = array();
		$params = array();
		$updates = array();
		
		foreach($groups as $id => $group) {
			if(strstr($id, '_new')) {
				array_push($inserts, $group, sanitize_title_with_dashes($group));
				$params[] = '(NULL, %s, %s)';
			} else {
				$wpdb->update($this->groupsTable, array('title' => $group), array('id' => $id),array('%s'));
			}
		}
		
		$params = implode(', ', $params);
			
        $wpdb->query($wpdb->prepare("INSERT INTO {$this->groupsTable} (id, title, slug) VALUES {$params}", $inserts));
        
        if(isset($_POST['groups_for_removal']))
        {
            $itemsForDeletion = implode(', ', $_POST['groups_for_removal']);
            
            //delete what must be deleted
            $wpdb->query($wpdb->prepare("DELETE FROM {$this->groupsTable} WHERE {$this->groupsTable}.title IN (%s)", $itemsForDeletion));   
        }
        
        
        wp_redirect(admin_url('network/sites.php?page=edit_groups&updated=1'));
        exit;
    }

    public function pipe_update_notice()
    {
       ?>
       <div id="message" class="updated below-h2">
           <p>Network Groups have been updated.</p>
       </div>
       <?php
    }
	
	public function add_network_groups_option()
    {
		global $pagenow;
       	
		if($pagenow == 'site-info.php'):
	        $groups = $this->get_network_groups();
			$blog_group = get_blog_option($this->blogID, $this->blogOption, '');
    ?>
        
                <select name="option[blog_group]" style="display:none;">
                	<option value=''>Select Group</option>
                    <?php foreach($groups as $group): ?>
                    <option value="<?=$group->id ?>" <?=($group->id == $blog_group) ? 'selected="selected"' : '' ?>><?=$group->title ?></option>
                    <?php endforeach; ?>
                </select>
       
    <?php
    	wp_enqueue_script('blog_region_option', plugins_url( 'js/blog_region_option.js', __FILE__ ), array('jquery'), false, true);
		endif;
    }
	
	
	public function update_blog_group()
	{
		if ( isset($_REQUEST['action']) && 'update-site' == $_REQUEST['action'] ) {
		    $region = isset($_POST['option']['blog_group']) ? $_POST['option']['blog_group'] : '';
             
            update_blog_option($this->blogID, $this->blogOption, $region);
		}
	}
	
	
	public function get_network_groups()
	{
		global $wpdb;
		
		return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->groupsTable}", array()));
	}
	
	
	public function get_group($id)
	{
		global $wpdb;
		$id = (int) $id;
		
		return $wpdb->get_var($wpdb->prepare('SELECT `title` FROM ' . $this->groupsTable . ' WHERE `id` = %d', $id) );
	}
	
	
	public function install_table()
	{
		global $wpdb;
		
		$sql = 'CREATE TABLE IF NOT EXISTS ' . $this->groupsTable . ' (
			`id` INT NOT NULL AUTO_INCREMENT ,
			`title` varchar(255) CHARACTER SET utf8 NOT NULL,
			`slug` varchar(255) CHARACTER SET utf8 NOT NULL,
			PRIMARY KEY ( `id` ) , INDEX ( `slug` )
		)';
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	
	
	public function not_multisite_message()
	{
	?>
		<div class="error">
			<p>
				<?php _e( 'Network Groups only works in a multisite installation. See how to install a multisite network:'); ?>
				<a href="http://codex.wordpress.org/Create_A_Network" title="<?php _e( 'WordPress Codex: Create a network'); ?>"><?php _e( 'WordPress Codex: Create a network'); ?></a>
			</p>
		</div>
	<?php
		
	}
	
	public function get_blog_menu_options($id)
	{
		return get_blog_option($id, $this->networkMenuOption);
	}
}

if (class_exists("network_groups")) {
    $NetworkGroups = new network_groups();
	
	function get_network_menu($status = 1)
	{
		global $NetworkGroups;
		$groups = $NetworkGroups->get_network_groups();
        
        //Add Miscellaneous Group
        $groups[] = 'Miscellaneous';
		
		//get list of network sites where status is public								
		$sites = $sites = wp_get_sites(array('public' => $status));
		
		$networkMenu = array();
		
		if(!$groups) {
			
			foreach($sites as $site) {
				$blogID = $site['blog_id'];
				$menuOptions = $NetworkRegions->getBlogMenuOptions($blogID);
				
				$title = get_blog_option($blogID, 'blogname');
				$link = get_blogaddress_by_id($blogID);
				
				$networkMenu['sites'][] = array('title' => $title, 'url' => $link);
			}
		} 
		else {
			$submenus = array();
			
			global $wpdb;
			
			foreach($sites as $site) {
					$blogID = $site['blog_id'];
					$blogGroup = $NetworkGroups->getRegion(get_blog_option($blogID, 'blog_group'));
					
					$title = get_blog_option($blogID, 'blogname');
					$link = get_blogaddress_by_id($blogID);
					
					if($blogGroup){
						$submenus[$blogGroup][] = array('title' => $title, 'url' => $link);
					} 
					else 
					{
					    $submenus['Miscellaneous'][] = array('title' => $title, 'url' => $link);
					}
			}
			
			foreach($groups as $group) {
				if(isset($submenus[$group->title]))
					$networkMenu['groups'][$group->title] = $submenus[$group->title];
			}
		}
		
		return $networkMenu;
	}
}

