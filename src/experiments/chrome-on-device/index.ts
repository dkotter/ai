/**
 * Chrome on-device AI experiment — JS entry.
 *
 * Replaces the server-side ability stubs with
 * client-side handlers that run prompts against Chrome's on-device
 * LanguageModel. Each handler falls back to the existing REST
 * endpoint internally when the on-device model is unavailable, so callers
 * never see a difference in error behavior.
 */

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import './types';
import { detectAvailability } from './detector';
import { runOnDevice } from './runner';
import { getAbilitySpec } from './schema';

interface ChromeOnDeviceData {
	enabled?: boolean;
	abilities?: string[];
}

declare global {
	interface Window {
		aiChromeOnDeviceData?: ChromeOnDeviceData;
	}
}

const REST_BASE = '/wp-abilities/v1/abilities';

async function callRestFallback(
	abilityName: string,
	input: unknown
): Promise< unknown > {
	return apiFetch( {
		path: `${ REST_BASE }/${ abilityName }/run`,
		method: 'POST',
		data: { input: input ?? null },
	} );
}

async function loadAbilitiesModule() {
	// Keep these as runtime imports so webpack does NOT emit a hard
	// `wp-abilities` / `wp-core-abilities` script dependency in the asset
	// manifest.
	try {
		const coreAbilities = await import(
			/* webpackIgnore: true */ '@wordpress/core-abilities'
		);
		await coreAbilities.ready;
	} catch {
		// `@wordpress/core-abilities` may not be available; we'll still try
		// `@wordpress/abilities` directly below.
	}
	try {
		return await import( /* webpackIgnore: true */ '@wordpress/abilities' );
	} catch {
		return null;
	}
}

async function registerHandlers(): Promise< void > {
	const data = window.aiChromeOnDeviceData;
	if ( ! data || ! data.enabled ) {
		return;
	}

	const eligible = ( data.abilities ?? [] ).filter( ( name ) =>
		Boolean( getAbilitySpec( name ) )
	);
	if ( eligible.length === 0 ) {
		return;
	}

	const availability = await detectAvailability();
	if ( availability === 'unavailable' ) {
		// eslint-disable-next-line no-console
		console.warn(
			"[chrome-on-device] Chrome's on-device Prompt API is unavailable; skipping client-side handler registration."
		);
		return;
	}

	const abilities = await loadAbilitiesModule();
	if ( ! abilities || typeof abilities.registerAbility !== 'function' ) {
		// eslint-disable-next-line no-console
		console.warn(
			'[chrome-on-device] @wordpress/abilities.registerAbility is unavailable; skipping client-side handler registration.'
		);
		return;
	}

	for ( const abilityName of eligible ) {
		const spec = getAbilitySpec( abilityName );
		if ( ! spec ) {
			continue;
		}

		// The server-side ability is already in the store (loaded by
		// @wordpress/core-abilities). Spread its metadata so we preserve
		// category, input_schema, output_schema, etc., and only override the
		// callback. If the server ability isn't in the store (e.g. user lacks
		// permission to discover it), skip — the existing REST wrapper still
		// works fine in that case.
		const existing = abilities.getAbility( abilityName );
		if ( ! existing ) {
			continue;
		}

		abilities.registerAbility( {
			...existing,
			callback: async ( input: unknown ) => {
				if ( spec.canRunOnDevice( input ) ) {
					try {
						const raw = await runOnDevice(
							spec.buildPrompt( input ),
							{
								initialPrompts: [ ...spec.initialPrompts ],
								responseConstraint: spec.responseConstraint,
							}
						);
						return spec.extractResult( raw );
					} catch ( error ) {
						// eslint-disable-next-line no-console
						console.warn(
							`[chrome-on-device] ${ abilityName } on-device run failed; falling back to REST.`,
							error
						);
					}
				}
				return callRestFallback( abilityName, input );
			},
		} );
	}
}

void registerHandlers();
