<?php

namespace Drupal\wienimal_services\Service\ContentSource;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\taxonomy\Entity\Vocabulary;

class TaxonomyTermContentSource extends AbstractContentSource {

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

        $taxonomyTerms = Vocabulary::loadMultiple();

        if (is_array($config)) {
            $taxonomyTerms = array_filter(
                $taxonomyTerms,
                function ($taxonomyTerm) use ($config) {
                    return in_array($taxonomyTerm->get('type'), $config);
                }
            );
        }

        // Map to menu item
        return array_map(
            function ($taxonomyTerm) use ($basePluginDefinition) {
                $vid = $taxonomyTerm->get('vid');
                $id = sprintf('taxonomy-%s', $vid);
                return [
                        'id' => $id,
                        'vid' => $vid,
                        'title' => new TranslatableMarkup($taxonomyTerm->get('name')),
                    ] + $basePluginDefinition;
            },
            $taxonomyTerms
        );
    }

    /**
     * @param array $menuItem
     * @return string
     */
    public function getOverviewRoute(array $menuItem)
    {
        return 'entity.taxonomy_vocabulary.overview_form';
    }

    /**
     * @param array $menuItem
     * @return array
     */
    public function getOverviewRouteParameters(array $menuItem)
    {
        return [
            'taxonomy_vocabulary' => $menuItem['vid'],
        ];
    }

    /**
     * @param array $menuItem
     * @return string
     */
    public function getCreateRoute(array $menuItem)
    {
        return 'entity.taxonomy_term.add_form';
    }

    /**
     * @param array $menuItem
     * @return array
     */
    public function getCreateRouteParameters(array $menuItem)
    {
        return [
            'taxonomy_vocabulary' => $menuItem['vid'],
        ];
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'taxonomy';
    }

    /**
     * @param array $info
     * @return string
     */
    public function buildId(array $info)
    {
        return sprintf(
            'taxonomy-%s',
            $info['vocabulary']
        );
    }
}
