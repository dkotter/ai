<?php
/**
 * Chrome on-device provider availability checker.
 *
 * @package WordPress\AI\Experiments\Chrome_On_Device\Provider
 */

declare( strict_types=1 );

namespace WordPress\AI\Experiments\Chrome_On_Device\Provider;

use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Reports availability for the Chrome on-device provider.
 *
 * Real availability is determined in the browser via LanguageModel.availability().
 * From PHP we always report configured: the JS detector is the source of truth and
 * surfaces unavailable/downloading states.
 *
 * @since x.x.x
 */
class Chrome_Availability implements ProviderAvailabilityInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function isConfigured(): bool {
		return true;
	}
}
