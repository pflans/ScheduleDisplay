<?php 


function sd_shortcode() {
    $args = array(
	'post_type' => 'sdprogram',
        'posts_per_page' => 5,
        
    
    );
    
    $postslist = get_posts( $args );
    
    foreach ( $postslist as $post ) :   
        $return_string .= get_the_title($post->ID); 
        $return_string .= '-';
    endforeach; 
    
    

   return $return_string;  
}

add_shortcode( 'scheduledisplay', 'sd_shortcode' );