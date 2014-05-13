<?php
/*
Plugin Name: CSV Importer
Description: Import data as posts from a CSV file. <em>You can reach the author at <a href="mailto:d.v.kobozev@gmail.com">d.v.kobozev@gmail.com</a></em>.
Version: 0.3.9
Author: Denis Kobozev, Bryan Headrick
*/
/**
 * LICENSE: The MIT License {{{
 *
 * Copyright (c) <2009> <Denis Kobozev>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Denis Kobozev <d.v.kobozev@gmail.com>
 * @copyright 2009 Denis Kobozev
 * @license   The MIT License
 * }}}
 */

class sdCSVImporterPlugin {
    var $defaults = array(
        'csv_post_title'      => null,
        'csv_post_post'       => null,
        'csv_post_type'       => null,
        'csv_post_excerpt'    => null,
        'csv_post_date'       => null,
        'csv_post_tags'       => null,
        'csv_post_categories' => null,
        'csv_post_author'     => null,
        'csv_post_slug'       => null,
        'csv_post_parent'     => 0,
        
    );

    var $log = array();

    var $timestorage = 0;
    /**
     * Determine value of option $name from database, $default value or $params,
     * save it to the db if needed and return it.
     *
     * @param string $name
     * @param mixed  $default
     * @param array  $params
     * @return string
     */
    function process_option($name, $default, $params) {
        if (array_key_exists($name, $params)) {
            $value = stripslashes($params[$name]);
        } elseif (array_key_exists('_'.$name, $params)) {
            // unchecked checkbox value
            $value = stripslashes($params['_'.$name]);
        } else {
            $value = null;
        }
        $stored_value = get_option($name);
        if ($value == null) {
            if ($stored_value === false) {
                if (is_callable($default) &&
                    method_exists($default[0], $default[1])) {
                    $value = call_user_func($default);
                } else {
                    $value = $default;
                }
                add_option($name, $value);
            } else {
                $value = $stored_value;
            }
        } else {
            if ($stored_value === false) {
                add_option($name, $value);
            } elseif ($stored_value != $value) {
                update_option($name, $value);
            }
        }
        return $value;
    }

    /**
     * Plugin's interface
     *
     * @return void
     */
    function form() {
        $opt_draft = $this->process_option('csv_importer_import_as_draft',
            'publish', $_POST);
        $opt_cat = $this->process_option('csv_importer_cat', 0, $_POST);

        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            $this->post(compact('opt_draft', 'opt_cat'));
        }

        // form HTML {{{
?>

<div class="wrap">
	<h2>Import CSV</h2>
	<form class="add:the-list: validate" method="post" enctype="multipart/form-data">
		<!-- Import as draft -->
		<p>
			<input name="_csv_importer_import_as_draft" type="hidden" value="publish" />
			<label><input name="csv_importer_import_as_draft" type="checkbox" <?php if ('draft' == $opt_draft) { echo 'checked="checked"'; } ?> value="draft" /> Import posts as drafts</label>
		</p>
		<!-- File input -->
		<p><label for="csv_import">Upload file:</label><br/>
			<input name="csv_import" id="csv_import" type="file" value="" aria-required="true" /></p>
			<p class="submit"><input type="submit" class="button" name="submit" value="Import" /></p>
	</form>
	<h2>Schedule Display</h2>
	<p>Once custom taxonomies are set up in your theme's functions.php file or by using a 3rd party plugin, <code>csv_ctax_{taxonomy_name}</code> columns can be used to assign imported data to the taxonomies.</p>

