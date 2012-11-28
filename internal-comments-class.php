<?php
! defined( 'ABSPATH' ) AND exit;
/*
Plugin Name: 	Multisite Site Category
Plugin URI: 	https://github.com/brasofilo/multisite-site-category
Description: 	Add a custom meta option when registering new sites in WordPress Multisite.
Author: 		Rodolfo Buaiz
Author URI: 	http://rodbuaiz.com/
Version: 		2012.11.25.01
License: 		GPL
*/

/**
 * REFERENCES
 http://core.trac.wordpress.org/browser/tags/3.4.2/wp-admin/includes/ajax-actions.php#L719
 http://wordpress.org/support/topic/using-comment_type-field-for-my-own-purposes
 http://wordpress.stackexchange.com/q/39784/12615
 http://wordpress.stackexchange.com/q/56652/12615
 http://wordpress.stackexchange.com/q/61072/12615
 http://wordpress.stackexchange.com/q/63422/12615
 http://wordpress.stackexchange.com/q/64973/12615
 http://wordpress.stackexchange.com/q/72210/12615
 http://wordpress.stackexchange.com/q/74018/12615
 http://stackoverflow.com/q/4054943/1287812
*/


/* HELPER CPT */
add_action( 'init', 'create_my_post_types' );
function create_my_post_types() {
	$labels = array(
    'name' => _x('Books', 'post type general name', 'your_text_domain'),
    'singular_name' => _x('Book', 'post type singular name', 'your_text_domain'),
    'add_new' => _x('Add New', 'book', 'your_text_domain'),
    'add_new_item' => __('Add New Book', 'your_text_domain'),
    'edit_item' => __('Edit Book', 'your_text_domain'),
    'new_item' => __('New Book', 'your_text_domain'),
    'all_items' => __('All Books', 'your_text_domain'),
    'view_item' => __('View Book', 'your_text_domain'),
    'search_items' => __('Search Books', 'your_text_domain'),
    'not_found' =>  __('No books found', 'your_text_domain'),
    'not_found_in_trash' => __('No books found in Trash', 'your_text_domain'), 
    'parent_item_colon' => '',
    'menu_name' => __('Books', 'your_text_domain')

  );
  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'show_in_menu' => true, 
    'query_var' => true,
    'rewrite' => array( 'slug' => _x( 'book', 'URL slug', 'your_text_domain' ) ),
    'capability_type' => 'post',
    'has_archive' => true, 
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array( 'title', 'editor', 'comments' )
  );
	register_post_type( 'portfolio', $args );
}


  /***************************************************************************************
 ***************************************************************************************
**************************************************************************************/

InternalComments::load();


class InternalComments 
{

    static $cpt = 'portfolio'; // Where to run
    
	static $cpt_include = array( 'draft', 'pending' );
    
    static $cpt_exclude = array( 'trash' ); // Deny only if cpt comment in trash
    
    static $other_exclude = array('draft','pending','trash'); // Deny to the rest of post_types
    
	static $row_id = 'inner_msgs';

    static function load() 
	{
        add_action( 'admin_init', array( __CLASS__, 'init_admin'));

		if( !is_admin() )
        	add_action( 'init', array( __CLASS__, 'init_front'));
    }

    static function init_admin() 
	{
        add_action( 'current_screen', array(  __CLASS__, 'exclude_lazy_hook' ), 10, 2 );
		add_action( 'wp_ajax_replyto-comment', array( __CLASS__, 'ajax_replyto_comment' ), 0 );

		if( isset( $_GET['post'] ) ) 
			self::add_comment_metabox();
			
		add_action( 'admin_footer-edit-comments.php', array( __CLASS__, 'karma_row_bg_color' ) );
		add_action( 'load-edit-comments.php', array( __CLASS__, 'wpse_64973_load' ) );
		add_action( 'manage_comments_custom_column', array( __CLASS__, 'wpse_64973_column_cb' ), 10, 2 );
    }

    static function init_front() 
	{
		add_filter( 'comments_array', array( __CLASS__, 'remove_karmic_comments' ), 20, 2 );
	}
	
