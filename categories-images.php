<?php
/**
 * Plugin Name: Categories Images
 * Plugin URI: http://zahlan.net/blog/2012/06/categories-images/
 * Description: Categories Images Plugin allow you to add an image to category or any custom term.
 * Author: Muhammad Said El Zahlan
 * Version: 2.4.1
 * Author URI: http://zahlan.net/
 */

/**
 * Define plugin URL and Placeholder URL constants
 */
if ( ! defined( 'Z_PLUGIN_URL' ) ) {
	define( 'Z_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );
}
if ( ! defined( 'Z_IMAGE_PLACEHOLDER' ) ) {
	define('Z_IMAGE_PLACEHOLDER', Z_PLUGIN_URL . '/images/placeholder.png' );
}

/**
 * Localization
 */
load_plugin_textdomain( 'zci', FALSE, 'categories-images/languages' );

/**
 * Load Scripts and Styles
 */
function z_load_scripts( $hook ) {
	if ( $hook != 'edit-tags.php' ) {
		return;
	} else {
		wp_enqueue_style( 'categories-images-style', Z_PLUGIN_URL . '/css/categories-images.css', array(), '01032014' );
		wp_enqueue_script( 'categories-images', Z_PLUGIN_URL . '/js/categories-images.js', array( 'jquery' ), '01032014', false );
		$params = array(
			'wpversion' => get_bloginfo( 'version' ),
			'imageplaceholder' => Z_IMAGE_PLACEHOLDER
		);
		wp_localize_script( 'categories-images', 'zciparams', $params );
	}
}
add_action( 'admin_enqueue_scripts', 'z_load_scripts' );

/**
 * Finding all of the taxonomies, removing any declared as excluded,
 * then adding form fields and columns for them
 */
function z_init() {
	$z_taxonomies = get_taxonomies();
	if ( is_array( $z_taxonomies ) ) {
		$zci_options = get_option( 'zci_options' );
		if ( empty( $zci_options['excluded_taxonomies'] ) ) {
			$zci_options['excluded_taxonomies'] = array();
		}
		
	    foreach ( $z_taxonomies as $z_taxonomy ) {
			if ( in_array( $z_taxonomy, $zci_options['excluded_taxonomies'] ) ) {
				continue;
			}
	        add_action( $z_taxonomy . '_add_form_fields', 'z_add_texonomy_field' );
			add_action( $z_taxonomy . '_edit_form_fields', 'z_edit_texonomy_field' );
			add_filter( 'manage_edit-' . $z_taxonomy . '_columns', 'z_taxonomy_columns' );
			add_filter( 'manage_' . $z_taxonomy . '_custom_column', 'z_taxonomy_column', 10, 3 );
	    }
	}
}
add_action( 'admin_init', 'z_init' );

/**
 * Adding the Image Upload Field on the Add New Taxonomy Form
 */
function z_add_texonomy_field() {
	if ( version_compare( get_bloginfo( 'version' ), '3.5', '>=' ) ) {
		wp_enqueue_media();
	} else {
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'thickbox' );
	}
	
	echo '<div class="form-field">
		<label for="taxonomy_image">' . __( 'Image', 'zci' ) . '</label>
		<input type="text" name="taxonomy_image" id="taxonomy_image" value="" />
		<br/>
		<button class="z_upload_image_button button">' . __( 'Upload/Add image', 'zci' ) . '</button>
	</div>';
}

/**
 * Adding the Image Upload Field on the Edit Taxonomy Form
 */
function z_edit_texonomy_field( $taxonomy ) {
	if ( version_compare( get_bloginfo('version'), '3.5', '>=' ) ) {
		wp_enqueue_media();
	} else {
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'thickbox' );
	}
	
	if ( z_taxonomy_image_url( (int) $taxonomy->term_id, NULL, TRUE ) == Z_IMAGE_PLACEHOLDER ) {
		$image_text = '';
	} else {
		$image_text = z_taxonomy_image_url( (int) $taxonomy->term_id, NULL, TRUE );
	}
	echo '<tr class="form-field">
		<th scope="row" valign="top"><label for="taxonomy_image">' . __( 'Image', 'zci' ) . '</label></th>
		<td><img class="taxonomy-image" src="' . z_taxonomy_image_url( (int) $taxonomy->term_id, NULL, TRUE ) . '"/><br/><input type="text" name="taxonomy_image" id="taxonomy_image" value="' . esc_url( $image_text ) . '" /><br />
		<button class="z_upload_image_button button">' . __( 'Upload/Add image', 'zci' ) . '</button>
		<button class="z_remove_image_button button">' . __( 'Remove image', 'zci' ) . '</button>
		</td>
	</tr>';
}

// save our taxonomy image while edit or save term
add_action('edit_term','z_save_taxonomy_image');
add_action('create_term','z_save_taxonomy_image');
function z_save_taxonomy_image($term_id) {
    if(isset($_POST['taxonomy_image']))
        update_option('z_taxonomy_image'.$term_id, $_POST['taxonomy_image']);
}

