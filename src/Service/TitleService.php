<?php

namespace Drupal\wienimal_services\Service;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eck\EckEntityTypeBundleInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TitleService
{
    use StringTranslationTrait;

    /** @var CurrentRouteMatch $currentRouteMatch */
    protected $currentRouteMatch;
    /** @var EckEntityTypeBundleInfo $entityTypeBundleInfo */
    protected $entityTypeBundleInfo;
    /** @var RequestStack $requestStack */
    protected $requestStack;
    /** @var Request $request */
    protected $request;
    /** @var ContentTypeInfoService $contentTypeInfoService */
    protected $contentTypeInfoService;

    /**
     * WmContentDescriptiveTitles constructor.
     * @param CurrentRouteMatch $currentRouteMatch
     * @param EckEntityTypeBundleInfo $entityTypeBundleInfo
     * @param RequestStack $requestStack
     * @param ContentTypeInfoService $contentTypeInfoService
     */
    public function __construct(
        CurrentRouteMatch $currentRouteMatch,
        EckEntityTypeBundleInfo $entityTypeBundleInfo,
        RequestStack $requestStack,
        ContentTypeInfoService $contentTypeInfoService
    ) {
        $this->currentRouteMatch = $currentRouteMatch;
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
        $this->requestStack = $requestStack;
        $this->contentTypeInfoService = $contentTypeInfoService;

        $this->request = $requestStack->getCurrentRequest();
    }

    public function getPageTitle()
    {
        $matches = [];
        $routeName = $this->currentRouteMatch->getRouteName();

        if (preg_match('/entity\.((?!taxonomy_term)(?!node).+)\.field_ui_fields/', $routeName, $matches)) {
            return $this->getEckEntityFieldUITitle();
        }

        if (preg_match('/entity\.entity_form_display\.((?!taxonomy_term)(?!node).+)\.default/', $routeName, $matches)) {
            return $this->getEckEntityFormDisplayTitle();
        }

        if (preg_match('/entity\.entity_view_display\.((?!taxonomy_term)(?!node).+)\.default/', $routeName, $matches)) {
            return $this->getEckEntityDisplayTitle();
        }

        switch ($routeName) {
            /* Node */
            case 'entity.node_type.edit_form':
                return $this->getNodeTypeEditTitle();
            case 'entity.node.field_ui_fields':
                return $this->getNodeFieldUITitle();
            case 'entity.entity_form_display.node.default':
                return $this->getNodeFormDisplayTitle();
            case 'entity.entity_view_display.node.default':
                return $this->getNodeDisplayTitle();
            case 'node.add':
                return $this->getNodeCreateTitle();
            case 'system.admin_content':
                return $this->getNodeOverviewTitle();

            /* Taxonomy */
            case 'entity.taxonomy_term.add_form':
                return $this->getTaxonomyTermCreateTitle();
            case 'entity.taxonomy_term.field_ui_fields':
                return $this->getTaxonomyTermFieldUITitle();
            case 'entity.entity_form_display.taxonomy_term.default':
                return $this->getTaxonomyTermFormDisplayTitle();
            case 'entity.entity_view_display.taxonomy_term.default':
                return $this->getTaxonomyTermDisplayTitle();
            case 'entity.taxonomy_vocabulary.edit_form':
                return $this->getTaxonomyVocabularyEditTitle();
            case 'entity.taxonomy_vocabulary.overview_form':
                return $this->getTaxonomyVocabularyOverviewTitle();

            /* ECK */
            case 'eck.entity.add':
                return $this->getEckEntityCreateTitle();
            case 'eck.entity.collection.list':
                return $this->getEckEntityOverviewTitle();

            default:
                return false;
        }
    }

    /**
     * @return TranslatableMarkup
     */
    public function getNodeTypeEditTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('node');
        return $this->t('Edit %nodeType content type', ['%nodeType' => $info['typeTitle']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getNodeFieldUITitle()
    {
        $info = $this->contentTypeInfoService->getInfo('node');
        return $this->t('Manage %nodeType fields', ['%nodeType' => $info['typeTitle']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getNodeFormDisplayTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('node');
        return $this->t('Manage %nodeType form display', ['%nodeType' => $info['typeTitle']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getNodeDisplayTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('node');
        return $this->t('Manage %nodeType display', ['%nodeType' => $info['typeTitle']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getNodeCreateTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('node');

        if (isset($info['subType'])) {
            return $this->t('Add %nodeType (@subType)', [
                '%nodeType' => $info['typeTitle'],
                '@subType' => $info['subTypeTitle']
            ]);
        }

        return $this->t('Add %nodeType', ['%nodeType' => $info['typeTitle']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getNodeOverviewTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('node');

        if (isset($info['typeTitle'])) {
            return $this->t('Manage content for %nodeType', [
                '%nodeType' => $info['typeTitle']
            ]);
        }

        return $this->t('Manage content');
    }

    /**
     * @return TranslatableMarkup
     */
    public function getTaxonomyTermCreateTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('taxonomy');
        return $this->t('Add %vocabulary', ['%vocabulary' => $info['title']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getTaxonomyTermFieldUITitle()
    {
        $info = $this->contentTypeInfoService->getInfo('taxonomy');
        return $this->t('Manage %vocabulary fields', ['%vocabulary' => $info['title']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getTaxonomyTermFormDisplayTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('taxonomy');
        return $this->t('Manage %vocabulary form display', ['%vocabulary' => $info['title']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getTaxonomyTermDisplayTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('taxonomy');
        return $this->t('Manage %vocabulary display', ['%vocabulary' => $info['title']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getTaxonomyVocabularyEditTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('taxonomy');
        return $this->t('Edit %vocabulary vocabulary', ['%vocabulary' => $info['title']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getTaxonomyVocabularyOverviewTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('taxonomy');
        return $this->t('Manage %vocabulary terms', ['%vocabulary' => $info['title']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getEckEntityCreateTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('eck');
        return $this->t('Add %entityType', ['%entityType' => $info['bundleTitle']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getEckEntityOverviewTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('eck');
        return $this->t('Manage content for %entityType', ['%entityType' => $info['entityTypeTitle']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getEckEntityFieldUITitle()
    {
        $info = $this->contentTypeInfoService->getInfo('eck');
        return $this->t('Manage %bundleTitle fields', ['%bundleTitle' => $info['bundleTitle']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getEckEntityDisplayTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('eck');
        return $this->t('Manage %bundleTitle display', ['%bundleTitle' => $info['bundleTitle']]);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getEckEntityFormDisplayTitle()
    {
        $info = $this->contentTypeInfoService->getInfo('eck');
        return $this->t('Manage %bundleTitle form display', ['%bundleTitle' => $info['bundleTitle']]);
    }
}
