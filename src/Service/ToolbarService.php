<?php

namespace Drupal\wienimal_services\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;

class ToolbarService
{
    /** @var array $config */
    protected $config;

    /**
     * WmContentDescriptiveTitles constructor.
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        ConfigFactory $configFactory
    ) {
        $this->config = $configFactory->get('system.theme');
    }

    public function getLogo()
    {
        $adminTheme = drupal_get_path('theme', $this->config->get('admin'));
        $activeTheme = drupal_get_path('theme', $this->config->get('default'));
        $module = drupal_get_path('module', 'wienimal_editor_toolbar');

        $possibilities = array_reduce(
            [$activeTheme, $adminTheme, $module],
            function ($carry, $item) {
                return array_merge(
                    $carry,
                    [
                        "$item/logo.svg",
                        "$item/logo.png",
                        "$item/logo.jpg",
                    ]
                );
            },
            []
        );

        foreach ($possibilities as $possibility) {
            if (file_exists($possibility)) {
                return '/' . $possibility;
            }
        }

        return false;
    }

    public function getVersionInfo()
    {
        $path = DRUPAL_ROOT . '/version.json';

        if (file_exists($path)) {
            return Json::decode(file_get_contents($path));
        }

        return false;
    }
}
