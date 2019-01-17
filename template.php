<?php
        wp_enqueue_style('xs_documentation_style', plugins_url('style/template.css', __FILE__));
        
        function docs_single($single)
        {
                echo '<div class="single-meta">';
                echo '<span class="label">Created By: </span>'.$single['create_by'].'</br>';
                echo '<span class="label">At: </span>'.$single['create_date'].'</br>';
                echo '<span class="label">Last Edit: </span>'.$single['modify_date'].'</br>';
                echo '</div>';
                echo '<div class="c">'.$single['text'].'</div>';
        }
        
        function docs_main($array)
        {
                echo '<div class="product_list">';
                foreach($array as $single)
                        echo '<div class="product_list_item"><a href="?id='.$single['id'].'"><span>'.$single['title'].'</span></a></div>';
                echo '</div>';
        }
?>
