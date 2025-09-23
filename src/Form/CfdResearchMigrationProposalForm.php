<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\CfdResearchMigrationProposalForm.
 */


namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Database\Database;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Render\RendererInterface;

class CfdResearchMigrationProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_research_migration_proposal_form';
  }



  /**
   * {@inheritdoc}
   */
   public function buildForm(array $form, FormStateInterface $form_state, $no_js_use = FALSE) {
    $current_user = \Drupal::currentUser();

    // Redirect anonymous users.
    if ($current_user->isAnonymous()) {
      $this->messenger()->addError($this->t('It is mandatory to <a href=":login">login</a> on this website to access the Research Migration proposal form. If you are a new user, please create a new account first.', [
        ':login' => Url::fromRoute('user.login')->toString(),
      ]));
      return new RedirectResponse(Url::fromRoute('user.login', [], ['query' => \Drupal::destination()->getAsArray()])->toString());
    }

    // Check for existing proposal.
    $connection = \Drupal::database();
    $query = $connection->select('research_migration_proposal', 'r')
      ->fields('r')
      ->condition('uid', $current_user->id())
      ->orderBy('id', 'DESC')
      ->range(0, 1);
    $proposal_data = $query->execute()->fetchObject();

    if ($proposal_data && in_array($proposal_data->approval_status, [0, 1])) {
      $this->messenger()->addStatus($this->t('We have already received your proposal.'));
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
    }

    $form['#attributes']['enctype'] = 'multipart/form-data';

    $form['name_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#options' => [
        'Dr' => 'Dr',
        'Prof' => 'Prof',
        'Mr' => 'Mr',
        'Ms' => 'Ms',
      ],
      '#required' => TRUE,
    ];

    $form['contributor_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of the contributor'),
      '#size' => 250,
      '#attributes' => ['placeholder' => $this->t('Enter your full name.....')],
      '#maxlength' => 250,
      '#required' => TRUE,
    ];

    $form['contributor_email_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#size' => 30,
      '#default_value' => $current_user->getEmail(),
      '#disabled' => TRUE,
    ];

    $form['contributor_contact_no'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact No.'),
      '#size' => 10,
      '#attributes' => ['placeholder' => $this->t('Enter your contact number')],
      '#maxlength' => 250,
    ];

    // Example of dependent fields (Country).
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
    ];

    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other than India'),
      '#size' => 100,
      '#attributes' => ['placeholder' => $this->t('Enter your country name')],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => ['value' => 'Others'],
        ],
      ],
    ];

    // TODO: Continue adding all other fields from your D7 form here...

    // File upload example.
    $form['abstract_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Synopsis Submission'),
      '#description' => $this->t('Allowed file extensions: @ext', [
        '@ext' => \Drupal::config('system.file')->get('allowed_extensions'),
      ]),
      '#upload_location' => 'public://abstracts/',
      '#required' => TRUE,
    ];

    // Dates - Use datetime instead of date_popup.
    $form['date_of_proposal'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Date of Proposal'),
      '#default_value' => \Drupal\Core\Datetime\DrupalDateTime::createFromTimestamp(REQUEST_TIME),
      '#disabled' => TRUE,
    ];

    $form['expected_date_of_completion'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Expected Date of Completion'),
      '#date_date_format' => 'd-m-Y',
      '#required' => TRUE,
    ];

    $form['term_condition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<a href="/research-migration-project/term-and-conditions" target="_blank">I agree to the Terms and Conditions</a>'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }



public function updateResearchMigrationFields(array &$form, FormStateInterface $form_state) {
  return $form['research_migration_fields'];
}

/**
 * AJAX callback for updating the solver used field.
 */
