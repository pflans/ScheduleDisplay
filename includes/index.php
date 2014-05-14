<?php 


function sd_shortcode() {
    
    $currentstamp = (current_time( 'timestamp', 0 ) - 7200); // subtract two hours
    
    $args = array(
	'post_type' => 'sdprogram',
        'posts_per_page' => 5,
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_key' => '_sd_airdate_textdate_timestamp',
        'meta_query'        => array(
             array(
                    'key'       => '_sd_airdate_textdate_timestamp',
                    'compare'   => '>=',
                    'value'     => $currentstamp,
                    'type'      => 'numeric'
             )
        ),
      );

    $postslist = get_posts( $args );
    
    $return_string .= '
        <div class="programSchedule">
            <h2>Now Playing & Coming Up</h2>';
           
                $first = true;
                foreach ( $postslist as $post ) :               
                   $idstor = $post->ID;  
                   $programTime = get_post_meta( $idstor, '_sd_airdate_textdate_timestamp', true);
                   $SeriesName = get_post_meta( $idstor, '_sd_seriesname_text', true);
                   $programOrgBroadcast = get_post_meta( $idstor, '_sd_orgdate_textsmall', true);
                   $programTitle = get_post_meta( $idstor, '_sd_episodename_text', true);
                   if ( $first ){
                       $return_string .= '<h5>';
                       $return_string .= gmdate("F j, Y", $programTime);
                       $return_string .= '
                       (all times Eastern - all start times approximate)</h5>
                       <table>';
                       $first = false;
                   }
                   
                   $default_string = '     
                        <tr>
                            <td class="programTime">%s</td>
                            <td class="seriesName">%s</td>
                            <td class="programOrgBroadcast">%s</td>
                            <td class="programTitle">%s</td>
                        </tr>';
                   
                   $new_string = sprintf($default_string, gmdate("h:i A", $programTime), $SeriesName, $programOrgBroadcast, $programTitle);
                   $return_string .= $new_string;
                endforeach;    
                wp_reset_postdata();                 
    $return_string .= '            
            </table>
           </div>';
    

   return $return_string;  
}

add_shortcode( 'scheduledisplay', 'sd_shortcode' );

function sddisplay_shortcode() {
    
    $currentstamp = (current_time( 'timestamp', 0 ) - 7200); // subtract two hours
    $start = strtotime("today midnight");
	$end   = strtotime("tomorrow", $start) - 1;
    
    $args = array(
	'post_type' => 'sdprogram',
        'posts_per_page' => -1,
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_key' => '_sd_airdate_textdate_timestamp',
        'meta_query' => array(
             array(
                    'key'       => '_sd_airdate_textdate_timestamp',
        			'value' => array($start, $end),
            		'compare' => 'BETWEEN',
                    'type'      => 'numeric'
             )
        ),
      );

    $postslist = get_posts( $args );

    $return_string .= '<div class="programSchedule_full">';
    $return_string .= '<h5>';
    $return_string .= gmdate("F j, Y", $start);
    $return_string .= '</h5>
    				   <h6>(all times Eastern - all start times approximate)</h6>
                       <table>
                       		<tr>
                       			<th>Time</th>
                       			<th>Series Name</th>
                       			<th>Original Broadcast Date</th>
                       			<th>Episode Name</th>
                       		</tr>
                       			';
                       
                foreach ( $postslist as $post ) :               
                   $idstor = $post->ID;  
                   $programTime = get_post_meta( $idstor, '_sd_airdate_textdate_timestamp', true);
                   $SeriesName = get_post_meta( $idstor, '_sd_seriesname_text', true);
                   $programOrgBroadcast = get_post_meta( $idstor, '_sd_orgdate_textsmall', true);
                   $programTitle = get_post_meta( $idstor, '_sd_episodename_text', true);
                   $default_string = '     
                        <tr>
                            <td class="programTime">%s</td>
                            <td class="seriesName">%s</td>
                            <td class="programOrgBroadcast">%s</td>
                            <td class="programTitle">%s</td>
                        </tr>';
                   
                   $new_string = sprintf($default_string, gmdate("h:i A", $programTime), $SeriesName, $programOrgBroadcast, $programTitle);
                   $return_string .= $new_string;
                endforeach;     
                wp_reset_postdata();                
    $return_string .= '            
            </table>
           </div>';
    

   return $return_string;  
}

add_shortcode( 'scheduledisplay_full', 'sddisplay_shortcode' );