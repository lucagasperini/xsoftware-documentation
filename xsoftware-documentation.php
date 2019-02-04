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
        
        private $default = array( 
                                'template_file' => 'template.php',
                                'product_list' => array('common' => 'Common'),
                                'import_products' => FALSE,
                                );
        
        private $options = NULL;

        public function __construct()
        {
                ob_start();
                add_action('admin_menu', array($this, 'admin_menu'));
                add_action('admin_init', array($this, 'section_menu'));
                add_action('init', array($this, 'create_post_type'));
                add_action('save_post', array($this,'save'), 10, 2 );
                add_filter('single_template', array($this,'single'));
                add_filter('archive_template', array($this,'archive'));
                add_action('add_meta_boxes', array($this, 'metaboxes'));
                
                $this->options = get_option('xs_options_docs', $this->default);
        }
        
        function create_post_type() 
        {
                register_post_type( 
                        'xs_doc',
                        array(
                                'labels' => array(
                                        'name' => __( 'Documentations' ),
                                        'singular_name' => __( 'Documentation' )
                                ),
                                'public' => true,
                                'has_archive' => true,
                                'rewrite' => array('slug' => 'docs'),
                                'hierarchical' => true
                        )
                );
                add_post_type_support('xs_doc', array('title','editor','comments','revisions') );
        }
        
        function metaboxes()
        {
                add_meta_box( 'xs_bugtracking_metaboxes', 'XSoftware Documentation', array($this,'metaboxes_print'), array('xs_doc'),'advanced','high');
        }
        
        function metaboxes_print()
        {
                global $post;
                $values = get_post_custom( $post->ID );
                $product = isset( $values['xs_documentation_product'][0] ) ? $values['xs_documentation_product'][0] : '';

                $products = $this->get_products_name();
                $data = array();
                
                $data['product'][0] = 'Product:';
                $data['product'][1] = xs_framework::create_select( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_documentation_product',
                        'data'=> $products,
                        'selected' => $product,
                        'return' => true
                ));

                
                xs_framework::create_table(array('class' => 'xs_full_width', 'data' => $data ));
        }
        
        function save($post_id, $post)
        {
                $post_type = get_post_type($post_id);
                if ( $post_type != 'xs_doc' ) return;
                
                $parser = new Gregwar\RST\Parser;
    
                $document = $parser->parse($post->post_content);
                if(isset($_POST['xs_documentation_product']))
                        update_post_meta( $post_id, 'xs_documentation_product', $_POST['xs_documentation_product'] );
                update_post_meta( $post_id, 'xs_documentation_html', $document );
        }
        
        
        function single($single) {
                global $post;

                /* Checks for single template by post type */
                if ( $post->post_type == 'xs_doc' ) {
                        if ( file_exists(  dirname( __FILE__ ) . '/template/single.php' ) ) {
                        return  dirname( __FILE__ ) . '/template/single.php';
                        }
                }

                return $single;
        }
        
        function archive($single) {
                global $post;

                /* Checks for single template by post type */
                if ( $post->post_type == 'xs_doc' ) {
                        if ( file_exists(  dirname( __FILE__ ) . '/template/archive.php' ) ) {
                        return  dirname( __FILE__ ) . '/template/archive.php';
                        }
                }

                return $single;
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
        
        function get_products_name()
        {
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

                if (mysqli_connect_error()) {
                        die("Connection to database failed: " . mysqli_connect_error());
                }
                if(is_resource($conn)) { 
                        $conn->query($conn, "SET NAMES 'utf8'"); 
                        $conn->query($conn, "SET CHARACTER SET 'utf8'"); 
                } 
                $offset = array();
                $sql = "SELECT name, title FROM xs_products WHERE lang='en_GB'"; //FIXME: FORCE LANG EN
                $result = $conn->query($sql);
                if (!$result) {
                        echo "Could not run query: SQL_ERROR -> " . $conn->error . " SQL_QUERY -> " . $sql;
                        exit;
                }
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                $offset[$row['name']] = $row['title'];
                        }
                }
                $result->close();
                return $offset;
        }
        
}

$documentation_plugin = new xs_documentation_plugin;

endif;

?>
