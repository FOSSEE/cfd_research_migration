<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\CfdResearchMigrationAbstractBulkApprovalForm.
 */

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class CfdResearchMigrationAbstractBulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_research_migration_abstract_bulk_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = _bulk_list_of_research_migration_project();
    $selected = !$form_state->getValue(['research_migration_project']) ? $form_state->getValue([
      'research_migration_project'
      ]) : key($options_first);
    $form = [];
    $form['research_migration_project'] = [
      '#type' => 'select',
      '#title' => t('Title of the Research Migration project'),
      '#options' => _bulk_list_of_research_migration_project(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => 'ajax_bulk_research_migration_abstract_details_callback'
        ],
      '#suffix' => '<div id="ajax_selected_research_migration"></div><div id="ajax_selected_research_migration_pdf"></div>',
    ];
    $form['research_migration_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for Research Migration project'),
      '#options' => _bulk_list_research_migration_actions(),
      '#default_value' => 0,
      '#prefix' => '<div id="ajax_selected_research_migration_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="research_migration_project"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('If Dis-Approved please specify reason for Dis-Approval'),
      '#prefix' => '<div id= "message_submit">',
      '#states' => [
        'visible' => [
          [
            ':input[name="research_migration_actions"]' => [
              'value' => 3
              ]
            ],
          'or',
          [
            ':input[name="research_migration_actions"]' => [
              'value' => 4
              ]
            ],
        ]
        ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#states' => [
        'invisible' => [
          ':input[name="lab"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $msg = '';
    $root_path = cfd_research_migration_path();
    //var_dump($root_path);die;
    if ($form_state->get(['clicked_button', '#value']) == 'Submit') {
      if ($form_state->getValue(['research_migration_project']))
        //var_dump($form_state['values']['research_migration_actions']);die;
        // research_migration_abstract_del_lab_pdf($form_state['values']['research_migration_project']);
 {
        if (user_access('Research Migration bulk manage abstract')) {
          $query = \Drupal::database()->select('research_migration_proposal');
          $query->fields('research_migration_proposal');
          $query->condition('id', $form_state->getValue(['research_migration_project']));
          $user_query = $query->execute();
          $user_info = $user_query->fetchObject();
          //var_dump($user_info);die;
          $user_data = user_load($user_info->uid);
          if ($form_state->getValue(['research_migration_actions']) == 1) {
            // approving entire project //
            $query = \Drupal::database()->select('research_migration_submitted_abstracts');
            $query->fields('research_migration_submitted_abstracts');
            $query->condition('proposal_id', $form_state->getValue(['research_migration_project']));
            $abstracts_q = $query->execute();
            //var_dump($abstracts_q);die;
            $experiment_list = '';
            while ($abstract_data = $abstracts_q->fetchObject()) {
              \Drupal::database()->query("UPDATE {research_migration_submitted_abstracts} SET abstract_approval_status = 1, is_submitted = 1, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->uid,
                ':id' => $abstract_data->id,
              ]);
              \Drupal::database()->query("UPDATE {research_migration_submitted_abstracts_file} SET file_approval_status = 1, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", [
                ':approver_uid' => $user->uid,
                ':submitted_abstract_id' => $abstract_data->id,
              ]);
            } //$abstract_data = $abstracts_q->fetchObject()
            drupal_set_message(t('Approved Research Migration project.'), 'status');
            // email 
            $email_subject = t('[!site_name][Research Migration Project] Your uploaded Research Migration project have been approved', [
              '!site_name' => variable_get('site_name', '')
              ]);
            $email_body = [
              0 => t('

Dear !user_name,

Your uploaded project files for the Research Migration project has been approved.

Title of Research Migration project  : ' . $user_info->project_title . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
                '!site_name' => variable_get('site_name', ''),
                '!user_name' => $user_data->name,
              ])
              ];
            /** sending email when everything done **/
            $email_to = $user_data->mail;
            $from = variable_get('research_migration_from_email', '');
            $bcc = variable_get('research_migration_emails', '');
            $cc = variable_get('research_migration_cc_emails', '');
            $params['standard']['subject'] = $email_subject;
            $params['standard']['body'] = $email_body;
            $params['standard']['headers'] = [
              'From' => $from,
              'MIME-Version' => '1.0',
              'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
              'Content-Transfer-Encoding' => '8Bit',
              'X-Mailer' => 'Drupal',
              'Cc' => $cc,
              'Bcc' => $bcc,
            ];
            if (!drupal_mail('research_migration', 'standard', $email_to, language_default(), $params, $from, TRUE)) {
              $msg = drupal_set_message('Error sending email message.', 'error');
            } //!drupal_mail('research_migration', 'standard', $email_to, language_default(), $params, $from, TRUE)
          } //$form_state['values']['research_migration_actions'] == 1
          elseif ($form_state->getValue(['research_migration_actions']) == 2) {
            //pending review entire project 
            $query = \Drupal::database()->select('research_migration_submitted_abstracts');
            $query->fields('research_migration_submitted_abstracts');
            $query->condition('proposal_id', $form_state->getValue(['research_migration_project']));
            $abstracts_q = $query->execute();
            $experiment_list = '';
            while ($abstract_data = $abstracts_q->fetchObject()) {
              \Drupal::database()->query("UPDATE {research_migration_submitted_abstracts} SET abstract_approval_status = 0, is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->uid,
                ':id' => $abstract_data->id,
              ]);
              \Drupal::database()->query("UPDATE {research_migration_proposal} SET is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->uid,
                ':id' => $abstract_data->proposal_id,
              ]);
              \Drupal::database()->query("UPDATE {research_migration_submitted_abstracts_file} SET file_approval_status = 0, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", [
                ':approver_uid' => $user->uid,
                ':submitted_abstract_id' => $abstract_data->id,
              ]);
            } //$abstract_data = $abstracts_q->fetchObject()
            drupal_set_message(t('Resubmit the project files'), 'status');
            // email 
            $email_subject = t('[!site_name][Research Migration Project] Your uploaded Research Migration project have been marked as pending', [
              '!site_name' => variable_get('site_name', '')
              ]);
            $email_body = [
              0 => t('

Dear !user_name,

Kindly resubmit the project files for the project : ' . $user_info->project_title . '.
 

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
                '!site_name' => variable_get('site_name', ''),
                '!user_name' => $user_data->name,
              ])
              ];
            /** sending email when everything done **/
            $email_to = $user_data->mail;
            $from = variable_get('research_migration_from_email', '');
            $bcc = variable_get('research_migration_emails', '');
            $cc = variable_get('research_migration_cc_emails', '');
            $params['standard']['subject'] = $email_subject;
            $params['standard']['body'] = $email_body;
            $params['standard']['headers'] = [
              'From' => $from,
              'MIME-Version' => '1.0',
              'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
              'Content-Transfer-Encoding' => '8Bit',
              'X-Mailer' => 'Drupal',
              'Cc' => $cc,
              'Bcc' => $bcc,
            ];
            if (!drupal_mail('research_migration', 'standard', $email_to, language_default(), $params, $from, TRUE)) {
              drupal_set_message('Error sending email message.', 'error');
            } //!drupal_mail('research_migration', 'standard', $email_to, language_default(), $params, $from, TRUE)
          } //$form_state['values']['research_migration_actions'] == 2
          elseif ($form_state->getValue(['research_migration_actions']) == 3) //disapprove and delete entire Research Migration project
 {
            if (strlen(trim($form_state->getValue(['message']))) <= 30) {
              $form_state->setErrorByName('message', t(''));
              $msg = drupal_set_message("Please mention the reason for disapproval. Minimum 30 character required", 'error');
              return $msg;
            } //strlen(trim($form_state['values']['message'])) <= 30
            if (!user_access('Research Migration bulk delete abstract')) {
              $msg = drupal_set_message(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Lab.'), 'error');
              return $msg;
            } //!user_access('research_migration bulk delete code')
            if (research_migration_abstract_delete_project($form_state->getValue(['research_migration_project']))) //////
 {
              drupal_set_message(t('Dis-Approved and Deleted Entire Research Migration project.'), 'status');
              $email_subject = t('[!site_name][Research Migration Project] Your uploaded Research Migration project have been marked as dis-approved', [
                '!site_name' => variable_get('site_name', '')
                ]);
              $email_body = [
                0 => t('
Dear !user_name,

Your uploaded Research Migration project files for the Research Migration project Title : ' . $user_info->project_title . ' have been marked as dis-approved.

Reason for dis-approval: ' . $form_state->getValue(['message']) . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
                  '!site_name' => variable_get('site_name', ''),
                  '!user_name' => $user_data->name,
                ])
                ];
              $email_to = $user_data->mail;
              $from = variable_get('research_migration_from_email', '');
              $bcc = variable_get('research_migration_emails', '');
              $cc = variable_get('research_migration_cc_emails', '');
              $params['standard']['subject'] = $email_subject;
              $params['standard']['body'] = $email_body;
              $params['standard']['headers'] = [
                'From' => $from,
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                'Content-Transfer-Encoding' => '8Bit',
                'X-Mailer' => 'Drupal',
                'Cc' => $cc,
                'Bcc' => $bcc,
              ];
              if (!drupal_mail('research_migration', 'standard', $email_to, language_default(), $params, $from, TRUE)) {
                drupal_set_message('Error sending email message.', 'error');
              }
            } //research_migration_abstract_delete_project($form_state['values']['research_migration_project'])
            else {
              drupal_set_message(t('Error Dis-Approving and Deleting Entire Research Migration project.'), 'error');
            }
            // email 

          } //$form_state['values']['research_migration_actions'] == 3

        }
      } //user_access('research_migration project bulk manage code')
      return $msg;
    } //$form_state['clicked_button']['#value'] == 'Submit'
  }

}
?>
