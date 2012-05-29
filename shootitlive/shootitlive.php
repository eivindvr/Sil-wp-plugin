<?php
/*
Plugin Name: Shootitlive
Plugin URI: http://www.shootitlive.com
Description: Plugin for displaying live photo feeds from Shootitlive.com
Author: Eivind Vogel-Rödin
Version: 1.0
Author URI: http://www.shootitlive.com
*/


//include("ob_settings_v1_2.php"); 
include("shootitlive_options.php"); 


add_action( 'add_meta_boxes', 'cd_meta_box_add' );
function cd_meta_box_add()
{
	add_meta_box( 'my-meta-box-id', 'Shootitlive', 'cd_meta_box_cb', 'post', 'normal', 'high' );
}

function cd_meta_box_cb( $post )
{
	$values = get_post_custom( $post->ID );
	$selected = isset( $values['my_meta_box_select'] ) ? esc_attr( $values['my_meta_box_select'][0] ) : '';
	//$json_data2 = file_get_contents('https://toolbox.shootitlive.com/projects/?client=aftonbladet&token=0293c072e468f3c2b1e0dfa49c324e205717c521');
	
    $options = get_option('silp_options');
    $silp_client = $options['silp_txt_one'];
    $silp_key = $options['silp_txt_two'];


	
	$silp_call = "https://api.shootitlive.com/v1/projects/?client=$silp_client&token=$silp_key&embed=true";
	
		$json_data2 = file_get_contents($silp_call);
	$obj2=json_decode($json_data2, true);
	
	wp_nonce_field( 'my_meta_box_nonce', 'meta_box_nonce' );
?>
		
	<p>
		<label for="my_meta_box_select">Projects:</label>
		<select name="my_meta_box_select" id="my_meta_box_select">
	
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
	
<?php echo 	$silp_call;?>
	
	

 	
<?php	


}


add_action( 'save_post', 'cd_meta_box_save' );
function cd_meta_box_save( $post_id )
{
	// Bail if we're doing an auto save
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	
	// if our nonce isn't there, or we can't verify it, bail
	if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'my_meta_box_nonce' ) ) return;
	
	// if our current user can't edit this post, bail
	if( !current_user_can( 'edit_post' ) ) return;
	
	// now we can actually save the data
	$allowed = array( 
		'a' => array( // on allow a tags
			'href' => array() // and those anchords can only have href attribute
		)
	);
	
	// Probably a good idea to make sure your data is set
	if( isset( $_POST['my_meta_box_text'] ) )
		update_post_meta( $post_id, 'my_meta_box_text', wp_kses( $_POST['my_meta_box_text'], $allowed ) );
		
	if( isset( $_POST['my_meta_box_select'] ) )
		update_post_meta( $post_id, 'my_meta_box_select', esc_attr( $_POST['my_meta_box_select'] ) );
		
	// This is purely my personal preference for saving checkboxes
	$chk = ( isset( $_POST['my_meta_box_check'] ) && $_POST['my_meta_box_check'] ) ? 'on' : 'off';
	update_post_meta( $post_id, 'my_meta_box_check', $chk );
}


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
$str = get_post_meta($post->ID, 'my_meta_box_select', true);
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



//media_button
function silp_media_button()
{
    print "<img src='http://shootitlive.com/favicon.ico' alt='Add VideofyMe Video' />";
}

# Add a button above the editor
add_action('media_buttons', 'silp_media_button', 22);

?>