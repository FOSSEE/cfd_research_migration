<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\CfdResearchMigrationAbstractBulkApprovalForm.
 */

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Database\Database;
use Drupal\Component\Render\Markup;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Cache;

class CfdResearchMigrationAbstractBulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_research_migration_abstract_bulk_approval_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $options_first = $this->_bulk_list_of_research_migration_project();
    $selected = !$form_state->getValue(['research_migration_project']) ? $form_state->getValue([
      'research_migration_project'
    ]) : key($options_first);

    $form = [];
    $form['research_migration_project'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the Research Migration project'),
      '#options' => $this->_bulk_list_of_research_migration_project(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajax_bulk_research_migration_abstract_details_callback',
      ],
      '#suffix' => '<div id="ajax_selected_research_migration"></div><div id="ajax_selected_research_migration_pdf"></div>',
    ];
    $form['research_migration_actions'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select action for Research Migration project'),
      '#options' => $this->_bulk_list_research_migration_actions(),
      '#default_value' => 0,
      '#prefix' => '<div id="ajax_selected_research_migration_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="research_migration_project"]' => [
            'value' => 0,
          ],
        ],
      ],
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('If Dis-Approved please specify reason for Dis-Approval'),
      '#prefix' => '<div id="message_submit">',
      '#states' => [
        'visible' => [
          [
            ':input[name="research_migration_actions"]' => [
              'value' => 3,
            ],
          ],
          'or',
          [
            ':input[name="research_migration_actions"]' => [
              'value' => 4,
            ],
          ],
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * AJAX callback for fetching research migration abstract details.
   */
  public function ajax_bulk_research_migration_abstract_details_callback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $research_migration_project_default_value = $form_state->getValue('research_migration_project');

    if ($research_migration_project_default_value != 0) {
      $response->addCommand(new HtmlCommand('#ajax_selected_research_migration', $this->_research_migration_details($research_migration_project_default_value)));
      $form['research_migration_actions']['#options'] = $this->_bulk_list_research_migration_actions();
      $renderer = \Drupal::service('renderer');
      $response->addCommand(new ReplaceCommand('#ajax_selected_research_migration_action', $renderer->render($form['research_migration_actions'])));
    }
    else {
      $response->addCommand(new HtmlCommand('#ajax_selected_research_migration', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_research_migration_action', ''));
    }

    return $response;
  }

  public function _bulk_list_of_research_migration_project() {
    $project_titles = [
      '0' => 'Please select...',
    ];

    $query = \Drupal::database()->select('research_migration_proposal', 'r');
    $query->fields('r', ['id', 'project_title', 'contributor_name']);
    $query->condition('is_submitted', 1);
    $query->condition('approval_status', 1);
    $query->orderBy('project_title', 'ASC');

    $project_titles_q = $query->execute();

    while ($project_titles_data = $project_titles_q->fetchObject()) {
      $project_titles[$project_titles_data->id] = $project_titles_data->project_title .
        ' (Proposed by ' . $project_titles_data->contributor_name . ')';
    }

    return $project_titles;
  }

  public function _bulk_list_research_migration_actions(): array {
    return [
      0 => 'Please select...',
      1 => 'Approve Entire Research Migration Project',
      2 => 'Resubmit Project files',
      3 => 'Dis-Approve Entire Research Migration Project (This will delete Research Migration Project)',
    ];
  }

  public function _research_migration_details($research_migration_proposal_id) {
    $return_html = "";

    $query_pro = \Drupal::database()->select('research_migration_proposal', 'r');
    $query_pro->fields('r');
    $query_pro->condition('r.id', $research_migration_proposal_id);
    $abstracts_pro = $query_pro->execute()->fetchObject();

    $query_pdf = \Drupal::database()->select('research_migration_submitted_abstracts_file', 'f');
    $query_pdf->fields('f');
    $query_pdf->condition('f.proposal_id', $research_migration_proposal_id);
    $query_pdf->condition('f.filetype', 'A');
    $abstracts_pdf = $query_pdf->execute()->fetchObject();

    $abstract_filename = "File not uploaded";
    if ($abstracts_pdf && !empty($abstracts_pdf->filename) && $abstracts_pdf->filename !== "NULL") {
      $abstract_filename = $abstracts_pdf->filename;
    }

    $query_process = \Drupal::database()->select('research_migration_submitted_abstracts_file', 'p');
    $query_process->fields('p');
    $query_process->condition('p.proposal_id', $research_migration_proposal_id);
    $query_process->condition('p.filetype', 'S');
    $abstracts_query_process = $query_process->execute()->fetchObject();

    $abstracts_query_process_filename = "File not uploaded";
    if ($abstracts_query_process && !empty($abstracts_query_process->filename) && $abstracts_query_process->filename !== "NULL") {
      $abstracts_query_process_filename = $abstracts_query_process->filename;
    }

    $download_research_migration = Link::fromTextAndUrl(
      'Download Research Migration project',
      Url::fromUri("internal:/research-migration-project/full-download/project/$research_migration_proposal_id")
    )->toString();

    $return_html .= '<strong>Proposer Name:</strong><br />' . $abstracts_pro->name_title . ' ' . $abstracts_pro->contributor_name . '<br /><br />';
    $return_html .= '<strong>Title of the Research Migration Project:</strong><br />' . $abstracts_pro->project_title . '<br /><br />';
    $return_html .= '<strong>Uploaded an abstract (brief outline) of the project:</strong><br />' . $abstract_filename . '<br /><br />';
    $return_html .= '<strong>Uploaded Case Directory Folder:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
    $return_html .= $download_research_migration;

    return $return_html;
  }
  /**
 * {@inheritdoc}
 */
public function validateForm(array &$form, FormStateInterface $form_state) {
  parent::validateForm($form, $form_state);

  $action = (int) $form_state->getValue('research_migration_actions');

  // Validate minimum length when Dis-Approve (Action 3) is selected.
  if ($action === 3) {
    $message = trim($form_state->getValue('message') ?? '');
    if (mb_strlen($message) <= 30) {
      $form_state->setErrorByName('message', $this->t('Minimum 30 characters required for disapproval reason.'));
    }
  }
}

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();

    if ($form_state->getTriggeringElement()['#value'] == 'Submit') {
      if ($form_state->getValue('research_migration_project')) {
        if ($current_user->hasPermission('Research Migration bulk manage abstract')) {

          $query = \Drupal::database()->select('research_migration_proposal', 'p');
          $query->fields('p');
          $query->condition('id', $form_state->getValue('research_migration_project'));
          $user_info = $query->execute()->fetchObject();

          $user_data = !empty($user_info->uid) ? User::load($user_info->uid) : NULL;

          if (!$user_data || empty($user_data->getEmail())) {
            \Drupal::messenger()->addError($this->t('User or user email address not found.'));
            return;
          }

          $email_to = $user_data->getEmail();
          $site_config = \Drupal::config('system.site');
          $site_name = $site_config->get('name');
          
          // Ensure $from always falls back to system site mail if module config is empty.
          $rm_config = \Drupal::config('research_migration.settings');
          $from = $rm_config->get('research_migration_from_email') ?: $site_config->get('mail');
          $cc = $rm_config->get('research_migration_cc_emails');
          $bcc = $rm_config->get('research_migration_emails');

          // Helper closure to build and sanitize email headers
          $build_headers = function ($from_addr, $cc_addr, $bcc_addr) {
            $headers = ['From' => $from_addr];
            if (!empty($cc_addr)) {
              $headers['Cc'] = $cc_addr;
            }
            if (!empty($bcc_addr)) {
              $headers['Bcc'] = $bcc_addr;
            }
            return $headers;
          };

          $action = $form_state->getValue('research_migration_actions');

          // =======================
          // CASE 1: APPROVED
          // =======================
          if ($action == 1) {
            $query = \Drupal::database()->select('research_migration_submitted_abstracts', 'a');
            $query->fields('a');
            $query->condition('proposal_id', $form_state->getValue('research_migration_project'));
            $abstracts_q = $query->execute();

            while ($abstract_data = $abstracts_q->fetchObject()) {
              \Drupal::database()->update('research_migration_submitted_abstracts')
                ->fields([
                  'abstract_approval_status' => 1,
                  'is_submitted' => 1,
                  'approver_uid' => $current_user->id(),
                ])
                ->condition('id', $abstract_data->id)
                ->execute();

              \Drupal::database()->update('research_migration_submitted_abstracts_file')
                ->fields([
                  'file_approval_status' => 1,
                  'approvar_uid' => $current_user->id(),
                ])
                ->condition('submitted_abstract_id', $abstract_data->id)
                ->execute();
            }

            \Drupal::messenger()->addStatus($this->t('Approved Research Migration project.'));

            $params = [
              'subject' => (string) $this->t('[@site][Research Migration Project] Approved', ['@site' => $site_name]),
              'body' => array_map('strval', [
                $this->t('Dear @user_name,', ['@user_name' => $user_data->getDisplayName()]),
                $this->t('Your uploaded project files have been approved.'),
                $this->t('Title: @title', ['@title' => $user_info->project_title]),
                '',
                $this->t('Best Wishes,'),
                $this->t('@site_name Team', ['@site_name' => $site_name]),
                'FOSSEE, IIT Bombay',
              ]),
              'headers' => $build_headers($from, $cc, $bcc),
            ];

            \Drupal::service('plugin.manager.mail')->mail('research_migration', 'standard', $email_to, $current_user->getPreferredLangcode(), $params, $from, TRUE);
          }

          // =======================
          // CASE 2: PENDING
          // =======================
// =======================
// CASE 2: PENDING
// =======================
elseif ($action == 2) {
  $query = \Drupal::database()->select('research_migration_submitted_abstracts', 'a');
  $query->fields('a');
  $query->condition('proposal_id', $form_state->getValue('research_migration_project'));
  $abstracts_q = $query->execute();

  while ($abstract_data = $abstracts_q->fetchObject()) {

    \Drupal::database()->update('research_migration_submitted_abstracts')
      ->fields([
        'abstract_approval_status' => 0,
        'is_submitted' => 0,
        'approver_uid' => $current_user->id(),
      ])
      ->condition('id', $abstract_data->id)
      ->execute();

    \Drupal::database()->update('research_migration_proposal')
      ->fields([
        'is_submitted' => 0,
        'approver_uid' => $current_user->id(),
      ])
      ->condition('id', $abstract_data->proposal_id)
      ->execute();

    \Drupal::database()->update('research_migration_submitted_abstracts_file')
      ->fields([
        'file_approval_status' => 0,
        'approvar_uid' => $current_user->id(),
      ])
      ->condition('submitted_abstract_id', $abstract_data->id)
      ->execute();
  }

  \Drupal::messenger()->addStatus($this->t('Resubmit the project files'));

  // Match the $params array expected by hook_mail()
  $params = [
    'abstract_approval' => [
      'proposal_id' => $form_state->getValue('research_migration_project'),
    ],
    'abstract_pending' => [
      'user_id' => $user_data->id(),
      'headers' => $build_headers($from, $cc, $bcc),
    ],
  ];

  \Drupal::service('plugin.manager.mail')->mail(
    'research_migration',
    'abstract_pending',
    $email_to,
    $current_user->getPreferredLangcode(),
    $params,
    $from,
    TRUE
  );
}      // =======================
// CASE 3: DISAPPROVED
// =======================
elseif ($action == 3) {
  if (strlen(trim($form_state->getValue('message'))) <= 30) {
    $form_state->setErrorByName('message', $this->t('Minimum 30 characters required.'));
    return;
  }

  if (!$current_user->hasPermission('Research Migration bulk delete abstract')) {
    \Drupal::messenger()->addError($this->t('No permission.'));
    return;
  }

  $proposal_id = $form_state->getValue('research_migration_project');

  // 1. Build params while proposal record still exists in DB
  $params = [
    'research_migration_proposal_deleted' => [
      'proposal_id' => $proposal_id,
      'user_id' => $user_data->id(),
      'headers' => $build_headers($from, $cc, $bcc),
      'reason' => $form_state->getValue('message'),
    ],
  ];

  // 2. Send email FIRST before database record is destroyed
  \Drupal::service('plugin.manager.mail')->mail(
    'research_migration',
    'research_migration_proposal_deleted',
    $email_to,
    $current_user->getPreferredLangcode(),
    $params,
    $from,
    TRUE
  );

  // 3. Delete the project after sending notification
  if (function_exists('research_migration_abstract_delete_project')) {
    research_migration_abstract_delete_project($proposal_id);
    \Drupal::messenger()->addStatus($this->t('Disapproved and deleted project.'));
  }
  
}
}}
      }
    }
  }
