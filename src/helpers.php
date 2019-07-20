<?php

use Illuminate\Support\Facades\App;

if ( ! function_exists( '__trans' ) ) :
    /**
     * Shorthand function for translating text.
     *
     * @param string $text
     * @param array  $replacements
     * @param string $toLocale
     *
     * @return string
     */
    function __trans( string $text, array $replacements = [], string $to_locale = '' ) {
        return App::make('translation')->translate( $text, $replacements, $to_locale );
    }
endif;