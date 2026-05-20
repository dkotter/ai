<?php
/**
 * Chrome on-device provider stub.
 *
 * @package WordPress\AI\Experiments\Chrome_On_Device\Provider
 */

declare( strict_types=1 );

namespace WordPress\AI\Experiments\Chrome_On_Device\Provider;

use WordPress\AiClient\Providers\AbstractProvider;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

defined( 'ABSPATH' ) || exit;

/**
 * Stub PHP Provider for Chrome's on-device Prompt API.
 *
 * Extends AbstractProvider directly — there is no HTTP transport because
 * the model lives in the user's browser, so no `baseUrl()`. Registering
 * this provider with AiClient::defaultRegistry() makes the Chrome runtime
 * appear in the Connectors UI even though all generation happens client-side
 * via the Chrome_On_Device experiment.
 *
 * @since x.x.x
 */
class Chrome_Provider extends AbstractProvider {

	public const PROVIDER_ID = 'chrome';

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	protected static function createModel(
		ModelMetadata $model_metadata,
		ProviderMetadata $provider_metadata
	): ModelInterface {
		return new Chrome_Model( $model_metadata, $provider_metadata );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	protected static function createProviderMetadata(): ProviderMetadata {
		return new ProviderMetadata(
			self::PROVIDER_ID,
			__( 'Chrome On-Device', 'ai' ),
			ProviderTypeEnum::client(),
			'',
			RequestAuthenticationMethod::apiKey(),
			__( 'Runs Gemini Nano on-device in Chrome 148+ via the Prompt API. No credentials required; availability is detected in the browser.', 'ai' ),
			__DIR__ . '/logo.svg'
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	protected static function createProviderAvailability(): ProviderAvailabilityInterface {
		return new Chrome_Availability();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {
		return new Chrome_Model_Metadata_Directory();
	}
}
