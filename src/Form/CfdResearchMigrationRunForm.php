<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\CfdResearchMigrationRunForm.
 */

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class CfdResearchMigrationRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_research_migration_run_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = _list_of_research_migration();
    $url_research_migration_id = (int) arg(2);
    $research_migration_data = _research_migration_information($url_research_migration_id);
    if ($research_migration_data == 'Not found') {
      $url_research_migration_id = '';
    } //$research_migration_data == 'Not found'
    if (!$url_research_migration_id) {
      $selected = !$form_state->getValue(['research_migration']) ? $form_state->getValue(['research_migration']) : key($options_first);
    } //!$url_research_migration_id
    elseif ($url_research_migration_id == '') {
      $selected = 0;
    } //$url_research_migration_id == ''
    else {
      $selected = $url_research_migration_id;
    }
    $form = [];
    $form['research_migration'] = [
      '#type' => 'select',
      '#title' => t('Title of the research migration'),
      '#options' => _list_of_research_migration(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => 'research_migration_project_details_callback'
        ],
    ];
    if (!$url_research_migration_id) {
      $form['research_migration_details'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_research_migration_details"></div>',
      ];
      $form['selected_research_migration'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_selected_research_migration"></div>',
      ];
    } //!$url_research_migration_id
    else {
      $research_migration_default_value = $url_research_migration_id;
      $form['research_migration_details'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_research_migration_details">' . _research_migration_details($research_migration_default_value) . '</div>',
      ];
      $form['selected_research_migration'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_selected_research_migration">' . l('Download Synopsis', "research-migration-project/download/project-file/" . $research_migration_default_value) . '<br>' . l('Download research migration', 'research-migration-project/full-download/project/' . $research_migration_default_value) . '</div>',
      ];
    }
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {


}
}
?>
