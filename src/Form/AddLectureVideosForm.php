<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\AddLectureVideosForm.
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

class AddLectureVideosForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_lecture_videos_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = [];
    $form['video_sno'] = [
      '#type' => 'textfield',
      '#title' => t('S.No of the video'),
      '#description' => t('Enter s.no starting from 1 to 100'),
      '#required' => TRUE,
    ];
    $form['title_of_video'] = [
      '#type' => 'textfield',
      '#title' => t("Title of the video lecture"),
      '#required' => TRUE,
    ];
    $form["description_of_video"] = [
      "#type" => "text_format",
      '#format' => 'full_html',
      "#title" => "Description of the video",
      "#required" => TRUE,
    ];
    $form["link_to_video"] = [
      "#type" => "textfield",
      "#title" => "Paste the URL of the video lecture",
      '#description' => t('Copy paste the static url of the video, for eg: https://static.fossee.in/cfd/<path_to_video>'),
      '#size' => 255,
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form["link_to_script_file"] = [
      "#type" => "textfield",
      "#title" => "Paste the URL of the script file  of the video lecture",
      '#description' => t('Copy paste the static url of the script file, for eg: https://static.fossee.in/cfd/<path_to_script_file>'),
      '#size' => 255,
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['lecture_visibility'] = [
      '#type' => 'select',
      '#title' => t('Do you want to disable this lecture?'),
      '#options' => [
        'Y' => 'Yes',
        'N' => 'No',
      ],
      '#required' => TRUE,
    ];
    $form["submit"] = [
      "#type" => "submit",
      "#value" => "Submit",
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $v = $form_state->getValues();
    $query = "INSERT INTO lecture_videos(video_sno, video_title, video_description_text, video_description_text_format, script_file_link, video_link, video_visibility, creation_date)VALUES(:video_sno, :video_title, :video_description_text, :video_description_text_format, :script_file_link, :video_link, :video_visibility, :creation_date)";
    $args = [
      ":video_sno" => $v ['video_sno'],
      ":video_title" => $v['title_of_video'],
      ":video_description_text" => $v['description_of_video']['value'],
      ":video_description_text_format" => $v['description_of_video']['format'],
      ":script_file_link" => $v['link_to_script_file'],
      ":video_link" => $v['link_to_video'],
      ":video_visibility" => $v['lecture_visibility'],
      ":creation_date" => time(),
    ];
    $result = \Drupal::database()->query($query, $args);
    if (!$result) {
      \Drupal::messenger()->addMessage("Something went wrong, please contact the web team", 'error');
    }
    else {
      \Drupal::messenger()->addMessage("Video has been added successfully", "status");
    }
    // drupal_goto('lecture-videos/add');
    $response = new RedirectResponse(Url::fromUserInput('/lecture-videos/add')->toString());
    $response->send();
    
  }

}
?>
