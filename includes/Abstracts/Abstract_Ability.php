<?php
/**
 * Abstract Ability base class.
 *
 * @package WordPress\AI\Abstracts
 */

declare( strict_types=1 );

namespace WordPress\AI\Abstracts;

/**
 * Base implementation for a WordPress Ability.
 *
 * @since 0.1.0
 */
abstract class Abstract_Ability {
	/**
	 * The ability prefix for the plugin
	 */
	protected static string $ability_prefix = 'ai/';

	/**
	 * Register any needed hooks.
	 *
	 * @since 0.1.0
	 */
	public function register(): void {
		add_action( 'wp_abilities_api_init', array( $this, 'register_ability' ) );
	}

	/**
	 * Registers any needed abilities.
	 *
	 * @since 0.1.0
	 */
	protected function register_ability(): void {
		wp_register_ability(
			self::$ability_prefix . $this->name(),
			array(
				'label'               => $this->label(),
				'description'         => $this->description(),
				'category'            => $this->category(),
				'input_schema'        => $this->input_schema(),
				'output_schema'       => $this->output_schema(),
				'execute_callback'    => array( $this, 'execute_callback' ),
				'permission_callback' => array( $this, 'permission_callback' ),
				'meta'                => $this->meta(),
			)
		);
	}

	/**
	 * Returns the name of the ability - without the "plugin prefix.
	 *
	 * @since 0.1.0
	 *
	 * @return string The name of the ability.
	 */
	abstract protected function name(): string;

	/**
	 * Returns the label of the ability.
	 *
	 * @since 0.1.0
	 *
	 * @return string The label of the ability.
	 */
	abstract protected function label(): string;

	/**
	 * Returns the description of the ability.
	 *
	 * @since 0.1.0
	 *
	 * @return string The description of the ability.
	 */
	abstract protected function description(): string;

	/**
	 * Returns the category of the ability.
	 *
	 * @since 0.1.0
	 *
	 * @return string The category of the ability.
	 */
	abstract protected function category(): string;

	/**
	 * Returns the input schema of the ability.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed> The input schema of the ability.
	 */
	abstract protected function input_schema(): array;

	/**
	 * Returns the output schema of the ability.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed> The output schema of the ability.
	 */
	abstract protected function output_schema(): array;

	/**
	 * Executes the ability with the given input arguments.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $input The input arguments to the ability.
	 * @return mixed|\WP_Error The result of the ability execution, or a WP_Error on failure.
	 */
	abstract protected function execute_callback( $input );

	/**
	 * Checks whether the current user has permission to execute the ability with the given input arguments.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $input The input arguments to the ability.
	 * @return bool|\WP_Error True if the user has permission, WP_Error otherwise.
	 */
	abstract protected function permission_callback( $input );

	/**
	 * Returns the meta of the ability.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed> The meta of the ability.
	 */
	abstract protected function meta(): array;
}
