<?php
/*
 * Plugin Name: Vertical Carousel
 * Description: Simple slideshow
 * 
 * Version: 1.0
 * Author: Andrea Acosta-Duarte
 * Author URI: http://www.n-somnium.com.com/
 * License: GPL2
 */

add_action('init', 'slide_init'); 
add_action('save_post', 'slide_save');
register_activation_hook( __FILE__, 'slide_flush_rewrite' );
register_deactivation_hook( __FILE__, 'slide_flush_rewrite' );
register_activation_hook(__FILE__, 'slide_add_defaults');
register_uninstall_hook(__FILE__, 'slide_delete_plugin_options');
add_action("widgets_init","slide_widget_init");

/*
 * Creates the new "slide" post type and registers it in wordpress
 */ 
function slide_init()
{
	//register the post type
	$labels = array(
		'name' 					=> _x('Slides', 'post type general name', 'slide'),
		'singular_name' 		=> _x('Slides', 'post type singular name', 'slide'),
		'menu_name' 			=> _x('Carousel Slides', 'admin menu', 'slide'),
		'name_admin_bar' 		=> _x('Slides', 'add new on admin bar', 'slide'),
		'add_new' 				=> _x('Add New', 'slide', 'slide'),
		'add_new_item' 			=> __('Add a New Slide', 'slide'),
		'new_item'				=> __('New Slides', 'slide'),
		'edit_item' 			=> __('Edit Slides', 'slide'),
		'view_item'				=> __('View Slides', 'slide'),
		'all_items'				=> __('All Slides', 'slide'),
		'search_items'			=> __('Search Slides', 'slide'),
		'parent_item_colon' 	=> __('Parent Slides:', 'slide'),
		'not_found' 			=> __('No slides found', 'slide'),
		'not_found_in_trash'	=> __('No slides found in Trash', 'slide'), 
	);
	$args = array(
		'labels' 				=> $labels,
		'public' 				=> false,
		'publicly_queryable' 	=> false,
		'show_ui' 				=> true, 
		'show_in_menu' 			=> true, 
		'query_var'				=> true,
		'rewrite' 				=> array('slug' => 'slide'),
		'capability_type'		=> 'post',
		'has_archive'			=> true, 
		'hierarchical'			=> false,
		'menu_position'			=> null,
		'supports'				=> array('title', 'editor'),
		'register_meta_box_cb'	=> 'slide_addfields',
	); 
	register_post_type('slide', $args);
	
	//register the taxonomy type
	$labels = array(
		'name'                       => _x( 'Slides', 'taxonomy general name' ),
		'singular_name'              => _x( 'Slides', 'taxonomy singular name' ),
		'search_items'               => __( 'Search Slides' ),
		'popular_items'              => __( 'Popular Slides' ),
		'all_items'                  => __( 'All Slides' ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit Slides' ),
		'update_item'                => __( 'Update Slides' ),
		'add_new_item'               => __( 'Add New Slides' ),
		'new_item_name'              => __( 'New Slides Name' ),
		'separate_items_with_commas' => __( 'Separate slides with commas' ),
		'add_or_remove_items'        => __( 'Add or remove slides' ),
		'choose_from_most_used'      => __( 'Choose from the most used slides' ),
		'not_found'                  => __( 'No slides found.' ),
		'menu_name'                  => __( 'Slides' ),
	);
	$args = array(
		'hierarchical'          => false,
		'labels'                => $labels,
		'show_ui'               => false,
		'show_admin_column'     => false,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'slide' ),
	);
	register_taxonomy( 'slide', 'slide', $args );
}

add_action( 'admin_menu', 'slide_admin_menu' );
function slide_admin_menu() {
  global $menu;
  foreach ( $menu as $key => $val ) {
    if ( __( 'Carousel Slides') == $val[0] ) {
      $menu[$key][6] = 'dashicons-format-gallery';
    }
  }
}
 
function slide_widget_init()
{
    register_widget("Slides_Widget");
}
 
/*
 * Force a refresh of the rewrite rules
 */
function slide_flush_rewrite()
{
	//flush re-write rules to ensure we don't get 404s
	global $wp_rewrite;
 $wp_rewrite->flush_rules();
}

