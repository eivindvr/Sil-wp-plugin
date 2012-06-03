<?php
/*
Plugin Name: Shootitlive
Plugin URI: http://www.shootitlive.com
Description: Plugin for displaying live photo feeds from Shootitlive.com
Author: Eivind Vogel-Rödin
Version: 1.0
Author URI: http://www.shootitlive.com
*/


// Delete options table entries ONLY when plugin deactivated AND deleted
function posk_delete_plugin_options() {
	delete_option('silp_options');
}


// Add meta_box
add_action( 'add_meta_boxes', 'silp_meta_box_add' );
function silp_meta_box_add()
{
	add_meta_box( 'silp-meta-box-id', 'Shootitlive', 'silp_meta_box_cb', 'post', 'side', 'high' );
}


//API call & drop down menu
function silp_meta_box_cb( $post )
{
	$values = get_post_custom( $post->ID );
	$selected = isset( $values['silp_meta_box_select'] ) ? esc_attr( $values['silp_meta_box_select'][0] ) : '';
	$options = get_option('silp_options');
    $silp_client = $options['silp_txt_one'];
    $silp_key = $options['silp_txt_two'];
	
	$silp_call = "https://api.shootitlive.com/v1/projects/?client=$silp_client&token=$silp_key&embed=true";
	
	$json_data2 = file_get_contents($silp_call);
	$obj2=json_decode($json_data2, true);
	
	wp_nonce_field( 'silp_meta_box_nonce', 'meta_box_nonce' );
?>
		
	<p>
		<!--<label for="silp_meta_box_select">Select a project:</label>-->
		<select name="silp_meta_box_select" id="silp_meta_box_select">
	<option>Select a project:</option>
<?php
foreach($obj2[$silp_client] as $p)
{
echo "<option value='";
echo $p[project];
echo "'";
?>

<?php selected( $selected, $p[project]); ?>

<?php 
echo ">";
echo $p[description];
echo "</option>";
}
?>

		</select>
	</p>
	
 	
<?php	


}


// SAVE the meta_box value
add_action( 'save_post', 'silp_meta_box_save' );
function silp_meta_box_save( $post_id )
{
	// Bail if we're doing an auto save
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	
	// if our nonce isn't there, or we can't verify it, bail
	if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'silp_meta_box_nonce' ) ) return;
	
	// if our current user can't edit this post, bail
	if( !current_user_can( 'edit_post' ) ) return;
	
	// now we can actually save the data
	$allowed = array( 
		'a' => array( // on allow a tags
			'href' => array() // and those anchords can only have href attribute
		)
	);
	
	// Probably a good idea to make sure your data is set
	if( isset( $_POST['silp_meta_box_text'] ) )
		update_post_meta( $post_id, 'silp_meta_box_text', wp_kses( $_POST['silp_meta_box_text'], $allowed ) );
		
	if( isset( $_POST['silp_meta_box_select'] ) )
		update_post_meta( $post_id, 'silp_meta_box_select', esc_attr( $_POST['silp_meta_box_select'] ) );
		
	// This is purely my personal preference for saving checkboxes
	$chk = ( isset( $_POST['silp_meta_box_check'] ) && $_POST['silp_meta_box_check'] ) ? 'on' : 'off';
	update_post_meta( $post_id, 'silp_meta_box_check', $chk );
}

//Random string to embedcode
function random_string( )
  {
    $character_set_array = array( );
    $character_set_array[ ] = array( 'count' => 20, 'characters' => 'abcdefghijklmnopqrstuvwxyz' );
    $character_set_array[ ] = array( 'count' => 20, 'characters' => '0123456789' );
    $temp_array = array( );
    foreach ( $character_set_array as $character_set )
    {
      for ( $i = 0; $i < $character_set[ 'count' ]; $i++ )
      {
        $temp_array[ ] = $character_set[ 'characters' ][ rand( 0, strlen( $character_set[ 'characters' ] ) - 1 ) ];
      }
    }
    shuffle( $temp_array );
    return implode( '', $temp_array );
  }



