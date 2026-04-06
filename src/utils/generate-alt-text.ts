/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { runAbility } from './run-ability';
import { replaceBlockWithPlaceholder } from '../utils/blocks';
import type {
	AltTextGenerationAbilityInput,
	ImageBlockAttributes,
} from '../experiments/alt-text-generation/types';

const IMAGE_PLACEHOLDER = '[[IMAGE_GOES_HERE]]';

/**
 * Builds machine-readable context when the image block is hyperlinked.
 * Serialized post content omits this after the block is replaced by a placeholder.
 *
 * @param {ImageBlockAttributes} attributes Image block attributes from the editor.
 * @return {string} Context for the ability, or empty if not linked.
 */
export function buildImageBlockUsageContext(
	attributes: Pick< ImageBlockAttributes, 'href' | 'linkDestination' >
): string {
	const href = attributes.href?.trim();
	if ( ! href ) {
		return '';
	}

	const destination =
		attributes.linkDestination && attributes.linkDestination !== 'none'
			? attributes.linkDestination
			: '';

	const lines = [
		'<image-block-usage>',
		'In the WordPress editor this image block is hyperlinked.',
		`Link URL: ${ href }`,
	];

	if ( destination ) {
		lines.push( `Link destination setting: ${ destination }` );
	}

	lines.push(
		'When the image is the only content inside the link, alternative text should describe the link purpose or destination (not a visual description of the image).',
		'If visible text in the same link already describes that purpose, respond with exactly [[DECORATIVE_ALT]] for empty alternative text.',
		'</image-block-usage>'
	);

	return lines.join( '\n' );
}

/**
 * Generates alt text for an image using the AI ability.
 *
 * @param {number|undefined} attachmentId    The attachment ID.
 * @param {string|undefined} imageUrl        The image URL (fallback if no attachment ID).
 * @param {string|undefined} content         The content of the post.
 * @param {string|undefined} clientId        The client ID of the current image block.
 * @param {string|undefined} imageUsageNotes Extra context (e.g. from buildImageBlockUsageContext).
 * @return {Promise<string>} The generated alt text (may be empty for decorative images).
 */
export async function generateAltText(
	attachmentId?: number | undefined,
	imageUrl?: string | undefined,
	content?: string | undefined,
	clientId?: string | undefined,
	imageUsageNotes?: string | undefined
): Promise< string > {
	const params: AltTextGenerationAbilityInput = {};

	if ( attachmentId ) {
		params.attachment_id = attachmentId;
	} else if ( imageUrl ) {
		params.image_url = imageUrl;
	} else {
		throw new Error(
			__( 'No image available to generate alt text for.', 'ai' )
		);
	}

	const contextParts: string[] = [];

	const trimmedUsage = imageUsageNotes?.trim();
	if ( trimmedUsage ) {
		contextParts.push( trimmedUsage );
	}

	if ( content ) {
		const contentWithPlaceholder =
			clientId !== undefined
				? replaceBlockWithPlaceholder(
						content,
						clientId,
						IMAGE_PLACEHOLDER
				  )
				: content;

		contextParts.push(
			`What follows is the full article content, where the target image block has been replaced with the placeholder ${ IMAGE_PLACEHOLDER }. Use surrounding text and any <image-block-usage> section to infer the image role (decorative, functional link, informative, etc.) per the accessibility rules in your instructions. For informative images, avoid repeating information already given in adjacent text unless it is required for understanding. CONTENT:\n\n${ contentWithPlaceholder }`
		);
	}

	if ( contextParts.length > 0 ) {
		params.context = contextParts.join( '\n\n' );
	}

	const response = await runAbility( 'ai/alt-text-generation', params );

	if ( response && typeof response === 'object' && 'alt_text' in response ) {
		return response.alt_text as string;
	}

	throw new Error( __( 'Failed to generate alt text.', 'ai' ) );
}
