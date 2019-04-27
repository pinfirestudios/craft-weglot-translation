<?php
/**
 * Weglot Translation plugin for Craft CMS 3.x
 *
 * Integrate with Weglot's API to provide automatic page translation.
 *
 * @link      www.pinfirestudios.com
 * @copyright Copyright (c) 2019 Pinfire Studios
 */

namespace pinfirestudios\weglottranslation\models;

use pinfirestudios\weglottranslation\WeglotTranslation;

use Craft;
use craft\base\Model;

/**
 * @author    Pinfire Studios
 * @package   WeglotTranslation
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $weglotApiKey = null;

    /**
     * @var array
     */
    public $excludeBlocks = '';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['weglotApiKey', 'required'],
            [['weglotApiKey', 'excludeBlocks'], 'string'],
        ];
    }

    public function getWeglotApiKey() : string
    {
        return Craft::parseEnv($this->weglotApiKey);
    }
}