	/**
	 * Delay hooking our clauses filter to ensure it's only applied when needed. 
	 *
	 * @param object $screen
	 * @return void
	 **/
	static function exclude_lazy_hook( $screen )
	{
	    if ( $screen->id != 'edit-comments' )
			return;

		// Check if our Query Var is defined	
		if( isset( $_GET['internal_messages'] ) )
			add_action( 'pre_get_comments', array( __CLASS__, 'list_only_internal_messages' ), 10, 1 );

		add_filter( 'comment_status_links', array( __CLASS__, 'link_to_internal_messages' ) );
	}
	
    /**
	 * Only display comments of specific karma
	 *
	 * @param integer $cols
	 * @return object WP_Comment_Query
	 **/
	static function list_only_internal_messages( $clauses )
	{
		$clauses->query_vars['karma'] = 3;
	}


	/**
	 * Add link to custom comments karma with counter
	 *
	 * @param integer $cols
	 * @return integer
	 **/
	static function link_to_internal_messages( $status_links )
	{
		global $wpdb;
		$count = count( 
			$wpdb->get_results( 
				$wpdb->prepare( 
					"SELECT comment_ID FROM $wpdb->comments 
					WHERE comment_karma = '3' 
					AND comment_approved != 'trash'" 
				) 
			) 
		);

		if( isset( $_GET['internal_messages'] ) ) 
		{
			$status_links['all'] = '<a href="edit-comments.php?comment_status=all">All</a>';
			$status_links['internal_messages'] = '<a href="edit-comments.php?comment_status=all&internal_messages=1" class="current" style="margin-leff:60px">Internal Comments <span class="count">('.$count.')</a></span>';
		} 
		else 
		{
			$status_links['internal_messages'] = '<a href="edit-comments.php?comment_status=all&internal_messages=1" style="margin-leff:60px">Internal Comments  <span class="count">('.$count.')</span></a>';
		}

		return $status_links;
	}
	
	
	/**
	 * Modified copy of wp_ajax_replyto_comment()
	 * /wp-admin/includes/ajax-actions.php
	 *
	 * Adjust the CPT that defines $diff_status 
	 */
	static function ajax_replyto_comment( $action ) 
	{
		global $wp_list_table, $wpdb;

		$comment_post_ID = absint( $_POST['comment_post_ID'] );
		if ( !current_user_can( 'edit_post', $comment_post_ID ) )
			wp_die( -1 );

		if ( empty( $action ) )
			$action = 'replyto-comment';

		check_ajax_referer( $action, '_ajax_nonce-replyto-comment' );

		set_current_screen( 'edit-comments' );

		$status = $wpdb->get_var( $wpdb->prepare( "SELECT post_status FROM $wpdb->posts WHERE ID = %d", $comment_post_ID ) );

		if( self::$cpt == get_post_type( $comment_post_ID ) )
			$diff_status = self::$cpt_exclude;
		else
			$diff_status = self::$other_exclude;

		if ( empty( $status ) )
			wp_die( 1 );
		elseif ( in_array( $status, $diff_status ) )
			wp_die( __('ERROR: you are replying to a comment on a draft post.') );

		$user = wp_get_current_user();
		if ( $user->exists() ) {
			$user_ID = $user->ID;
			$comment_author       = $wpdb->escape( $user->display_name );
			$comment_author_email = $wpdb->escape( $user->user_email );
			$comment_author_url   = $wpdb->escape( $user->user_url );
			$comment_content      = trim( $_POST['content'] );
			if ( current_user_can( 'unfiltered_html' ) ) {
				if ( wp_create_nonce( 'unfiltered-html-comment' ) != $_POST['_wp_unfiltered_html_comment'] ) {
					kses_remove_filters(); // start with a clean slate
					kses_init_filters(); // set up the filters
				}
			}
		} else {
			wp_die( __( 'Sorry, you must be logged in to reply to a comment.' ) );
		}

		if ( '' == $comment_content )
			wp_die( __( 'ERROR: please type a comment.' ) );

		$comment_parent = absint($_POST['comment_ID']);
		$comment_auto_approved = false;
		$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');

		$commentdata['comment_karma'] = 
			( $diff_status == self::$other_exclude ) 
			? $commentdata['comment_karma'] 
			: 3;
loga($commentdata);
		$comment_id = wp_new_comment( $commentdata );
		$comment = get_comment($comment_id);
		if ( ! $comment ) wp_die( 1 );

		if( !in_array( 'draft', $diff_status ) ) 
			update_comment_meta( $comment_id, self::$row_id, 'yes' );

		$position = ( isset($_POST['position']) && (int) $_POST['position'] ) ? (int) $_POST['position'] : '-1';

		// automatically approve parent comment
		if ( !empty($_POST['approve_parent']) ) {
			$parent = get_comment( $comment_parent );

			if ( $parent && $parent->comment_approved === '0' && $parent->comment_post_ID == $comment_post_ID ) {
				if ( wp_set_comment_status( $parent->comment_ID, 'approve' ) )
					$comment_auto_approved = true;
			}
		}

		ob_start();
			if ( 'dashboard' == $_REQUEST['mode'] ) {
				require_once( ABSPATH . 'wp-admin/includes/dashboard.php' );
				_wp_dashboard_recent_comments_row( $comment );
			} else {
				if ( 'single' == $_REQUEST['mode'] ) {
					$wp_list_table = _get_list_table('WP_Post_Comments_List_Table');
				} else {
					$wp_list_table = _get_list_table('WP_Comments_List_Table');
				}
				$wp_list_table->single_row( $comment );
			}
			$comment_list_item = ob_get_contents();
		ob_end_clean();

		$response =  array(
			'what' => 'comment',
			'id' => $comment->comment_ID,
			'data' => $comment_list_item,
			'position' => $position
		);

		if ( $comment_auto_approved )
			$response['supplemental'] = array( 'parent_approved' => $parent->comment_ID );

		$x = new WP_Ajax_Response();
		$x->add( $response );
		$x->send();
	}

	
	/**
	 * Enables the default Comments Meta Box
	 *
	 * @return void
	 */
	static function add_comment_metabox()
	{
		$post_id = absint( $_GET['post'] ); 
        $post = get_post( $post_id ); 
        if ( in_array( $post->post_status, self::$cpt_include ) )
		{
            add_meta_box(
                'commentsdiv' 
            ,   __('Offline Comments')
            ,   'post_comment_meta_box'
            ,   self::$cpt
            ,   'normal' 
            ,   'core'
            );
		}        
	}
	

