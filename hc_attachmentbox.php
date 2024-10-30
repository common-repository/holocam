<?php
if(!defined('ABSPATH')) {
  die('You are not allowed to call this page directly.');
}

add_action('add_meta_boxes','holocam_addmetaboxes');

add_action( 'save_post', 'holocam_zip_upload' );
add_action( 'admin_menu','holocam_settingspage');
add_action( 'admin_init','holocam_apikey_update');
add_action( 'admin_init','holocam_update_privacy');
add_action( 'save_post', 'holocam_save_desc' );
add_action( 'admin_init','holocam_delzip_update');
add_action( 'plugins_loaded','holocam_load_textdomain');
add_action( 'init','holocam_init');

function holocam_addmetaboxes() {
  // Define the custom attachment for holocamimage
    add_meta_box(
        'holocam_custom_attachment',
        __('Attachment','holocam'),
        'holocam_custom_attachment',
        'holocamimage',
        'advanced'
    );
    add_meta_box(
        'hc_description',
        __('Description','holocam'),
        'holocam_desc_editor',
        'holocamimage',
        'advanced'
    );
    add_meta_box(
        'holocam_privacy',
        'Privacy',
        'holocam_privacy',
        'holocamimage',
        'advanced'
    );
}

function holocam_settingspage() {
  add_menu_page(
        'holocam.io',
        __( 'holocam.io settings', 'holocam' ),
        'edit_posts',
        'Holocamsettings',
        'holocam_settings_page',
        'dashicons-lock',
        '26'
    );
}
/**
* Callback for the Unsub page.
*/
function holocam_settings_page() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'hcapikey';
  $hc_api_key = $wpdb->get_results( "SELECT * FROM $table_name");
  echo '<h2>'.__('holocam.io panorama images settings','holocam').'</h2>';
  echo '<h2>'.__('API Key of product','holocam').':</h2>';
  echo '<form name="hc_apikeyform" method="post">';
  echo wp_nonce_field('hc-apikey','hcapikeynonce');
  echo 'API key: ';
  echo '<input type="text" name="apkey" value="'.$hc_api_key[0]->apikey.'">';
  echo '<input type="submit" value="update key" name="hcapikeyupdate">';
  echo '</form>';
  echo '<h2>Shortcodes:</h2>';
  echo '<strong>[hc_show_img name=xyz]</strong> '.__('shows preview of holocam.io panorama image with title=xyz','holocam').'<br />';
  echo '<strong>[holocam num=x]</strong> '.__('lists previews of count x last public holocam.io panorama images. Default: last 10 images.','holocam').'<br />';
  echo '<strong>[holocam_insert_viewer name=xyx]</strong> '.__('inserts viewer into post and shows the panorama with title=xyz (just like embed code from holodeck.cloud but without iframe)','holocam').'<br />';
  echo '<form name="hc_zip_check" method="post">';
  echo wp_nonce_field('hc-zip-check','hczipchecknonce');
  echo '<br />'.__('Delete holocam.io panorama image zip file after upload and extracted it: ','holocam');
  echo '<input type="checkbox" name="zipdel" value="delzip" ';
  $hc_zipdel_option = get_option('holocam_zipdel');
  if($hc_zipdel_option) {
    echo ' checked ';
  }
  echo '>';
  echo '<input type="submit" name="hc_zipdelupdate" value="'.__('update','holocam').'">';
  echo '</form>';
}

function holocam_apikey_update() {
  //it is a planned future function, not yet implemented
}

function holocam_delzip_update() {
  if(isset($_POST['hc_zipdelupdate'])) {
    if(!check_admin_referer('hc-zip-check','hczipchecknonce'))wp_die('Failed security check');
    if(isset($_POST['zipdel'])) {
      update_option( 'holocam_zipdel', 1 );
    }
    else update_option( 'holocam_zipdel', 0 );
  }
}

