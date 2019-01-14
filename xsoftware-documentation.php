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

include 'RST/autoload.php';

class xs_documentation_plugin
{
        
        private $default = array( 'rest' => '/template/linux.rst' );
        
        private $options = NULL;
        
        private $parser = NULL;

        public function __construct()
        {
                add_action("admin_menu", array($this, "admin_menu"));
                add_action("admin_init", array($this, "section_menu"));
                $this->parser = new Gregwar\RST\Parser;
                // Using parser
                $this->parser->getEnvironment()->getErrorManager()->abortOnError(false);
                $this->options = get_option('xs_docs', $this->default);
                
                add_shortcode( 'xsoftware_documentation', array($this, 'page_docs') );
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
                
                xs_framework::init_admin_style();
                
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
                register_setting( "xsoftware_documentation", "xs_docs", array($this, "input") );
                add_settings_section( "documentation_settings", "Documentation configuration", array($this, "show"), "xsoftware_documentation" );
        }
        
        function input($input)
        {
                $input['rest'] = sanitize_text_field($input['rest']);
                return $input;
        }
        
        function show()
        {
                $settings_field = array('value' => $this->options['rest'], 'name' => 'xs_docs[rest]');
                add_settings_field($settings_field['name'], 
                'File ReST:',
                'xs_framework::create_input',
                'xsoftware_documentation',
                'documentation_settings',
                $settings_field);
        }
        
        function page_docs()
        {
                wp_enqueue_style('xs_documentation_style', plugins_url('style/template.css', __FILE__));
                
                $filename = __DIR__ . $this->options['rest']; //FIXME: Handle if is not set or if not exists!
                
                $file = fopen($filename, 'r');
                $source = fread($file, filesize($filename));
                
                $document = $this->parser->parse($source);
                
                echo $document;
        }
        
}

endif;

$documentation_plugin = new xs_documentation_plugin;

?>
