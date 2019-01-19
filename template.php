<?php
        wp_enqueue_style('xs_documentation_style', plugins_url('style/template.css', __FILE__));
        wp_enqueue_style('xs_documentation_fontawesome_style', 'https://use.fontawesome.com/releases/v5.6.3/css/all.css');
        
        function docs_single($single)
        {
                echo '<a href="?doc='.$single['name'].'&cat='.$single['product'].'&download"><i class="fas fa-file-download"></i></a>';
                echo '<div class="single-meta">';
                echo '<span class="label">Created By: </span>'.$single['create_by'].'</br>';
                echo '<span class="label">At: </span>'.$single['create_date'].'</br>';
                echo '<span class="label">Last Edit: </span>'.$single['modify_date'].'</br>';
                echo '</div>';
                echo '<div class="c">'.$single['text'].'</div>';
        }
        
        function docs_main($array, $cats)
        {

                foreach($array as $single)
                {
                        $docs[$single['product']][] = $single;
                }
                echo '<div class="css-treeview"><ul>';
                foreach($docs as $product => $list)
                {
                        echo "<label>".$cats[$product]."</label>";
                        echo "<ul>";
                        foreach($list as $s) {
                                echo '<li><div class="row">';
                                echo '<a href="?doc='.$s['name'].'&cat='.$s['product'].'">'.$s['title'].'</a>';
                                echo '<a class="download-link" href="?doc='.$s['name'].'&cat='.$s['product'].'&download">';
                                echo '<i class="fas fa-file-download"></i>';
                                echo '</a>';
                                echo '</div></li>';
                        }
                        echo "</ul>";
                }
                echo "</ul>";
        }
?>