//[silp]
function silp_embed()  {

global $post;
$str = get_post_meta($post->ID, 'silp_meta_box_select', true);
$yo = random_string();
$kiss = $str;
$options = get_option('silp_options');
    $silp_client = $options['silp_txt_one'];

$silp_player = "<div id='shootitlive-[UNIQUE]'></div><script>silp_settings = typeof silp_settings != 'undefined'?silp_settings:new Array();silp_settings.push({'element_id':'shootitlive-[UNIQUE]','client':'$silp_client','project':$str,'width':'auto','height':'auto','preroll':'http://ad-emea.doubleclick.net/ad/shootitlive.se.smartclip/aftonbladet;sz=400x320;cat=ENTERTAINMENT;dcmt=text/xml;ord=__random-number__?','analytics':true,'flash_default':true,'category':'Entertainment'});var s = document.createElement('script');s.async = true;var protocol = (('https:' == document.location.protocol) ? 'https://s3-eu-west-1.amazonaws.com/' : 'http://cdn.');s.src = protocol + 'silp.shootitlive.com/src/branch/master/js/silp.min.js';function silp_onload_callback() {while(silp_settings.length > 0) {var settings = silp_settings.pop();init_silp(settings);}}var x = document.getElementsByTagName('script')[0];x.parentNode.insertBefore(s, x);</script>";


$GoodCode = str_replace('[SILPID]', $kiss, $silp_player);
$UNIQUE = str_replace('[UNIQUE]', $yo, $GoodCode);

echo $UNIQUE;

}
add_shortcode( 'silp', 'silp_embed' );



//Hook the_content

add_filter('the_content', 'silp_content');

function silp_content($content = '') {
			$content .= do_shortcode("[silp]");
			return $content;
		}
		
		


//Settings page content

add_action("admin_menu","jcorgcr_create_testmenu");
function jcorgcr_create_testmenu(){
add_menu_page(/*page title*/'Dashboard', /*Menu Title*/'Shootitlive',/*access*/'administrator', 'shootitlive', 'silp_dashboard_page',plugins_url('sil.ico', __FILE__));

add_submenu_page( 'shootitlive', 'Settings', 'Settings', 'administrator', 'dashboard', 'jcorgcr_test_page' );

}
function jcorgcr_test_page() { /*handler for above menu item*/

?>
	<div class="wrap">
		
		<!-- Display Plugin Icon, Header, and Description -->
		<div class="icon32" id="icon-options-general"><br></div>
		<h2>Shootitlive</h2>

		<!-- Beginning of the Plugin Options Form -->
		<form method="post" action="options.php">
			<?php settings_fields('silp_plugin_options'); ?>
			<?php $options = get_option('silp_options'); ?>

			<!-- Table Structure Containing Form Controls -->
			<!-- Each Plugin Option Defined on a New Table Row -->
			<table class="form-table">

									<!-- Textbox Control -->
				<tr>
					<th scope="row">Enter Organisation Name:</th>
					<td>
						<input type="text" size="57" name="silp_options[silp_txt_one]" value="<?php echo $options['silp_txt_one']; ?>" />
					</td>
				</tr>
				
				<tr>
					<th scope="row">Enter API Key:</th>
					<td>
						<input type="text" size="57" name="silp_options[silp_txt_two]" value="<?php echo $options['silp_txt_two']; ?>" />
					</td>
				</tr>

			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>

		<p style="margin-top:15px;">
			<p style="font-style: italic;font-weight: bold;color: #26779a;">If you have found this starter kit at all useful, please consider making a <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XKZXD2BHQ5UB2" target="_blank" style="color:#72a1c6;">donation</a>. Thanks.</p>
		</p>

	</div>
		
<?php } 


function silp_dashboard_page() { /*handler for above menu item*/

?>
	<iframe src="http://admin.shootitlive.com/projects" frameBorder="0" marginwidth="0px" marginheight="0px" scrolling="yes" width="100%" height="100%"></iframe>

<?php }

// Sanitize and validate input. Accepts an array, return a sanitized array.
function silp_validate_options($input) {
	 // strip html from textboxes
	$input['silp_txt_one'] =  wp_filter_nohtml_kses($input['silp_txt_one']); // Sanitize textarea input (strip html tags, and escape characters)
	$input['silp_txt_two'] =  wp_filter_nohtml_kses($input['silp_txt_two']); // Sanitize textbox input (strip html tags, and escape characters)
	return $input;
}


?>