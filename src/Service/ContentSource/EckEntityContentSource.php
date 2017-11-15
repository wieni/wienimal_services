<?php

namespace Drupal\wienimal_services\Service\ContentSource;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eck\EckEntityTypeBundleInfo;

class EckEntityContentSource extends AbstractContentSource {

    /** @var EckEntityTypeBundleInfo $bundleInfo */
    private $bundleInfo;

    /**
     * EditorToolbarContentCollector constructor.
     * @param EckEntityTypeBundleInfo $bundleInfo
     */
    public function __construct(EckEntityTypeBundleInfo $bundleInfo)
    {
        $this->bundleInfo = $bundleInfo;
    }

    /**
     * @param array $basePluginDefinition
     * @param array|string $config
     * @return array
     */
    public function getContent(array $basePluginDefinition, $config)
    {
        if (!$config || $config === 'none') {
            return [];
        }

        $content = [];

        // Get ECK bundles
        $types = array_filter(
            $this->bundleInfo->getAllBundleInfo(),
            function ($key) {
                return !in_array($key, ['taxonomy_term', 'node']);
            },
            ARRAY_FILTER_USE_KEY
        );

        if (is_array($config)) {
            foreach ($types as $entityType => &$bundles) {
                if (!isset($config[$entityType])) {
                    unset($types[$entityType]);
                    continue;
                }

                // Only bundles from config
                $bundles = array_filter(
                    $bundles,
                    function ($bundle) use ($config, $entityType) {
                        $bundles = $config[$entityType] ?? [];
                        $bundles = !is_array($bundles) ? [] : $bundles;
                        return !$bundles || in_array($bundle, $bundles);
                    },
                    ARRAY_FILTER_USE_KEY
                );

                // Map to menu item
                foreach ($bundles as $bundleName => $bundle) {
                    $id = sprintf('eck-%s-%s', $entityType, $bundleName);
                    array_push($content, [
                            'id' => $id,
                            'entity_type' => $entityType,
                            'bundle' => $bundleName,
                            'title' => new TranslatableMarkup($bundle['label']),
                        ] + $basePluginDefinition);
                }
            }
        }

        return $content;
    }

    /**
     * @param array $menuItem
     * @return string
     */
    public function getOverviewRoute(array $menuItem)
    {
        return "eck.entity.{$menuItem['entity_type']}.list";
    }

    /**
     * @param array $menuItem
     * @return array
     */
    public function getOverviewRouteParameters(array $menuItem)
    {
        return [];
    }

    /**
     * @param array $menuItem
     * @return string
     */
    public function getCreateRoute(array $menuItem)
    {
        return 'eck.entity.add';
    }

    /**
     * @param array $menuItem
     * @return array
     */
    public function getCreateRouteParameters(array $menuItem)
    {
        return [
            'eck_entity_type' => $menuItem['entity_type'],
            'eck_entity_bundle' => $menuItem['bundle'],
        ];
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'eck';
    }

    /**
     * @param array $info
     * @return string
     */
    public function buildId(array $info)
    {
        return sprintf(
            'eck-%s-%s',
            $info['entityType'],
            $info['bundle']
        );
    }
}
