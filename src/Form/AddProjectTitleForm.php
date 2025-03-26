<?php

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\Entity\File;

/**
 * Provides the Add Project Title Form.
 */
class AddProjectTitleForm extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new AddProjectTitleForm.
   */
  public function __construct(AccountProxyInterface $current_user, Connection $database, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->database = $database;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('database'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_project_title_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->currentUser->isAnonymous()) {
      $this->messenger->addError($this->t('You must <a href=":login">log in</a> to access this form.', [':login' => '/user/login']));
      return [];
    }

    $form['new_project_title_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the name of the project title'),
      '#maxlength' => 250,
      '#required' => TRUE,
    ];

    $form['project_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the Link of the project'),
      '#maxlength' => 250,
      '#required' => TRUE,
    ];

    // $form['project_title_resource_file'] = [
    //   '#type' => 'managed_file',
    //   '#title' => $this->t('Upload a project title resource file'),
    //   '#upload_location' => 'public://project_titles/',
    //   '#required' => FALSE,
    //   '#description' => $this->t('Allowed extensions: pdf doc docx'),
    //   '#upload_validators' => [
    //     'file_validate_extensions' => ['pdf doc docx'],
    //   ],
    // ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $file = $form_state->getValue('project_title_resource_file');
    if (!empty($file)) {
      $file_entity = File::load(reset($file));
      if ($file_entity && $file_entity->getSize() <= 0) {
        $form_state->setErrorByName('project_title_resource_file', $this->t('File size cannot be zero.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Insert project title into database.
    $id = $this->database->insert('rm_list_of_project_titles')
      ->fields([
        'rm_project_title_name' => $values['new_project_title_name'],
        'rm_project_link' => $values['project_link'],
      ])
      ->execute();

    // Handle file upload.
    if (!empty($values['project_title_resource_file'])) {
      $file = File::load(reset($values['project_title_resource_file']));
      if ($file) {
        $file->setPermanent();
        $file->save();

        // Update database with file path.
        $this->database->update('rm_list_of_project_titles')
          ->fields(['filepath' => $file->getFileUri()])
          ->condition('id', $id)
          ->execute();
      }
    }

    $this->messenger->addStatus($this->t('Project title added successfully.'));
  }

}
