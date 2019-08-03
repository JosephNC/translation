# Translation

[![Travis CI](https://img.shields.io/travis/josephnc/translation.svg?style=flat-square)](https://travis-ci.org/josephnc/translation)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/josephnc/translation.svg?style=flat-square)](https://scrutinizer-ci.com/g/josephnc/translation/?branch=master)
[![License](https://img.shields.io/packagist/l/josephnc/translation.svg?style=flat-square)](https://packagist.org/packages/josephnc/translation)

## Description

Forked and updated from https://github.com/stevebauman/translation <br>

Now the fastest automatic translator for laravel 5

For example:

Controller:
```php
public function index()
{
    return view('home.index');
}
```

View:

```php
@extends('layout.default')

{{ ___trans('Welcome to our home page') }}
```

Seen:

    Welcome to our home page

When you visit the page, you won't notice anything different, but if you take a look at your database, your default
application locale has already been created, and the translation attached to that locale.

Now if we set locale to something different, such as French (fr), it'll automatically translate it for you.

Controller:

```php
public function index()
{
    App::setLocale('fr');

    // Or Translation::setLocale( 'fr' );

    return view('home.index');
}
```

View:

```php
@extends('layout.default')

{{ __trans('Welcome to our home page') }}
```

Seen:

    Bienvenue sur notre page d'accueil

We can even use placeholders for dynamic content:

View:
```php
{{ __trans('Welcome :name, to our home page', ['name' => 'John']) }}
```

Seen:

    Bienvenue John , à notre page d'accueil

Notice that we didn't actually change the text inside the view, which means everything stays completely readable in your
locale to you (the developer!), which means no more managing tons of translation files and trying to decipher what text may be inside that dot-notated translation
path:
    
## Installation

Require the translation package 

    composer require josephnc/translation

Add the service provider to your `config/app.php` config file

```php
'Josephnc\Translation\TranslationServiceProvider',
```

Add the facade to your aliases in your `config/app.php` config file

```php
'Translation' => 'Josephnc\Translation\Facades\Translation',
```

Publish the migrations

    php artisan vendor:publish --provider="Josephnc\Translation\TranslationServiceProvider"
    
Run the migrations

    php artisan migrate

Your good to go!

## Usage

Anywhere in your application, either use the the shorthand function (can be disabled in config file)

```php
__trans('Translate')
```

Or

```php
Translation::translate('Translate')
```

This is typically most useful in blade views:

```php
{{ __trans('Translate') }}
```

And you can even translate models easily by just plugging in your content:

```php
{{ __trans($post->title) }}
```

Or use placeholders:

```php
{{ __trans('Post :title', ['title' => $post->title]) }}
```

In your `translations` database table you'll have something like this:

    | id |     text     |                  data                |
      1     'Translate'    {"en":"Translate","fr":"Traduire"}

To switch languages for the users session, all you need to call is:

```php
App::setLocale('fr') // Setting to French locale

// Or use this, they both work
Translation::setLocale('fr') // Setting to French locale
```

Locales are automatically created when you call the `Translation::setLocale($code)` method,
and when the translate function is called, it will automatically create a new translation record
for the new locale, with the default locale translation. The default locale is taken from the laravel `app.php` config file.

You can now update the translation on the new record and it will be shown wherever it's called:

```php
__trans('Translate me!')
```

###### Need to translate a single piece of text without setting the users default locale?

Just pass in the locale into the third argument inside the translation functions show above like so:


View:
```php
{{ __trans('Our website also supports russian!', [], 'ru') }}
```
<br>

```php
{{ __trans('And french!', [], 'fr') }}
```

Seen:

    Наш сайт также поддерживает России !
    
    Et françaises !
    
This is great for showing users that your site supports different languages without changing the entire site
language itself. You can also perform replacements like usual:

View:

```php
{{ __trans('Hello :name, we also support french!', ['name' => 'John Doe'], 'fr') }}
```

Seen:

    Bonjour John Doe , nous soutenons aussi le français !

## Routes

Translating your site with a locale prefix couldn't be easier. First inside your `app/Http/Kernel.php` file, insert
the locale middleware:

```php
/**
 * The application's route middleware.
 *
 * @var array
	*/
protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,

    // Insert Locale Middleware
    'locale' => \Josephnc\Translation\Middleware\LocaleMiddleware::class
];
```

Now, in your `app/Http/routes.php` file, insert the middleware and the following Translation method in the route
group prefix like so:

```php
Route::group([
    'prefix'        => Translation::getRoutePrefix(),
    'middleware'    => ['locale'],
], function () {
    Route::get('home', function ()
    {
        return view('home');
    });
});
```

You should now be able to access routes such as:

    http://localhost/home
    http://localhost/en/home
    http://localhost/fr/home

## Automatic Translation

Automatic translation is enabled by default in the configuration file. It utilizes this fantastic packages <br>
[Stichoza Google Translate PHP](https://github.com/Stichoza/google-translate-php).<br>
[Viniciusgava Google Translate PHP](https://github.com/viniciusgava/google-translate-php-client).<br>

Using automatic translation will send the inserted text to google and save the returned text to the database.
Once a translation is saved in the database, it is never sent back to google to get re-translated.
This means that you don't have to worry about hitting a cap that google may impose. You effectively <b>own</b> that translation.

## Questions / Concerns

#### Why are there underscores where my placeholders should be in my database translations?

When you add placeholders to your translation, and add the data to replace it, for example:

```php
__trans('Hi :name', ['name' => 'John'])
```

Translation parses each entry in the data array to see if the placeholder actually exists for the data inserted. For example,
in the translation field in your database, here is what is saved:

```php
__trans('Hi :name', ['name' => 'John']) // Hi __name__

__trans('Hi :name', ['test' => 'John']) // Hi :name
```

Since the placeholder data inserted doesn't match a placeholder inside the string, the text will be left as is. The
reason for the underscores is because google translate will try to translate text containing `:name`, however providing
double underscores on both sides of the placeholder, prevents google from translating that specific word, allowing us to translate
everything else, but keep placeholders in tact. Translation then replaces the double underscore variant of the placeholder
(in this case `__name__`) at runtime.

#### If I update / modify the text inside the translation function, what happens to it's translations?

If you modify the text inside a translation function, it will create a new record and you will need to translate it again.
This is intended because it could be a completely different translation after modification.

For example using:

```php
    {{ __trans('Welcome!') }}
```

And modifying it to:

```php
    {{ __trans('Welcome') }}
```

Would automatically generate a new translation record.

#### Is there a maximum amount of text that can be auto-translated?

The package use the [Viniciusgava's](https://github.com/viniciusgava/google-translate-php-client) Google translate client
then falls back to [Stichoza's](https://github.com/Stichoza/google-translate-php) if any error occurred.

However, [Stichoza's](https://github.com/Stichoza/google-translate-php) new 3.0 update allows you to translate
up to 4200 words per request (tested, possibly more allowed).
