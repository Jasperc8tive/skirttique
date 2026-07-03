/**
 * Skirttique block library — editor side.
 *
 * Client registration for the blocks the theme registers server-side in
 * inc/blocks.php. One generic edit factory: sidebar controls generated
 * from the FIELDS registry below (kept in sync with the PHP manifest),
 * canvas preview via ServerSideRender so the editor shows exactly what
 * the storefront ships.
 */

import { registerBlockType } from '@wordpress/blocks';
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

/**
 * Field registry — mirrors inc/blocks.php manifest() attribute-for-
 * attribute. type: text|textarea|url|toggle|number|select|image|images.
 */
const BLOCKS = {
	'skirttique/hero': {
		title: __( 'Hero', 'skirttique' ),
		description: __( 'Full-bleed editorial hero with statement and hemline CTA.', 'skirttique' ),
		icon: 'cover-image',
		fields: [
			{ key: 'imageId', type: 'image', label: __( 'Hero image', 'skirttique' ) },
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ) },
			{ key: 'statement', type: 'text', label: __( 'Statement', 'skirttique' ), default: __( 'The skirt, reconsidered', 'skirttique' ) },
			{ key: 'sub', type: 'text', label: __( 'Supporting line', 'skirttique' ) },
			{ key: 'ctaLabel', type: 'text', label: __( 'CTA label', 'skirttique' ) },
			{ key: 'ctaUrl', type: 'url', label: __( 'CTA link', 'skirttique' ) },
			{ key: 'isBanner', type: 'toggle', label: __( 'Section banner (not the page heading)', 'skirttique' ) },
			{ key: 'parallax', type: 'toggle', label: __( 'Subtle parallax', 'skirttique' ), default: true },
		],
	},
	'skirttique/editorial': {
		title: __( 'Editorial Section', 'skirttique' ),
		description: __( 'Statement and prose beside a portrait figure.', 'skirttique' ),
		icon: 'align-pull-right',
		fields: [
			{ key: 'imageId', type: 'image', label: __( 'Figure image', 'skirttique' ) },
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ) },
			{ key: 'statement', type: 'text', label: __( 'Statement', 'skirttique' ) },
			{ key: 'prose', type: 'textarea', label: __( 'Prose', 'skirttique' ) },
			{ key: 'ctaLabel', type: 'text', label: __( 'CTA label', 'skirttique' ) },
			{ key: 'ctaUrl', type: 'url', label: __( 'CTA link', 'skirttique' ) },
			{
				key: 'mediaSide',
				type: 'select',
				label: __( 'Image side', 'skirttique' ),
				options: [
					{ value: 'right', label: __( 'Right', 'skirttique' ) },
					{ value: 'left', label: __( 'Left', 'skirttique' ) },
				],
			},
		],
	},
	'skirttique/cta': {
		title: __( 'CTA Band', 'skirttique' ),
		description: __( 'Statement on Foliage ground with one action.', 'skirttique' ),
		icon: 'megaphone',
		fields: [
			{ key: 'statement', type: 'text', label: __( 'Statement', 'skirttique' ) },
			{ key: 'ctaLabel', type: 'text', label: __( 'CTA label', 'skirttique' ) },
			{ key: 'ctaUrl', type: 'url', label: __( 'CTA link', 'skirttique' ) },
		],
	},
	'skirttique/collection-cards': {
		title: __( 'Collection Cards', 'skirttique' ),
		description: __( 'Every collection as an editorial card (imagery from Products → Categories).', 'skirttique' ),
		icon: 'grid-view',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ) },
			{ key: 'title', type: 'text', label: __( 'Title', 'skirttique' ) },
		],
	},
	'skirttique/product-grid': {
		title: __( 'Product Grid', 'skirttique' ),
		description: __( 'Four-up product cards from a chosen source.', 'skirttique' ),
		icon: 'products',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ) },
			{ key: 'title', type: 'text', label: __( 'Title', 'skirttique' ) },
			{
				key: 'source',
				type: 'select',
				label: __( 'Products', 'skirttique' ),
				options: [
					{ value: 'newest', label: __( 'Newest', 'skirttique' ) },
					{ value: 'bestsellers', label: __( 'Bestsellers', 'skirttique' ) },
					{ value: 'sale', label: __( 'On sale', 'skirttique' ) },
					{ value: 'category', label: __( 'From a collection', 'skirttique' ) },
					{ value: 'handpicked', label: __( 'Handpicked', 'skirttique' ) },
				],
			},
			{ key: 'extra', type: 'text', label: __( 'Collection slug or product ids', 'skirttique' ), help: __( 'Only for “From a collection” (slug) or “Handpicked” (comma-separated ids).', 'skirttique' ) },
			{ key: 'count', type: 'number', label: __( 'How many', 'skirttique' ), min: 1, max: 12, default: 4 },
			{ key: 'moreLabel', type: 'text', label: __( '“View more” label', 'skirttique' ) },
			{ key: 'moreUrl', type: 'url', label: __( '“View more” link', 'skirttique' ) },
		],
	},
	'skirttique/product-slider': {
		title: __( 'Product Slider', 'skirttique' ),
		description: __( 'Products on a horizontal editorial rail.', 'skirttique' ),
		icon: 'slides',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ) },
			{ key: 'title', type: 'text', label: __( 'Title', 'skirttique' ) },
			{
				key: 'source',
				type: 'select',
				label: __( 'Products', 'skirttique' ),
				options: [
					{ value: 'newest', label: __( 'Newest', 'skirttique' ) },
					{ value: 'bestsellers', label: __( 'Bestsellers', 'skirttique' ) },
					{ value: 'sale', label: __( 'On sale', 'skirttique' ) },
					{ value: 'category', label: __( 'From a collection', 'skirttique' ) },
					{ value: 'handpicked', label: __( 'Handpicked', 'skirttique' ) },
				],
			},
			{ key: 'extra', type: 'text', label: __( 'Collection slug or product ids', 'skirttique' ) },
			{ key: 'count', type: 'number', label: __( 'How many', 'skirttique' ), min: 2, max: 12, default: 8 },
		],
	},
	'skirttique/testimonials': {
		title: __( 'Testimonials', 'skirttique' ),
		description: __( 'Rotating client-voice quotes with a pause control.', 'skirttique' ),
		icon: 'format-quote',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ), default: __( 'In their words', 'skirttique' ) },
			{ key: 'quotes', type: 'textarea', label: __( 'Quotes', 'skirttique' ), help: __( 'One per line: Quote|Source', 'skirttique' ), rows: 6 },
			{ key: 'interval', type: 'number', label: __( 'Rotation (ms)', 'skirttique' ), min: 4000, max: 20000, default: 9000 },
		],
	},
	'skirttique/newsletter': {
		title: __( 'Newsletter', 'skirttique' ),
		description: __( 'The house-list join form, as a standalone band.', 'skirttique' ),
		icon: 'email',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ) },
			{ key: 'title', type: 'text', label: __( 'Title', 'skirttique' ) },
			{ key: 'promise', type: 'textarea', label: __( 'Promise', 'skirttique' ) },
		],
	},
	'skirttique/faq': {
		title: __( 'FAQ', 'skirttique' ),
		description: __( 'Hairline accordion of questions.', 'skirttique' ),
		icon: 'editor-help',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ) },
			{ key: 'title', type: 'text', label: __( 'Title', 'skirttique' ) },
			{ key: 'items', type: 'textarea', label: __( 'Questions', 'skirttique' ), help: __( 'One per line: Question|Answer', 'skirttique' ), rows: 8 },
		],
	},
	'skirttique/stats': {
		title: __( 'Statistics', 'skirttique' ),
		description: __( 'A quiet row of numbers.', 'skirttique' ),
		icon: 'chart-bar',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ) },
			{ key: 'title', type: 'text', label: __( 'Title', 'skirttique' ) },
			{ key: 'items', type: 'textarea', label: __( 'Stats', 'skirttique' ), help: __( 'One per line: Value|Label', 'skirttique' ), rows: 5 },
		],
	},
	'skirttique/feature-list': {
		title: __( 'Feature List', 'skirttique' ),
		description: __( 'Title and description pairs on hairline rows.', 'skirttique' ),
		icon: 'editor-ul',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ) },
			{ key: 'title', type: 'text', label: __( 'Title', 'skirttique' ) },
			{ key: 'items', type: 'textarea', label: __( 'Features', 'skirttique' ), help: __( 'One per line: Title|Description', 'skirttique' ), rows: 6 },
		],
	},
	'skirttique/trust-badges': {
		title: __( 'Trust Badges', 'skirttique' ),
		description: __( 'The store’s promises as a hairline strip.', 'skirttique' ),
		icon: 'shield',
		fields: [
			{ key: 'delivery', type: 'toggle', label: __( 'Worldwide delivery', 'skirttique' ), default: true },
			{ key: 'returns', type: 'toggle', label: __( '14-day returns', 'skirttique' ), default: true },
			{ key: 'secure', type: 'toggle', label: __( 'Secure checkout', 'skirttique' ), default: true },
			{ key: 'made', type: 'toggle', label: __( 'Made in limited runs', 'skirttique' ) },
		],
	},
	'skirttique/gallery': {
		title: __( 'Gallery', 'skirttique' ),
		description: __( 'Editorial image grid with drape reveals.', 'skirttique' ),
		icon: 'format-gallery',
		fields: [
			{ key: 'imageIds', type: 'images', label: __( 'Images', 'skirttique' ) },
			{ key: 'columns', type: 'number', label: __( 'Columns', 'skirttique' ), min: 2, max: 4, default: 3 },
			{ key: 'zoom', type: 'toggle', label: __( 'Click to zoom', 'skirttique' ) },
		],
	},
	'skirttique/video': {
		title: __( 'Video', 'skirttique' ),
		description: __( 'A poster-first editorial film.', 'skirttique' ),
		icon: 'video-alt3',
		fields: [
			{ key: 'videoUrl', type: 'url', label: __( 'Video URL (mp4)', 'skirttique' ) },
			{ key: 'posterId', type: 'image', label: __( 'Poster image', 'skirttique' ) },
			{ key: 'ambient', type: 'toggle', label: __( 'Ambient (muted loop, no controls)', 'skirttique' ) },
			{ key: 'caption', type: 'text', label: __( 'Caption', 'skirttique' ) },
		],
	},
	'skirttique/pricing': {
		title: __( 'Pricing', 'skirttique' ),
		description: __( 'Bespoke tiers on hairline cards.', 'skirttique' ),
		icon: 'tag',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ) },
			{ key: 'title', type: 'text', label: __( 'Title', 'skirttique' ) },
			{ key: 'items', type: 'textarea', label: __( 'Tiers', 'skirttique' ), help: __( 'One per line: Tier|Price|Description', 'skirttique' ), rows: 5 },
		],
	},
	'skirttique/breadcrumbs': {
		title: __( 'Breadcrumbs', 'skirttique' ),
		description: __( 'The “you are here” trail for the current page.', 'skirttique' ),
		icon: 'admin-links',
		fields: [],
	},
	'skirttique/featured-collection': {
		title: __( 'Featured Collection', 'skirttique' ),
		description: __( 'One collection as a wide editorial spotlight — story and hero from Products → Categories.', 'skirttique' ),
		icon: 'awards',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ), default: __( 'Collection in focus', 'skirttique' ) },
			{ key: 'slug', type: 'text', label: __( 'Collection slug', 'skirttique' ), help: __( 'Blank picks the first collection with an editorial story or hero.', 'skirttique' ) },
			{ key: 'ctaLabel', type: 'text', label: __( 'CTA label', 'skirttique' ), help: __( 'Blank: “Explore {collection}”.', 'skirttique' ) },
		],
	},
	'skirttique/featured-product': {
		title: __( 'Featured Product', 'skirttique' ),
		description: __( 'One piece given the full editorial spotlight.', 'skirttique' ),
		icon: 'star-filled',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ), default: __( 'The featured piece', 'skirttique' ) },
			{ key: 'productId', type: 'id', label: __( 'Product id', 'skirttique' ), help: __( '0 or blank: the newest piece.', 'skirttique' ) },
			{ key: 'ctaLabel', type: 'text', label: __( 'CTA label', 'skirttique' ), help: __( 'Blank: “Discover the {product}”.', 'skirttique' ) },
		],
	},
	'skirttique/lookbook': {
		title: __( 'Lookbook Feature', 'skirttique' ),
		description: __( 'The latest (or a chosen) lookbook as a full-width cover tease.', 'skirttique' ),
		icon: 'camera',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ), default: __( 'The lookbook', 'skirttique' ) },
			{ key: 'lookbookId', type: 'id', label: __( 'Lookbook id', 'skirttique' ), help: __( '0 or blank: the latest published lookbook.', 'skirttique' ) },
			{ key: 'ctaLabel', type: 'text', label: __( 'CTA label', 'skirttique' ), help: __( 'Blank: “View the lookbook”.', 'skirttique' ) },
		],
	},
	'skirttique/instagram': {
		title: __( 'Instagram', 'skirttique' ),
		description: __( 'A quiet tile strip with one follow link. Placeholder until the feed integration (Stage 26).', 'skirttique' ),
		icon: 'instagram',
		fields: [
			{ key: 'eyebrow', type: 'text', label: __( 'Eyebrow', 'skirttique' ), default: __( 'On Instagram', 'skirttique' ) },
			{ key: 'title', type: 'text', label: __( 'Title', 'skirttique' ), default: __( 'The house, worn', 'skirttique' ) },
			{ key: 'url', type: 'url', label: __( 'Profile URL', 'skirttique' ), help: __( 'Blank: the Instagram profile from House Settings → Social.', 'skirttique' ) },
			{ key: 'imageIds', type: 'images', label: __( 'Tiles (up to six)', 'skirttique' ) },
		],
	},
};

