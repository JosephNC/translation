<?php

namespace JosephNC\Translation\Tests;

use JosephNC\Translation\TranslationServiceProvider;
use Orchestra\Testbench\TestCase;

class FunctionalTestCase extends TestCase
{
    /**
     * Set up the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => 'testbench',
            '--realpath' => realpath(__DIR__.'/../src/Migrations'),
        ]);
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        $app['config']->set('translation.locales', [
            'en' => 'English',
            'fr' => 'French',
        ]);

        $app['config']->set('translation.key', 123456);
    }

    /**
     * Returns the package providers.
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [TranslationServiceProvider::class];
    }

    /**
     * Returns the package aliases.
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return ['Translation' => \JosephNC\Translation\Facades\Translation::class];
    }
}
