<?php
! defined( 'ABSPATH' ) AND exit;
/*
* @package    WordPress
* @subpackage Private Comments in CPT
*/

/* HELPER CPT */

//add_action( 'init', 'create_my_post_types' );

function create_my_post_types() {
	$labels = array(
    'name' => _x( 'Portfolios', 'post type general name', 'iccpt' ),
    'singular_name' => _x( 'Portfolio', 'post type singular name', 'iccpt' ),
    'add_new' => _x( 'Add New', 'portfolio', 'iccpt' ),
    'add_new_item' => __( 'Add New Portfolio', 'iccpt' ),
    'edit_item' => __( 'Edit Portfolio', 'iccpt' ),
    'new_item' => __( 'New Portfolio', 'iccpt' ),
    'all_items' => __( 'All Portfolios', 'iccpt' ),
    'view_item' => __( 'View Portfolio', 'iccpt' ),
    'search_items' => __( 'Search Portfolios', 'iccpt' ),
    'not_found' =>  __( 'No portfolios found', 'iccpt' ),
    'not_found_in_trash' => __( 'No portfolios found in Trash', 'iccpt' ), 
    'parent_item_colon' => '',
    'menu_name' => __( 'Portfolios', 'iccpt' )

  );
  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'show_in_menu' => true, 
    'query_var' => true,
    'rewrite' => array( 'slug' => _x( 'portfolio', 'URL slug', 'iccpt' ) ),
    'capability_type' => 'post',
    'has_archive' => true, 
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array( 'title', 'editor', 'comments' )
  );
	register_post_type( 'portfolio', $args );
}