/** Render one sidebar control for a field. */
function Field( { field, attributes, setAttributes } ) {
	const value = attributes[ field.key ];
	const set = ( next ) => setAttributes( { [ field.key ]: next } );

	switch ( field.type ) {
		case 'textarea':
			return <TextareaControl label={ field.label } help={ field.help } rows={ field.rows ?? 4 } value={ value ?? '' } onChange={ set } />;
		case 'toggle':
			return <ToggleControl label={ field.label } checked={ !! value } onChange={ set } />;
		case 'number':
			return <RangeControl label={ field.label } min={ field.min ?? 0 } max={ field.max ?? 100 } value={ value ?? field.default ?? 0 } onChange={ set } />;
		case 'id':
			return (
				<TextControl
					label={ field.label }
					help={ field.help }
					type="number"
					value={ value ? String( value ) : '' }
					onChange={ ( next ) => set( parseInt( next, 10 ) || 0 ) }
				/>
			);
		case 'select':
			return <SelectControl label={ field.label } options={ field.options } value={ value } onChange={ set } />;
		case 'image':
			return (
				<MediaUploadCheck>
					<MediaUpload
						allowedTypes={ [ 'image' ] }
						value={ value }
						onSelect={ ( media ) => set( media.id ) }
						render={ ( { open } ) => (
							<div style={ { marginBottom: '16px' } }>
								<Button variant="secondary" onClick={ open }>
									{ value ? __( 'Change', 'skirttique' ) + ' — ' + field.label : field.label }
								</Button>
								{ !! value && (
									<Button variant="link" isDestructive onClick={ () => set( 0 ) }>
										{ __( 'Remove', 'skirttique' ) }
									</Button>
								) }
							</div>
						) }
					/>
				</MediaUploadCheck>
			);
		case 'images':
			return (
				<MediaUploadCheck>
					<MediaUpload
						multiple
						gallery
						allowedTypes={ [ 'image' ] }
						value={ value ?? [] }
						onSelect={ ( media ) => set( media.map( ( m ) => m.id ) ) }
						render={ ( { open } ) => (
							<div style={ { marginBottom: '16px' } }>
								<Button variant="secondary" onClick={ open }>
									{ ( value ?? [] ).length
										? __( 'Edit images', 'skirttique' ) + ` (${ value.length })`
										: field.label }
								</Button>
							</div>
						) }
					/>
				</MediaUploadCheck>
			);
		case 'url':
		case 'text':
		default:
			return <TextControl label={ field.label } help={ field.help } type={ 'url' === field.type ? 'url' : 'text' } value={ value ?? '' } onChange={ set } />;
	}
}

