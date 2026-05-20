<?php
/**
 * Smoke tests for the Chrome On-Device experiment + provider registration.
 *
 * @package WordPress\AI\Tests\Integration\Includes\Experiments\Chrome_On_Device
 */

declare( strict_types=1 );

namespace WordPress\AI\Tests\Integration\Includes\Experiments\Chrome_On_Device;

use RuntimeException;
use WP_UnitTestCase;
use WordPress\AI\Experiments\Chrome_On_Device\Chrome_On_Device;
use WordPress\AI\Experiments\Chrome_On_Device\Provider\Chrome_Model;
use WordPress\AI\Experiments\Chrome_On_Device\Provider\Chrome_Model_Metadata_Directory;
use WordPress\AI\Experiments\Chrome_On_Device\Provider\Chrome_Provider;
use WordPress\AI\Experiments\Experiment_Category;
use WordPress\AI\Experiments\Experiments;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;

/**
 * @since x.x.x
 */
class Chrome_On_DeviceTest extends WP_UnitTestCase {

	public function test_experiment_is_in_default_classes(): void {
		$experiments = new Experiments();
		$experiments->init();
		$results = apply_filters( 'wpai_default_feature_classes', array() );

		$this->assertContains(
			Chrome_On_Device::class,
			$results,
			'Chrome_On_Device should be registered as a default experiment.'
		);
	}

	public function test_experiment_metadata(): void {
		$experiment = new Chrome_On_Device();
		$this->assertSame( 'chrome-on-device', $experiment::get_id() );
		$this->assertSame( Experiment_Category::EDITOR, $experiment->get_category() );
		$this->assertNotEmpty( $experiment->get_label() );
		$this->assertNotEmpty( $experiment->get_description() );
	}

	public function test_register_provider_registers_with_ai_client(): void {
		$experiment = new Chrome_On_Device();
		$experiment->register_provider();

		$registry = AiClient::defaultRegistry();

		$this->assertTrue(
			$registry->hasProvider( Chrome_Provider::class ),
			'Chrome_Provider should be in the AI Client registry after register_provider().'
		);
		$this->assertTrue(
			$registry->hasProvider( Chrome_Provider::PROVIDER_ID ),
			'Registry lookup by provider id "chrome" should also succeed.'
		);
	}

	public function test_register_provider_is_idempotent(): void {
		$experiment = new Chrome_On_Device();
		$experiment->register_provider();
		$experiment->register_provider();

		$this->assertTrue( AiClient::defaultRegistry()->hasProvider( Chrome_Provider::class ) );
	}

	public function test_provider_metadata_is_client_type(): void {
		$metadata = Chrome_Provider::metadata();
		$this->assertSame( Chrome_Provider::PROVIDER_ID, $metadata->getId() );
		$this->assertTrue(
			$metadata->getType()->isClient(),
			'Chrome provider must declare ProviderTypeEnum::CLIENT so client-side-aware UI can distinguish it.'
		);
		$this->assertSame( ProviderTypeEnum::client()->value, $metadata->getType()->value );
	}

	public function test_model_directory_exposes_gemini_nano(): void {
		$directory = new Chrome_Model_Metadata_Directory();

		$this->assertTrue( $directory->hasModelMetadata( Chrome_Model_Metadata_Directory::MODEL_ID ) );

		$model_metadata = $directory->getModelMetadata( Chrome_Model_Metadata_Directory::MODEL_ID );
		$this->assertSame( Chrome_Model_Metadata_Directory::MODEL_ID, $model_metadata->getId() );

		$capability_values = array_map(
			static fn( $capability ) => $capability->value,
			$model_metadata->getSupportedCapabilities()
		);
		$this->assertContains( 'text_generation', $capability_values );
	}

	public function test_chrome_model_throws_on_php_side_generation(): void {
		$model = Chrome_Provider::model( Chrome_Model_Metadata_Directory::MODEL_ID );

		$this->assertInstanceOf( Chrome_Model::class, $model );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageMatches( '/client-side provider/' );
		$model->generateTextResult( array() );
	}

	public function test_provider_availability_reports_configured(): void {
		$this->assertTrue(
			Chrome_Provider::availability()->isConfigured(),
			'Chrome_Availability reports true from PHP; real availability is detected in the browser.'
		);
	}
}
