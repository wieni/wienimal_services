<?php

namespace Drupal\wienimal_services\Service\ContentSource;

use Drupal\Core\StringTranslation\TranslatableMarkup;

abstract class AbstractContentSource {

    /**
     * @param array $basePluginDefinition
     * @param array|string $config
     * @return mixed
     */
    abstract public function getContent(array $basePluginDefinition, $config);

    /**
     * @param array $menuItem
     * @return string
     */
    abstract public function getOverviewRoute(array $menuItem);

    /**
     * @param array $menuItem
     * @return array
     */
    abstract public function getOverviewRouteParameters(array $menuItem);

    /**
     * @param array $menuItem
     * @return string
     */
    abstract public function getCreateRoute(array $menuItem);

    /**
     * @param array $menuItem
     * @return array
     */
    abstract public function getCreateRouteParameters(array $menuItem);

    /**
     * @return string
     */
    abstract public function getKey();

    /**
     * @param array $info
     * @return string
     */
    abstract public function buildId(array $info);

    /**
     * @param $basePluginDefinition
     * @param $id
     * @param string $label
     * @param string $routeName
     * @param array $routeParameters
     * @return array
     */
    private function buildMenuItem($basePluginDefinition, $id, string $label, string $routeName, array $routeParameters = []) {
        return [
                'id' => $id,
                'title' => new TranslatableMarkup($label),
                'route_name' => $routeName,
                'route_parameters' => $routeParameters,
            ] + $basePluginDefinition;
    }

}
