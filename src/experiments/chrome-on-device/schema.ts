/**
 * Per-ability prompt mappings for Chrome's on-device Prompt API.
 *
 * Each entry translates an ability's PHP-side input into Chrome's
 * `initialPrompts` (system instruction) + a user prompt string + a
 * JSON `responseConstraint` derived from the ability's output_schema.
 *
 * Adding an ability here without updating the eligible-abilities list in the
 * PHP-side localized data does nothing — the runtime only registers handlers
 * for IDs that PHP says are eligible.
 */

/**
 * Internal dependencies
 */
import type { LanguageModelInitialPrompt } from './types';

interface AbilitySpec {
	readonly initialPrompts: LanguageModelInitialPrompt[];
	readonly responseConstraint: Record< string, unknown >;
	readonly buildPrompt: ( input: unknown ) => string;
	readonly extractResult: ( raw: string ) => unknown;
	readonly canRunOnDevice: ( input: unknown ) => boolean;
}

const TITLE_SYSTEM_INSTRUCTION = [
	'You are an editorial assistant that generates title suggestions for online articles and pages.',
	'',
	"Goal: You will be provided with content and optionally some additional context and you should then generate a concise, engaging, and accurate title that reflects that. This title should be optimized for clarity, engagement, and SEO - while maintaining an appropriate tone for the author's intent and audience.",
	'',
	'The title suggestion should follow these requirements:',
	'',
	'- Be no more than 80 characters',
	'- Should not contain any markdown, bullets, numbering, or formatting - plain text only',
	'- Should be distinct in tone and focus',
	'- Must reflect the actual content and context, not generic clickbait',
	'- Ensure the title you return matches the language of the content you are given. For example, if the content is in English, the title should be in English. If the content is in Spanish, the title should be in Spanish',
	'- Output only the title text. Respond directly without preamble, without phrases like "Here is...", "Here\'s...", "Sure,", or "Of course,". Do not wrap the output in quotes, code fences, or tags. Do not add closing remarks, follow-up questions, or meta-commentary',
].join( '\n' );

const TITLE_RESPONSE_CONSTRAINT = {
	type: 'object',
	properties: {
		title: { type: 'string' },
	},
	required: [ 'title' ],
	additionalProperties: false,
};

function getString( input: unknown, key: string ): string {
	if ( input && typeof input === 'object' && key in input ) {
		const value = ( input as Record< string, unknown > )[ key ];
		if ( typeof value === 'string' ) {
			return value;
		}
	}
	return '';
}

function isNumericContextValue( value: unknown ): boolean {
	if ( typeof value === 'number' ) {
		return Number.isFinite( value );
	}
	if ( typeof value === 'string' && value.trim() !== '' ) {
		return ! Number.isNaN( Number( value ) );
	}
	return false;
}

const titleGeneration: AbilitySpec = {
	initialPrompts: [ { role: 'system', content: TITLE_SYSTEM_INSTRUCTION } ],
	responseConstraint: TITLE_RESPONSE_CONSTRAINT,
	canRunOnDevice( input ) {
		// When context is a post ID, the PHP side hydrates content from that
		// post — on-device can't do that lookup, so fall back to REST.
		if ( input && typeof input === 'object' && 'context' in input ) {
			const context = ( input as { context: unknown } ).context;
			if (
				context !== undefined &&
				context !== null &&
				isNumericContextValue( context )
			) {
				return false;
			}
		}
		// Need at least some content to summarize.
		return getString( input, 'content' ).trim().length > 0;
	},
	buildPrompt( input ) {
		const content = getString( input, 'content' );
		const context = getString( input, 'context' );
		let prompt = '<content>' + content + '</content>';
		if ( context ) {
			prompt +=
				'\n\n<additional-context>' + context + '</additional-context>';
		}
		return prompt;
	},
	extractResult( raw ) {
		const parsed: unknown = JSON.parse( raw );
		if ( parsed && typeof parsed === 'object' && 'title' in parsed ) {
			const { title } = parsed as { title: unknown };
			if ( typeof title === 'string' ) {
				return { title: title.trim() };
			}
		}
		throw new Error(
			'chrome-on-device: on-device output did not match the title schema.'
		);
	},
};

const ABILITY_SPECS: Record< string, AbilitySpec > = {
	'ai/title-generation': titleGeneration,
};

export function getAbilitySpec( abilityName: string ): AbilitySpec | undefined {
	return ABILITY_SPECS[ abilityName ];
}