/*
 * Sets up the default plugin values on activation: For future improvement
 */
function slide_add_defaults() {
	$tmp = get_option('slide_options');
  if((!is_array($tmp))) {
		delete_option('slide_options'); 
		$arr = array(	"slide_version" => "1.0",
						"slide_speed" => "0",
						"slide_width" => "500",
						"title_disabled" => "0",
						"description_disabled" => "0",
						"thumb_disabled" => "0",
						"slide_css" => "",
		);
		update_option('slide_options', $arr);
	}
}

/*
 * Removes the plugin options on deactivate
 */
function slide_delete_plugin_options() {
	delete_option('slide_options');
}

/*
 * Adds the description field using meta boxes
 */
function slide_addfields()
{
	add_meta_box('slide-meta','Slides Information','slide_meta','slide','normal','high');
	do_meta_boxes('slide-meta','normal',null);
}

/*
 * Adds the html to the admin page for the slide description
 */
function slide_meta()
{
	//used to get to the plugin folder
	$base = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
	global $post;
	$slide_description = get_post_meta($post->ID, 'slide_description', true);
	$slide_thumb = get_post_meta($post->ID, 'slide_thumb', true);
	
	//create a nonce for security
	echo '<input type="hidden" name="slide_noncename" id="slide_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	?>
		<div class="inside">
			<div class="form-field">
				<label for="slide_description"><img src="<?php echo $base; ?>/description.png" alt="Slide Description"/> Slide Description: </label>
				<input type="text" name="slide_description" tabindex="3" style="width: 100%;" value="<?php echo $slide_description; ?>"/>
				
				<div style="margin-bottom:10px;">&nbsp;</div>
				
				<label for="slide_thumb"><img src="<?php echo $base; ?>/thumbnail.png" alt="Slides Thumb"/> Slide Thumbnail: </label>  		<input id="carousel_slide_thumb" type="text" name="carousel_slide_thumb" value="<?php echo $slide_thumb; ?>" />
				<input class="upload_image_button" type="button" value="Upload Thumbnail Image" />				
			</div> <!-- /form-field -->
		</div> <!-- /inside -->
<!-- plugin admin script image: start -->
<script type="text/javascript">
var upload_image_button=false;
jQuery(document).ready(function() {
 
	//uploader script image
	jQuery('.upload_image_button').click(function() {
		upload_image_button =true;
		formfieldID=jQuery(this).prev().attr("id");
		formfield = jQuery("#"+formfieldID).attr('name');
		tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
					if(upload_image_button==true){
	
													var oldFunc = window.send_to_editor;
													window.send_to_editor = function(html) {
	
													//imgurl = jQuery('img', html).attr('src');
													imgurl = jQuery("<div>" + html + "</div>").find('img').attr('src');
													jQuery("#"+formfieldID).val(imgurl);
														tb_remove();
													window.send_to_editor = oldFunc;
													}
					}
					upload_image_button=false;
	});
})
</script>
<!-- plugin admin script image: end -->  		
	<?php
}

/*
 * Called on a post save, used to save the slide description using metadata
 */
function slide_save($post_id)
{	
	if ( !wp_verify_nonce( $_POST['slide_noncename'], plugin_basename(__FILE__) ))
		return $post_id;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		return $post_id;
			
	// Check permissions
	if ( !current_user_can( 'edit_posts', $post_id ) )
		return $post_id;
	
	$slide_description = $_POST['slide_description'];
	$slide_thumb = $_POST['carousel_slide_thumb'];
	update_post_meta($post_id, 'slide_description', $slide_description);
	update_post_meta($post_id, 'slide_thumb', $slide_thumb);
	return $post_id;
}

class Slides_Widget extends WP_Widget
{
	function Slides_Widget()
	{
		$widget_options = array('classname'=>'widget-slide','description'=>__('This widget shows a random slide.'));
		$control_options = array('height'=>300,'width' =>300);
		$this->WP_Widget('slide_widget','Slides Widget',$widget_options,$control_options);
	}
	
