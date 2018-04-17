<?php

namespace Drupal\wienimal_services\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\eck\EckEntityTypeBundleInfo;
use Drupal\eck\Entity\EckEntityType;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\wienimal_services\Service\ContentSource\EckEntityContentSource;
use Drupal\wienimal_services\Service\ContentSource\NodeContentSource;
use Drupal\wienimal_services\Service\ContentSource\TaxonomyTermContentSource;
use Drupal\wmcustom\Entity\TaxonomyTerm\TermModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentTypeInfoService
{
    /** @var CurrentRouteMatch */
    protected $currentRouteMatch;
    /** @var EckEntityTypeBundleInfo */
    protected $entityTypeBundleInfo;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var RequestStack */
    protected $requestStack;
    /** @var Request */
    protected $request;
    /** @var EckEntityContentSource */
    private $eckContentSource;
    /** @var NodeContentSource */
    private $nodeContentSource;
    /** @var TaxonomyTermContentSource */
    private $taxonomyTermContentSource;

    public function __construct(
        CurrentRouteMatch $currentRouteMatch,
        EckEntityTypeBundleInfo $entityTypeBundleInfo,
        EntityTypeManagerInterface $entityTypeManager,
        RequestStack $requestStack,
        NodeContentSource $nodeContentSource,
        TaxonomyTermContentSource $taxonomyTermContentSource,
        EckEntityContentSource $eckContentSource
    ) {
        $this->currentRouteMatch = $currentRouteMatch;
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
        $this->entityTypeManager = $entityTypeManager;
        $this->requestStack = $requestStack;

        $this->nodeContentSource = $nodeContentSource;
        $this->taxonomyTermContentSource = $taxonomyTermContentSource;
        $this->eckContentSource = $eckContentSource;

        $this->request = $requestStack->getCurrentRequest();
    }

    public function getContentIdFromRoute()
    {
        $matches = [];
        $routeName = $this->currentRouteMatch->getRouteName();
        $eckRoutes = [
            '/entity\.((?!taxonomy_term)(?!node).+)\.field_ui_fields/',
            '/entity\.entity_form_display\.((?!taxonomy_term)(?!node).+)\.default/',
            '/entity\.entity_view_display\.((?!taxonomy_term)(?!node).+)\.default/',
        ];

        foreach ($eckRoutes as $eckRoute) {
            if (preg_match($eckRoute, $routeName, $matches)) {
                return $this->getContentId('eck');
            }
        }

        if (
            $routeName === 'system.admin_content'
            && $id = $this->getContentId('node')
        ) {
            return $id;
        }

        switch ($routeName) {
            case 'entity.node_type.edit_form':
            case 'entity.node.edit_form':
            case 'entity.node.field_ui_fields':
            case 'entity.entity_form_display.node.default':
            case 'entity.entity_view_display.node.default':
            case 'node.add':
                return $this->getContentId('node');

            case 'entity.taxonomy_term.add_form':
            case 'entity.taxonomy_term.edit_form':
            case 'entity.taxonomy_term.field_ui_fields':
            case 'entity.entity_form_display.taxonomy_term.default':
            case 'entity.entity_view_display.taxonomy_term.default':
            case 'entity.taxonomy_vocabulary.edit_form':
            case 'entity.taxonomy_vocabulary.overview_form':
                return $this->getContentId('taxonomy');

            case 'eck.entity.add':
            case 'eck.entity.collection.list':
                return $this->getContentId('eck');

            case 'entity.node.wmcontent_add':
            case 'entity.taxonomy_term.wmcontent_add':
                return $this->getContentId('wmcontent');

            default:
                return false;
        }
    }

    public function getInfo(string $source)
    {
        switch($source) {
            case 'eck':
                return $this->getEckInfoFromRoute();
            case 'taxonomy':
                return $this->getTaxonomyInfoFromRoute();
            case 'node':
                return $this->getNodeInfoFromRoute();
            case 'wmcontent':
                return $this->getWmContentInfoFromRoute();
            default:
                return false;
        }
    }

    public function getContentId(string $source, $data = [])
    {
        $info = array_merge($this->getInfo($source), $data);

        switch($source) {
            case 'eck':
            case 'wmcontent':
                return $this->eckContentSource->buildId($info);
            case 'taxonomy':
                return $this->taxonomyTermContentSource->buildId($info);
            case 'node':
                return $this->nodeContentSource->buildId($info);
            default:
                return false;
        }
    }

    protected function getEckInfoFromRoute()
    {
        $result = [];
        $entityTypeBundle = $this->currentRouteMatch->getParameter('eck_entity_bundle');
        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();

        // From request
        $entityTypeFromRequest = $this->request->attributes->get('entity_type');
        if (!empty($entityTypeFromRequest)) {
            $entityType = $entityTypeFromRequest;
        }

        // From route
        $entityTypeFromRoute = $this->currentRouteMatch->getParameter('eck_entity_type');
        if (!empty($entityTypeFromRoute)) {
            $entityType = $entityTypeFromRoute->id();
        }

        if (!empty($entityType)) {
            $result['entityType'] = $entityType;
            $result['entityTypeTitle'] = EckEntityType::load($entityType)->label();
        }

        if (!empty($entityTypeBundle)) {
            $result['bundle'] = $entityTypeBundle;

            $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();
            $result['bundleTitle'] = $bundleInfo[$result['entityType']][$result['bundle']]['label'];
        }

        $entityTypeId = $this->currentRouteMatch->getParameter('entity_type_id');

        if (!empty($entityTypeId)) {
            $result['entityType'] = $entityTypeId;

            $entity = EckEntityType::load($entityTypeId);
            if (!empty($entity)) {
                $result['entityTypeTitle'] = $entity->label();
            }
        }

        $bundle = $this->currentRouteMatch->getParameter('bundle');
        if (!empty($bundle)) {
            $result['bundle'] = $bundle;
        }

        if ($result['bundle']) {
            $result['bundleTitle'] = $bundleInfo[$result['entityType']][$result['bundle']]['label'];
        }

        return $result;
    }

    protected function getTaxonomyInfoFromRoute()
    {
        /** @var Vocabulary $vocabulary */
        $vocabulary = $this->currentRouteMatch->getParameter('taxonomy_vocabulary');

        /** @var TermModel $taxonomyTerm */
        $taxonomyTerm = $this->currentRouteMatch->getParameter('taxonomy_term');
        if ($taxonomyTerm) {
            $vocabulary = Vocabulary::load($taxonomyTerm->getVocabularyId());
        }

        if (!$vocabulary) {
            return [];
        }

        if (!$taxonomyTerm) {
            return [
                'vocabulary' => $vocabulary->get('vid'),
                'title' => $vocabulary->get('name'),
            ];
        }

        return [
            'vocabulary' => $vocabulary->get('vid'),
            'title' => $vocabulary->get('name'),
            'term' => $taxonomyTerm->label(),
        ];
    }

    protected function getNodeInfoFromRoute()
    {
        /** @var NodeType $nodeType */
        $nodeTypeFromRoute = $this->currentRouteMatch->getParameter('node_type');
        $nodeTypeFromRequest = $this->request->get('type');
        $bundles = $this->entityTypeBundleInfo->getBundleInfo('node');
        $result = [];

        /** @var Node $node */
        if ($node = $this->currentRouteMatch->getParameter('node')) {
            $entity = NodeType::load($node->getType());
            return [
                'type' => $entity->get('type'),
                'typeTitle' => $entity->get('name'),
                'nodeTitle' => $node->label()
            ];
        }

        if (
            $this->currentRouteMatch->getRouteName() === 'system.admin_content'
            && array_key_exists($nodeTypeFromRequest, $bundles)
        ) {
            $entity = NodeType::load($nodeTypeFromRequest);
            return [
                'type' => $entity->get('type'),
                'typeTitle' => $entity->get('name')
            ];
        }

        if (!empty($nodeTypeFromRoute)) {
            $result['type'] = $nodeTypeFromRoute->id();
            $result['typeTitle'] = $nodeTypeFromRoute->get('name');
        }

        $subType = $this->request->get('type');
        if (!empty($subType)) {
            $result['subType'] = $subType;
            $result['subTypeTitle'] = ucfirst($subType);
        }

        return $result;
    }

    protected function getWmContentInfoFromRoute()
    {
        $result = [];

        $containers = $this->entityTypeManager
            ->getStorage('wmcontent_container')
            ->loadByProperties(['id' => $this->currentRouteMatch->getParameter('container')]);

        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();
        $container = reset($containers)->getChildEntityType();

        if ($childId = $this->currentRouteMatch->getParameter('child_id')) {
            $child = $this->entityTypeManager
                ->getStorage($container)
                ->load($childId);

            $bundle = $child->bundle();
        } else {
            $bundle = $this->currentRouteMatch->getParameter('bundle');
        }

        $result['entityType'] = $container;
        $result['bundle'] = $bundle;

        if ($bundle) {
            $result['bundleTitle'] = $bundleInfo[$container][$bundle]['label'];
        }

        return $result;
    }
}
