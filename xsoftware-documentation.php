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
include 'database.php';

class xs_documentation_plugin
{
        
        private $default = array( 'rest' => '/template/linux.rst' );
        
        private $options = NULL;
        
        private $parser = NULL;
        private $db = NULL;

        public function __construct()
        {
                add_action("admin_menu", array($this, "admin_menu"));
                add_action("admin_init", array($this, "section_menu"));
                $this->parser = new Gregwar\RST\Parser;
                // Using parser
                $this->parser->getEnvironment()->getErrorManager()->abortOnError(false);
                $this->options = get_option('xs_docs', $this->default);
                $this->db = new xs_documentation_database();
                
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
                
                echo "<form action=\"options.php\" method=\"post\">";
               
                settings_fields('setting_docs');
                do_settings_sections('docs');
                
                submit_button( 'Update products', 'primary', 'product_update', true, NULL );
                
                echo "</form>";
                
                echo "</div>";
        }
        
        function section_menu()
        {
                register_setting( "xsoftware_documentation", "xs_docs", array($this, "input") );
                add_settings_section( "documentation_settings", "Documentation configuration", array($this, "show"), "xsoftware_documentation" );
                
                register_setting( 'setting_docs', 'xs_docs', array($this, 'input_docs') );
                add_settings_section( 'section_docs', 'Documentation', array($this, 'show_docs'), 'docs' );
        }
        
        function input($input)
        {
                $input['rest'] = sanitize_text_field($input['rest']);
                return $input;
        }
        
        function input_docs($input)
        {
                if(isset($input['new']))
                        $this->db->add($input['new']);
                else
                        $this->db->update_single($input, $input['id']);

                unset($input);
        }
        
        function show_docs()
        {
                if(!isset($_GET["edit"])) {
                        $this->show_docs_all();
                        return;
                }
                
                $get = $_GET["edit"];
                
                if($get == "new") {
                        $this->show_docs_add();
                        return;
                }
               
                $products = $this->db->get(NULL, $get);
                $this->show_docs_edit_single($get);
                return;
        }
       
        public function show_docs_edit_single($id)
        {
                xs_framework::create_link( array(
                        'href' => 'admin.php?page=xsoftware_documentation', 
                        'class' => 'button-primary', 
                        'text' => 'Back'
                ));
                
                $docs = $this->db->get_by(array('id' => $id));
                if(count($docs) != 1)
                        return;
                $doc = $docs[0];
               
                $products = $this->db->get_products_name();
                $data = array();
                
                $data['id'][0] = 'ID:';
                $data['id'][1] = xs_framework::create_input( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[id]',
                        'value'=> $doc['id'],
                        'readonly' => true,
                        'return' => true
                ));
                
                $data['lang'][0] = 'Language:';
                $data['lang'][1] = xs_framework::create_select( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[lang]', 
                        'data' => xs_language::$language_codes, 
                        'selected' => $doc['lang'],
                        'reverse' => true, 
                        'return' => true
                ));
                
                $data['title'][0] = 'Title:';
                $data['title'][1] = xs_framework::create_textarea( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[title]',
                        'text' => $doc['title'],
                        'return' => true
                ));
                
                $data['product'][0] = 'Product:';
                $data['product'][1] = xs_framework::create_select( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[product]',
                        'data'=> $products,
                        'selected' => $doc['product'],
                        'return' => true
                ));
                
                $data['text'][0] = 'Text:';
                $data['text'][1] = xs_framework::create_textarea( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[text]',
                        'text'=> $doc['text'],
                        'return' => true
                ));
                
                $headers = array('Field', 'Value');
                xs_framework::create_table(array('class' => 'xs_full_width', 'headers' => $headers, 'data' => $data ));
        }

        public function show_docs_add()
        {
                xs_framework::create_link( array(
                        'href' => 'admin.php?page=xsoftware_documentation', 
                        'class' => 'button-primary', 
                        'text' => 'Back'
                ));
                
                $fields = $this->db->get_fields(array('id'));
                $size_fields = count($fields);
                $products = $this->db->get_products_name();
                
                $headers = array('Field', 'Value');
                $data = array();
               
                $data['product'][0] = 'Product:';
                $data['product'][1] = xs_framework::create_select( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[new][product]',
                        'data'=> $products,
                        'return' => true
                ));
                
                $data['lang'][0] = 'Language:';
                $data['lang'][1] = xs_framework::create_select( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[new][lang]', 
                        'data' => xs_language::$language_codes, 
                        'reverse' => true, 
                        'return' => true
                ));
                        
                $data['title'][0] = 'Title:';
                $data['title'][1] = xs_framework::create_textarea( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[new][title]',
                        'return' => true
                ));
                
                $data['text'][0] = 'Text:';
                $data['text'][1] = xs_framework::create_textarea( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[new][text]',
                        'return' => true
                ));
                
                
                xs_framework::create_table(array('class' => 'xs_full_width', 'headers' => $headers, 'data' => $data ));
        }

        
        function show_docs_all()
        {
                xs_framework::create_link( array(
                        'href' => 'admin.php?page=xsoftware_documentation&edit=new', 
                        'class' => 'button-primary', 
                        'text' => 'Add'
                ));
                       
                $docs = $this->db->get();
               
                
                for($i = 0; $i < count($docs); $i++) {
                        $actions = xs_framework::create_link( array(
                                'href' => 'admin.php?page=xsoftware_documentation&edit='.$docs[$i]['id'], 
                                'class' => 'button-primary xs_full_width xs_text_center', 
                                'text' => 'Show', 
                                'return' => true
                        ));
                        /*$actions .= xs_framework::create_button( array( 
                                'name' => 'products[delete]', 
                                'class' => 'button-primary xs_full_width', 
                                'value' => $docs[$i]['id'], 'text' => 'Remove', 
                                'onclick'=>'return confirm_box()', 
                                'return' => true
                        ));*/
                        array_unshift($docs[$i], $actions);
                }
                
                $fields[] = "Actions";
                $fields[] = "ID";
                $fields[] = "Product";
                $fields[] = "Lang";
                $fields[] = "Title";
                $fields[] = "Text";
                $fields[] = "Created By";
                $fields[] = "Created at";
                $fields[] = "Last edit on";
                
                xs_framework::create_table(array('headers' => $fields, 'data' => $docs));
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