/** Generic edit: sidebar fields + a server-rendered canvas preview. */
function makeEdit( name, config ) {
	return function Edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps();

		return (
			<div { ...blockProps }>
				{ config.fields.length > 0 && (
					<InspectorControls>
						<PanelBody title={ config.title } initialOpen>
							{ config.fields.map( ( field ) => (
								<Field key={ field.key } field={ field } attributes={ attributes } setAttributes={ setAttributes } />
							) ) }
						</PanelBody>
					</InspectorControls>
				) }
				<ServerSideRender block={ name } attributes={ attributes } />
			</div>
		);
	};
}

/** Attribute schema from fields (client mirror of the PHP manifest). */
function attributesFor( config ) {
	const attrs = {};
	for ( const field of config.fields ) {
		if ( 'images' === field.type ) {
			attrs[ field.key ] = { type: 'array', default: [] };
		} else if ( 'toggle' === field.type ) {
			attrs[ field.key ] = { type: 'boolean', default: field.default ?? false };
		} else if ( 'number' === field.type || 'image' === field.type || 'id' === field.type ) {
			attrs[ field.key ] = { type: 'number', default: field.default ?? 0 };
		} else {
			attrs[ field.key ] = { type: 'string', default: field.default ?? '' };
		}
	}
	return attrs;
}

Object.entries( BLOCKS ).forEach( ( [ name, config ] ) => {
	registerBlockType( name, {
		apiVersion: 3,
		title: config.title,
		description: config.description,
		icon: config.icon,
		category: 'skirttique',
		attributes: attributesFor( config ),
		supports: { html: false },
		edit: makeEdit( name, config ),
		save: () => null,
	} );
} );
