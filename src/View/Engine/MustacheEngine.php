<?php
namespace Bow\View\Engine;

use Bow\Support\Str;
use Bow\Security\Security;
use Bow\View\EngineAbstract;
use Bow\Translate\Translator;
use Bow\Application\Configuration;

class MustacheEngine extends EngineAbstract
{
    /**
     * @var string
     */
    protected $name = 'mustache';

    /**
     * @var \Mustache_Engine
     */
    private $template;

    /**
     * MustacheEngine constructor.
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;

        $partial_loader = is_dir($config->getViewpath().'/partials') ?
            new \Mustache_Loader_FilesystemLoader($config->getViewpath().'/partials', [
                'extension' => $this->config->getTemplateExtension()
            ]) : null;

        $loader = new \Mustache_Loader_FilesystemLoader($config->getViewpath(), [
            'extension' => $this->config->getTemplateExtension()
        ]);

        $helpers = [
            'secure' => function ($data) {
                return Security::sanitaze($data, true);
            },
            'sanitaze' => function ($data) {
                return Security::sanitaze($data);
            },
            'slugify' => function ($data) {
                return Str::slugify($data);
            },
            'csrf_token' => function () {
                return Security::getCsrfToken()->token;
            },
            'csrf_field' => function () {
                return Security::getCsrfToken()->field;
            },
            'trans' => function ($key, $data = [], $choose = null) {
                return Translator::make($key, $data, $choose);
            },
            '_public', $config->getPublicPath(),
            '_root', $config->getApproot(),
            'escape' => function($value) {
                return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
            }
        ];

        $this->template = new \Mustache_Engine([
            'cache' => $config->getCachepath().'/view',
            'loader' => $loader,
            'partials_loader' => $partial_loader,
            'helpers' => $helpers
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render($filename, array $data = [])
    {
        $filename = $this->checkParseFile($filename);

        return $this->template->render(
            $filename,
            $data
        );
    }
}