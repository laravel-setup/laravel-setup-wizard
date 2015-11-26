<?php namespace Lanin\Laravel\SetupWizard;

use Illuminate\Support\ServiceProvider;
use Lanin\Laravel\SetupWizard\Commands\Setup;

class SetupWizardServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap application service.
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/../config/setup.php' => config_path('setup.php'),
		]);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerCommand();
		$this->registerConfig();
	}

	/**
	 * Register setup command.
	 */
	protected function registerCommand()
	{
		$this->app->singleton('setup-wizard.setup', function ($app) {
			return $app[Setup::class];
		});

		$this->commands('setup-wizard.setup');
	}

	/**
	 * Register setup config.
	 */
	protected function registerConfig()
	{
		$this->mergeConfigFrom(
				__DIR__ . '/../config/setup.php', 'setup'
		);
	}
}