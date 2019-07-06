<?php
/**
 * Weglot Translation plugin for Craft CMS 3.x
 *
 * Integrate with Weglot's API to provide automatic page translation.
 *
 * @link      www.pinfirestudios.com
 * @copyright Copyright (c) 2019 Pinfire Studios
 */

namespace pinfirestudios\weglottranslation;

use pinfirestudios\weglottranslation\services\Weglot as WeglotService;
use pinfirestudios\weglottranslation\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\TemplateEvent;
use craft\web\View;

use Symfony\Component\Cache\Adapter\SimpleCacheAdapter;

use Weglot\Client\Factory\Languages;
use Weglot\Client\Api\TranslateEntry;
use Weglot\Client\Api\WordEntry;
use Weglot\Client\Client;
use Weglot\Client\Endpoint\Translate;
use Weglot\Client\Api\Enum\BotType;
use Weglot\Client\Api\Enum\WordType;
use Weglot\Util\Url;
use Weglot\Util\Server;
use Weglot\Parser\Parser;
use Weglot\Parser\ConfigProvider\ManualConfigProvider;
use Weglot\Parser\ConfigProvider\ServerConfigProvider;

use yii\base\Event;

/**
 * Class WeglotTranslation
 *
 * @author    Pinfire Studios
 * @package   WeglotTranslation
 * @since     1.0.0
 *
 * @property  WeglotService $weglot
 */
class WeglotTranslation extends Plugin
{
    const LOG_CATEGORY = 'weglot-translation';
    const TRANSLATION_CATEGORY = 'weglot-translation';
    // Static Properties
    // =========================================================================

    /**
     * @var WeglotTranslation
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @var Parser
     */
    private $_parser = null;

    // Public Methods
    // =========================================================================
    
    public function __construct($id, $parent = null, $config = [])
    {
        if (!array_key_exists('components', $config))
        {
            $config['components'] = [];
        }

        $config['components']['psr16Cache'] =[
            'class' => \flipbox\craft\psr16\SimpleCacheAdapter::class
        ];

        parent::__construct($id, $parent, $config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        if (!Craft::$app->request->isCpRequest) {
            Event::on(
                View::class,
                View::EVENT_AFTER_RENDER_TEMPLATE,
                [$this, 'onAfterPageRenderTemplateEvent']
            );
        }

        Craft::info(
            Craft::t(
                self::TRANSLATION_CATEGORY,
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }


    /**
     * Translates the page as needed after we've completed rendering.
     *
     * @param TemplateEvent $event
     */
    public function onAfterPageRenderTemplateEvent(TemplateEvent $event) {
        $dom = $event->output;

        $sourceLang = substr(Craft::$app->sites->primarySite->language, 0, 2);
        $destLang = substr(Craft::$app->sites->currentSite->language, 0, 2);

        if ($sourceLang == $destLang) {
            Craft::debug(Craft::t(
                self::LOG_CATEGORY,
                'Current site language {lang} == primary site language {lang}, no translation needed.',
                [
                    '{lang}' => $sourceLang
                ]
            ), self::LOG_CATEGORY);

            return;
        }

        $cacheKey = $destLang . $dom;
        if (isset($event->variables['entry']))
        {
            $entry = $event->variables['entry'];
            $cacheKey = $destLang . ':' . $entry->uid . ':' . $entry->dateUpdated->format('U') . $entry->contentId;
        }

        Craft::error("Content hash: {$cacheKey}", self::LOG_CATEGORY);
        $translated = Craft::$app->cache->get($cacheKey);
        if (empty($translated))
        {
            Craft::warning('Weglot cache miss', self::LOG_CATEGORY);

            $parser = $this->getWeglotParser();

            Craft::debug("Translating page from {$sourceLang} to {$destLang}", self::LOG_CATEGORY);

            Craft::beginProfile('Weglot translation', self::LOG_CATEGORY);
            $translated = $parser->translate($dom, $sourceLang, $destLang);
            Craft::endProfile('Weglot translation', self::LOG_CATEGORY);

            Craft::$app->cache->set($cacheKey, $translated);
        }
        else
        {
            Craft::info('Weglot cache hit', self::LOG_CATEGORY);
        }

        $event->output = $translated;
    }
    
    // Protected Methods
    // =========================================================================
    
    protected function getWeglotParser() : Parser
    {
        if (isset($this->_parser)) {
            return $this->_parser;
        }

        $weglot = new Client($this->settings->getWeglotApiKey(), 1);

        $psr16Cache = $this->get('psr16Cache');
        $psr6Cache = new SimpleCacheAdapter($psr16Cache);
        $weglot->setCacheItemPool($psr6Cache);

        $config = new ServerConfigProvider();

        $exclude_blocks = array_map('trim', explode(',', $this->settings->excludeBlocks));

        $this->_parser = new Parser($weglot, $config, $exclude_blocks);

        return $this->_parser;
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'weglot-translation/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