// get attachment ID by image url
function z_get_attachment_id_by_url($image_src) {
    global $wpdb;
    $query = "SELECT ID FROM {$wpdb->posts} WHERE guid = '$image_src'";
    $id = $wpdb->get_var($query);
    return (!empty($id)) ? $id : NULL;
}

// get taxonomy image url for the given term_id (Place holder image by default)
function z_taxonomy_image_url($term_id = NULL, $size = NULL, $return_placeholder = FALSE) {
	if (!$term_id) {
		if (is_category())
			$term_id = get_query_var('cat');
		elseif (is_tax()) {
			$current_term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
			$term_id = $current_term->term_id;
		}
	}
	
    $taxonomy_image_url = get_option('z_taxonomy_image'.$term_id);
    $attachment_id = z_get_attachment_id_by_url($taxonomy_image_url);
    if(!empty($attachment_id)) {
    	if (empty($size))
    		$size = 'full';
    	$taxonomy_image_url = wp_get_attachment_image_src($attachment_id, $size);
	    $taxonomy_image_url = $taxonomy_image_url[0];
    }

    if ($return_placeholder)
		return ($taxonomy_image_url != '') ? $taxonomy_image_url : Z_IMAGE_PLACEHOLDER;
	else
		return $taxonomy_image_url;
}

function z_quick_edit_custom_box($column_name, $screen, $name) {
	if ($column_name == 'thumb') 
		echo '<fieldset>
		<div class="thumb inline-edit-col">
			<label>
				<span class="title"><img src="" alt="Thumbnail"/></span>
				<span class="input-text-wrap"><input type="text" name="taxonomy_image" value="" class="tax_list" /></span>
				<span class="input-text-wrap">
					<button class="z_upload_image_button button">' . __('Upload/Add image', 'zci') . '</button>
					<button class="z_remove_image_button button">' . __('Remove image', 'zci') . '</button>
				</span>
			</label>
		</div>
	</fieldset>';
}

/**
 * Thumbnail column added to category admin.
 *
 * @access public
 * @param mixed $columns
 * @return void
 */
function z_taxonomy_columns( $columns ) {
	$new_columns = array();
	$new_columns['cb'] = $columns['cb'];
	$new_columns['thumb'] = __('Image', 'zci');

	unset( $columns['cb'] );

	return array_merge( $new_columns, $columns );
}

/**
 * Thumbnail column value added to category admin.
 *
 * @access public
 * @param mixed $columns
 * @param mixed $column
 * @param mixed $id
 * @return void
 */
function z_taxonomy_column( $columns, $column, $id ) {
	if ( $column == 'thumb' )
		$columns = '<span><img src="' . z_taxonomy_image_url($id, NULL, TRUE) . '" alt="' . __('Thumbnail', 'zci') . '" class="wp-post-image" /></span>';
	
	return $columns;
}

// change 'insert into post' to 'use this image'
function z_change_insert_button_text($safe_text, $text) {
    return str_replace("Insert into Post", "Use this image", $text);
}

// style the image in category list
if ( strpos( $_SERVER['SCRIPT_NAME'], 'edit-tags.php' ) > 0 ) {
	add_action('quick_edit_custom_box', 'z_quick_edit_custom_box', 10, 3);
	add_filter("attribute_escape", "z_change_insert_button_text", 10, 2);
}

// New menu submenu for plugin options in Settings menu
add_action('admin_menu', 'z_options_menu');
function z_options_menu() {
	add_options_page(__('Categories Images settings', 'zci'), __('Categories Images', 'zci'), 'manage_options', 'zci-options', 'zci_options');
	add_action('admin_init', 'z_register_settings');
}

// Register plugin settings
function z_register_settings() {
	register_setting('zci_options', 'zci_options', 'z_options_validate');
	add_settings_section('zci_settings', __('Categories Images settings', 'zci'), 'z_section_text', 'zci-options');
	add_settings_field('z_excluded_taxonomies', __('Excluded Taxonomies', 'zci'), 'z_excluded_taxonomies', 'zci-options', 'zci_settings');
}

// Settings section description
function z_section_text() {
	echo '<p>'.__('Please select the taxonomies you want to exclude it from Categories Images plugin', 'zci').'</p>';
}

// Excluded taxonomies checkboxs
function z_excluded_taxonomies() {
	$options = get_option('zci_options');
	$disabled_taxonomies = array('nav_menu', 'link_category', 'post_format');
	foreach (get_taxonomies() as $tax) : if (in_array($tax, $disabled_taxonomies)) continue; ?>
		<input type="checkbox" name="zci_options[excluded_taxonomies][<?php echo $tax ?>]" value="<?php echo $tax ?>" <?php checked(isset($options['excluded_taxonomies'][$tax])); ?> /> <?php echo $tax ;?><br />
	<?php endforeach;
}

// Validating options
function z_options_validate($input) {
	return $input;
}

// Plugin option page
function zci_options() {
	if (!current_user_can('manage_options'))
		wp_die(__( 'You do not have sufficient permissions to access this page.', 'zci'));
		$options = get_option('zci_options');
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e('Categories Images', 'zci'); ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields('zci_options'); ?>
			<?php do_settings_sections('zci-options'); ?>
			<?php submit_button(); ?>
		</form>
	</div>
<?php
}