	/**
	 * Removes comments with Karma from frontend 
	 *
	 * @param object $comments
	 * @param int	 $post_id
	 * @return object
	 **/
	static function remove_karmic_comments( $comments, $post_id )
	{
	   foreach ( $comments as $index => $c )
	   {
	       if ( $c->comment_karma == 3 )
	           unset( $comments[ $index ] );
	   }
	   return $comments;
	}
	
	
	static function karma_row_bg_color()
	{
		if( isset( $_GET['internal_messages'] ) )
			return;
	    ?>
	        <script type="text/javascript">
	            jQuery(document).ready( function($) {
	                //$('.inner-msgs-span').parent().parent().css('background-color','#858585').fadeTo('slow', 1);
					$('.inner-msgs-span').parent().parent().fadeTo('fast', .3, function()
					{
					    $(this).css('background-color', '#858585');
					}).fadeTo('slow', 1);
	            });     
	        </script>
	    <?php
	}
	
	
	/*
	Plugin Name. Add Extra Comment Columns for Comment_Meta
	Author URI. http://pmg.co/people/chris
	License. MIT
	*/
	static function wpse_64973_load()
	{
		if( isset( $_GET['internal_messages'] ) )
			return;
	    $screen = get_current_screen();

	    add_filter( "manage_{$screen->id}_columns", array( __CLASS__, 'wpse_64973_add_columns' ) );
	}
	static function wpse_64973_add_columns( $cols )
	{
	    $cols[self::$row_id] = __( 'Internal Comments', 'wpse_64973' );
	    return $cols;
	}
	static function wpse_64973_column_cb( $col, $comment_id )
	{
	    switch( $col )
	    {
	        case self::$row_id:
	            if( 'yes' == get_comment_meta( $comment_id, self::$row_id, true ) )
	            {
	                echo '<span style="color:#f00;font-size:7em;line-height:.5em;margin-left:15%" class="inner-msgs-span"><sub>&#149;</sub></span>';
	            }
	            break;
	    }
	} /* end plugin */
	
} /* end class */


