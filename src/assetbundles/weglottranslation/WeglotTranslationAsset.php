<?php
/**
 * Weglot Translation plugin for Craft CMS 3.x
 *
 * Integrate with Weglot's API to provide automatic page translation.
 *
 * @link      www.pinfirestudios.com
 * @copyright Copyright (c) 2019 Pinfire Studios
 */

namespace pinfirestudios\weglottranslation\assetbundles\WeglotTranslation;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Pinfire Studios
 * @package   WeglotTranslation
 * @since     1.0.0
 */
class WeglotTranslationAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@pinfirestudios/weglottranslation/assetbundles/weglottranslation/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/WeglotTranslation.js',
        ];

        $this->css = [
            'css/WeglotTranslation.css',
        ];

        parent::init();
    }
}
