<?php

namespace Drupal\wienimal_services\Service;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;

class PageTitleService
{
    use StringTranslationTrait;

    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var ModuleHandlerInterface */
    protected $moduleHandler;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        ModuleHandlerInterface $moduleHandler
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->moduleHandler = $moduleHandler;
    }

    public function getTitle(Request $request): ?MarkupInterface
    {
        $routeName = $request->attributes->get('_route');

        if ($this->moduleHandler->moduleExists('field_ui')) {
            if (preg_match('#entity\.(.+)\.field_ui_fields#', $routeName, $matches)) {
                return $this->getEntityFieldsTitle($request);
            }

            if (preg_match('#entity\.entity_form_display\.(.+)\.default#', $routeName, $matches)) {
                return $this->getEntityFormDisplayTitle($request);
            }

            if (preg_match('#entity\.entity_view_display\.(.+)\.default#', $routeName, $matches)) {
                return $this->getEntityViewDisplayTitle($request);
            }
        }

        if ($this->moduleHandler->moduleExists('node')) {
            switch ($routeName) {
                case 'node.add':
                    return $this->getEntityCreateTitle('node', $request);
                case 'system.admin_content':
                    return $this->getEntityOverviewTitle('node', $request);
            }
        }

        if ($this->moduleHandler->moduleExists('taxonomy')) {
            if ($routeName === 'entity.taxonomy_vocabulary.overview_form') {
                return $this->getEntityOverviewTitle('taxonomy_term', $request);
            }
        }

        if ($this->moduleHandler->moduleExists('eck')) {
            if ($routeName === 'eck.entity.add') {
                return $this->getEntityCreateTitle(
                    $request->attributes->get('eck_entity_type')->id(),
                    $request,
                    $request->attributes->get('eck_entity_bundle')
                );
            }

            if (preg_match('#eck\.entity\.(.+)\.list#', $routeName, $matches)) {
                if (empty($request->attributes->get('entity_type'))) {
                    return null;
                }
                return $this->getEntityOverviewTitle($request->attributes->get('entity_type'), $request);
            }
        }

        if ($this->moduleHandler->moduleExists('content_translation')) {
            if (preg_match('#entity\.(?<entityType>.+)\.content_translation_add#', $routeName, $matches)) {
                return $this->getEntityTranslationCreateTitle($matches['entityType'], $request);
            }
        }

        if ($this->moduleHandler->moduleExists('menu_ui')) {
            if ($routeName === 'entity.menu.add_link_form') {
                $menu = $request->attributes->get('menu');

                return $this->t('Add new menu link to %menuLabel', [
                    '%menuLabel' => $menu->label(),
                ]);
            }

            if ($routeName === 'entity.menu_link_content.canonical') {
                $menuLink = $request->attributes->get('menu_link_content');
                $menu = $this->entityTypeManager
                    ->getStorage('menu')
                    ->load($menuLink->getMenuName());

                return $this->t('Edit %entity on menu %menuLabel', [
                    '%entity' => $menuLink->label(),
                    '%menuLabel' => $menu->label(),
                ]);
            }
        }

        if (preg_match('#entity\.(?<entityType>.+)\.add_form#', $routeName, $matches)) {
            return $this->getEntityCreateTitle($matches['entityType'], $request);
        }

        if (preg_match('#entity\.(?<entityType>.+)\.delete_form#', $routeName, $matches)) {
            return $this->getEntityDeleteTitle($matches['entityType'], $request);
        }

        if (preg_match('#entity\.(?<entityType>.+)\.edit_form#', $routeName, $matches)) {
            return $this->getEntityEditTitle($matches['entityType'], $request);
        }

        return null;
    }

    protected function getEntityFormDisplayTitle(Request $request): MarkupInterface
    {
        return $this->t('Manage %bundle form display', [
            '%bundle' => $this->getBundleLabel(
                $request->attributes->get('entity_type_id'),
                $request->attributes->get('bundle')
            ),
        ]);
    }

    protected function getEntityViewDisplayTitle(Request $request): MarkupInterface
    {
        return $this->t('Manage %bundle display', [
            '%bundle' => $this->getBundleLabel(
                $request->attributes->get('entity_type_id'),
                $request->attributes->get('bundle')
            ),
        ]);
    }

    public function getEntityFieldsTitle(Request $request): MarkupInterface
    {
        return $this->t('Manage %bundle fields', [
            '%bundle' => $this->getBundleLabel(
                $request->attributes->get('entity_type_id'),
                $request->attributes->get('bundle')
            ),
        ]);
    }

    protected function getEntityCreateTitle(string $entityTypeId, Request $request, string $bundle = null): ?MarkupInterface
    {
        if (!$this->entityTypeManager->hasDefinition($entityTypeId)) {
            return null;
        }

        $entityType = $this->entityTypeManager->getDefinition($entityTypeId);

        if ($bundleEntityType = $entityType->getBundleEntityType()) {
            if ($bundle) {
                /** @var ConfigEntityBundleBase $bundle */
                $bundle = $this->entityTypeManager->getStorage($bundleEntityType)->load($bundle);
            } else {
                $bundle = $request->attributes->get($bundleEntityType);
            }

            return $this->t('Create @bundle', [
                '@bundle' => mb_strtolower($bundle->label()),
            ]);
        }

        return $this->t('Create @entityType', [
            '@entityType' => $entityType->getSingularLabel(),
        ]);
    }

    protected function getEntityTranslationCreateTitle(string $entityTypeId, Request $request, string $bundle = null): MarkupInterface
    {
        /** @var EntityInterface $entity */
        $entity = $request->attributes->get($entityTypeId);
        /** @var EntityTypeInterface $entityType */
        $entityType = $entity->getEntityType();

        $type = $bundle ?: $entityType->getSingularLabel();
        if (empty($bundle) && $bundleKey = $entityType->getKey('bundle')) {
            /** @var ConfigEntityBundleBase $bundle */
            $bundle = $entity->get($bundleKey)->entity;
            $type = mb_strtolower($bundle->label());
        }

        return $this->t('Add @language translation for %entity @type', [
            '@language' => $entity->language()->getName(),
            '%entity' => $entity->label(),
            '@type' => $type,
        ]);
    }

    protected function getEntityDeleteTitle(string $entityTypeId, Request $request): ?MarkupInterface
    {
        if (
            !$this->entityTypeManager->hasDefinition($entityTypeId)
            || !$request->attributes->has($entityTypeId)
        ) {
            return null;
        }

        /** @var EntityInterface $entityType */
        $entity = $request->attributes->get($entityTypeId);
        /** @var EntityTypeInterface $entityType */
        $entityType = $this->entityTypeManager->getDefinition($entityTypeId);

        if (
            ($bundleKey = $entityType->getKey('bundle'))
            && ($bundle = $entity->get($bundleKey)->entity)
        ) {
            /** @var ConfigEntityBundleBase $bundle */
            return $this->t('Delete %entity @bundle', [
                '%entity' => $entity->label(),
                '@bundle' => mb_strtolower($bundle->label()),
            ]);
        }

        return $this->t('Delete %entity @entityType', [
            '%entity' => $entity->label(),
            '@entityType' => $entityType->getSingularLabel(),
        ]);
    }

    public function getEntityEditTitle(string $entityTypeId, Request $request): ?MarkupInterface
    {
        if (
            !$this->entityTypeManager->hasDefinition($entityTypeId)
            || !$request->attributes->has($entityTypeId)
        ) {
            return null;
        }

        /** @var EntityInterface $entity */
        $entity = $request->attributes->get($entityTypeId);
        /** @var EntityTypeInterface $entityType */
        $entityType = $entity->getEntityType();

        if ($bundleKey = $entityType->getKey('bundle')) {
            /** @var ConfigEntityBundleBase $bundle */
            $bundle = $entity->get($bundleKey)->entity;

            if ($bundle->getThirdPartySetting('wmsingles', 'isSingle', false)) {
                return $this->t('Edit %bundle', [
                    '%bundle' => mb_strtolower($bundle->label()),
                ]);
            }

            return $this->t('Edit %entity @bundle', [
                '%entity' => $entity->label(),
                '@bundle' => mb_strtolower($bundle->label()),
            ]);
        }

        return $this->t('Edit %entity @entityType', [
            '%entity' => $entity->label(),
            '@entityType' => $entityType->getSingularLabel(),
        ]);
    }

    public function getEntityOverviewTitle(string $entityTypeId, Request $request): MarkupInterface
    {
        /** @var EntityTypeInterface $entityType */
        $entityType = $this->entityTypeManager->getDefinition($entityTypeId);

        if (
            ($bundleKey = $entityType->getKey('bundle'))
            && $request->query->has($bundleKey)
        ) {
            $bundleLabel = $this->getBundleLabel($entityTypeId, $request->query->get($bundleKey));
        }

        if ($bundle = $request->attributes->get($entityType->getBundleEntityType())) {
            /** @var ConfigEntityBundleBase $bundle */
            $bundleLabel = $bundle->label();
        }

        if (isset($bundleLabel)) {
            return $this->t('Manage @bundle @entityType', [
                '@bundle' => mb_strtolower($bundleLabel),
                '@entityType' => $entityType->getPluralLabel(),
            ]);
        }

        return $this->t('Manage @entityType', [
            '@entityType' => $entityType->getPluralLabel(),
        ]);
    }

    protected function getEntityTypeLabel(string $entityType, bool $plural = false): ?string
    {
        $definition = $this->entityTypeManager->getDefinition($entityType);

        if (!$definition instanceof EntityTypeInterface) {
            return null;
        }

        if ($plural) {
            if ($label = $definition->getCollectionLabel()) {
                return $label;
            }

            return $definition->getPluralLabel();
        }

        return $definition->getLabel();
    }

    protected function getBundleLabel(string $entityType, string $bundle): ?string
    {
        $definition = $this->entityTypeManager->getDefinition($entityType);

        if (!$definition instanceof EntityTypeInterface) {
            return null;
        }

        $bundleEntityType = $definition->getBundleEntityType();

        // Entity types without bundles (e.g. user) don't have a bundle entity type
        if (!$bundleEntityType) {
            return null;
        }

        $bundleEntity = $this->entityTypeManager->getStorage($bundleEntityType)->load($bundle);

        if (!$bundleEntity instanceof EntityInterface) {
            return null;
        }

        return $bundleEntity->label();

    }
}
