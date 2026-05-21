/**
 * TypeScript declarations for Chrome's on-device Prompt API.
 *
 * The runtime exposes a global `LanguageModel` in Chrome ≥ 148 when the
 * Prompt API is available. See
 * https://developer.chrome.com/docs/ai/prompt-api for the canonical spec.
 */

export type AvailabilityStatus =
	| 'available'
	| 'downloadable'
	| 'downloading'
	| 'unavailable';

export interface LanguageModelExpectedIO {
	type: 'text' | 'image' | 'audio';
	languages?: string[];
}

export interface LanguageModelAvailabilityOptions {
	expectedInputs?: LanguageModelExpectedIO[];
	expectedOutputs?: LanguageModelExpectedIO[];
}

export interface LanguageModelInitialPrompt {
	role: 'system' | 'user' | 'assistant';
	content: string;
}

export interface LanguageModelCreateOptions
	extends LanguageModelAvailabilityOptions {
	initialPrompts?: LanguageModelInitialPrompt[];
	signal?: AbortSignal;
}

export interface LanguageModelPromptOptions {
	signal?: AbortSignal;
	responseConstraint?: Record< string, unknown >;
}

export interface LanguageModelSession {
	prompt: (
		input: string,
		options?: LanguageModelPromptOptions
	) => Promise< string >;
	promptStreaming: (
		input: string,
		options?: LanguageModelPromptOptions
	) => ReadableStream< string >;
	destroy: () => void;
}

export interface LanguageModelStatic {
	availability: (
		options?: LanguageModelAvailabilityOptions
	) => Promise< AvailabilityStatus >;
	create: (
		options?: LanguageModelCreateOptions
	) => Promise< LanguageModelSession >;
}

declare global {
	var LanguageModel: LanguageModelStatic | undefined;
}

export {};
