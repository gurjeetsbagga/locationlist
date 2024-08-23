<?php

declare(strict_types=1);

namespace Drupal\location_finder\Form;

use Drupal\Core\Form\FormBase as parentAlias;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Location Finder form.
 */
final class LocationForm extends parentAlias
{
    /**
     * @return string
     */
    public function getFormId(): string
    {
        return 'location_finder_location';
    }

    /**
     * @param  array              $form
     * @param  FormStateInterface $form_state
     * @return array
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['country_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Country Code'),
            '#required' => true,
        ];

        $form['city'] = [
            '#type' => 'textfield',
            '#title' => $this->t('City'),
            '#required' => true,
        ];
        $form['postal_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Postal Code'),
            '#required' => true,
        ];

        $form['actions'] = [
            '#type' => 'actions',
            'submit' => [
                '#type' => 'submit',
                '#value' => $this->t('Send'),
            ],
        ];

        return $form;
    }

    /**
     * @param  array              $form
     * @param  FormStateInterface $form_state
     * @return void
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $form_state->setRedirect(
            'location_finder.finder',
            [
                'country_code' => $form_state->getValue('country_code'),
                'city' => $form_state->getValue('city'),
                'postal_code' => $form_state->getValue('postal_code'),
            ]
        );
        $this->messenger()->addMessage('Form is submitted');
    }
}
