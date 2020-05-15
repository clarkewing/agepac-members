<?php

namespace App\Providers;

use HTMLPurifier_HTMLDefinition;
use Illuminate\Support\ServiceProvider;
use Stevebauman\Purify\Facades\Purify;

class PurifySetupProvider extends ServiceProvider
{
    const DEFINITION_ID = 'trix-editor';
    const DEFINITION_REV = 1;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        /** @var \HTMLPurifier $purifier */
        $purifier = Purify::getPurifier();

        $config = $purifier->config;

        $config->set('HTML.DefinitionID', static::DEFINITION_ID);
        $config->set('HTML.DefinitionRev', static::DEFINITION_REV);

        if ($def = $config->maybeGetRawHTMLDefinition()) {
            $this->setupDefinitions($def);
        }

        $purifier->config = $config;
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Adds elements and attributes to the HTML purifier
     * definition required by the trix editor.
     *
     * @param HTMLPurifier_HTMLDefinition $def
     */
    protected function setupDefinitions(HTMLPurifier_HTMLDefinition $def)
    {
        $def->addElement('figure', 'Inline', 'Inline', 'Common');
        $def->addAttribute('figure', 'class', 'Text');
        $def->addAttribute('figure', 'data-trix-attachment', 'Text');
        $def->addAttribute('figure', 'data-trix-attributes', 'Text');
        $def->addAttribute('figure', 'data-trix-content-type', 'Text');

        $def->addElement('figcaption', 'Inline', 'Inline', 'Common');
        $def->addAttribute('figcaption', 'class', 'Text');
    }
}
