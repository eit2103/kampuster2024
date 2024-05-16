<?php

namespace Drupal\user_role_selection\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * Implements form for user role selection settings.
 */
class UserRoleSelectionAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'user_role_selection.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_role_selection_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('user_role_selection.settings');

    // Load available roles.
    $roles = Role::loadMultiple();
    $role_options = [];
    foreach ($roles as $role_id => $role) {
      if ($role_id != 'authenticated' && $role_id != 'anonymous') {
        $role_options[$role_id] = $role->label();
      }
    }

    $form['user_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles available for registration'),
      '#options' => $role_options,
      '#default_value' => $config->get('user_roles') ?: [],
    ];

    $form['role_sort_order'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Role'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('No roles available for sorting.'),
      '#attributes' => [
        'id' => 'role-sort-order-table',
      ],
    ];

    $role_sort_order = $config->get('role_sort_order') ?: array_keys($role_options);
    foreach ($role_sort_order as $role_id) {
      if (isset($role_options[$role_id])) {
        $form['role_sort_order'][$role_id] = [
          'role' => [
            '#markup' => $role_options[$role_id],
          ],
          'weight' => [
            '#type' => 'weight',
            '#default_value' => array_search($role_id, $role_sort_order),
            '#delta' => count($role_options),
            '#title' => $this->t('Weight for @title', ['@title' => $role_options[$role_id]]),
          ],
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_roles = array_filter($form_state->getValue('user_roles'));
    $role_sort_order = [];
    foreach ($form_state->getValue('role_sort_order') as $role_id => $values) {
      $role_sort_order[$values['weight']] = $role_id;
    }
    ksort($role_sort_order);

    $this->config('user_role_selection.settings')
      ->set('user_roles', $user_roles)
      ->set('role_sort_order', array_values($role_sort_order))
      ->save();

    parent::submitForm($form, $form_state);
  }
}