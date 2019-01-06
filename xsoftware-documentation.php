<?php
/*
Plugin Name: XSoftware Documentation
Description: Documentation management on WordPress.
Version: 1.0
Author: Luca Gasperini
Author URI: https://xsoftware.it/
*/

if(!defined("ABSPATH")) exit;

if (!class_exists("xs_documentation_plugin")) :

class xs_documentation_plugin
{
        
        private $default = array();

        public function __construct()
        {
                add_action("admin_menu", array($this, "admin_menu"));
                add_action("admin_init", array($this, "section_menu"));
        }
        
        function admin_menu()
        {
                global $menu;
                $menuExist = false;
                foreach($menu as $item) {
                        if(strtolower($item[0]) == strtolower("XSoftware")) {
                                $menuExist = true;
                        }
                }
                
                if(!$menuExist)
                        add_menu_page( "XSoftware", "XSoftware", "manage_options", "xsoftware", array($this, "menu_page") );
                        
                add_submenu_page( "xsoftware", "XSoftware Documentation", "Documentation", "manage_options", "xsoftware_documentation", array($this, "menu_page") );
        }
        
        function menu_page()
        {
                if ( !current_user_can( "manage_options" ) )  {
                        wp_die( __( "Exit!" ) );
                }
                
                wp_enqueue_style("admin_style", plugins_url("style/admin.css", __FILE__));
                
                echo "<div class=\"wrap\">";
                echo "<h2>Documentation configuration</h2>";
                
                echo "<form action=\"options.php\" method=\"post\">";
                
                settings_fields("xsoftware_documentation");
                do_settings_sections("xsoftware_documentation");
                
                submit_button( "", "primary", "globals", true, NULL );
                
                echo "</form>";
                
                echo "</div>";
        }
        
        function section_menu()
        {
                register_setting( "xsoftware_documentation", "socials_accounts", array($this, "input") );
                add_settings_section( "documentation_settings", "Documentation configuration", array($this, "show"), "xsoftware_documentation" );
        }
        
        function input()
        {
        }
        
        function show()
        {
        }
        
}

endif;

$documentation_plugin = new xs_documentation_plugin;

?>
