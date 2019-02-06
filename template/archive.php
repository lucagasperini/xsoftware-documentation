<?php
        if(!defined("ABSPATH")) die;
        
        wp_enqueue_style('xs_documentation_style', plugins_url('template.css', __FILE__));
        wp_enqueue_style('xs_documentation_fontawesome_style', 'https://use.fontawesome.com/releases/v5.6.3/css/all.css');
        
        get_header();
        
        $option = get_option('xs_options_docs');
        $option = $option['categories'];
        
        echo '<div id="primary" class="content-area col-md-9">';

        echo '<main id="main" class="post-wrap" role="main">';

        if ( have_posts() ) {
                echo '<header class="page-header">';
                the_archive_title( '<h3 class="archive-title">', '</h3>' );
                the_archive_description( '<div class="taxonomy-description">', '</div>' );
                echo '</header>';
                
                $html_list = array();
                while ( have_posts() ) { 
                        the_post();
                        $id = get_the_ID();
                        $product = get_post_meta( $id, 'xs_documentation_category', true );
                        $values['permalink'] = get_permalink($id);
                        $values['title'] = get_the_title($id);
                        $html_list[$product][] = $values;
                }

                echo '<div class="posts-layout">';
                foreach($html_list as $product => $docs) {
                        echo '<ul class="css-treeview">';
                        echo '<div class="c">';
                        xs_framework::create_image([
                                'src' => $option[$product]['img'],
                                'alt' => $option[$product]['name'],
                                'height' => 150,
                                'width' => 150,
                                'echo' => TRUE
                        ]);
                        echo '<div class="c">';
                        echo '<label>'.$option[$product]['name'].'</label>';
                        echo '<p>'.$option[$product]['descr'].'</p>';
                        echo '</div></div>';
                        foreach($docs as $single) {
                                echo '<li><div class="row">';
                                echo '<a href="'.$single['permalink'].'">'.$single['title'].'</a>';
                                echo '<a class="download-link" href="'.$single['permalink'].'?download">';
                                echo '<i class="fas fa-file-download"></i>';
                                echo '</a>';
                                echo '</div></li>';
                        }
                        echo '</ul>';
                }
                echo '</div>';

                the_posts_pagination( array(
                        'mid_size'  => 1,
                ) );
        } else {
                get_template_part( 'content', 'none' );
        }

        echo '</main></div>';

        get_sidebar();

        
        get_footer();
?>
