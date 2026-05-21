/**
 * Runner for executing prompts against Chrome's on-device LanguageModel.
 */

/**
 * Internal dependencies
 */
import type {
	LanguageModelCreateOptions,
	LanguageModelInitialPrompt,
	LanguageModelPromptOptions,
	LanguageModelSession,
} from './types';

export interface RunOptions {
	initialPrompts?: LanguageModelInitialPrompt[];
	responseConstraint?: Record< string, unknown >;
	signal?: AbortSignal;
}

/**
 * Runs a single prompt against the on-device model and returns the raw text.
 *
 * Creates a fresh session per call. Sessions are not pooled in v1 because the
 * inputs vary per ability invocation (different initialPrompts/schemas) and
 * Gemini Nano sessions are cheap to create. We can introduce a session cache
 * later if profiling shows it matters.
 */
export async function runOnDevice(
	prompt: string,
	options: RunOptions = {}
): Promise< string > {
	if ( typeof LanguageModel === 'undefined' ) {
		throw new Error(
			'chrome-on-device: LanguageModel is not available in this browser context.'
		);
	}

	const { initialPrompts, responseConstraint, signal } = options;

	const createOptions: LanguageModelCreateOptions = {};
	if ( initialPrompts ) {
		createOptions.initialPrompts = initialPrompts;
	}
	if ( signal ) {
		createOptions.signal = signal;
	}

	const session: LanguageModelSession =
		await LanguageModel.create( createOptions );

	try {
		const promptOptions: LanguageModelPromptOptions = {};
		if ( signal ) {
			promptOptions.signal = signal;
		}
		if ( responseConstraint ) {
			promptOptions.responseConstraint = responseConstraint;
		}
		return await session.prompt( prompt, promptOptions );
	} finally {
		try {
			session.destroy();
		} catch {
			// `destroy()` may already have run if the signal aborted the session.
		}
	}
}
