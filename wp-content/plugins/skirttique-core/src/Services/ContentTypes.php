<?php
/**
 * Content types — Lookbook and Campaign.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * The Phase 2 editorial content types. Both are block-composed (the
 * Stage 17 library is their vocabulary) and live in the plugin so the
 * content survives any future theme redesign.
 *
 * The Journal is deliberately NOT a custom post type: WordPress posts
 * with core categories are purpose-built for editorial blogging (feeds,
 * comments, SEO integration all free). Stage 18 seeds its categories;
 * /journal/ is the posts page.
 *
 * - lookbook: immersive photography stories. Public, archived at
 *   /lookbook/, no shopping pressure — galleries, film, full-bleed heroes.
 * - campaign: one landing page per launch. Public, no archive (each is
 *   its own destination at /campaign/{slug}/), noindexed until the
 *   owner chooses otherwise per campaign via Rank Math.
 */
final class ContentTypes implements ServiceInterface {

	public function register(): void {
		add_action( 'init', array( $this, 'register_types' ) );
	}

	public function register_types(): void {
		register_post_type(
			'lookbook',
			array(
				'labels'       => array(
					'name'          => __( 'Lookbooks', 'skirttique-core' ),
					'singular_name' => __( 'Lookbook', 'skirttique-core' ),
					'add_new_item'  => __( 'New lookbook', 'skirttique-core' ),
					'edit_item'     => __( 'Edit lookbook', 'skirttique-core' ),
				),
				'description'  => __( 'Immersive editorial photography stories.', 'skirttique-core' ),
				'public'       => true,
				'show_in_rest' => true, // Block editor.
				'menu_icon'    => 'dashicons-camera',
				'menu_position' => 21,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'lookbook', 'with_front' => false ),
				'template'     => array(
					array( 'skirttique/hero', array( 'isBanner' => false ) ),
					array( 'skirttique/gallery' ),
				),
			)
		);

		register_post_type(
			'campaign',
			array(
				'labels'       => array(
					'name'          => __( 'Campaigns', 'skirttique-core' ),
					'singular_name' => __( 'Campaign', 'skirttique-core' ),
					'add_new_item'  => __( 'New campaign', 'skirttique-core' ),
					'edit_item'     => __( 'Edit campaign', 'skirttique-core' ),
				),
				'description'  => __( 'Immersive landing pages for launches.', 'skirttique-core' ),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-megaphone',
				'menu_position' => 22,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
				'has_archive'  => false,
				'rewrite'      => array( 'slug' => 'campaign', 'with_front' => false ),
				'template'     => array(
					array( 'skirttique/hero' ),
					array( 'skirttique/product-grid' ),
					array( 'skirttique/cta' ),
				),
			)
		);
	}
}
