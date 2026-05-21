/**
 * Availability detector for Chrome's on-device Prompt API.
 */

/**
 * Internal dependencies
 */
import type { AvailabilityStatus } from './types';

const SAFE_DEFAULT_LANGUAGE = 'en';

function getDocumentLanguage(): string {
	if ( typeof document === 'undefined' ) {
		return SAFE_DEFAULT_LANGUAGE;
	}
	return document.documentElement.lang || SAFE_DEFAULT_LANGUAGE;
}

/**
 * Reports the availability of Chrome's on-device Prompt API.
 *
 * Returns 'unavailable' when the global `LanguageModel` is missing (every
 * non-Chrome browser, plus Chrome < 148) or when the runtime probe throws
 * (Workers, cross-origin iframes without the Permissions-Policy, etc.).
 */
export async function detectAvailability(): Promise< AvailabilityStatus > {
	if ( typeof LanguageModel === 'undefined' ) {
		return 'unavailable';
	}
	try {
		const language = getDocumentLanguage();
		return await LanguageModel.availability( {
			expectedInputs: [ { type: 'text', languages: [ language ] } ],
			expectedOutputs: [ { type: 'text', languages: [ language ] } ],
		} );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.warn(
			'[chrome-on-device] availability() threw, treating as unavailable.',
			error
		);
		return 'unavailable';
	}
}
