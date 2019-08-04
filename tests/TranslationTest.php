<?php

namespace JosephNC\Translation\Tests;

use Illuminate\Support\Facades\Cache;
use JosephNC\Translation\Facades\Translation;
use JosephNC\Translation\Models\Translation as TranslationModel;

class TranslationTest extends FunctionalTestCase
{
    public function testTranslationInvalidText()
    {
        $this->setExpectedException('InvalidArgumentException');

        Translation::translate( [ 'Invalid' ] );
    }

    public function testTranslationPlaceHoldersDynamicLanguage()
    {
        $replace = ['name' => 'John'];

        $this->assertEquals('Hello John', Translation::translate('Hello :name', $replace, 'en'));
        $this->assertEquals('Bonjour John', Translation::translate('Hello :name', $replace, 'fr'));
    }

    public function testTranslationPlaceHoldersMultiple()
    {
        $replace = [
            'name'    => 'John',
            'apples'  => '10',
            'bananas' => '15',
            'grapes'  => '20',
        ];
        $expected = 'Hello John, I see you have 10 apples, 15 bananas, and 20 grapes.';
        $translation = 'Hello :name, I see you have :apples apples, :bananas bananas, and :grapes grapes.';

        $this->assertEquals($expected, Translation::translate($translation, $replace));
    }

    public function testTranslationPlaceHoldersMultipleOfTheSame()
    {
        $replace = [
            'name' => 'Name',
        ];
        $expected = 'Name Name Name Name Name';
        $translation = ':name :name :name :name :name';

        $this->assertEquals($expected, Translation::translate($translation, $replace));
    }

    public function testTranslationPlaceHoldersCaseInsensitivity()
    {
        $replace = [
            'name' => 'John',
            'NAME' => 'Test',
        ];

        $translation = ':name :NAME';
        $expected = 'John John';

        $this->assertEquals($expected, Translation::translate($translation, $replace));
    }
}