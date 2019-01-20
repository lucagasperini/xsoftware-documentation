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
        
        private $default = array( 
                                'template_file' => 'template.php',
                                'product_list' => array('common' => 'Common'),
                                'import_products' => FALSE,
                                );
        
        private $options = NULL;
       
        private $db = NULL;

        public function __construct()
        {
                ob_start();
                add_action("admin_menu", array($this, "admin_menu"));
                add_action("admin_init", array($this, "section_menu"));
                $this->options = get_option('xs_options_docs', $this->default);
                $this->db = new xs_documentation_database();
                
                add_shortcode( 'xsoftware_documentation', array($this, 'shortcode') );
        }
        
        function admin_menu()
        {
                add_submenu_page( "xsoftware", "XSoftware Documentation", "Documentation", "manage_options", "xsoftware_documentation", array($this, "menu_page") );
        }
        
        function menu_page()
        {
                if ( !current_user_can( "manage_options" ) )  {
                        wp_die( __( "Exit!" ) );
                }
                
                xs_framework::init_admin_style();
                xs_framework::init_admin_script();
                
                echo "<div class=\"wrap\">";
                echo "<h2>Documentation configuration</h2>";
                
                echo '<form action="options.php" method="post">';

                settings_fields('doc_setting_globals');
                do_settings_sections('doc_globals');

                submit_button( '', 'primary', 'submit', true, NULL );
                echo '</form>';
                
                echo "<form enctype=\"multipart/form-data\" action=\"options.php\" method=\"post\">";
               
                settings_fields('setting_docs');
                do_settings_sections('docs');
                
                submit_button( '', 'primary', 'submit', true, NULL );
                
                echo "</form>";
                
                echo "</div>";
        }
        
        function section_menu()
        {
                register_setting( 'doc_setting_globals', 'xs_options_docs', array($this, 'input_doc_globals') );
                add_settings_section( 'doc_section_globals', 'Globals settings', array($this, 'show_doc_globals'), 'doc_globals' );
                
                register_setting( 'setting_docs', 'xs_docs', array($this, 'input_docs') );
                add_settings_section( 'section_docs', 'Documentation', array($this, 'show_docs'), 'docs' );
        }
        
        function input_doc_globals($input)
        {
                $input['template_file'] = sanitize_text_field( $input['template_file'] );
                $input['product_list'] = $this->options['product_list'];
                
                $new_key = isset($input['key']) ? $input['key'] : '';
                $new_text = isset($input['text']) ? $input['text'] : '';
                
                if($input['import_products'] == TRUE) { //"on" by default
                        $input['import_products'] = TRUE;
                        $input['product_list'] += $this->db->get_products_name();
                        unset($input['import_products']);
                }
               
                if(!empty($new_key) && !empty($new_text)){
                        $input['product_list'][$input['key']] = $input['text'];
                }
                
                return $input;
        }
        
        function show_doc_globals()
        {
                $data = array();
                $headers = array('Key', 'Text');
                
                foreach($this->options['product_list'] as $key => $text) {
                        $data[$key][0] = $key;
                        $data[$key][1] = $text;
                }
                
                $input_key = xs_framework::create_input( array(
                                'class' => 'xs_full_width', 
                                'name' => 'xs_options_docs[key]',
                                'return' => true
                                ));
                                
                $input_text = xs_framework::create_input( array(
                                'class' => 'xs_full_width', 
                                'name' => 'xs_options_docs[text]',
                                'return' => true
                                ));
                                
                $data[] = array( 0 => $input_key, 1 => $input_text);
                        
                xs_framework::create_table(array('class' => 'xs_full_width', 'headers' => $headers, 'data' => $data ));
                
                //FIXME: Better a button to handle import!
                $import_products = isset($this->options['import_products']) ? $this->options['import_products'] : FALSE; 
                $settings_field = array('value' => $import_products, 'name' => 'xs_options_docs[import_products]', 'compare' => TRUE);
                add_settings_field($settings_field['name'], 
                'Import from XSoftware Products:',
                'xs_framework::create_input_checkbox',
                'doc_globals',
                'doc_section_globals',
                $settings_field);
                
                $settings_field = array('value' => $this->options['template_file'], 'name' => 'xs_options_docs[template_file]');
                add_settings_field($settings_field['name'], 
                'Template file path:',
                'xs_framework::create_input',
                'doc_globals',
                'doc_section_globals',
                $settings_field);
                
        }
        
        function input_docs($input)
        {
                if(isset($_FILES["xs_docs"]["tmp_name"]["text"])) {
                        $file_input = $_FILES["xs_docs"]["tmp_name"]['text'];
                        $file_basename = basename($_FILES["xs_docs"]["name"]['text']);
                }
                else
                        $file_input = '';
                        
                $document = '';
                if(!empty($file_input)) //FIXME: Add a file check!
                {
                        $doc_dir = WP_CONTENT_DIR . '/documentation/';
                        if(is_dir($doc_dir) === FALSE)
                                mkdir($doc_dir, 0774);
                        
                        if(isset($input['lang']) && isset($input['product']))
                                $lang_dir = $doc_dir . $input['lang'] . '/';
                        else if(isset($input['new']['lang']) && isset($input['new']['product']))
                                $lang_dir = $doc_dir . $input['new']['lang'] . '/' ;
                        
                        if(is_dir($lang_dir) === FALSE)
                                mkdir($lang_dir, 0774);
                        
                        if(isset($input['product']))
                                $product_dir = $lang_dir . $input['product'] . '/';
                        else if(isset($input['new']['product']))
                                $product_dir = $lang_dir . $input['new']['product'] . '/';
                                
                        if(is_dir($product_dir) === FALSE)
                                mkdir($product_dir, 0774);
                                
                        $target_file = $product_dir . $file_basename;
                        if(move_uploaded_file($file_input, $target_file) !== TRUE)
                                trigger_error('Cannot move the file: ' . $target_file, E_USER_ERROR);
                        $parser = new Gregwar\RST\Parser;
                        $file = fopen($target_file, 'r');
                        $source = fread($file, filesize($target_file));
                        
                        if(isset($input['new']))
                                $input['new']['file'] = $target_file;
                        else
                                $input['file'] = $target_file;
                                
                        $document = $parser->parse($source);
                }
                
                if(isset($input['new'])) {
                        $input['new']['text'] = $document;
                        $this->db->add($input['new']);
                }
                else if(isset($input['delete']))  {
                        $this->db->remove($input['delete']); //FIXME: delete file is exists!
                }
                else if(isset($input['id'])){
                        $input['text'] = $document;
                        if(empty($input['text']))
                                unset($input['text']);
                        $this->db->update_single($input, $input['id']);
                }
                unset($input);
                return;
        }
        
        function show_docs()
        {
                if(!isset($_GET["edit"])) {
                        $this->show_docs_all();
                        return;
                }
                
                $get = array('id' => $_GET["edit"]);

                if($get['id'] == "new")
                        $this->show_docs_add();
                else
                        $this->show_docs_edit($get);
        }
       
        public function show_docs_edit($id)
        {
                xs_framework::create_link( array(
                        'href' => 'admin.php?page=xsoftware_documentation', 
                        'class' => 'button-primary', 
                        'text' => 'Back'
                ));
                
                $docs = $this->db->get($id);
                if(count($docs) != 1)
                        return;
                $doc = $docs[0];
                
                xs_framework::create_input( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[id]',
                        'value'=> $doc['id'],
                        'type' => 'hidden'
                ));
               
                $products = $this->options['product_list'];
                $data = array();
                
                $data['name'][0] = 'Name:';
                $data['name'][1] = xs_framework::create_textarea( array(
                        'class' => 'xs_full_width', 
                        'text'=> $doc['name'],
                        'name' => 'xs_docs[name]',
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
                        'compare_key' => TRUE,
                        'return' => true
                ));
                
                $data['text'][0] = 'Text:';
                $data['text'][1] = xs_framework::create_upload_file( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[text]',
                        'id' => 'xs_docs[text]',
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
                $products = $this->options['product_list'];
                
                $headers = array('Field', 'Value');
                $data = array();
                
                $data['name'][0] = 'Name:';
                $data['name'][1] = xs_framework::create_textarea( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[new][name]',
                        'return' => true
                ));
               
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
                $data['text'][1] = xs_framework::create_upload_file( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_docs[text]',
                        'id' => 'xs_docs[text]',
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
                       
                $docs = $this->db->get_meta();
               
                
                for($i = 0; $i < count($docs); $i++) {
                        $actions = xs_framework::create_link( array(
                                'href' => 'admin.php?page=xsoftware_documentation&edit='.$docs[$i]['id'], 
                                'class' => 'button-primary xs_full_width xs_text_center', 
                                'text' => 'Edit
                                ', 
                                'return' => true
                        ));
                        $actions .= xs_framework::create_button( array( 
                                'name' => 'xs_docs[delete]', 
                                'class' => 'button-primary xs_full_width', 
                                'value' => $docs[$i]['id'], 'text' => 'Remove', 
                                'onclick'=>'return confirm_box()', 
                                'return' => true
                        ));
                        array_unshift($docs[$i], $actions);
                }
                
                $fields[] = "Actions";
                $fields[] = "ID";
                $fields[] = "Name";
                $fields[] = "Product";
                $fields[] = "Language";
                $fields[] = "Title";
                $fields[] = "File";
                $fields[] = "Created By";
                $fields[] = "Created at";
                $fields[] = "Last edit on";
                
                xs_framework::create_table(array('headers' => $fields, 'data' => $docs));
        }

        function shortcode($attr)
        {
                include $this->options['template_file'];
                shortcode_atts( array( 'lang' => ''), $attr );
                
                //FIXME: AVOID TO USE SPACE ON PRODUCT!
                $query = isset($_GET['doc']) && isset($_GET['cat'])  ? 
                        array('name' => $_GET['doc'], 'product' => $_GET['cat'], 'lang' => $attr['lang']) : 
                        array('lang' => $attr['lang']);
                        
                $download = isset($_GET['download']);
                
                $search = $this->db->get($query);
                
                if(count($search) > 1)
                        docs_main($search, $this->options['product_list']);
                else if(count($search) == 1) {
                        docs_single($search[0]);
                        if($download == TRUE)
                                $this->download_docs($search[0]['file']);
                                
                }
                else
                        wp_die();
        }
        
        function download_docs($file)
        {
                if (file_exists($file)) {
                        ob_end_clean();
                        ob_start();
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="'.basename($file).'"');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($file));
                        readfile($file);
                        exit;
                }
        }
        
}

$documentation_plugin = new xs_documentation_plugin;

endif;

?>