function ajax_solver_used_callback(array &$form, FormStateInterface $form_state) {
    $simulation_id = $form_state->getValue('simulation_type') ?? key(_rm_list_of_solvers()); // Ensure a default value

    $response = new AjaxResponse();

    if ($simulation_id < 19) {
        $form['solver_used']['#options'] = \Drupal::service("cfd_research_migration_global")->_rm_list_of_solvers($simulation_id);
        $form['solver_used']['#required'] = TRUE;
        $form['solver_used']['#validated'] = TRUE;

        // Render and replace the solver_used field
        $response->addCommand(new ReplaceCommand('#ajax-solver-replace', \Drupal::service('renderer')->render($form['solver_used'])));
        $response->addCommand(new HtmlCommand('#ajax-solver-text-replace', ''));
    } 
    else {
        $response->addCommand(new HtmlCommand('#ajax-solver-replace', ''));
        $form['solver_used_text']['#required'] = TRUE;
        $form['solver_used_text']['#validated'] = TRUE;

        // Render and replace the solver_used_text field
        $response->addCommand(new ReplaceCommand('#ajax-solver-text-replace', \Drupal::service('renderer')->render($form['solver_used_text'])));
    }

    return $response;
  
}

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    //var_dump($form_state['values']['solver_used']);die;
    if ($form_state->getValue([
      'cfd_project_title_check'
      ]) == 1) {
      $project_title = $form_state->getValue([
        'cfd_research_migration_name_dropdown'
        ]);
    }
    else {

      $project_title = $form_state->getValue(['project_title']);
    }
    if ($form_state->getValue(['term_condition']) == '1') {
      $form_state->setErrorByName('term_condition', t('Please check the terms and conditions'));
      // $form_state['values']['country'] = $form_state['values']['other_country'];
    } //$form_state['values']['term_condition'] == '1'
    if ($form_state->getValue([
      'country'
      ]) == 'Others') {
      if ($form_state->getValue(['other_country']) == '') {
        $form_state->setErrorByName('other_country', t('Enter country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_country'] == ''
      else {
        $form_state->setValue(['country'], $form_state->getValue([
          'other_country'
          ]));
      }
      if ($form_state->getValue(['other_state']) == '') {
        $form_state->setErrorByName('other_state', t('Enter state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_state'] == ''
      else {
        $form_state->setValue(['all_state'], $form_state->getValue([
          'other_state'
          ]));
      }
      if ($form_state->getValue(['other_city']) == '') {
        $form_state->setErrorByName('other_city', t('Enter city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_city'] == ''
      else {
        $form_state->setValue(['city'], $form_state->getValue(['other_city']));
      }
    } //$form_state['values']['country'] == 'Others'
    else {
      if ($form_state->getValue(['country']) == '') {
        $form_state->setErrorByName('country', t('Select country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['country'] == ''
      if ($form_state->getValue([
        'all_state'
        ]) == '') {
        $form_state->setErrorByName('all_state', t('Select state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['all_state'] == ''
      if ($form_state->getValue([
        'city'
        ]) == '') {
        $form_state->setErrorByName('city', t('Select city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['city'] == ''
    }
    //Validation for project title
    $form_state->setValue(['project_title'], trim($form_state->getValue([
      'project_title'
      ])));
    if ($form_state->getValue(['project_title']) != '') {
      if (strlen($form_state->getValue(['project_title'])) > 250) {
        $form_state->setErrorByName('project_title', t('Maximum character limit is 250 characters only, please check the length of the project title'));
      } //strlen($form_state['values']['project_title']) > 250
      else {
        if (strlen($form_state->getValue(['project_title'])) < 10) {
          $form_state->setErrorByName('project_title', t('Minimum character limit is 10 characters, please check the length of the project title'));
        }
      } //strlen($form_state['values']['project_title']) < 10
    } //$form_state['values']['project_title'] != ''
	/*else
	{
		form_set_error('project_title', t('Project title shoud not be empty'));
	}*/

    if ($form_state->getValue(['simulation_type']) < 19) {
      if ($form_state->getValue(['solver_used']) == '0') {
        $form_state->setErrorByName('solver_used', t('Please select an option for Simulation Type'));
      }
    }
    else {
      if ($form_state->getValue(['simulation_type']) == 19) {
        if ($form_state->getValue(['solver_used_text']) != '') {
          if (strlen($form_state->getValue(['solver_used_text'])) > 100) {
            $form_state->setErrorByName('solver_used_text', t('Maximum charater limit is 100 charaters only, please check the length of the solver used'));
          } //strlen($form_state['values']['project_title']) > 250
          else {
            if (strlen($form_state->getValue(['solver_used_text'])) < 7) {
              $form_state->setErrorByName('solver_used_text', t('Minimum charater limit is 7 charaters, please check the length of the solver used'));
            }
          } //strlen($form_state['values']['project_title']) < 10
        }
        else {
          $form_state->setErrorByName('solver_used_text', t('Solver used cannot be empty'));
        }
      }
    }
    if (strtotime(date($form_state->getValue(['expected_date_of_completion']))) < time()) {
      $form_state->setErrorByName('expected_date_of_completion', t('Completion date should not be earlier than proposal date'));
    }

    if ($form_state->getValue(['how_did_you_know_about_project']) == 'Others') {
      if ($form_state->getValue(['others_how_did_you_know_about_project']) == '') {
        $form_state->setErrorByName('others_how_did_you_know_about_project', t('Please enter how did you know about the project'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_country'] == ''
      else {
        $form_state->setValue(['how_did_you_know_about_project'], $form_state->getValue([
          'others_how_did_you_know_about_project'
          ]));
      }
    }
    // if ($form_state['values']['faculty_name'] != '' || $form_state['values']['faculty_name'] != "NULL") {
      if ($form_state->getValue('faculty_name') !== '' && $form_state->getValue('faculty_name') !== "NULL") {

		// if($form_state['values']['faculty_email'] == '' || $form_state['values']['faculty_email'] == "NULL")
    if ($form_state->getValue('faculty_email') === '' || $form_state->getValue('faculty_email') === "NULL") 

    {
			form_set_error('faculty_email', t('Please enter the email id of your faculty'));
		}
		// if($form_state['values']['faculty_department'] == '' || $form_state['values']['faculty_department'] == 'NULL'){
      if ($form_state->getValue('faculty_department') === '' || $form_state->getValue('faculty_department') === 'NULL') {

			form_set_error('faculty_department', t('Please enter the Department of your faculty'));
		}
	}

    if (isset($_FILES['files'])) {
      /* check if atleast one source or result file is uploaded */
      if (!($_FILES['files']['name']['abstract_file_path'])) {
        $form_state->setErrorByName('abstract_file_path', t('Please upload the Synopsis file'));
      }
      /* check for valid filename extensions */
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          /* checking file type */
          $allowed_extensions_str = \Drupal::config('cfd_research_migration.settings')->get('resource_upload_extensions','');
          $allowed_extensions = explode(',', $allowed_extensions_str);
          $fnames = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
          $temp_extension = end($fnames);
          if (!in_array($temp_extension, $allowed_extensions)) {
            $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
          }
          if ($_FILES['files']['size'][$file_form_name] <= 0) {
            $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
          }
          /* check if valid file name */
          if (!cfd_research_migration_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
            $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
          }
        } //$file_name
      } //$_FILES['files']['name'] as $file_form_name => $file_name
    }
    return $form_state;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $root_path =  \Drupal::service("cfd_research_migration_global")->cfd_research_migration_path();
    if (!$user->id()) {
      \Drupal::messenger()->addMessage('It is mandatory to login on this website to access the proposal form', 'error');
      return;
    }
    if ($form_state->getValue(['cfd_project_title_check']) == 1) {
      $project_title = $form_state->getValue(['cfd_research_migration_name_dropdown']);
    }
    else {

      $project_title = $form_state->getValue(['project_title']);
    }
    if ($form_state->getValue(['how_did_you_know_about_project']) == 'Others') {
      $how_did_you_know_about_project = $form_state->getValue(['others_how_did_you_know_about_project']);
    }
    else {
      $how_did_you_know_about_project = $form_state->getValue(['how_did_you_know_about_project']);
    }
    /* inserting the user proposal */
    $v = $form_state->getValues();
    $project_title = trim($project_title);
    $proposar_name = $v['name_title'] . ' ' . $v['contributor_name'];
    $university = $v['university'];
    $directory_name = \Drupal::service("cfd_research_migration_global")->_rm_df_dir_name($project_title, $proposar_name);
    $simulation_id = $v['simulation_type'];
    if ($simulation_id < 19) {
      $solver = $v['solver_used'];
    }
    else {
      $solver = $v['solver_used_text'];
    }
    $result = "INSERT INTO {research_migration_proposal} 
    (
    uid, 
    approver_uid,
    name_title, 
    contributor_name,
    contact_no,
    university,
    institute,
    how_did_you_know_about_project,
    faculty_name,
    faculty_department,
    faculty_email,
    city, 
    pincode, 
    state, 
    country,
    project_title, 
    version_id,
    simulation_type_id,
    solver_used,
    directory_name,
    approval_status,
    is_completed, 
    dissapproval_reason,
    creation_date, 
    expected_date_of_completion,
    approval_date
    ) VALUES
    (
    :uid, 
    :approver_uid, 
    :name_title, 
    :contributor_name, 
    :contact_no,
    :university, 
    :institute,
    :how_did_you_know_about_project,
    :faculty_name,
    :faculty_department,
    :faculty_email,
    :city, 
    :pincode, 
    :state,  
    :country,
    :project_title, 
    :version_id,
    :simulation_type_id,
    :solver_used,
    :directory_name,
    :approval_status,
    :is_completed, 
    :dissapproval_reason,
    :creation_date, 
    :expected_date_of_completion,
    :approval_date
    )";
    $args = [
      ":uid" => $this->currentUser()->id(),
      ":approver_uid" => 0,
      ":name_title" => $v['name_title'],
      ":contributor_name" => \Drupal::service("cfd_research_migration_global")->_df_sentence_case(trim($v['contributor_name'])),
      ":contact_no" => $v['contributor_contact_no'],
      ":university" => $v['university'],
      ":institute" => \Drupal::service("cfd_research_migration_global")->_df_sentence_case($v['institute']),
      ":how_did_you_know_about_project" => trim($how_did_you_know_about_project),
      ":faculty_name" => $v['faculty_name'],
      ":faculty_department" => $v['faculty_department'],
      ":faculty_email" => $v['faculty_email'],
      ":city" => $v['city'],
      ":pincode" => $v['pincode'],
      ":state" => $v['all_state'],
      ":country" => $v['country'],
      ":project_title" => $project_title,
      ":version_id" => $v['version'],
      ":simulation_type_id" => $simulation_id,
      ":solver_used" => $solver,
      ":directory_name" => $directory_name,
      ":approval_status" => 0,
      ":is_completed" => 0,
      ":dissapproval_reason" => "NULL",
      ":creation_date" => time(),
      ":expected_date_of_completion" => strtotime(date($v['expected_date_of_completion'])),
      ":approval_date" => 0,
    ];
    $result1 = \Drupal::database()->query($result, $args, ['return' => Database::RETURN_INSERT_ID]);
    //var_dump($result1->id);die;
    $query_pro = \Drupal::database()->select('research_migration_proposal');
    $query_pro->fields('research_migration_proposal');
    //	$query_pro->condition('id', $proposal_data->id);
    $abstracts_pro = $query_pro->execute()->fetchObject();
    //	$proposal_id = $abstracts_pro->id;
    $dest_path = $directory_name . '/';
    $dest_path1 = $root_path . $dest_path;
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }

    // var_dump($dest_path);die;
    /* uploading files */
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        //$file_type = 'S';
        if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          \Drupal::messenger()->addMessage(t("Error uploading file. File !filename already exists.", [
            '!filename' => $_FILES['files']['name'][$file_form_name]
            ]), 'error');
          //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
        } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
			/* uploading file */
        if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          $query_pro = \Drupal::database()->select('research_migration_proposal');
          $query_pro->fields('research_migration_proposal');
          //$query_pro->condition('id', $proposal_data->id);
          $abstracts_pro = $query_pro->execute()->fetchObject();
          //$proposal_id = $abstracts_pro->id;
          //var_dump($proposal_id);die;
          //$proposal_id = $result1->id;
          $query_abstracts = "INSERT INTO {research_migration_submitted_abstracts} (
	proposal_id,
	approver_uid,
	abstract_approval_status,
	abstract_upload_date,
	abstract_approval_date,
	is_submitted) VALUES (:proposal_id, :approver_uid, :abstract_approval_status,:abstract_upload_date, :abstract_approval_date, :is_submitted)";
          $args = [
            ":proposal_id" => $result1,
            ":approver_uid" => 0,
            ":abstract_approval_status" => 0,
            ":abstract_upload_date" => time(),
            ":abstract_approval_date" => 0,
            ":is_submitted" => 0,
          ];
          $submitted_abstract_id = \Drupal::database()->query($query_abstracts, $args, [
            'return' => Database::RETURN_INSERT_ID
            ]);
          $query = "INSERT INTO {research_migration_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
          $args = [
            ":submitted_abstract_id" => $submitted_abstract_id,
            ":proposal_id" => $result1,
            ":uid" => $user->uid,
            ":approvar_uid" => 0,
            ":filename" => $_FILES['files']['name'][$file_form_name],
            ":filepath" => $_FILES['files']['name'][$file_form_name],
            ":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
            ":filesize" => $_FILES['files']['size'][$file_form_name],
            ":filetype" => 'A',
            ":timestamp" => time(),
          ];

          /*$query = "UPDATE {research_migration_proposal} SET abstract_file_path = :abstract_file_path WHERE id = :id";
				$args = array(
					":abstract_file_path" => $dest_path . $_FILES['files']['name'][$file_form_name],
					":id" => $result1
				);*/

          $updateresult = \Drupal::database()->query($query, $args);
          //var_dump($args);die;

          \Drupal::messenger()->addMessage($file_name . ' uploaded successfully.', 'status');
        } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
        else {
          // \Drupal::messenger()->addMessage('Error uploading file : ' . $dest_path . '/' . $file_name, 'error');
        }
      } //$file_name
    } //$_FILES['files']['name'] as $file_form_name => $file_name
    if (!$result1) {
      \Drupal::messenger()->addMessage(t('Error receiving your proposal. Please try again.'), 'error');
      return;
    } //!$proposal_id
	/* sending email */
    // $email_to = $user->mail;
    // $form = variable_get('research_migration_from_email', '');
    // $bcc = variable_get('research_migration_emails', '');
    // $cc = variable_get('research_migration_cc_emails', '');
    // $params['research_migration_proposal_received']['result1'] = $result1;
    // $params['research_migration_proposal_received']['user_id'] = $user->uid;
    // $params['research_migration_proposal_received']['headers'] = [
    //   'From' => $form,
    //   'MIME-Version' => '1.0',
    //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
    //   'Content-Transfer-Encoding' => '8Bit',
    //   'X-Mailer' => 'Drupal',
    //   'Cc' => $cc,
    //   'Bcc' => $bcc,
    // ];
    // if (!drupal_mail('research_migration', 'research_migration_proposal_received', $email_to, user_preferred_language($user), $params, $form, TRUE)) {
    //   \Drupal::messenger()->addMessage('Error sending email message.', 'error');
    // }
    \Drupal::messenger()->addMessage(t('We have received your Research Migration proposal. We will get back to you soon.'), 'status');
    // drupal_goto('');
    $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  
    // Send the redirect response
      $response->send();
      }
    }
  


?>
