<?php

namespace JosephNC\Translation;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use InvalidArgumentException;
use UnexpectedValueException;
use GoogleTranslate\Client as T1;
use Stichoza\GoogleTranslate\GoogleTranslate as T2;

class Translation
{
    /**
     * Holds the app configuration.
     * 
     * @access protected
     * @var Illuminate\Config\Repository
     */
    public $config;

    /**
     * Holds the current request.
     * 
     * @access protected
     * @var Illuminate\Http\Request
     */
    protected $request;

    /**
     * Represents the translation model
     * 
     * @access protected
     * @var JosephNC\Translation\Models\Translation
     */
    protected $translationModel;

    /**
     * Translation constructor
     * 
     * @access public
     * @var Illuminate\Contracts\Foundation\Application
     */
    public function __construct( Application $app )
    {
        $this->config           = $app->make('config');
        $this->request          = app( \Illuminate\Http\Request::class );
        $this->translationModel = $app->make($this->getTranslationModel());
    }

    /**
     * Returns the API Key
     * 
     * @return string
     */
    public function getApiKey() : string
    {
        return (string) $this->config->get( 'translation.key', '' );
    }

    /**
     * Returns the locale
     * 
     * @return string
     */
    public function getLocale() : string
    {
        return (string) $_SESSION['locale'] ?? $this->getDefaultLocale();
    }

    /**
     * Returns the array of configuration locales.
     *
     * @return array
     */
    protected function getLocales() : array
    {
        return (array) $this->config->get( 'translation.locales' );
    }

    /**
     * Returns the default locale from the configuration.
     *
     * @access protected
     * 
     * @return string
     */
    protected function getDefaultLocale() : string
    {
        return (string) $this->config->get( 'app.locale', $this->config->get('app.fallback_locale', 'en') );
    }

    /**
     * Returns the translation model from the configuration.
     *
     * @access protected
     * 
     * @return string
     */
    protected function getTranslationModel() : string
    {
        return (string) $this->config->get('translation.models.translation', Models\Translation::class);
    }

    /**
     * Returns the current route translation prefix
     * using the srequest segment set in the config
     * 
     * @return string
     */
    public function getRoutePrefix() : string
    {
        $locale = $this->request->segment( $this->getRequestSegment() );
        
        if ( $this->localeExist( $locale ) ) return (string) $locale;
    }

    /**
     * Returns the request segment to retrieve the locale from.
     *
     * @return int
     */
    protected function getRequestSegment()
    {
        return $this->config->get( 'translation.request_segment', 1 );
    }

    /**
     * Sets the locale
     * 
     * @param string $locale    The locale to use. Defaults to 'en'.
     * @throws InvalidArgumentException|Exception
     */
    public function setLocale( string $locale = 'en' )
    {
        if ( ! $this->localeExist( $locale ) ) {
            $message = 'Invalid Argument! Locale passed does not exist.';

            throw new InvalidArgumentException( $message );
        }

        $_SESSION['locale'] = $locale;
    }

    /**
     * Checks if the locale passed is exist
     *
     * @param string $locale    The locale to check
     * 
     * @return bool
     */
    public function localeExist( string $locale = 'en' ) : bool
    {
        return array_key_exists( $locale, (array) $this->getLocales() ) ? true : false;
    }

    /**
     * Translate the text
     * 
     * @access public
     * 
     * @param string $text
     * @param array $replacements
     * @param string $to_locale
     * 
     * @throws  InvalidArgumentException
     * 
     * @return string   The translated text.
     */
    public function translate( string $text, array $replacements = [], string $to_locale = '' ) : string
    {
        $do_translate   = false;
        $data           = [];
        $locale         = 'en';
        $to_locale      = empty( $to_locale ) ? $this->getLocale() : $to_locale;
        $text           = preg_replace( '/:([a-z]+)/i', '__$1__', $text ); // Turn replacements to placeholders
        $trans          = $text;
        $translations   = [];

        if ( ! array_key_exists( $to_locale, $this->getLocales() ) ) {
            $message = 'Invalid Argument. Locale not found for translation.';

            throw new InvalidArgumentException( $message );
        }

        if ( empty( $text ) ) return $text;

        // Get translation data from session or database
        $translations = (array) ($_SESSION['__translations'] ?? $this->translationModel->pluck( 'data', 'text' )->toArray());

        if ( ( empty( $translations ) || ! isset( $translations[ $text ] ) ) && $locale != $to_locale ) {
            $do_translate = true;
        } else if ( isset( $translations[ $text ] ) ) {
            $data = json_decode( $translations[ $text ], true );

            if ( $locale != $to_locale ) {
                $trans = $data[ $to_locale ] ?? '';

                $do_translate = ! empty( $trans ) && $trans == $text ? true : ( empty( $trans ) ? true : false );
            }
        }

        if ( $do_translate ) {
            // Let's request for translation
            try {
                $trans = (new T1( $this->getApiKey() ))->translate( $text, $to_locale, $locale );

            } catch (\Exception $e) {
                Log::info( $e->getMessage() );

                // $trans = T2::trans( $text, $to_locale, $locale );
            }
        }

        $data = array_merge( $data, [ $to_locale => $trans ] );
        $translations[ $text ] = json_encode( $data );

        // Save to session
        $_SESSION['__translations'] = $translations;

        return (string) ( empty( $replacements ) ? $trans : $this->makeReplacements( $trans, $replacements ) );
    }

    /**
     * Replaces placeholders with its real value
     * 
     * @access private
     * @param string $text  The text having the placeholder
     * @param array $replacements   The replacement values in array
     * 
     * @return string   The replaced text
     */
    private function makeReplacements( string $text, array $replacements ) : string
    {
        $keys       = strtolower( '__' . join( '__,__', array_keys( $replacements ) ) . '__' );
        $search     = explode( ',', $keys );
        $replace    = array_values( $replacements );

        return (string) str_replace( $search, $replace, $text );
    }

    /**
     * Runs on page shutdown
     * 
     * @access public
     */
    public function shutdown()
    {
        // Get translation data from session
        $new_translations = $_SESSION['__translations'] ?? [];
        $old_translations = (array) $this->translationModel->pluck( 'data', 'text' )->toArray();

        if ( json_encode( $new_translations ) == json_encode( $old_translations ) ) return;

        $now = Carbon::now('utc')->toDateTimeString();
        $insert = $update = [];

        if ( empty( $new_translations ) ) return;

        foreach ($new_translations as $text => $data) {
            if ( isset( $old_translations[ $text ] ) ) {
                if ( $old_translations[ $text ] == $data ) continue;

                // Update old
                $this->translationModel->where( 'text', $text )->update( [ 'data' => $data ] );
                continue;
            }

            // Create new
            $insert[] = [
                'text'          => $text,
                'data'          => $data,
                'created_at'    => $now,
                'updated_at'    => $now
            ];
        }

        if ( ! empty( $insert ) ) $this->translationModel->insert( $insert );

        // Remove the translation
        if ( isset( $_SESSION['__translations'] ) ) unset( $_SESSION['__translations'] );
    }
}
