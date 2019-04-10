<?php

namespace Drupal\wienimal_services\Service;

use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class TitleResolver implements TitleResolverInterface
{
    use StringTranslationTrait;

    /** @var TitleResolverInterface */
    protected $titleResolver;
    /** @var PageTitleService */
    protected $pageTitleService;

    public function __construct(
        TitleResolverInterface $titleResolver,
        PageTitleService $pageTitleService
    ) {
        $this->titleResolver = $titleResolver;
        $this->pageTitleService = $pageTitleService;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(Request $request, Route $route)
    {
        return $this->pageTitleService->getTitle($request)
            ?? $this->titleResolver->getTitle($request, $route);
    }
}
