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
                                'categories' => array(
                                        array(
                                                'name' => 'Common',
                                                'img' => '',
                                                'descr' => ''
                                        )
                                )
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
                add_filter('wp_default_editor', array($this,'html_editor') );
                add_filter('admin_footer', array($this,'remove_editor'), 99);

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
                $selected = isset( $values['xs_documentation_category'][0] ) ? intval($values['xs_documentation_category'][0]) : '';

                foreach($this->options['categories'] as $id => $prop)
                        $categories[$id] = $prop['name'];
                        
                $data = array();
                
                $data['category'][0] = 'Category:';
                $data['category'][1] = xs_framework::create_select( array(
                        'class' => 'xs_full_width', 
                        'name' => 'xs_documentation_category',
                        'data'=> $categories,
                        'selected' => $selected,
                        'return' => true
                ));

                
                xs_framework::create_table(array('class' => 'xs_full_width', 'data' => $data ));
        }
        
        function save($post_id, $post)
        {
                $post_type = get_post_type($post_id);
                if ( $post_type != 'xs_doc' ) return;
               
                if(isset($_POST['xs_documentation_category']))
                        update_post_meta( $post_id, 'xs_documentation_category', $_POST['xs_documentation_category'] );
                        
                $parser = new Gregwar\RST\Parser;
    
                $document = $parser->parse($post->post_content);

                update_post_meta( $post_id, 'xs_documentation_html', $document );
        }
        /*
        * Callback called to show post RST source instead of rendered content
        * inside the editor.
        */
        function html_editor($content) 
        {
                global $post;
                $post_type = get_post_type($post->ID);
                
                if ( $post_type != 'xs_doc' ) 
                        return $content;

                return 'html';
        }
        
        function remove_editor()
        {
                global $post;
                if(!isset($post) || empty($post)) return;
                
                $post_type = get_post_type($post->ID);
                
                if ( $post_type != 'xs_doc' ) return;
                
                echo '  <style type="text/css">
                                #content-tmce, #content-tmce:hover, #qt_content_fullscreen{
                                display:none;
                        }
                        </style>';
                        
                echo '  <script type="text/javascript">
                        jQuery(document).ready(function(){
                                jQuery("#content-tmce").attr("onclick", null);
                        });
                        </script>';
        }
        
        
        function single($single) 
        {
                global $post;

                /* Checks for single template by post type */
                if ( $post->post_type == 'xs_doc' ) {
                        if ( file_exists(  dirname( __FILE__ ) . '/template/single.php' ) ) {
                        return  dirname( __FILE__ ) . '/template/single.php';
                        }
                }

                return $single;
        }
        
        function archive($single)
        {
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
                
                echo "<div class=\"wrap\">";
                echo "<h2>Documentation configuration</h2>";
                
                echo '<form enctype="multipart/form-data" action="options.php" method="post">';

                settings_fields('doc_setting');
                do_settings_sections('doc');

                submit_button( '', 'primary', 'submit', true, NULL );
                echo '</form>';
                
                echo "</div>";
        }
        
        function section_menu()
        {
                register_setting( 'doc_setting', 'xs_options_docs', array($this, 'input') );
                add_settings_section( 'doc_section', 'Settings', array($this, 'show'), 'doc' );
        }
        
        function input($input)
        {

                $current = $this->options;
                
                if(isset($input['cat']) && !empty($input['cat']))
                        $current['categories'] = $input['cat'];
                        
                if(isset($input['add_cat']))
                        $current['categories'][] = ['name' => 'New Category', 'descr' => 'This is a description.', 'img' => ''];
                        
                if(isset($input['remove_cat']) && !empty($input['remove_cat']))
                        unset($current['categories'][$input['remove_cat']]);
                
                return $current;
        }
       
        function show()
        {
                wp_enqueue_style('xs_documentation_admin_style', plugins_url('style/admin.css', __FILE__));
                xs_framework::init_admin_script();
                xs_framework::init_admin_style();
                wp_enqueue_media();
                
                $tab = xs_framework::create_tabs( array(
                        'href' => '?page=xsoftware_documentation',
                        'tabs' => array(
                                'home' => 'Homepage',
                                'categories' => 'Categories'
                        ),
                        'home' => 'home',
                        'name' => 'main_tab'
                ));
                
                switch($tab) {
                        case 'home':
                                return;
                        case 'categories':
                                $this->show_categories();
                                return;
                }
                
        }
        
        function show_categories()
        {
                $options = $this->options['categories'];
                $data = array();
                
                xs_framework::create_button([
                                'class' => 'button-primary xs_margin',
                                'text' => 'Add new category', 
                                'name' => 'xs_options_docs[add_cat]',
                        ]);
                
                foreach($options as $key => $prop) {
                        $img_input = xs_framework::create_input([
                                'id' => 'cat['.$key.'][input]',
                                'style' => 'display:none;',
                                'name' => 'xs_options_docs[cat]['.$key.'][img]',
                                'onclick' => 'wp_media_gallery_url(\'' . 'cat['.$key.'][input]' . '\',\'' . 'cat['.$key.'][image]' . '\')',
                                'value' => $prop['img'],
                                'return' => TRUE,
                        ]);
                        if(empty($prop['img']))
                                $url_img = xs_framework::url_image('select.png');
                        else
                                $url_img = $prop['img'];
                                
                        $img = xs_framework::create_image([
                                'src' => $url_img,
                                'alt' => $prop['name'],
                                'id' => 'cat['.$key.'][image]',
                                'width' => 150,
                                'height' => 150,
                        ]);
                        
                        $name = xs_framework::create_input([
                                'name' => 'xs_options_docs[cat]['.$key.'][name]',
                                'value' => $prop['name'],
                                'return' => TRUE,
                        ]);
                        $descr = xs_framework::create_textarea([
                                'name' => 'xs_options_docs[cat]['.$key.'][descr]',
                                'text' => $prop['descr'],
                                'return' => TRUE,
                        ]);
                        
                        $data[$key]['img'] = xs_framework::create_label([
                                'for' => 'cat['.$key.'][input]',
                                'obj' => [$img_input, $img]
                        ]);
                        
                        $data[$key]['text'] = xs_framework::create_container([
                                'class' => 'xs_docs_container',
                                'obj' => [$name, $descr],
                        ]);
                        $data[$key]['delete'] = xs_framework::create_button([
                                'class' => 'button-primary',
                                'text' => 'Remove',
                                'onclick' => 'return confirm_box()',
                                'value' => $key, 
                                'name' => 'xs_options_docs[remove_cat]',
                                'return' => TRUE
                        ]);
                }
                
                xs_framework::create_table([
                        'class' => 'xs_docs_table',
                        'data' => $data
                ]);

        }
}

$documentation_plugin = new xs_documentation_plugin;

endif;

?>