function holocam_custom_attachment() {    
    $html = '<p class="description">';
        $html .= 'Panorama Image Data in Zip packed';
    $html .= '</p>';
     
    $previewlink = get_post_meta(get_the_ID(), 'hc_panorama_url', true);
    if($previewlink) {
      $hcthumb = get_post_meta(get_the_ID(), 'hc_panorama_thumb', true);
      echo '<img src="'.$hcthumb.'">';
      echo '<br /><a href="'.$previewlink.'" target="_blank">'.__('preview','holocam').'</a><br />or';
      }
    $aid = get_post_meta(get_the_ID(), 'holocam_custom_attachment', true);
    
		$zip_image_url = wp_get_attachment_url( $aid );
    $zip_image_file = get_attached_file( $aid );
    echo '<br />'.__('Upload holocam.io Image zip package','holocam').':<br />'.$zip_image_url.'';
    echo '<br /><span class="info">'.__('!Max Upload file size limit and post_max_size are defined from your host. If your zip data is bigger than that, you should first incrase the limit.','holocam').'</span>';
    echo '<br />'.__('Actually the maximum allowed size for uploaded files and for post size: ','holocam').ini_get("upload_max_filesize").__(' and ','holocam').ini_get("post_max_size");
    $html = '<p><form name="hcuploadform">';
    $html .= wp_nonce_field('hc-upload','hcuploadnonce');
    $html .= '<input type="file" name="ufile" id="ufile" accept=".zip"><br />';
    $html .= '<input type="submit" value="upload" name="hcupload">';

    $html .= '<input type="hidden" value="'.$zip_image_file.'" name="zipfilename">';
    //$html .= '<input type="submit" value="extract" name="unzipfile">';
    $html .= '</form></p>';
    echo $html;
 
} // end wp_custom_attachment

function holocam_desc_editor() 
{
    //so, dont ned to use esc_attr in front of get_post_meta
    $value2 = get_post_meta(get_the_ID(), 'hc_description', true);
    wp_editor( htmlspecialchars_decode($value2), 'desc_editor', $settings = array('textarea_name'=>'descInput','media_buttons'=>false,'textarea_rows'=>4) );
}

function holocam_save_desc() 
{                  
    if (!empty($_POST['descInput']))
        {
        $datta=htmlspecialchars($_POST['descInput']);
        update_post_meta(get_the_ID(), 'hc_description', $datta );
        }
} 

function holocam_privacy() {
  $terms = get_terms( 'privacy', 'orderby=count&hide_empty=0' );
  $pr_terms = wp_get_object_terms( get_the_ID(),  'privacy' );
  $html = '<form name="changeprivacy" method="post">';
  $html .= '<ul>';
  if(isset($pr_terms[0])) {
    foreach ( $terms as $term ) {
        
        $html .= '<li><input type="radio" name="privacy" value="'.$term->term_id.'"';
        if($term->term_id==$pr_terms[0]->term_id){
          $html .= ' checked ';
        }
        $html .= '>'.$term->name.'<br></li>';
    }
  }
    $html .= '</ul>';
    $html .= '<input type="submit" value="update" name="updateprivacy">';
    $html .= wp_nonce_field('hc-changeprivacy','hcprivacynonce');
    $html .= '</form>';     
    echo $html;
}

function holocam_update_privacy() {
   if(isset($_REQUEST['updateprivacy'])){
    if(!check_admin_referer('hc-changeprivacy','hcprivacynonce'))wp_die('Failed security check');
      $category = get_term_by('term_id', $_REQUEST['privacy'], 'privacy');
      if(isset($_REQUEST['post'])) {
        wp_set_object_terms( $_REQUEST['post'], $category->name, 'privacy' );
      } 
    }
}

function holocam_add_edit_form_multipart_encoding() {

    echo ' enctype="multipart/form-data"';

}
add_action('post_edit_form_tag', 'holocam_add_edit_form_multipart_encoding');
 

function holocam_zip_upload() {
  if(isset($_REQUEST['hcupload'])){
    
    if ( ! function_exists( 'wp_handle_upload' ) ) {
      require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }
    if(!check_admin_referer('hc-upload','hcuploadnonce'))wp_die('Failed security check');
    $uploadedfile = $_FILES['ufile'];

    $upload_overrides = array( 'test_form' => false );

    $movefile = wp_handle_upload( $uploadedfile, $upload_overrides);

    $uploadok = 0;
    if ( $movefile && !isset( $movefile['error'] ) ) {
      //echo "File is valid, and was successfully uploaded.<br>";
      $uploadok = 1;
    } 
    else {
    /**
     * Error generated by _wp_handle_upload()
     * @see _wp_handle_upload() in wp-admin/includes/file.php
     */
      echo $movefile['error'];
    }
    if($uploadok) {
    $post_id = get_the_ID();
      $title = $_FILES["ufile"]['tmp_name'];
            $attachment = array(
                'post_mime_type' =>$_FILES["ufile"]['type'],
                'post_title' => addslashes($title),
                'post_content' => '',
                'post_status' => 'inherit',
                'post_parent' => $post_id
            );
            //create attachment & update metadata
            $attach_id = wp_insert_attachment( $attachment, $movefile['file'] );
            
            // Now, update the post meta to associate the new pdf with the post
            update_post_meta($post_id, 'holocam_custom_attachment', $attach_id);
            //auto unzip after upload:
            extracthczip($movefile['file']);
    }    
  }
  if(isset($_REQUEST['unzipfile'])){
     extracthczip($_REQUEST['zipfilename']);
  }  
  if(isset($_REQUEST['updateprivacy'])){
    $category = get_term_by('term_id', $_REQUEST['privacy'], 'privacy');
     wp_set_object_terms( get_the_ID(), $category->name, 'privacy' );
  }
  
  $pr_terms = wp_get_object_terms( get_the_ID(),  'privacy' );
    
  if(empty( $pr_terms )) {
    $category = get_term_by('name', 'Public', 'privacy');
    wp_set_object_terms( get_the_ID(), $category->name, 'privacy' );
  }
}

