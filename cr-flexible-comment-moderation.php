<?php
/*
Plugin Name: CR Flexible Comment Moderation
Plugin URI: http://bayu.freelancer.web.id/search/plugin
Description: Allow handling comment moderation mode in flexible way.
Version: 0.1
Author: Arief Bayu Purwanto
Author URI: http://bayu.freelancer.web.id/

Version History:
  0.1
    - Initial Release
*/

add_action('admin_menu', 'cr_flexible_comment_moderation_add_custom_box');
add_action('save_post', 'cr_flexible_comment_moderation_save_postdata');
add_action('comment_post', 'cr_flexible_comment_moderation_comment_post');

function cr_flexible_comment_moderation_add_custom_box() {
	add_meta_box( 'cr_flexible_comment_moderation_sectionid', 'Advanced Moderation', 
		'cr_flexible_comment_moderation_inner_custom_box', 'post', 'side' );
	add_meta_box( 'cr_flexible_comment_moderation_sectionid', 'Advanced Moderation', 
		'cr_flexible_comment_moderation_inner_custom_box', 'page', 'side' );
}
function cr_flexible_comment_moderation_inner_custom_box() {

  $post_id = mysql_escape_string($_GET['post']);
  
  // The actual fields for data entry
  $msm = get_post_meta( $post_id, '_cr_flexible_comment_moderation_system_mode', true);
  $mom = get_post_meta( $post_id, '_cr_flexible_comment_moderation_overide_mode', true);
    
  if($msm == "") { $msm = 'default'; }
  if($mom == "") { $mom = 'moderate'; }
  echo '<input type="hidden" name="cr_flexible_comment_moderation_noncename" id="cr_flexible_comment_moderation_noncename" value="' . 
    wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

  echo '<label for="cr_flexible_comment_moderation_system_mode">Moderation Mode:</label><br />';
  echo '<input type="radio"'
  		.' name="cr_flexible_comment_moderation_system_mode"'
  		. ($msm == 'default' ? ' checked="checked"' : '')
		.' value="default" /> use system default<br />';
  echo '<input type="radio"'
  		.' name="cr_flexible_comment_moderation_system_mode"'
  		. ($msm == 'overide' ? ' checked="checked"' : '')
		.' value="overide" /> overide system default<br />';
  echo '<p><u><strong>Moderation mode</strong></u> will determine if we will overide discussion setting.
  	If you choose default, it will use system setting (<em>discussion setting</em>). Otherwise, it will use setting below (<em>if the comment is not marked as spam</em>)</p><br />';
	  echo '<label for="cr_flexible_comment_moderation_overide_mode">Overide Mode:</label><br />';
	  echo '<input type="radio"'
	  		.' name="cr_flexible_comment_moderation_overide_mode"'
	  		. ($mom == 'approve' ? ' checked="checked"' : '')
			.' value="approve" /> always approved<br />';
	  echo '<input type="radio"'
	  		.' name="cr_flexible_comment_moderation_overide_mode"'
	  		. ($mom == 'moderate' ? ' checked="checked"' : '')
			.' value="moderate" /> always moderated<br />';
  echo '<p><u><strong>Overide mode</strong></u> will help system to determine if new comment to this page/post will be marked as approved/hold moderation.</p>';
}

function cr_flexible_comment_moderation_save_postdata( $post_id ) {
	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times

	if ( !wp_verify_nonce( $_POST['cr_flexible_comment_moderation_noncename'], plugin_basename(__FILE__) )) {
		return $post_id;
	}

	if ( 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ))
			return $post_id;
	} else {
		if ( !current_user_can( 'edit_post', $post_id ))
			return $post_id;
	}

	// OK, we're authenticated: we need to find and save the data

	$msm = $_POST['cr_flexible_comment_moderation_system_mode'];
	if(!add_post_meta($post_id, "_cr_flexible_comment_moderation_system_mode", $msm, true))
		update_post_meta($post_id, "_cr_flexible_comment_moderation_system_mode", $msm);

	$mom = $_POST['cr_flexible_comment_moderation_overide_mode'];
	if(!add_post_meta($post_id, "_cr_flexible_comment_moderation_overide_mode", $mom, true))
		update_post_meta($post_id, "_cr_flexible_comment_moderation_overide_mode", $mom);

	return $post_id;
}
function cr_flexible_comment_moderation_comment_post( $comment_id ) {
	$comment = get_comment($comment_id);
	$status = $comment->comment_approved;
	if($status !== "spam") // approved
	{
		$post_id =  $comment->comment_post_ID;

		$msm = get_post_meta( $post_id, '_cr_flexible_comment_moderation_system_mode', true);
		$mom = get_post_meta( $post_id, '_cr_flexible_comment_moderation_overide_mode', true);
		
		if("overide" == $msm)
		{
			$cstatus = "";
			if("approve" == $mom)
			{
				$cstatus = "approve";
			}
			else if("moderate" == $mom)
			{
				$cstatus = "hold";
			}
			wp_set_comment_status($comment_id, $cstatus);
		}
	}
	return $comment_id;
}

