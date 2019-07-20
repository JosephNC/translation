<?php

namespace JosephNC\Translation\Facades;

use Illuminate\Support\Facades\Facade;

class Translation extends Facade
{
    /**
     * The facade accessor for retrieving translation from the IoC.
     *
     * @return string
     */
    protected static function getFacadeAccessor() : string
    {
        return 'translation';
    }
}