function dir_tree($dir) {
        //http://www.php.net/manual/de/function.scandir.php#102505
        $paths = array();
        $stack[] = $dir;
        while ($stack) {
            $thisdir = array_pop($stack);
            if ($dircont = scandir($thisdir)) {
                $i=0;
                while (isset($dircont[$i])) {
                    if ($dircont[$i] !== '.' && $dircont[$i] !== '..') {
                        $current_file = "{$thisdir}/{$dircont[$i]}";
                        if (is_file($current_file)) {
                            $paths[] = "{$thisdir}/{$dircont[$i]}";
                        } elseif (is_dir($current_file)) {
                            $paths[] = "{$thisdir}/{$dircont[$i]}";
                            $stack[] = $current_file;
                        }
                    }
                    $i++;
                }
            }
        }
        return $paths;
}

function extracthczip($zipfilename) {
  $panoramaurl = '';
  update_post_meta(get_the_ID(),'holocam_panorama_url',$panoramaurl);
  WP_Filesystem();
  //$dirname = get_the_title();
  $dirname = 'ziptmp';
  $destination = wp_upload_dir();  
  $destination_path = $destination['path'].'/'.$dirname.'/';
  $unzipfile = unzip_file( $zipfilename, $destination_path);
   if ( $unzipfile ) {
      //Successfully unzipped the file 
      $filepaths = dir_tree($destination_path);
      $newdirname = '';
     
      foreach( $filepaths as $k => $filepath ) {
                    if( is_file($filepath) ) {
                        $file = array();
                        $file['name'] = basename( $filepath );
                        $file['size'] = filesize( $filepath );
                        $file['tmp_name'] = $filepath;
                        $unzipped["unzipped_$k"] = $file;
                        $ff = explode('.',$file['name']);
                        if($ff[1]=='html'){
                          $newdirname = $ff[0];
                          $panoramaurl = $filepath;
                          }
                        $tt = explode('_',$ff[0]);
                        if($tt[1]=='thumb')$panoramathumb = $filepath;
                    }
                }
          $new_path = str_replace($dirname,$newdirname,$destination_path);
          rename($destination_path,$new_path);
          $panoramaurl = str_replace(WP_CONTENT_DIR,'',$panoramaurl);
          $panoramaurl = str_replace($dirname,$newdirname,$panoramaurl);
          $panoramaurl = WP_CONTENT_URL.$panoramaurl;
          $panoramathumb = str_replace(WP_CONTENT_DIR,'',$panoramathumb);
          $panoramathumb = str_replace($dirname,$newdirname,$panoramathumb);
          $panoramathumb = WP_CONTENT_URL.$panoramathumb;
   
   update_post_meta(get_the_ID(),'hc_panorama_url',$panoramaurl);
   update_post_meta(get_the_ID(),'hc_panorama_thumb',$panoramathumb);
   $hc_zipdel_option = get_option('holocam_zipdel');
   //checking if delete the zip file
    if($hc_zipdel_option) {
      $aid = get_post_meta(get_the_ID(), 'hc_custom_attachment', true);
      wp_delete_attachment( $aid );
    }      
   } else {
      echo 'There was an error unzipping the file.';       
   }
}

function holocam_preview($atts) {
  //shortcode
  wp_reset_postdata();
  
  $out = '';
  $args = array( 'post_type' => 'holocamimage', 'posts_per_page'=>-1 );
  $loop = new WP_Query( $args );
     while ( $loop->have_posts() ) : $loop->the_post();
      	$hc_id = get_the_ID();
        $pr_terms = wp_get_object_terms( $hc_id,'privacy' );
        $atts = shortcode_atts( array(
		        'name' => 'none',
	       ), $atts, 'hc_show_img' );

	      if($atts['name']!='none') {
          $hc_title=get_the_title();
          if($hc_title==$atts['name']) {
            $mid = get_post_meta($hc_id, 'hc_panorama_url', true);
            $hcthumb = get_post_meta($hc_id, 'hc_panorama_thumb', true);
            $descval = get_post_meta($hc_id, 'hc_description', true);
            $out .= '<div class="hc_preview_area" id="hcio-'.$hc_id.'">';
            $out .= '<a href="'.$mid.'"><h2 class="hc_title">'.esc_html($hc_title).'</h2><img src="'.$hcthumb.'"></a>'.'<br />';
            $out .= '<p class="hc_preview_description">'.$descval.'</p>';
            $out .= '</div>';
            }
        }
        if($atts['name']=='none' && $pr_terms[0]->name=='Public') {
        //List Public only
        $hc_title=get_the_title();
        $mid = get_post_meta($hc_id, 'hc_panorama_url', true);
        $hcthumb = get_post_meta($hc_id, 'hc_panorama_thumb', true);
        $descval = get_post_meta($hc_id, 'hc_description', true);
        $out .= '<div class="hc_preview_area" id="hcio-'.$hc_id.'">';
        $out .= '<a href="'.$mid.'"><h2>'.esc_html($hc_title).'</h2><img src="'.$hcthumb.'"></a>'.'<br />';
        $out .= '<p class="hc_preview_description">'.$descval.'</p>';
        $out .= '</div>';
        }
      endwhile;
      wp_reset_postdata();
      
  return $out;
}

