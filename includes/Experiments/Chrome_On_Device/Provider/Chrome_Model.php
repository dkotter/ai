<?php
/**
 * Chrome on-device model stub.
 *
 * @package WordPress\AI\Experiments\Chrome_On_Device\Provider
 */

declare( strict_types=1 );

namespace WordPress\AI\Experiments\Chrome_On_Device\Provider;

use RuntimeException;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

defined( 'ABSPATH' ) || exit;

/**
 * Stub model for Chrome's on-device runtime.
 *
 * Implements ModelInterface + TextGenerationModelInterface so the model
 * advertises text-generation capability and appears in capability-aware
 * surfaces. Any PHP-side invocation throws — actual generation lives in
 * the browser and is invoked from JS via LanguageModel.*.
 *
 * @since x.x.x
 */
class Chrome_Model implements ModelInterface, TextGenerationModelInterface {

	private ModelMetadata $model_metadata;

	private ProviderMetadata $provider_metadata;

	private ModelConfig $config;

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 *
	 * @param \WordPress\AiClient\Providers\Models\DTO\ModelMetadata $model_metadata The model metadata.
	 * @param \WordPress\AiClient\Providers\DTO\ProviderMetadata $provider_metadata The provider metadata.
	 */
	public function __construct( ModelMetadata $model_metadata, ProviderMetadata $provider_metadata ) {
		$this->model_metadata    = $model_metadata;
		$this->provider_metadata = $provider_metadata;
		$this->config            = new ModelConfig();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function metadata(): ModelMetadata {
		return $this->model_metadata;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function providerMetadata(): ProviderMetadata {
		return $this->provider_metadata;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function setConfig( ModelConfig $config ): void {
		$this->config = $config;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function getConfig(): ModelConfig {
		return $this->config;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 *
	 * @throws \RuntimeException Always — Chrome's Prompt API only exists in the user's browser.
	 */
	public function generateTextResult( array $prompt ): GenerativeAiResult {
		throw new RuntimeException(
			'chrome-on-device is a client-side provider; generation must be invoked from JavaScript via LanguageModel.*'
		);
	}
}
