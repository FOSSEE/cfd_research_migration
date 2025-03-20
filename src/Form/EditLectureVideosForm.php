<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\EditLectureVideosForm.
 */

namespace Drupal\cfd_research_migration\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Link;


class EditLectureVideosForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_lecture_videos_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $video_id = (int) arg(2);
    $route_match = \Drupal::routeMatch();

$video_id = (int) $route_match->getParameter('video_id');
// var_dump($video_id);die;
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {research_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('lecture_videos');
    $query->fields('lecture_videos');
    $query->condition('id', $video_id);
    $lecture_video_q = $query->execute();
    $lecture_video_data = $lecture_video_q->fetchObject();
    // var_dump($lecture_video_data);die;

    $form['video_sno'] = [
      '#type' => 'textfield',
      '#title' => t('S.No of the video'),
      '#required' => TRUE,
      '#disabled' => TRUE,
      '#default_value' => $lecture_video_data->video_sno,
    ];

    $form['title_of_video'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the video lecture'),
      // '#size' => 30,
      // '#maxlength' => 50,
        '#required' => TRUE,
      '#default_value' => $lecture_video_data->video_title,
    ];

    $form['description_of_video'] = [
      '#type' => 'text_format',
      '#format' => $lecture_video_data->video_description_text_format,
      '#title' => 'Description of the video',
      '#required' => TRUE,
      '#default_value' => $lecture_video_data->video_description_text,
    ];
    $form['link_to_video'] = [
      '#type' => 'textfield',
      "#title" => "Paste the URL of the video lecture",
      '#size' => 255,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $lecture_video_data->video_link,
    ];
    $form["link_to_script_file"] = [
      "#type" => "textfield",
      "#title" => "Paste the URL of the script file  of the video lecture",
      '#size' => 255,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $lecture_video_data->script_file_link,
    ];
    //var_dump($lecture_video_data->video_visibility);die;
    $form['lecture_visibility'] = [
      '#type' => 'select',
      '#title' => t('Do you want to disable this lecture?'),
      '#default_value' => t($lecture_video_data->video_visibility),
      '#options' => [
        'Y' => t('Yes'),
        'N' => t('No'),
      ],
      '#required' => TRUE,
    ];
    $form["submit"] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $v = $form_state->getValues();
    $query = "UPDATE lecture_videos SET
            video_title = :video_title,
            video_description_text = :video_description_text,
            video_description_text_format = :video_description_text_format,
            script_file_link = :script_file_link,
            video_link = :video_link,
            video_visibility = :video_visibility
            WHERE video_sno = :video_sno";
    $args = [
      ":video_title" => $v['title_of_video'],
      ":video_description_text" => $v['description_of_video']['value'],
      ":video_description_text_format" => $v['description_of_video']['format'],
      ":script_file_link" => $v['link_to_script_file'],
      ":video_link" => $v['link_to_video'],
      ":video_visibility" => $v['lecture_visibility'],
      ":video_sno" => $v['video_sno'],
    ];
    $result = \Drupal::database()->query($query, $args);
    drupal_set_message('Video details updated successfully', 'status');
    // drupal_goto('lecture-videos/manage');
  }

}
?>