function holocam_list($atts) {
  //shortcode
  wp_reset_postdata();
  
  $out = '';
  $args = array( 'post_type' => 'holocamimage', 'orderby' => 'id', 'order' => 'DESC');
  $loop = new WP_Query( $args );
     while ( $loop->have_posts() ) : $loop->the_post();
      	$hc_id = get_the_ID();
        $pr_terms = wp_get_object_terms( $hc_id,'privacy' );
        $atts = shortcode_atts( array(
		        'num' => 10,
	       ), $atts, 'holocam' );

	      $hc_count = 0;
        if($pr_terms[0]->name=='Public' && $hc_count<$atts['num']) {
        //List Public only
        $hc_count++;
        $hc_title=get_the_title();
        $mid = get_post_meta($hc_id, 'hc_panorama_url', true);
        $hcthumb = get_post_meta($hc_id, 'hc_panorama_thumb', true);
        $descval = get_post_meta($hc_id, 'hc_description', true);
        $out .= '<div class="hc_preview_area" id="hcio-'.$hc_id.'">';
        $out .= '<a href="'.$mid.'"><h2 class="hc_title">'.esc_html($hc_title).'</h2><img src="'.$hcthumb.'"></a>'.'<br />';
        $out .= '<p class="hc_preview_description">'.$descval.'</p>';
        $out .= '</div>';
        }
      endwhile;
      wp_reset_postdata();
      
  return $out;
}

function hc_get_panorama_json_url($name) {
/*Get full url from holocam panorama image control json file
   * return .json url
  */
     $args = array( 'post_type' => 'holocamimage', 'posts_per_page'=>-1 );
  $loop = new WP_Query( $args );
  $mid = 'no_url';
     while ( $loop->have_posts() ) : $loop->the_post();
      	$hc_id = get_the_ID();
         $hc_title=get_the_title();

	      if($name==$hc_title) {
          $mid = get_post_meta($hc_id, 'hc_panorama_url', true);
           $midjson = str_replace('html','json',$mid);
           
           wp_reset_postdata();
           return $midjson;
        }
        endwhile;
        wp_reset_postdata();  
        return $mid;
}

function holocam_insert_custom_viewer_tag($holojson) { 
  /*Insert viewer to Post
  */
 
  $base_url = get_option( 'siteurl' );
  
  $plugin_dir = $base_url . '/wp-content/plugins/';
  
  $holoviewer = $plugin_dir."holocam/".'holocamViewer.js';
 
   
   echo '<div id="customHolocamViewer" style=""></div>
		<script type="text/javascript" src="'.$holoviewer.'"></script>
		<script type="text/javascript">holocamViewer.insert("customHolocamViewer","'.$holojson.'",{html5:"disable_webgl_warning"});</script>';
}

function holocam_insertholoviewer($atts) {
  /*Shortcode
   * 
  */
  $midjson = "";
  $atts = shortcode_atts( array(
		        'name' => 'none',
	       ), $atts, 'holocam_insert_viewer' );

	      if($atts['name']!='none') {
           $midjson = hc_get_panorama_json_url($atts['name']);
           if($midjson!='no_url') {
            holocam_insert_custom_viewer_tag($midjson);
           }
           else echo "Panorama json file not found";
        }
}


function holocam_load_textdomain() {
  load_plugin_textdomain( 'holocam', false, dirname( plugin_basename( __FILE__ ) ).'/lang' );
}

function holocam_init() {
  wp_register_style('hc_style', plugins_url('hc_style.css',__FILE__ ));
  wp_enqueue_style('hc_style');
}

add_shortcode('hc_show_img', 'holocam_preview');
add_shortcode('holocam', 'holocam_list');
add_shortcode('holocam_insert_viewer', 'holocam_insertholoviewer');
 
 ?>