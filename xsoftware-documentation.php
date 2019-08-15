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

/*
*  XSoftware Bugtracking Plugin Class
*  The following class is used to execute plugin operations
*/
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
                $this->options = get_option('xs_options_docs');

                /* Create the post type #xs_doc */
                add_action('init', [$this, 'create_post_type']);
                /* Save metaboxes values to #xs_doc posts when save a post */
                add_action('save_post', [$this,'metaboxes_save']);
                /* Use built-in template for show single #xs_doc posts */
                add_filter('single_template', [$this,'single']);
                /* Use built-in template for show archive #xs_doc posts */
                add_filter('archive_template', [$this,'archive']);
                /* Add metaboxes to #xs_doc posts */
                add_action('add_meta_boxes', [$this, 'metaboxes']);
                /* Filter called to show post from RST source */
                add_filter('wp_default_editor', [$this,'html_editor']);
                /* Filter to remove the default visual editor for #xs_doc */
                add_filter('admin_footer', [$this,'remove_editor'], 99);
        }

        /*
        *  void : create_post_type : void
        *  This method create the post type #xs_doc and set it's property:
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
                /* Get the global variable $post */
                global $post;
                /* Get the values from post metadata */
                $values = get_post_custom($post->ID);
                /*
                *  Get the current category of the documentation casted as integer,
                *  get an empty string if it's not present
                */
                $selected = isset( $values['xs_documentation_category'][0] ) ?
                        intval($values['xs_documentation_category'][0]) :
                        '';

                /* Put all categories name in the array $categories by their id */
                foreach($this->options['categories'] as $id => $prop)
                        $categories[$id] = $prop['name'];

                $data = array();

                /* Create a html select with the list of categories */
                $data['category'][0] = 'Category:';
                $data['category'][1] = xs_framework::create_select([
                        'class' => 'xs_full_width',
                        'name' => 'xs_documentation_category',
                        'data'=> $categories,
                        'selected' => $selected
                ]);

                /* Print the previous html element as table */
                xs_framework::create_table([
                        'class' => 'xs_full_width',
                        'data' => $data
                ]);
        }

        /*
        *  void : metaboxes_save : int
        *  This method is used to save the metaboxes values into post metadata
        *  $post_id is the current post id
        */
        function metaboxes_save($post_id)
        {
                /* Return if current post type is not a #xs_doc */
                if(get_post_type($post_id) !== 'xs_doc')
                        return;

                /* Update post metadata of category by current $_POST value */
                if(isset($_POST['xs_documentation_category']))
                        update_post_meta(
                                $post_id,
                                'xs_documentation_category',
                                $_POST['xs_documentation_category']
                        );
                include 'RST/autoload.php';

                /* Fetch by post query the post_content */
                $content = get_post_field('post_content', $post_id);
                /* Initialize the RST Parser */
                $parser = new Gregwar\RST\Parser;
                /* Parse in HTML the current content */
                $document = $parser->parse($content);

                /* Update the post meta of html documentation text */
                update_post_meta( $post_id, 'xs_documentation_html', $document );
        }

        /*
        *  string : html_editor : string
        *  This method is used to show post RST source instead of
        *  rendered content inside the editor.
        *  $content is the post content
        */
        function html_editor($content)
        {
                global $post;

                /* Return the content if current post type is not a #xs_doc */
                if(get_post_type($post->ID) !== 'xs_doc')
                        return $content;

                return 'html';
        }

        /*
        *  void : remove_editor : void
        *  This method is used to add on the footer od administration panel
        *  the css code and the javascript code to remove the default visual editor
        */
        function remove_editor()
        {
                global $post;

                /* Return if current post type is not a #xs_doc */
                if(empty($post) || get_post_type($post->ID) !== 'xs_doc')
                        return;

                /* Print the css on the footer to hide visual editor */
                echo '  <style type="text/css">
                                #content-tmce, #content-tmce:hover, #qt_content_fullscreen{
                                display:none;
                        }
                        </style>';

                /* Print the javascript on the footer to remove onclick function of visual editor */
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
        function single($path)
        {
                /* Get the global variable $post */
                global $post;

                /* Return if current post is empty or is not a #xs_doc */
                if(empty($post) || $post->post_type !== 'xs_doc')
                        return $path;

                /* Return the path of php file where is defined the template */
                return dirname( __FILE__ ) . '/single.php';
        }

        /*
        *  string : archive : string
        *  This method is used set the path of php template file for archive post
        *  $path is the default single post path
        */
        function archive($path)
        {
                /* Get the global variable $post */
                global $post;

                /* Return if current post is empty or is not a #xs_doc */
                if(empty($post) || $post->post_type !== 'xs_doc')
                        return $path;

                /* Return the path of php file where is defined the template */
                return dirname( __FILE__ ) . '/archive.php';
        }
}

$xs_documentation_plugin = new xs_documentation_plugin;

endif;

?>
