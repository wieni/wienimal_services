<?php

namespace Drupal\wienimal_services\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CancelButtonService
{
    /** @var CurrentRouteMatch $currentRouteMatch */
    protected $currentRouteMatch;
    /** @var array $config */
    protected $config;
    /** @var Request $request */
    protected $request;

    /**
     * WmContentDescriptiveTitles constructor.
     * @param CurrentRouteMatch $currentRouteMatch
     * @param ConfigFactory $configFactory
     * @param RequestStack $requestStack
     */
    public function __construct(
        CurrentRouteMatch $currentRouteMatch,
        ConfigFactory $configFactory,
        RequestStack $requestStack
    ) {
        $this->currentRouteMatch = $currentRouteMatch;
        $this->config = $configFactory->get('wienimal.settings');
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param $form
     */
    public function addCancelButton(&$form)
    {
        // Adds a cancel button to the following forms.
        $containers = $this->config->get('forms_cancel_button') ?? [];

        if (!array_key_exists($this->currentRouteMatch->getRouteName(), array_flip($containers))) {
            return;
        }

        // Decide between using history.back or the ?destination GET query
        $location = 'history.back()';
        if ($destination = $this->request->get('destination')) {
            $location = "location=\"$destination\"; return false";
        }

        $form['actions']['cancel'] = [
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#weight' => 10,
            '#attributes' => [
                'onclick' => $location,
            ],
        ];
    }
}
