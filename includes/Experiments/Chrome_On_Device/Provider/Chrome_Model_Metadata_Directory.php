<?php
/**
 * Chrome on-device model metadata directory.
 *
 * @package WordPress\AI\Experiments\Chrome_On_Device\Provider
 */

declare( strict_types=1 );

namespace WordPress\AI\Experiments\Chrome_On_Device\Provider;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

defined( 'ABSPATH' ) || exit;

/**
 * Static metadata directory for the single Chrome on-device model.
 *
 * Chrome does not expose a model-list API, so the directory is hardcoded.
 * Currently exposes only `gemini-nano` (text-in/text-out). Sampling params
 * (temperature, topK) are intentionally omitted because they are still
 * Origin-Trial-only on the stable web surface as of Chrome 148.
 *
 * @since x.x.x
 */
class Chrome_Model_Metadata_Directory implements ModelMetadataDirectoryInterface {

	public const MODEL_ID = 'gemini-nano';

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function listModelMetadata(): array {
		return array( self::create_gemini_nano_metadata() );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function hasModelMetadata( string $model_id ): bool {
		return self::MODEL_ID === $model_id;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 *
	 * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If model metadata is not found.
	 */
	public function getModelMetadata( string $model_id ): ModelMetadata {
		if ( ! $this->hasModelMetadata( $model_id ) ) {
			throw new InvalidArgumentException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not output.
				sprintf( 'Unknown Chrome on-device model "%s".', $model_id )
			);
		}
		return self::create_gemini_nano_metadata();
	}

	/**
	 * Builds the static ModelMetadata entry for Chrome's gemini-nano.
	 *
	 * @since x.x.x
	 *
	 * @return \WordPress\AiClient\Providers\Models\DTO\ModelMetadata The gemini-nano metadata.
	 */
	private static function create_gemini_nano_metadata(): ModelMetadata {
		return new ModelMetadata(
			self::MODEL_ID,
			'Gemini Nano (Chrome On-Device)',
			array( CapabilityEnum::textGeneration() ),
			array(
				new SupportedOption( OptionEnum::systemInstruction() ),
				new SupportedOption( OptionEnum::outputSchema() ),
				new SupportedOption( OptionEnum::outputMimeType(), array( 'text/plain', 'application/json' ) ),
				new SupportedOption( OptionEnum::inputModalities(), array( array( ModalityEnum::text() ) ) ),
				new SupportedOption( OptionEnum::outputModalities(), array( array( ModalityEnum::text() ) ) ),
			)
		);
	}
}
