<?php 


function sd_shortcode() {
    
    date_default_timezone_set('America/New_York');
    $currentstamp = date();
    
    $args = array(
	'post_type' => 'sdprogram',
        'posts_per_page' => 5,
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_key' => '_sd_airdate_textdate_timestamp',
        'meta_query' => array(
              array(
                  'key' => '_sd_airdate_textdate_timestamp',
                  'value' => strtotime("now"),
                  'type' => 'NUMERIC',
                  'compare' => '>='
              ),
          )
      );
    
    $postslist = get_posts( $args );
    
    foreach ( $postslist as $post ) :   
        $return_string .= get_the_title($post->ID); 
        $return_string .= '-';
    endforeach; 
    
    

   return $return_string;  
}

add_shortcode( 'scheduledisplay', 'sd_shortcode' );