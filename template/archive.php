<?php
        if(!defined("ABSPATH")) die;

        /* Load the documention template style */
        wp_enqueue_style('xs_documentation_style', plugins_url('template.min.css', __FILE__));

        get_header();

        /* Get the options from documention plugin */
        $option = get_option('xs_options_docs');

        /* Print primary and main elements */
        echo '<div id="primary" class="archive_content_area">
        <main id="main" class="post-wrap" role="main">';

        if ( have_posts() ) {
                /* Print the header */
                echo '<header class="page-header">';
                the_archive_title( '<h3 class="archive-title">', '</h3>' );
                the_archive_description( '<div class="taxonomy-description">', '</div>' );
                echo '</header>';

                /* Get all post in $html_list */
                $html_list = array();
                while ( have_posts() ) {
                        the_post();
                        /* Get the post id */
                        $id = get_the_ID();
                        /* Get the post category */
                        $category = get_post_meta( $id, 'xs_documentation_category', true );
                        /* Get the post permalink at 'permalink' */
                        $values['permalink'] = get_permalink($id);
                        /* Get the post title at 'title' */
                        $values['title'] = get_the_title($id);
                        /* Create the matrix by categories with all important values */
                        $html_list[$category][] = $values;
                }

                /* Print the matrix */
                foreach($html_list as $cat => $docs) {
                        /* Get the current category */
                        $current = $option['categories'][$cat];
                        /* Print the treeview */
                        echo '<ul class="css-treeview">';
                        /* Print the category image */
                        xs_framework::create_image([
                                'src' => $current['img'],
                                'alt' => $current['name'],
                                'height' => 150,
                                'width' => 150,
                                'echo' => TRUE
                        ]);
                        /* Print the title of the category */
                        echo '<label>'.$current['name'].'</label>';
                        /* Print the description of the category */
                        echo '<p>'.$current['descr'].'</p>';
                        /* Print the sub array of documentations */
                        foreach($docs as $single) {
                                /* Print the row */
                                echo '<li><div class="row">';
                                /* Print the link on title*/
                                echo '<a href="'.$single['permalink'].'">'.$single['title'].'</a>';
                                /* Print the download link */
                                echo '<a class="download-link" href="'.$single['permalink'].'?download">';
                                /* Print the font-awesome icon for download */
                                echo '<i class="fas fa-file-download"></i>';
                                /* Close download link */
                                echo '</a>';
                                /* Close the row */
                                echo '</div></li>';
                        }
                        /* Close the treeview */
                        echo '</ul>';
                }

        } else {
                get_template_part( 'content', 'none' );
        }
        /* Close primary and main elements */
        echo '</main></div>';

        get_footer();
?>
