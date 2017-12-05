<?php

namespace Drupal\wienimal_services\Service\ContentSource;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;

class NodeContentSource extends AbstractContentSource
{

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

        // Map to menu item
        return array_map(
            function (NodeTypeInterface $nodeType) use ($basePluginDefinition) {
                $type = $nodeType->get('type');
                $id = sprintf('node-%s', $type);
                return [
                        'id' => $id,
                        'type' => $type,
                        'title' => new TranslatableMarkup($nodeType->get('name')),
                    ] + $basePluginDefinition;
            },
            $this->getTypes($config)
        );
    }

    /**
     * @param $config
     * @return NodeTypeInterface[]
     */
    protected function getTypes($config)
    {
        return array_filter(
            NodeType::loadMultiple(),
            function (NodeTypeInterface $nodeType) use ($config) {
                // Filter out wmSingles
                return !$this->isWmSingle($nodeType)
                    // only return allowed bundles
                    && (
                        !is_array($config)
                        || in_array($nodeType->get('type'), $config)
                    );
            }
        );
    }

    private function isWmSingle(NodeTypeInterface $nodeType)
    {
        return $nodeType->getThirdPartySetting('wmsingles', 'isSingle', false);
    }

    /**
     * @param array $menuItem
     * @return string
     */
    public function getOverviewRoute(array $menuItem)
    {
        return 'system.admin_content';
    }

    /**
     * @param array $menuItem
     * @return array
     */
    public function getOverviewRouteParameters(array $menuItem)
    {
        return [
            'type' => $menuItem['type'],
        ];
    }

    /**
     * @param array $menuItem
     * @return string
     */
    public function getCreateRoute(array $menuItem)
    {
        return 'node.add';
    }

    /**
     * @param array $menuItem
     * @return array
     */
    public function getCreateRouteParameters(array $menuItem)
    {
        return [
            'node_type' => $menuItem['type'],
        ];
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'node';
    }

    /**
     * @param array $info
     * @return string
     */
    public function buildId(array $info)
    {
        if (isset($info['subType']) && isset($info['type'])) {
            return sprintf(
                'node-%s_%s',
                $info['type'],
                $info['subType']
            );
        }

        if (isset($info['type'])) {
            return sprintf(
                'node-%s',
                $info['type']
            );
        }

        return '';
    }
}
