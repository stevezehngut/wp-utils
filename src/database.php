<?php

namespace Zeek\WP_Util;

use Throwable;

/**
 * Run the given callback within a database transaction, rolling back the transaction if an error occurs.
 *
 * @param callable $callback
 * @param array    $args
 *
 * @return mixed
 *
 * @throws Throwable
 */
function db_transaction( callable $callback, array $args = [] ) {
	try {
		wpdb_query( 'START TRANSACTION' );

		$result = call_user_func_array( $callback, $args );

		wpdb_query( 'COMMIT' );
	} catch ( Throwable $exception ) {
		wpdb_query( 'ROLLBACK' );

		throw $exception;
	}

	return $result;
}

/**
 * Performs a reverse lookup of a post based on it's slug
 * Stores in whatever default cache is available in order to minimize duplicate
 * lookups (as this can get expensive)
 *
 * @return int|null
 */
function get_id_from_slug( $slug, $post_type = 'post', $force = false ) {

	global $wpdb;

	$cache_key = sprintf( 'post_%s_id', md5( $post_type . $slug ) );
	$id        = wp_cache_get( $cache_key );

	if ( false === $id || $force ) {

		$id = $wpdb->get_var( $wpdb->prepare( "
			SELECT 
				ID
			FROM 
				$wpdb->posts
			WHERE 
				post_status = 'publish' AND
				post_name   = '%s' AND
				post_type   = '%s'
			LIMIT 1
			",
			sanitize_text_field( $slug ),
			sanitize_text_field( $post_type )
		) );

		wp_cache_set( $cache_key, $id );
	}

	if ( empty( $id ) ) {
		return null;
	}

	return intval( $id );
}

/**
 * Perform a reverse lookup for a meta key based on a meta value.
 *
 * This is pretty non-performant, so take care in using this.
 *
 * @param $post_id
 * @param $meta_value
 *
 * @return null|string
 */
function get_meta_key_from_meta_value( $post_id, $meta_value ) {
	global $wpdb;

	return $wpdb->get_var( $wpdb->prepare( "
			SELECT
				pm.meta_key
			FROM
				$wpdb->postmeta as pm
			WHERE
				pm.post_id = %d AND
				pm.meta_value = %s
			",
		intval( $post_id ),
		sanitize_text_field( $meta_value )
	) );
}

/**
 * Performs a very direct, simple query to the WordPress Post Meta table
 * that bypasses normal WP caching
 *
 * @param $key
 *
 * @return int
 */
function get_raw_post_meta_value( $post_id, $key ) {
	global $wpdb;

	return $wpdb->get_var( $wpdb->prepare( "
			SELECT 
				meta_value
			FROM 
				{$wpdb->postmeta}
			WHERE 
				post_id = %d AND
				meta_key = %s
			LIMIT 1
			",
		intval( $post_id ),
		$key
	) );
}

/**
 * Performs a very direct, simple query to the WordPress Options table
 * that bypasses normal WP caching
 *
 * @param $key
 *
 * @return int
 */
function get_raw_option_value( $key ) {
	global $wpdb;

	return $wpdb->get_var( $wpdb->prepare( "
			SELECT 
				option_value 
			FROM 
				{$wpdb->options}
			WHERE 
				option_name = %s
			LIMIT 1
			",
		$key
	) );
}

function does_table_exist( $table ) {
	global $wpdb;

	return $wpdb->query( $wpdb->prepare( "
			SHOW TABLES LIKE %s
		",
		$wpdb->prefix . $table
	) );
}

function get_var_from_table( $table, $select, $where_col, $where_val ) {
	global $wpdb;

	$select = sanitize_text_field( $select );
	$table = $wpdb->prefix . sanitize_text_field( $table );
	$where_col = sanitize_text_field( $where_col );

	return wpdb_get_var( $wpdb->prepare ("
		SELECT
			{$select}
		FROM
			{$table}
		WHERE
			{$where_col} = %s
		",
		$where_val
	) );
}

function get_post_id_by_meta_key_value( $key, $value ) {
	global $wpdb;

	return $wpdb->get_var( $wpdb->prepare( "
			SELECT
				pm.post_id
			FROM
				$wpdb->postmeta as pm
			WHERE
				pm.meta_key = %s AND
				pm.meta_value = %s
			",
		sanitize_text_field( $key ),
		sanitize_text_field( $value )
	) );
}
