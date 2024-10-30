<?php
if(!defined('ABSPATH')) {
  die('You are not allowed to call this page directly.');
}

//hook into the init action and call create_book_taxonomies when it fires
add_action( 'init', 'holocam_create_privacy_taxonomy', 0 );

//create a custom taxonomy name it topics for your posts

function holocam_create_privacy_taxonomy() {


  $labels = array(
    'name' => _x( 'Privacy', 'taxonomy general name' ),
    'singular_name' => _x( 'Privacy', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Privacy' ),
    'all_items' => __( 'All Privacy' ),
    'parent_item' => __( 'Parent Privacy' ),
    'parent_item_colon' => __( 'Parent Privacy:' ),
    'edit_item' => __( 'Edit Privacy' ), 
    'update_item' => __( 'Update Privacy' ),
    'add_new_item' => __( 'Add New Privacy' ),
    'new_item_name' => __( 'New Privacy Name' ),
    'menu_name' => __( 'Privacy' ),
  ); 	

// register the taxonomy

  register_taxonomy('privacy',array('post'), array(
    'hierarchical' => false,
    'labels' => $labels,
    'show_ui' => false,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'privacy' ),
  ));

  wp_insert_term(
  'Public', // the term 
  'privacy', // the taxonomy
  array(
    'description'=> 'A public panorama image',
    'slug' => 'public'
  )
  );
  wp_insert_term(
  'Unlisted', // the term 
  'privacy', // the taxonomy
  array(
    'description'=> 'An unlisted panorama image',
    'slug' => 'unlisted'
  )
  );
  wp_insert_term(
  'Private', // the term 
  'privacy', // the taxonomy
  array(
    'description'=> 'A private panorama image',
    'slug' => 'private'
  )
  );
 
}

add_action('init','post_type_holocamimage');

function post_type_holocamimage(){
  register_post_type(
    'holocamimage',
    
    array(
      'labels' => array(
          'name' => __('Panorama Images','holocam'),
          'singular_name' => __('Panorama Image Data', 'holocam'),
          'add_new_item' => __('Add new','holocam')
      ),
      'public' => false,
      'show_ui' =>true,
      'menu_position' => 27,
       'supports'=>array(
        'title'
       ),
       'taxonomies' => array( 'privacy' )
	)
	);

}
add_action('restrict_manage_posts', 'holocam_filter_post_type_by_taxonomy');
function holocam_filter_post_type_by_taxonomy() {
	global $typenow;
	$post_type = 'holocamimage';
	$taxonomy  = 'privacy';
	if ($typenow == $post_type) {
		$selected      = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
		$info_taxonomy = get_taxonomy($taxonomy);
		wp_dropdown_categories(array(
			'show_option_all' => __('Show All '.$info_taxonomy->label, 'holocam'),
			'taxonomy'        => $taxonomy,
			'name'            => $taxonomy,
			'orderby'         => 'name',
			'selected'        => $selected,
			'show_count'      => true,
			'hide_empty'      => false,
		));
	};
}

add_filter('parse_query', 'holocam_convert_id_to_term_in_query');
function holocam_convert_id_to_term_in_query($query) {
	global $pagenow;
	$post_type = 'holocamimage'; 
	$taxonomy  = 'privacy';
	$q_vars    = &$query->query_vars;
	if ( $pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $post_type && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0 ) {
		$term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
		$q_vars[$taxonomy] = $term->slug;
	}
} 

?>