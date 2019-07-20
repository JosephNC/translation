<?php

namespace JosephNC\Translation;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Set up the blade directive.
     */
    public function boot()
    {
        Blade::directive('trans', function ( string $text, array $replacements = [], string $to_locale = '' ) {
            $translation = __trans( $text, $replacements, $to_locale );

            return "<?php echo $translation ?>";
        });
    }

    /**
     * Register the service provider.
     *
     * @method void package(string $package, string $namespace, string $path)
     */
    public function register()
    {
        if (PHP_SESSION_NONE == session_status()) session_start(); // Start Session

        // Allow configuration to be publishable.
        $this->publishes([
            __DIR__.'/Config/config.php' => config_path('translation.php'),
        ], 'config');

        // Allow migrations to be publishable.
        $this->publishes([
            __DIR__.'/Migrations/' => base_path('/database/migrations'),
        ], 'migrations');

        // Include the helpers file for global `__trans()` function
        require_once __DIR__.'/helpers.php';

        // Bind translation to the IoC.
        $this->app->bind( 'translation', function ( Application $app ) {
            return new Translation( $app );
        } );

        register_shutdown_function( [ $this->app->translation, 'shutdown' ] );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() : array
    {
        return ['translation'];
    }
}