	<h3>Non-hierarchical Taxonomies</h3>
	<p>The syntax for non-hierarchical taxonomies is straightforward and is essentially the same as the <code>csv_post_tags</code> syntax.</p>

</div><!-- end wrap -->

<?php
        // end form HTML }}}

    }

    function print_messages() {
        if (!empty($this->log)) {

        // messages HTML {{{
?>

<div class="wrap">
    <?php if (!empty($this->log['error'])): ?>

    <div class="error">

        <?php foreach ($this->log['error'] as $error): ?>
            <p><?php echo $error; ?></p>
        <?php endforeach; ?>

    </div>

    <?php endif; ?>

    <?php if (!empty($this->log['notice'])): ?>

    <div class="updated fade">

        <?php foreach ($this->log['notice'] as $notice): ?>
            <p><?php echo $notice; ?></p>
        <?php endforeach; ?>

    </div>

    <?php endif; ?>
</div><!-- end wrap -->

<?php
        // end messages HTML }}}

            $this->log = array();
        }
    }

    /**
     * Handle POST submission
     *
     * @param array $options
     * @return void
     */
    function post($options) {
        if (empty($_FILES['csv_import']['tmp_name'])) {
            $this->log['error'][] = 'No file uploaded, aborting.';
            $this->print_messages();
            return;
        }

        set_time_limit(120);
        require_once 'File_CSV_DataSource/DataSource.php';

        $time_start = microtime(true);
        $csv = new File_CSV_DataSource;
        $file = $_FILES['csv_import']['tmp_name'];
        $this->stripBOM($file);
        $csv->load($file);

            
        
        // pad shorter rows with empty values
        $csv->symmetrize();

        // WordPress sets the correct timezone for date functions somewhere
        // in the bowels of wp_insert_post(). We need strtotime() to return
        // correct time before the call to wp_insert_post().
        $tz = get_option('timezone_string');
        if ($tz && function_exists('date_default_timezone_set')) {
            date_default_timezone_set($tz);
        }

        $skipped = 0;
        $imported = 0;
        $comments = 0;
        
        //$csv->headers = array_map('trim', $csv->headers);
        //$emptyRemoved = array_filter($csv->headers);
       
        
        
        foreach ($csv->connect() as $csv_data) {
            
         
            if ($post_id = $this->create_post($csv_data, $options)) {
                $imported++;
            } else {
                $skipped++;
            }
            
        }

        if (file_exists($file)) {
            @unlink($file);
        }

        $exec_time = microtime(true) - $time_start;

        if ($skipped) {
            $this->log['notice'][] = "<b>Skipped {$skipped} posts (most likely due to empty title, body and excerpt).</b>";
        }
        $this->log['notice'][] = sprintf("<b>Imported {$imported} posts and {$comments} comments in %.2f seconds.</b>", $exec_time);
        $this->print_messages();
    }

    function create_post($data, $options) {
        extract($options);
        global $timestorage;
        $data = array_merge($this->defaults, $data);
        $type = 'sdprogram';
        $valid_type = (function_exists('post_type_exists') &&
            post_type_exists($type)) || in_array($type, array('post', 'page'));

        if (!$valid_type) {
            $this->log['error']["type-{$type}"] = sprintf(
                'Unknown post type "%s".', $type);
        }
             

        $new_post = array(
            'post_title'   => convert_chars($data['Series Name']) ." ". convert_chars($data['Date']),
            'post_status'  => $opt_draft,
            'post_type'    => $type,
            
        );

        if ($data['Time (Eastern)'] != ''){
            $airtime = $data['Time (Eastern)'];
            $timestorage = $airtime;
        } else {
            $airtime = $timestorage;
        }

        
        $airtime = sprintf( '%04s', $airtime ); // Add leading zero
        $airtime = substr_replace($airtime, ':', 2, 0); // Correctly format to 24:00 
       // $airtime = date( 'g:i A' ,$airtime); // Format to display
        $date = $data['Date'];
        $datetime = $date.' '.$airtime;
        
        if (convert_chars($data['Episode Name'] != '')){                    
            // create!
            $id = wp_insert_post($new_post);

            add_post_meta($id, '_sd_seriesname_text' , convert_chars($data['Series Name']), true);
            add_post_meta($id, '_sd_episodename_text' , convert_chars($data['Episode Name']), true);
            add_post_meta($id, '_sd_runningtime_textsmall' , convert_chars($data['Running Time']), true);
            add_post_meta($id, '_sd_programnumber_textsmall' , convert_chars($data['Program #']), true);
            add_post_meta($id, '_sd_airdate_textdate_timestamp' , strtotime($datetime), true);
            add_post_meta($id, '_sd_orgdate_textsmall' , convert_chars($data['Original Broadcast Date']), true);
            add_post_meta($id, '_sd_weekday_textsmall' , $datetime, true);


            if ('page' !== $type && !$id) {
                // cleanup new categories on failure
                foreach ($cats['cleanup'] as $c) {
                    wp_delete_term($c, 'category');
                }
            }
        } else {
            $id = 0;
        }
        return $id;
    }
    /**
     * Return id of first image that matches the passed filename
     * @param string $filename csv_post_image cell contents
     * 
     */