	function widget($args, $instance)
	{
		extract($args,EXTR_SKIP);
		$title =  ($instance['title'])?$instance['title']:"Vertical Carousel";
		?><h3 class="widget-title"><?php echo $title; ?></h3><?php
		display_carousel();
  }
	
	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$instance["title"] = $new_instance["title"];
		return $instance;
	}
	
	function form($instance)
	{
		?>
		<label for="<?php echo $this->get_field_id("title"); ?>">
		<p>Title: <input type="text"  value="<?php echo $instance['title']; ?>" name="<?php echo $this->get_field_name("title"); ?>" id="<?php echo $this->get_field_id("title"); ?>"></p>
		</label>
		<?php
	}
}

//Admin: upload image
function my_admin_uploader_scripts() {
	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	wp_register_script('my-upload', plugin_dir_url(__FILE__) . 'js/admin-submit.js', array('jquery','media-upload','thickbox'));
	wp_enqueue_script('my-upload');
}

function my_admin_uploader_styles() {
	wp_enqueue_style('thickbox');
}
add_action('admin_print_scripts', 'my_admin_uploader_scripts');
add_action('admin_print_styles', 'my_admin_uploader_styles'); 

// Displays the carousel
function display_carousel() {
$plugins_url = plugins_url();	
		?>
			<!-- carousel content: start -->
			<link rel="stylesheet" type="text/css" href="<?php echo $plugins_url; ?>/carousel-vertical/css/jquery.jcarousel.css" />
			<link rel="stylesheet" type="text/css" href="<?php echo $plugins_url; ?>/carousel-vertical/css/skin.css" />
			<link rel="stylesheet" type="text/css" href="<?php echo $plugins_url; ?>/carousel-vertical/css/slideshow.css" />
			<link rel="stylesheet" type="text/css" href="<?php echo $plugins_url; ?>/carousel-vertical/css/responsive.css" />
			<div class="carousel-vertical">
				<!-- column 1: start -->
				<div id="col1">  	
					
					<div id="welcomeHero">
						<div id="slideshow-main">
						<?php
						global $wpdb; 
						
						$sql="SELECT * FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON $wpdb->posts.id = $wpdb->postmeta.post_id WHERE $wpdb->posts.post_type='slide' AND $wpdb->posts.post_status='publish' AND $wpdb->postmeta.meta_key = 'slide_description'";
						
						$posts = $wpdb->get_results($sql);
						$p = 1;
						echo "<ul>";
						foreach ($posts as $post)
						{
						?>
						<li class="p<?php echo $p; if ($p==1) { echo " active"; } ?>">
							<a href="#">
								<?php echo $post->post_content; ?>
								<span class="opacity"></span>
								<span class="content"><h1><?php echo $post->post_title; ?></h1><p><?php echo $post->meta_value; ?></p></span>
							</a>
						</li>
						<?php				
						$p++;
						}
						echo "</ul>";
						?>
							</div>
									
							<div id="slideshow-carousel">				
								<ul id="carousel" class="jcarousel jcarousel-skin-tango">
								<?php
							//	global $wpdb; 
								$sql="SELECT * FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON $wpdb->posts.id = $wpdb->postmeta.post_id WHERE $wpdb->posts.post_type='slide' AND $wpdb->posts.post_status='publish' AND $wpdb->postmeta.meta_key = 'slide_thumb'";
								
								$posts = $wpdb->get_results($sql);
								$p = 1;
								foreach ($posts as $post)
								{
								?>
								 <li><a href="#" rel="p<?php echo $p; ?>"><img src="<?php echo $post->meta_value; ?>" width="154" height="127" alt="#"/></a></li>
								<?php				
								$p++;
								}
								?>								
								</ul>
							</div>
							
							<div class="clear"></div>
		
					</div>
								
				</div>
				<!-- column 1: end -->
		
			</div>
			<script type="text/javascript" src="<?php echo $plugins_url; ?>/carousel-vertical/js/jquery.jcarousel.pack.js"></script>			
			<script type="text/javascript" src="<?php echo $plugins_url; ?>/carousel-vertical/js/custom-carousel.js"></script>			
			<!-- carousel content: end -->

		<?php
	//}
}
?>
