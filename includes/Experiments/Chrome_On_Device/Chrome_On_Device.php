<?php
/**
 * Chrome on-device Prompt API experiment.
 *
 * @package WordPress\AI
 */

declare( strict_types=1 );

namespace WordPress\AI\Experiments\Chrome_On_Device;

use WP_Connector_Registry;
use WordPress\AI\Abstracts\Abstract_Feature;
use WordPress\AI\Asset_Loader;
use WordPress\AI\Experiments\Chrome_On_Device\Provider\Chrome_Provider;
use WordPress\AI\Experiments\Experiment_Category;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;

defined( 'ABSPATH' ) || exit;

/**
 * Chrome on-device Prompt API experiment.
 *
 * Registers a stub PHP provider so Chrome's on-device Prompt API surface
 * appears in the Connectors screen and Provider-aware widgets. The provider
 * does not serve PHP-side generation; the JS module intercepts executeAbility()
 * for eligible abilities and runs prompts in the browser via `LanguageModel.*`,
 * falling back to REST when the on-device model is unavailable.
 *
 * @since x.x.x
 */
class Chrome_On_Device extends Abstract_Feature {

	/**
	 * {@inheritDoc}
	 */
	public static function get_id(): string {
		return 'chrome-on-device';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function load_metadata(): array {
		return array(
			'label'       => __( 'Chrome On-Device AI', 'ai' ),
			'description' => __( 'Routes selected AI abilities through Chrome\'s on-device Prompt API when available, falling back to your server-side AI provider otherwise. Requires Chrome 148+.', 'ai' ),
			'category'    => Experiment_Category::EDITOR,
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function register(): void {
		$this->register_provider();
		$this->register_fallback_auth();
		$this->register_connector();
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Returns the ability IDs that have an on-device prompt mapping.
	 *
	 * Source of truth for what `src/experiments/chrome-on-device/schema.ts`
	 * implements. Adding an ability here without a JS-side spec is a no-op.
	 *
	 * @since x.x.x
	 *
	 * @return list<string> Ability IDs eligible for on-device execution.
	 */
	public function get_eligible_ability_ids(): array {
		return array(
			'ai/title-generation',
		);
	}

	/**
	 * Enqueues the experiment script on the post-edit screens.
	 *
	 * @since x.x.x
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		Asset_Loader::enqueue_script(
			'chrome_on_device',
			'experiments/chrome-on-device',
			array( 'include_core_abilities' => true )
		);
		Asset_Loader::localize_script(
			'chrome_on_device',
			'ChromeOnDeviceData',
			array(
				'enabled'   => $this->is_enabled(),
				'abilities' => $this->get_eligible_ability_ids(),
			)
		);
	}

	/**
	 * Registers Chrome_Provider with the AI Client registry.
	 *
	 * @since x.x.x
	 */
	public function register_provider(): void {
		$registry = AiClient::defaultRegistry();

		if ( $registry->hasProvider( Chrome_Provider::class ) ) {
			return;
		}

		$registry->registerProvider( Chrome_Provider::class );
	}

	/**
	 * Registers an empty API-key authentication for the Chrome provider.
	 *
	 * Chrome's Prompt API takes no credential, but ProviderMetadata currently
	 * requires a RequestAuthenticationMethod (API_KEY is the only declared
	 * value). Setting an empty key keeps the registry's auth state populated
	 * so configured checks don't return null.
	 *
	 * @since x.x.x
	 */
	public function register_fallback_auth(): void {
		$registry = AiClient::defaultRegistry();

		if ( ! $registry->hasProvider( Chrome_Provider::PROVIDER_ID ) ) {
			return;
		}

		if ( null !== $registry->getProviderRequestAuthentication( Chrome_Provider::PROVIDER_ID ) ) {
			return;
		}

		$registry->setProviderRequestAuthentication(
			Chrome_Provider::PROVIDER_ID,
			new ApiKeyRequestAuthentication( '' )
		);
	}

	/**
	 * Registers the Chrome connector with the WP core connector registry.
	 *
	 * @since x.x.x
	 */
	public function register_connector(): void {
		$registry = WP_Connector_Registry::get_instance();
		if ( null === $registry || $registry->is_registered( Chrome_Provider::PROVIDER_ID ) ) {
			return;
		}

		$logo_url = plugins_url(
			'Provider/logo.svg',
			__FILE__
		);

		$registry->register(
			Chrome_Provider::PROVIDER_ID,
			array(
				'name'           => __( 'Chrome On-Device', 'ai' ),
				'description'    => __( 'Runs Gemini Nano on-device in Chrome 148+ via the Prompt API. No credentials required.', 'ai' ),
				'type'           => 'ai_provider',
				'logo_url'       => $logo_url,
				'authentication' => array(
					'method' => 'none',
				),
			)
		);
	}
}