function get_image_id($filename){
    //try searching titles first
    $filename =  preg_replace('/\.[^.]*$/', '', $filename);
     $filename = strtolower(str_replace(' ','-',$filename));
     $args = array('post_type' => 'attachment','name'=>$filename);
    $results = get_posts($args);
    //$results = get_page_by_title($filename, ARRAY_A, 'attachment');
    if(count($results)==0) return;
     if(count($results)==1) return $results[0]->ID;
    elseif(count($results)>1) {
        foreach($results as $result){
        if(strpos($result->guid,$filename))
                return $result->ID;
        }
    }
    
    
}
    /**
     * Return an array of category ids for a post.
     *
     * @param string  $data csv_post_categories cell contents
     * @param integer $common_parent_id common parent id for all categories
     * @return array category ids
     */
    function create_or_get_categories($data, $common_parent_id) {
        $ids = array(
            'post' => array(),
            'cleanup' => array(),
        );
        $items = array_map('trim', explode(',', $data['csv_post_categories']));
        foreach ($items as $item) {
            if (is_numeric($item)) {
                if (get_category($item) !== null) {
                    $ids['post'][] = $item;
                } else {
                    $this->log['error'][] = "Category ID {$item} does not exist, skipping.";
                }
            } else {
                $parent_id = $common_parent_id;
                // item can be a single category name or a string such as
                // Parent > Child > Grandchild
                $categories = array_map('trim', explode('>', $item));
                if (count($categories) > 1 && is_numeric($categories[0])) {
                    $parent_id = $categories[0];
                    if (get_category($parent_id) !== null) {
                        // valid id, everything's ok
                        $categories = array_slice($categories, 1);
                    } else {
                        $this->log['error'][] = "Category ID {$parent_id} does not exist, skipping.";
                        continue;
                    }
                }
                foreach ($categories as $category) {
                    if ($category) {
                        $term = $this->term_exists($category, 'category', $parent_id);
                        if ($term) {
                            $term_id = $term['term_id'];
                        } else {
                            $term_id = wp_insert_category(array(
                                'cat_name' => $category,
                                'category_parent' => $parent_id,
                            ));
                            $ids['cleanup'][] = $term_id;
                        }
                        $parent_id = $term_id;
                    }
                }
                $ids['post'][] = $term_id;
            }
        }
        return $ids;
    }

    /**
     * Parse taxonomy data from the file
     *
     * array(
     *      // hierarchical taxonomy name => ID array
     *      'my taxonomy 1' => array(1, 2, 3, ...),
     *      // non-hierarchical taxonomy name => term names string
     *      'my taxonomy 2' => array('term1', 'term2', ...),
     * )
     *
     * @param array $data
     * @return array
     */
    function get_taxonomies($data) {
        $taxonomies = array();
        foreach ($data as $k => $v) {
            if (preg_match('/^csv_ctax_(.*)$/', $k, $matches)) {
                $t_name = $matches[1];
                if ($this->taxonomy_exists($t_name)) {
                    $taxonomies[$t_name] = $this->create_terms($t_name,
                        $data[$k]);
                } else {
                    $this->log['error'][] = "Unknown taxonomy $t_name";
                }
            }
        }
        return $taxonomies;
    }
     /**
     * Parse attachment data from the file
     *
     * @param int   $post_id
     * @param array $data
     * @return array
     */
function add_attachments($post_id, $data){
   // $this->log['notice'][]= 'adding attachments for id#'. $post_id;
    $attachments = array();
    foreach ($data as $k => $v) {
            if (preg_match('/^csv_attachment_(.*)$/', $k, $matches)) {
               // $this->log['notice'][] = 'Found this attachment: ' . $matches[1] . ' with this value:' . $data[$k];
                $a_name = $matches[1];
               
                    $attachment[$a_name] = $data[$k];
                   
                    if(preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $data[$k])) {
                        $url = $v;
                        $id = $this->download_attachment($data[$k],$post_id,$a_name);}
                    if($a_name == 'thumbnail' && $id<>''){
                        add_post_meta($post_id, '_thumbnail_id',$id);
                    }
            }
            else if($k=='csv_post_image'){
                $id = $this->get_image_id($v);
                if($id<>'') add_post_meta($post_id, '_thumbnail_id',$this->get_image_id($v));
            }
        } 
        return $attachments;
}
/**
     * Download file from remote URL, save it to the Media Library, and return
     * the attachment id
     *
     * @param string $url
     * @param int  $post_id
     * @param string $desc
     * @return int
     */
