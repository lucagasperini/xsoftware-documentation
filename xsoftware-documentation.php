<?php
/*
Plugin Name: XSoftware Documentation
Description: Documentation management on WordPress.
Version: 1.0
Author: Luca Gasperini
Author URI: https://xsoftware.it/
*/

if(!defined('ABSPATH')) die;

if (!class_exists('xs_documentation_plugin')) :

include 'xsoftware-documentation-options.php';
include 'RST/autoload.php';

class xs_documentation_plugin
{

        private $options = NULL;

        /*
        *  __construct : void
        *  The class constructor does not require any parameters and
        *  initializes the options and hooks for plugin operations
        */
        public function __construct()
        {
                add_action('init', [$this, 'create_post_type']);
                add_action('save_post', [$this,'save']);
                add_filter('single_template', [$this,'single']);
                add_filter('archive_template', [$this,'archive']);
                add_action('add_meta_boxes', [$this, 'metaboxes']);
                add_filter('wp_default_editor', [$this,'html_editor']);
                add_filter('admin_footer', [$this,'remove_editor'], 99);

                $this->options = get_option('xs_options_docs');
        }

        /*
        *  void : create_post_type : void
        *  This method create the post type #xs_bug and set it's property:
        *  Name for the post type: "Documentations"
        *  Name for single post type: "Documentation"
        *  It's a public post type
        *  It has an archive
        *  Rewrite URL with slug: "docs"
        *  It can have parent posts
        *  It supports posts titles
        *  It supports the editor
        *  It supports comments
        *  It supports revisions versions
        */
        function create_post_type()
        {
                register_post_type(
                        'xs_doc',
                        [
                                'labels' => [
                                        'name' => __( 'Documentations' ),
                                        'singular_name' => __( 'Documentation' )
                                ],
                                'public' => true,
                                'has_archive' => true,
                                'rewrite' => ['slug' => 'docs'],
                                'hierarchical' => true
                        ]
                );
                add_post_type_support('xs_doc', ['title','editor','comments','revisions'] );
        }

        /*
        *  void : metaboxes : void
        *  This method is used to create the metaboxes in editor
        */
        function metaboxes()
        {
                add_meta_box(
                        'xs_documentation_metaboxes',
                        'XSoftware Documentation',
                        [$this,'metaboxes_print'],
                        ['xs_doc'],
                        'advanced',
                        'high'
                );
        }

        /*
        *  void : metaboxes_print : void
        *  This method is used to print the metaboxes in editor
        */
        function metaboxes_print()
        {
                global $post;
                $values = get_post_custom( $post->ID );
                $selected = isset( $values['xs_documentation_category'][0] ) ?
                        intval($values['xs_documentation_category'][0]) :
                        '';

                foreach($this->options['categories'] as $id => $prop)
                        $categories[$id] = $prop['name'];

                $data = array();

                $data['category'][0] = 'Category:';
                $data['category'][1] = xs_framework::create_select([
                        'class' => 'xs_full_width',
                        'name' => 'xs_documentation_category',
                        'data'=> $categories,
                        'selected' => $selected
                ]);


                xs_framework::create_table([
                        'class' => 'xs_full_width',
                        'data' => $data
                ]);
        }

        /*
        *  void : save : int
        *  This method is used to save the metaboxes values into post metadata
        *  $post_id is the current post id
        */
        function save($post_id)
        {
                $post_type = get_post_type($post_id);
                if ( $post_type != 'xs_doc' ) return;

                $post = get_post($post_id);

                if(isset($_POST['xs_documentation_category']))
                        update_post_meta(
                                $post_id,
                                'xs_documentation_category',
                                $_POST['xs_documentation_category']
                        );

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

        /*
        *  string : single : string
        *  This method is used set the path of php template file for single post
        *  $path is the default single post path
        */
        function single($single)
        {
                /* Get the global variable $post */
                global $post;

                /* Return if current post is empty or is not a #xs_doc */
                if(empty($post) || $post->post_type !== 'xs_doc')
                        return $path;

                /* Return the path of php file where is defined the template */
                return dirname( __FILE__ ) . '/template/single.php';
        }

        /*
        *  string : archive : string
        *  This method is used set the path of php template file for archive post
        *  $path is the default single post path
        */
        function archive($single)
        {
                /* Get the global variable $post */
                global $post;

                /* Return if current post is empty or is not a #xs_doc */
                if(empty($post) || $post->post_type !== 'xs_doc')
                        return $path;

                /* Return the path of php file where is defined the template */
                return dirname( __FILE__ ) . '/template/archive.php';
        }
}

$xs_documentation_plugin = new xs_documentation_plugin;

endif;

?>
