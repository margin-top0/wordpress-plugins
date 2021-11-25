<?php
/**
 * Plugin Name:       Fetch any post content to current post
 * Description:       Fetch any post content to current editing post with ajax without page reload.
 * Version:           1.0.0
 * Author:            Ioakeim D.
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fetch-any-post-content-to-current-post
 */

add_action('admin_menu', 'fapc_fetch_any_post_content_metabox');
function fapc_fetch_any_post_content_metabox(){
	add_meta_box('fapc_fetch_any_post_content_metabox', 'Fetch post content to current', 'fapc_fetch_any_post_content_data', 'post', 'side', 'default');
}
function fapc_fetch_any_post_content_data($post_object){

	if(!current_user_can('edit_post', get_the_id())) return;

	$posts = get_posts(array(
		'post_type' => 'post',
		'post_status' => 'publish',
		'exclude' => array(get_the_id()),
		'posts_per_page' => -1,
	));
?>
	<label for="fapc-fetch-any-post-content">Select post to fetch content:</label>
	<span class="spinner" style=""></span>
	<p>
		<select id="fapc-fetch-any-post-content" name="fapc-fetch-any-post-content">
			<option value=""></option>
			<?php foreach($posts as $post){ ?>
					<option value="<?php echo esc_attr($post->ID); ?>" <?php esc_attr($post->post_title); ?>><?php echo esc_html($post->post_title); ?></option>
			<?php } ?>
		</select>
	</p>
	<div id="fapc-fetch-post-content-message"></div>
<?php
}

add_action('admin_footer', 'fapc_fetch_post_content_javascript');
function fapc_fetch_post_content_javascript(){ ?>
	<script>
		jQuery(function($){
			$('body.post-type-post #fapc_fetch_any_post_content_metabox #fapc-fetch-any-post-content').change(function(event){
				var fetch_post_content = $(this).val();
				if(fetch_post_content){
					$.ajax({
						method: 'POST',
						url: ajaxurl,
						data: {
							'action': 'fapc_custom_action_fetch_post_content',
							'fetch_post_content_id': fetch_post_content,
						},
						beforeSend: function(){
							$("#fapc_fetch_any_post_content_metabox .spinner").addClass("is-active");
						},
						success: function(response){
							var name = $("#fapc-fetch-any-post-content option:selected" ).text();
							if(response){
								if($('#wp-content-wrap').hasClass('html-active')){
									$('#content').val(response);
								}else{
									var activeEditor = tinyMCE.get('content');
									if(activeEditor !== null){
										activeEditor.setContent(response);
									}
								}
								$("#fapc-fetch-post-content-message").html("Content copied successfully from:<br><i>"+name+"</i>");
								$("#fapc-fetch-post-content-message").removeClass("notice-warning").addClass("notice notice-success");
							}else{
								$("#fapc-fetch-post-content-message").html("Empty content. Nothing to fetch from:<br><i>"+name+"</i>");
								$("#fapc-fetch-post-content-message").removeClass("notice notice-success").addClass("notice notice-warning");
							}
							$("#fapc_fetch_any_post_content_metabox .spinner").removeClass("is-active");
							$("#fetch-any-post-content option:selected" ).prop("selected", false);
						},
						error: function(response){
							$("#fapc_fetch_any_post_content_metabox .spinner").removeClass("is-active");
							$("#fapc-fetch-post-content-message").html("Cannot fetch content from:<br><i>"+name+"</i>");
							$("#fapc-fetch-post-content-message").addClass("notice notice-error");
							$("#fetch-any-post-content option:selected" ).prop("selected", false);
						}
					});
				}else{
					$("#fapc-fetch-post-content-message").removeAttr('class');
					$("#fapc-fetch-post-content-message").html("");
				}
			});
		});
	</script>
<?php
}

add_action('wp_ajax_fapc_custom_action_fetch_post_content', function(){
	if(isset($_POST['fetch_post_content_id'])){
		echo wp_kses(get_post_field('post_content', $_POST['fetch_post_content_id']));
	}
	die();
});