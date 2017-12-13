<?php

namespace Drupal\wienimal_services\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\inline_entity_form\ElementSubmit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class FormAlterService
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

    /**
     * @param $form
     */
    public function addSaveAndContinueButton(&$form)
    {
        // Adds a cancel button to the following forms.
        $containers = $this->config->get('forms_save_continue_button') ?? [];

        if (!array_key_exists($this->currentRouteMatch->getRouteName(), array_flip($containers))) {
            return;
        }

        // We copy from 'submit' so we are certain IEF fields are saved as well
        $form['actions']['saveAndContinue'] = $form['actions']['submit'];
        $form['actions']['saveAndContinue']['#value'] = 'Save and Continue Editing';
        $form['actions']['saveAndContinue']['#name'] = 'save_and_continue';
        array_unshift($form['actions']['saveAndContinue']['#submit'], [ElementSubmit::class, 'trigger']);
        $form['actions']['saveAndContinue']['#submit'][] = [static::class, 'addSaveAndContinueButtonSubmitHandler'];
        $form['actions']['saveAndContinue']['#ief_submit_trigger'] = true;
        $form['actions']['saveAndContinue']['#ief_submit_trigger_all'] = true;
        $form['actions']['saveAndContinue']['#weight'] = 7;
        $form['actions']['saveAndContinue']['#access'] = true;
    }

    /**
     * @param $form
     * @param FormStateInterface $form_state
     */
    public static function addSaveAndContinueButtonSubmitHandler($form, FormStateInterface $form_state)
    {
        /** @var \Drupal\node\NodeForm $nodeForm */
        $nodeForm = $form_state->getBuildInfo()['callback_object'];
        $entity = $nodeForm->getEntity();

        // @see https://drupal.stackexchange.com/questions/223482/force-redirect-in-submit-handler-even-though-destination-parameter-set
        // @see https://www.drupal.org/node/2325463
        $options = [];
        $query = \Drupal::request()->query;
        if ($query->has('destination')) {
            $options['query']['destination'] = $query->get('destination');
            $query->remove('destination');
        }

        if ($fragment = \Drupal::request()->get('url-fragment')) {
            $fragment = substr($fragment, 1, strlen($fragment) - 1);
            $options['fragment'] = $fragment;
        }

        /** @var CurrentRouteMatch $currentRouteMatch */
        $currentRouteMatch = \Drupal::service('current_route_match');

        switch ($currentRouteMatch->getRouteName()) {
            case 'node.add':
            case 'entity.node.edit_form':
                $form_state->setRedirect(
                    'entity.node.edit_form',
                    ['node' => $entity->id()],
                    $options
                );
                break;
            case 'entity.taxonomy_term.add_form':
            case 'entity.taxonomy_term.edit_form':
                $form_state->setRedirect(
                    'entity.taxonomy_term.edit_form',
                    ['taxonomy_term' => $entity->id()],
                    $options
                );
                break;
        }
    }

    /**
     * @param $form
     */
    public function addContentOverviewRedirect(&$form)
    {
        $form['actions']['submit']['#submit'][] = [static::class, 'addContentOverviewRedirectSubmitHandler'];
    }

    /**
     * @param $form
     * @param FormStateInterface $form_state
     */
    public static function addContentOverviewRedirectSubmitHandler($form, FormStateInterface $form_state)
    {
        $form_state->setRedirect('system.admin_content');
    }
}
