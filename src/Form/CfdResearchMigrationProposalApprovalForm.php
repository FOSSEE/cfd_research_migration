<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\CfdResearchMigrationProposalApprovalForm.
 */

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class CfdResearchMigrationProposalApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_research_migration_proposal_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = (int) arg(3);
    $query = \Drupal::database()->select('research_migration_proposal');
    $query->fields('research_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    $query_abstract = \Drupal::database()->select('research_migration_submitted_abstracts_file');
    $query_abstract->fields('research_migration_submitted_abstracts_file');
    $query_abstract->condition('proposal_id', $proposal_id);
    $query_abstract->condition('filetype', 'A');
    $query_abstract_pdf = $query_abstract->execute()->fetchObject();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
        drupal_goto('research-migration-project/manage-proposal');
        return;
      }
    } //$proposal_q
    else {
      drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
      drupal_goto('research-migration-project/manage-proposal');
      return;
    }
    if ($proposal_data->faculty_name == '') {
      $faculty_name = 'NA';
    }
    else {
      $faculty_name = $proposal_data->faculty_name;
    }
    if ($proposal_data->faculty_department == '') {
      $faculty_department = 'NA';
    }
    else {
      $faculty_department = $proposal_data->faculty_department;
    }
    if ($proposal_data->faculty_email == '') {
      $faculty_email = 'NA';
    }
    else {
      $faculty_email = $proposal_data->faculty_email;
    }
    $query = \Drupal::database()->select('research_migration_software_version');
    $query->fields('research_migration_software_version');
    $query->condition('id', $proposal_data->version_id);
    $version_data = $query->execute()->fetchObject();
    $version = $version_data->research_migration_version;
    $query = \Drupal::database()->select('research_migration_simulation_type');
    $query->fields('research_migration_simulation_type');
    $query->condition('id', $proposal_data->simulation_type_id);
    $simulation_type_data = $query->execute()->fetchObject();
    $simulation_type = $simulation_type_data->simulation_type;
    $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => l($proposal_data->name_title . ' ' . $proposal_data->contributor_name, 'user/' . $proposal_data->uid),
      '#title' => t('Student name'),
    ];
    $form['student_email_id'] = [
      '#title' => t('Student Email'),
      '#type' => 'item',
      '#markup' => user_load($proposal_data->uid)->mail,
      '#title' => t('Email'),
    ];
    $form['contributor_contact_no'] = [
      '#title' => t('Contact No.'),
      '#type' => 'item',
      '#markup' => $proposal_data->contact_no,
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => t('University/Institute'),
    ];
    $form['how_did_you_know_about_project'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->how_did_you_know_about_project,
      '#title' => t('How did you know about the project'),
    ];
    $form['faculty_name'] = [
      '#type' => 'item',
      '#markup' => $faculty_name,
      '#title' => t('Name of the faculty'),
    ];
    $form['faculty_department'] = [
      '#type' => 'item',
      '#markup' => $faculty_department,
      '#title' => t('Department of the faculty'),
    ];
    $form['faculty_email'] = [
      '#type' => 'item',
      '#markup' => $faculty_email,
      '#title' => t('Email of the faculty'),
    ];
    $form['country'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->country,
      '#title' => t('Country'),
    ];
    $form['all_state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->state,
      '#title' => t('State'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->city,
      '#title' => t('City'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->pincode,
      '#title' => t('Pincode/Postal code'),
    ];
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Research Migration Project'),
    ];
    $form['version'] = [
      '#type' => 'item',
      '#markup' => $version,
      '#title' => t('Version used'),
    ];
    $form['simulation_type'] = [
      '#type' => 'item',
      '#markup' => $simulation_type,
      '#title' => t('Simulation Type'),
    ];
    $form['solver_used'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solver_used,
      '#title' => t('Solver used'),
    ];

    $form['date_of_proposal'] = [
      '#type' => 'textfield',
      '#title' => t('Date of Proposal'),
      '#default_value' => date('d/m/Y', $proposal_data->creation_date),
      '#disabled' => TRUE,
    ];
    $form['expected_completion_date'] = [
      '#type' => 'textfield',
      '#title' => t('Expected Date of Completion'),
      '#default_value' => date('d/m/Y', $proposal_data->expected_date_of_completion),
      '#disabled' => TRUE,
    ];
    if (($query_abstract_pdf->filename != "") && ($query_abstract_pdf->filename != 'NULL')) {
      $str = substr($query_abstract_pdf->filename, strrpos($query_abstract_pdf->filename, '/'));
      $resource_file = ltrim($str, '/');

      $form['abstract_file_path'] = [
        '#type' => 'item',
        '#title' => t('Synopsis file '),
        '#markup' => l($resource_file, 'research-migration-project/download/project-file/' . $proposal_id) . "",
      ];
    } //$proposal_data->user_defined_compound_filepath != ""
    else {
      $form['abstract_file_path'] = [
        '#type' => 'item',
        '#title' => t('Synopsis file '),
        '#markup' => "Not uploaded<br><br>",
      ];
    }
    $form['approval'] = [
      '#type' => 'radios',
      '#title' => t('CFD research migration proposal'),
      '#options' => [
        '1' => 'Approve',
        '2' => 'Disapprove',
      ],
      '#required' => TRUE,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('Reason for disapproval'),
      '#attributes' => [
        'placeholder' => t('Enter reason for disapproval in minimum 30 characters '),
        'cols' => 50,
        'rows' => 4,
      ],
      '#states' => [
        'visible' => [
          ':input[name="approval"]' => [
            'value' => '2'
            ]
          ]
        ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'item',
      '#markup' => l(t('Cancel'), 'research-migration-project/manage-proposal'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['approval']) == 2) {
      if ($form_state->getValue(['message']) == '') {
        $form_state->setErrorByName('message', t('Reason for disapproval could not be empty'));
      } //$form_state['values']['message'] == ''
    } //$form_state['values']['approval'] == 2
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = (int) arg(3);
    $query = \Drupal::database()->select('research_migration_proposal');
    $query->fields('research_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
        drupal_goto('research-migration-project/manage-proposal');
        return;
      }
    } //$proposal_q
    else {
      drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
      drupal_goto('research-migration-project/manage-proposal');
      return;
    }
    if ($form_state->getValue(['approval']) == 1) {
      $query = "UPDATE {research_migration_proposal} SET approver_uid = :uid, approval_date = :date, approval_status = 1 WHERE id = :proposal_id";
      $args = [
        ":uid" => $user->uid,
        ":date" => time(),
        ":proposal_id" => $proposal_id,
      ];
      \Drupal::database()->query($query, $args);
      /* sending email */
      $user_data = user_load($proposal_data->uid);
      $email_to = $user_data->mail;
      $from = variable_get('research_migration_from_email', '');
      $bcc = $user->mail . ', ' . variable_get('research_migration_emails', '');
      $cc = variable_get('research_migration_cc_emails', '');
      $params['research_migration_proposal_approved']['proposal_id'] = $proposal_id;
      $params['research_migration_proposal_approved']['user_id'] = $proposal_data->uid;
      $params['research_migration_proposal_approved']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];
      if (!drupal_mail('research_migration', 'research_migration_proposal_approved', $email_to, language_default(), $params, $from, TRUE)) {
        drupal_set_message('Error sending email message.', 'error');
      }

      drupal_set_message('CFD research migration proposal No. ' . $proposal_id . ' approved. User has been notified of the approval.', 'status');
      drupal_goto('research-migration-project/manage-proposal');
      return;
    } //$form_state['values']['approval'] == 1
    else {
      if ($form_state->getValue(['approval']) == 2) {
        $query = "UPDATE {research_migration_proposal} SET approver_uid = :uid, approval_date = :date, approval_status = 2, dissapproval_reason = :dissapproval_reason WHERE id = :proposal_id";
        $args = [
          ":uid" => $user->uid,
          ":date" => time(),
          ":dissapproval_reason" => $form_state->getValue(['message']),
          ":proposal_id" => $proposal_id,
        ];
        $result = \Drupal::database()->query($query, $args);
        /* sending email */
        $user_data = user_load($proposal_data->uid);
        $email_to = $user_data->mail;
        $from = variable_get('research_migration_from_email', '');
        $bcc = $user->mail . ', ' . variable_get('research_migration_emails', '');
        $cc = variable_get('research_migration_cc_emails', '');
        $params['research_migration_proposal_disapproved']['proposal_id'] = $proposal_id;
        $params['research_migration_proposal_disapproved']['user_id'] = $proposal_data->uid;
        $params['research_migration_proposal_disapproved']['headers'] = [
          'From' => $from,
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          'Content-Transfer-Encoding' => '8Bit',
          'X-Mailer' => 'Drupal',
          'Cc' => $cc,
          'Bcc' => $bcc,
        ];
        if (!drupal_mail('research_migration', 'research_migration_proposal_disapproved', $email_to, language_default(), $params, $from, TRUE)) {
          drupal_set_message('Error sending email message.', 'error');
        }

        drupal_set_message('CFD research migration proposal No. ' . $proposal_id . ' dis-approved. User has been notified of the dis-approval.', 'error');
        drupal_goto('research-migration-project/manage-proposal');
        return;
      }
    } //$form_state['values']['approval'] == 2
  }

}
?>