function download_attachment($url, $post_id, $desc){
     set_time_limit(10);
    $tmp = download_url( $url );
	 if(strlen(trim($url))<5) return;
	
	// Set variables for storage
	// fix file filename for query strings
	//preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG|wav|mp3|pdf)/', $file, $matches);
	 $file_array = array(
        'name' => basename( $url ),
        'tmp_name' => $tmp
             );


	// If error storing temporarily, unlink
	if ( is_wp_error( $tmp ) ) {
		@unlink($file_array['tmp_name']);
		$file_array['tmp_name'] = '';
	}

	// do the validation and storage stuff
	$id = media_handle_sideload( $file_array, $post_id, $desc );
        
	// If error storing permanently, unlink
	if ( is_wp_error($id) ) {
             $this->log['error'][] = $id->get_error_message() .' : ' . $url;
		@unlink($file_array['tmp_name']);
		return $id;
	}
         //$this->log['notice'][] = 'Downloaded the file. Here\'s the id: ' . $id;

	$src = wp_get_attachment_url( $id );
         //$this->log['notice'][] = 'Saved the file successfully! Here\'s the path: ' . $src ;
    return $id;
}
    /**
     * Return an array of term IDs for hierarchical taxonomies or the original
     * string from CSV for non-hierarchical taxonomies. The original string
     * should have the same format as csv_post_tags.
     *
     * @param string $taxonomy
     * @param string $field
     * @return mixed
     */
    function create_terms($taxonomy, $field) {
        if (is_taxonomy_hierarchical($taxonomy)) {
            $term_ids = array();
            foreach ($this->_parse_tax($field) as $row) {
                list($parent, $child) = $row;
                $parent_ok = true;
                if ($parent) {
                    $parent_info = $this->term_exists($parent, $taxonomy);
                    if (!$parent_info) {
                        // create parent
                        $parent_info = wp_insert_term($parent, $taxonomy);
                    }
                    if (!is_wp_error($parent_info)) {
                        $parent_id = $parent_info['term_id'];
                    } else {
                        // could not find or create parent
                        $parent_ok = false;
                    }
                } else {
                    $parent_id = 0;
                }

                if ($parent_ok) {
                    $child_info = $this->term_exists($child, $taxonomy, $parent_id);
                    if (!$child_info) {
                        // create child
                        $child_info = wp_insert_term($child, $taxonomy,
                            array('parent' => $parent_id));
                    }
                    if (!is_wp_error($child_info)) {
                        $term_ids[] = $child_info['term_id'];
                    }
                }
            }
            return $term_ids;
        } else {
            return $field;
        }
    }

    /**
     * Compatibility wrapper for WordPress term lookup.
     */
    function term_exists($term, $taxonomy = '', $parent = 0) {
        if (function_exists('term_exists')) { // 3.0 or later
            return term_exists($term, $taxonomy, $parent);
        } else {
            return is_term($term, $taxonomy, $parent);
        }
    }

    /**
     * Compatibility wrapper for WordPress taxonomy lookup.
     */
    function taxonomy_exists($taxonomy) {
        if (function_exists('taxonomy_exists')) { // 3.0 or later
            return taxonomy_exists($taxonomy);
        } else {
            return is_taxonomy($taxonomy);
        }
    }

    /**
     * Hierarchical taxonomy fields are tiny CSV files in their own right.
     *
     * @param string $field
     * @return array
     */
    function _parse_tax($field) {
        $data = array();
        if (function_exists('str_getcsv')) { // PHP 5 >= 5.3.0
            $lines = $this->split_lines($field);

            foreach ($lines as $line) {
                $data[] = str_getcsv($line, ',', '"');
            }
        } else {
            // Use temp files for older PHP versions. Reusing the tmp file for
            // the duration of the script might be faster, but not necessarily
            // significant.
            $handle = tmpfile();
            fwrite($handle, $field);
            fseek($handle, 0);

            while (($r = fgetcsv($handle, 999999, ',', '"')) !== false) {
                $data[] = $r;
            }
            fclose($handle);
        }
        return $data;
    }

    /**
     * Try to split lines of text correctly regardless of the platform the text
     * is coming from.
     */
    function split_lines($text) {
        $lines = preg_split("/(\r\n|\n|\r)/", $text);
        return $lines;
    }

    function add_comments($post_id, $data) {
        // First get a list of the comments for this post
        $comments = array();
        foreach ($data as $k => $v) {
            // comments start with cvs_comment_
            if (    preg_match('/^csv_comment_([^_]+)_(.*)/', $k, $matches) &&
                    $v != '') {
                $comments[$matches[1]] = 1;
            }
        }
        // Sort this list which specifies the order they are inserted, in case
        // that matters somewhere
        ksort($comments);

        // Now go through each comment and insert it. More fields are possible
        // in principle (see docu of wp_insert_comment), but I didn't have data
        // for them so I didn't test them, so I didn't include them.
        $count = 0;
        foreach ($comments as $cid => $v) {
            $new_comment = array(
                'comment_post_ID' => $post_id,
                'comment_approved' => 1,
            );

            if (isset($data["csv_comment_{$cid}_author"])) {
                $new_comment['comment_author'] = convert_chars(
                    $data["csv_comment_{$cid}_author"]);
            }
            if (isset($data["csv_comment_{$cid}_author_email"])) {
                $new_comment['comment_author_email'] = convert_chars(
                    $data["csv_comment_{$cid}_author_email"]);
            }
            if (isset($data["csv_comment_{$cid}_url"])) {
                $new_comment['comment_author_url'] = convert_chars(
                    $data["csv_comment_{$cid}_url"]);
            }
            if (isset($data["csv_comment_{$cid}_content"])) {
                $new_comment['comment_content'] = convert_chars(
                    $data["csv_comment_{$cid}_content"]);
            }
            if (isset($data["csv_comment_{$cid}_date"])) {
                $new_comment['comment_date'] = $this->parse_date(
                    $data["csv_comment_{$cid}_date"]);
            }

            $id = wp_insert_comment($new_comment);
            if ($id) {
                $count++;
            } else {
                $this->log['error'][] = "Could not add comment $cid";
            }
        }
        return $count;
    }

    function create_custom_fields($post_id, $data) {
        foreach ($data as $k => $v) {
            // anything that doesn't start with csv_ is a custom field
            if (!preg_match('/^csv_/', $k) && $v != '') {
                 // if value is serialized unserialize it
            if( is_serialized($v) ) {
                $v = unserialize($v);
                // the unserialized array will be re-serialized with add_post_meta()
            }elseif(strpos($v,'::')){
                // import data and serialize it formatted as
                // key::value[]key::value
                $array = explode("[]",$v);
                
                foreach ($array as $lineNum => $line)
{
list($key, $value) = explode("::", $line);
$newArray[$key] = $value;
}
$v = $newArray;
            }
                add_post_meta($post_id, $k, $v);
            }
        }
        
    }

    function get_auth_id($author) {
        if (is_numeric($author)) {
            return $author;
        }
        $author_data = get_user_by('login', $author);
        return ($author_data) ? $author_data->ID : 0;
    }

    /**
     * Convert date in CSV file to 1999-12-31 23:52:00 format
     *
     * @param string $data
     * @return string
     */
    function parse_date($data) {
        $timestamp = strtotime($data);
        if (false === $timestamp) {
            return '';
        } else {
            return date('Y-m-d H:i:s', $timestamp);
        }
    }

    /**
     * Delete BOM from UTF-8 file.
     *
     * @param string $fname
     * @return void
     */
    function stripBOM($fname) {
        $res = fopen($fname, 'rb');
        if (false !== $res) {
            $bytes = fread($res, 3);
            if ($bytes == pack('CCC', 0xef, 0xbb, 0xbf)) {
                $this->log['notice'][] = 'Getting rid of byte order mark...';
                fclose($res);

                $contents = file_get_contents($fname);
                if (false === $contents) {
                    trigger_error('Failed to get file contents.', E_USER_WARNING);
                }
                $contents = substr($contents, 3);
                $success = file_put_contents($fname, $contents);
                if (false === $success) {
                    trigger_error('Failed to put file contents.', E_USER_WARNING);
                }
            } else {
                fclose($res);
            }
        } else {
            $this->log['error'][] = 'Failed to open file, aborting.';
        }
    }
}


function sdcsv_admin_menu() {
    require_once ABSPATH . '/wp-admin/admin.php';
    $plugin = new sdCSVImporterPlugin;
    add_management_page('edit.php', 'Schedule Display', 'manage_options', __FILE__,
        array($plugin, 'form'));
}

add_action('admin_menu', 'sdcsv_admin_menu');

?>
