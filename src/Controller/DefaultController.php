<?php /**
 * @file
 * Contains \Drupal\cfd_research_migration\Controller\DefaultController.
 */

namespace Drupal\cfd_research_migration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Service;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Default controller for the cfd_research_migration module.
 */
class DefaultController extends ControllerBase {

  public function manage_lecture_videos() {
    $lecture_videos_array = [];
    $page_content = [];
    $query = \Drupal::database()->select('lecture_videos');
    $query->fields('lecture_videos');
    $query->orderBy('video_sno', 'ASC');
    $videos_q = $query->execute();
    while ($result = $videos_q->fetchObject()) {
      if ($result->video_visibility == 'Y') {
        $visibility = 'Yes';
      }
      else {
        $visibility = 'No';
      }
      $lecture_videos_array[$result->id] = [
        $result->video_sno,
        $result->video_title,
        $visibility,
        date('d-m-Y', $result->creation_date),
        // l('Edit details', 'lecture-videos/edit/' . $result->video_sno),
        
       Link::fromTextAndUrl(
          $this->t('Edit details'),Url::fromUserInput('/lecture-videos/edit/' . $result->video_sno)
        )->toString(),
        
      ];
    }
    $lecture_videos_header = [
      'S.No',
      'Title of the video',
      'Video disabled',
      'Date of video creation',
      'Action',
    ];
    $page_content =  [
      '#type' => 'table',
      '#header' => $lecture_videos_header,
      '#rows' => $lecture_videos_array,
    ];
    return $page_content;
  }

  public function view_lecture_videos() {
    $page_content = "<div id='lecture-video-wrapper'>";
    //$lecture_video_rows = \Drupal::database()->query("select * from lecture_videos where video_visibility = 'N' order by video_sno ASC");
    $query = \Drupal::database()->select('lecture_videos');
    $query->fields('lecture_videos');
    $query->condition('video_visibility', 'N');
    $query->orderBy('video_sno', 'ASC');
    $row = $query->execute();
    while ($result = $row->fetchObject()) {
      $page_content .= "<div class='container-testimonial'><h3><strong>{$result->video_title}</strong></h3>";
      $page_content .= "<video title='' controls='' preload='' data-setup='{}' width='500' height='250'>
 <source src={$result->video_link} type='video/mp4'></video>";
      $page_content .= "<span>{$result->video_description_text}</span><h4>Click <a href='{$result->script_file_link}' target='_blank'>here</a> to view the script file</h4></div>";
    }
    return $page_content;
  }

//   public function cfd_research_migration_proposal_pending() {
//     /* get pending proposals to be approved */
//     $pending_rows = [];
//     $query = \Drupal::database()->select('research_migration_proposal');
//     $query->fields('research_migration_proposal');
//     $query->condition('approval_status', 0);
//     $query->orderBy('id', 'DESC');
//     $pending_q = $query->execute();
//     while ($pending_data = $pending_q->fetchObject()) {
//       $approve_url = Url::fromRoute('cfd_research_migration.proposal_approval_form', ['id' => $pending_data->id]);
//       $edit_url = Url::fromRoute('cfd_research_migration.proposal_edit_form', ['id' => $pending_data->id]);
      
//       $approve_link = Link::fromTextAndUrl(t('Approve'), $approve_url)->toString();
//       $edit_link = Link::fromTextAndUrl(t('Edit'), $edit_url)->toString();
    
//       $mainlink = $approve_link . ' | ' . $edit_link;
      
      
//       $pending_rows[$pending_data->id] = [
//         date('d-m-Y', $pending_data->creation_date),
//         // l($pending_data->name_title . ' ' . $pending_data->contributor_name, 'user/' . $pending_data->uid),

//  Url::fromRoute('entity.user.canonical', ['user' => $pending_data->uid]),
//  Link::fromTextAndUrl($pending_data->name_title . ' ' . $pending_data->contributor_name, $url)->toString(),

//         $pending_data->project_title,
//         $mainlink,

//         // l('Approve', 'research-migration-project/manage-proposal/approve/' . $pending_data->id) . ' | ' . l('Edit', 'research-migration-project/manage-proposal/edit/' . $pending_data->id),



//       ];
//     } //$pending_data = $pending_q->fetchObject()
//     /* check if there are any pending proposals */
//     // if (!$pending_rows) {
//     //   \Drupal::messenger()->addMessage(t('There are no pending proposals.'), 'status');
//     //   return '';
//     // } //!$pending_rows
//     $pending_header = [
//       'Date of Submission',
//       'Student Name',
//       'Title of the Research Migration Project',
//       'Action',
//     ];
//     //$output = theme_table($pending_header, $pending_rows);
//     $output =  [
//       '#type' =>'table',
//       '#header' => $pending_header,
//       '#rows' => $pending_rows,
//     ];
//     return $output;
//   }

public function cfd_research_migration_proposal_pending() {
    /* Get pending proposals to be approved */
    $pending_rows = [];
    $database = Database::getConnection();
    
    $query = $database->select('research_migration_proposal', 'rmp')
        ->fields('rmp')
        ->condition('approval_status', 0)
        ->orderBy('id', 'DESC');
    
    $pending_q = $query->execute();

    // while ($pending_data = $pending_q->fetchObject()) {
    //     // Generate links for approve and edit actions
    //     $approve_url = Url::fromRoute('cfd_research_migration.proposal_approval_form', ['id' => $pending_data->id]);
    //     $edit_url = Url::fromRoute('cfd_research_migration.proposal_edit_form', ['id' => $pending_data->id]);

    //     $approve_link = Link::fromTextAndUrl(t('Approve'), $approve_url)->toString();
    //     $edit_link = Link::fromTextAndUrl(t('Edit'), $edit_url)->toString();
    //     $mainlink = $approve_link . ' | ' . $edit_link;

    //     // Generate user profile link
    //     $user_url = Url::fromRoute('entity.user.canonical', ['user' => $pending_data->uid]);
    //     $user_link = Link::fromTextAndUrl($pending_data->name_title . ' ' . $pending_data->contributor_name, $user_url)->toString();

    //     $pending_rows[] = [
    //         date('d-m-Y', $pending_data->creation_date),
    //         $user_link,
    //         $pending_data->project_title,
    //         $mainlink,
    //     ];
    // }

    while ($pending_data = $pending_q->fetchObject()) {
      // Generate links for approve and edit actions
      $approve_url = Url::fromRoute('cfd_research_migration.proposal_approval_form', ['id' => $pending_data->id]);
      $edit_url = Url::fromRoute('cfd_research_migration.proposal_edit_form', ['id' => $pending_data->id]);

      $approve_link = Link::fromTextAndUrl(t('Approve'), $approve_url)->toRenderable();
      $edit_link = Link::fromTextAndUrl(t('Edit'), $edit_url)->toRenderable();

      // Ensure the action column renders properly
      $mainlink = [
          'data' => [
              '#type' => 'inline_template',
              '#template' => '{{ approve }} | {{ edit }}',
              '#context' => [
                  'approve' => render($approve_link),
                  'edit' => render($edit_link),
              ],
          ],
      ];

    /* Check if there are any pending proposals */
    // if (empty($pending_rows)) {
    //     \Drupal::messenger()->addMessage(t('There are no pending proposals.'), 'status');
    //     return [];
    // }

      $pending_header = [
              'Date of Submission',
              'Student Name',
              'Title of the Research Migration Project',
              'Action',
            ];
            

    return [
        '#type' => 'table',
        '#header' => $pending_header,
        '#rows' => $pending_rows,
    ];
}
}

  // public function cfd_research_migration_proposal_all() {
  //   /* get pending proposals to be approved */
  //   $proposal_rows = [];
  //   $query = \Drupal::database()->select('research_migration_proposal');
  //   $query->fields('research_migration_proposal');
  //   $query->orderBy('id', 'DESC');
  //   $proposal_q = $query->execute();
  //   while ($proposal_data = $proposal_q->fetchObject()) {
  //     $approval_status = '';
  //     switch ($proposal_data->approval_status) {
  //       case 0:
  //         $approval_status = 'Pending';
  //         break;
  //       case 1:
  //         $approval_status = 'Approved';
  //         break;
  //       case 2:
  //         $approval_status = 'Dis-approved';
  //         break;
  //       case 3:
  //         $approval_status = 'Completed';
  //         break;
  //       case 5:
  //         $approval_status = 'On Hold';
  //         break;
  //       default:
  //         $approval_status = 'Unknown';
  //         break;
  //     } //$proposal_data->approval_status
  //     if ($proposal_data->actual_completion_date == 0) {
  //       $actual_completion_date = "Not Completed";
  //     } //$proposal_data->actual_completion_date == 0
  //     else {
  //       $actual_completion_date = date('d-m-Y', $proposal_data->actual_completion_date);
  //     }
  //     if ($proposal_data->approval_date == 0) {
  //       $approval_date = "Not Approved";
  //     } //$proposal_data->actual_completion_date == 0
  //     else {
  //       $approval_date = date('d-m-Y', $proposal_data->approval_date);
  //     }
  //     $proposal_rows[] = [
  //       date('d-m-Y', $proposal_data->creation_date),
  //       l($proposal_data->contributor_name, 'user/' . $proposal_data->uid),
  //       $proposal_data->project_title,
  //       $approval_date,
  //       $actual_completion_date,
  //       $approval_status,
  //       l('Status', 'research-migration-project/manage-proposal/status/' . $proposal_data->id) . ' | ' . l('Edit', 'research-migration-project/manage-proposal/edit/' . $proposal_data->id),
  //     ];
  //   } //$proposal_data = $proposal_q->fetchObject()
  //   /* check if there are any pending proposals */
  //   if (!$proposal_rows) {
  //     \Drupal::messenger()->addMessage(t('There are no proposals.'), 'status');
  //     return '';
  //   } //!$proposal_rows
  //   $proposal_header = [
  //     'Date of Submission',
  //     'Student Name',
  //     'Title of the Research Migration project',
  //     'Date of Approval',
  //     'Date of Project Completion',
  //     'Status',
  //     'Action',
  //   ];
  //   $output = theme('table', [
  //     'header' => $proposal_header,
  //     'rows' => $proposal_rows,
  //   ]);
  //   return $output;
  // }
  
  
  public function cfd_research_migration_proposal_all() {
      /* Get all proposals */
      $proposal_rows = [];
      $database = Database::getConnection();
      
      $query = $database->select('research_migration_proposal', 'rmp')
          ->fields('rmp')
          ->orderBy('id', 'DESC');
      
      $proposal_q = $query->execute();
  
      while ($proposal_data = $proposal_q->fetchObject()) {
          // Determine Approval Status
          $approval_statuses = [
              0 => 'Pending',
              1 => 'Approved',
              2 => 'Dis-approved',
              3 => 'Completed',
              5 => 'On Hold',
          ];
          $approval_status = $approval_statuses[$proposal_data->approval_status] ?? 'Unknown';
  
          // Format Dates
          $actual_completion_date = $proposal_data->actual_completion_date ? date('d-m-Y', $proposal_data->actual_completion_date) : "Not Completed";
          $approval_date = $proposal_data->approval_date ? date('d-m-Y', $proposal_data->approval_date) : "Not Approved";
  
          // Generate user profile link
          $user_url = Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid]);
          $user_link = Link::fromTextAndUrl($proposal_data->contributor_name, $user_url)->toRenderable();
  
          // Generate Status and Edit links
          $status_url = Url::fromRoute('cfd_research_migration.proposal_status_form', ['id' => $proposal_data->id]);
          $edit_url = Url::fromRoute('cfd_research_migration.proposal_edit_form', ['id' => $proposal_data->id]);
  
          $status_link = Link::fromTextAndUrl(t('Status'), $status_url)->toRenderable();
          $edit_link = Link::fromTextAndUrl(t('Edit'), $edit_url)->toRenderable();
  
          // Render links inline
          $action_links = [
              'data' => [
                  '#type' => 'inline_template',
                  '#template' => '{{ status }} | {{ edit }}',
                  '#context' => [
                      'status' => render($status_link),
                      'edit' => render($edit_link),
                  ],
              ],
          ];
  
          $proposal_rows[] = [
              'date' => date('d-m-Y', $proposal_data->creation_date),
              'student_name' => ['data' => $user_link],
              'title' => $proposal_data->project_title,
              'approval_date' => $approval_date,
              'completion_date' => $actual_completion_date,
              'status' => $approval_status,
              'action' => $action_links,
          ];
      }
  
      /* Check if there are any proposals */
      if (empty($proposal_rows)) {
          \Drupal::messenger()->addMessage(t('There are no proposals.'), 'status');
          return [];
      }
  
      // Define table headers
      $proposal_header = [
          'date' => t('Date of Submission'),
          'student_name' => t('Student Name'),
          'title' => t('Title of the Research Migration Project'),
          'approval_date' => t('Date of Approval'),
          'completion_date' => t('Date of Project Completion'),
          'status' => t('Status'),
          'action' => t('Action'),
      ];
  
      return [
          '#type' => 'table',
          '#header' => $proposal_header,
          '#rows' => $proposal_rows,
      ];
  }
  
  // public function cfd_research_migration_proposal_edit_file_all() {
  //   /* get pending proposals to be approved */
  //   $proposal_rows = [];
  //   $query = \Drupal::database()->select('research_migration_proposal');
  //   $query->fields('research_migration_proposal');
  //   $query->orderBy('id', 'DESC');
  //   $query->condition('approval_status', '0', '<>');
  //   $query->condition('approval_status', '1', '<>');
  //   $query->condition('approval_status', '2', '<>');
  //   $query->orderBy('approval_status', 'DESC');
  //   $proposal_q = $query->execute();
  //   while ($proposal_data = $proposal_q->fetchObject()) {
  //     $approval_status = '';
  //     switch ($proposal_data->approval_status) {
  //       case 0:
  //         $approval_status = 'Pending';
  //         break;
  //       case 1:
  //         $approval_status = 'Approved';
  //         break;
  //       case 2:
  //         $approval_status = 'Dis-approved';
  //         break;
  //       case 3:
  //         $approval_status = 'Completed';
  //         break;
  //       case 5:
  //         $approval_status = 'On Hold';
  //         break;
  //       default:
  //         $approval_status = 'Unknown';
  //         break;
  //     } //$proposal_data->approval_status
  //     if ($proposal_data->actual_completion_date == 0) {
  //       $actual_completion_date = "Not Completed";
  //     } //$proposal_data->actual_completion_date == 0
  //     else {
  //       $actual_completion_date = date('d-m-Y', $proposal_data->actual_completion_date);
  //     }
  //     if ($proposal_data->approval_date == 0) {
  //       $approval_date = "Not Approved";
  //     } //$proposal_data->actual_completion_date == 0
  //     else {
  //       $approval_date = date('d-m-Y', $proposal_data->approval_date);
  //     }
  //     $proposal_rows[] = [
  //       date('d-m-Y', $proposal_data->creation_date),
  //       l($proposal_data->contributor_name, 'user/' . $proposal_data->uid),
  //       $proposal_data->project_title,
  //       $approval_date,
  //       $actual_completion_date,
  //       $approval_status,
  //       l('Edit', 'research-migration-project/abstract-code/edit-upload-files/' . $proposal_data->id),
  //     ];
  //   } //$proposal_data = $proposal_q->fetchObject()
  //   /* check if there are any pending proposals */
  //   if (!$proposal_rows) {
  //     \Drupal::messenger()->addMessage(t('There are no proposals.'), 'status');
  //     return '';
  //   } //!$proposal_rows
  //   $proposal_header = [
  //     'Date of Submission',
  //     'Student Name',
  //     'Title of the Research Migration project',
  //     'Date of Approval',
  //     'Date of Project Completion',
  //     'Status',
  //     'Action',
  //   ];
  //   $output = theme('table', [
  //     'header' => $proposal_header,
  //     'rows' => $proposal_rows,
  //   ]);
  //   return $output;
  // }


public function cfd_research_migration_proposal_edit_file_all() {
    /* Get proposals excluding approval_status 0, 1, and 2 */
    $proposal_rows = [];
    $database = Database::getConnection();

    $query = $database->select('research_migration_proposal', 'rmp')
        ->fields('rmp')
        ->condition('approval_status', [0, 1, 2], 'NOT IN')
        ->orderBy('approval_status', 'DESC')
        ->orderBy('id', 'DESC');
    
    $proposal_q = $query->execute();

    while ($proposal_data = $proposal_q->fetchObject()) {
        // Determine Approval Status
        $approval_statuses = [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Dis-approved',
            3 => 'Completed',
            5 => 'On Hold',
        ];
        $approval_status = $approval_statuses[$proposal_data->approval_status] ?? 'Unknown';

        // Format Dates
        $actual_completion_date = $proposal_data->actual_completion_date ? date('d-m-Y', $proposal_data->actual_completion_date) : "Not Completed";
        $approval_date = $proposal_data->approval_date ? date('d-m-Y', $proposal_data->approval_date) : "Not Approved";

        // Generate user profile link
        $user_url = Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid]);
        $user_link = Link::fromTextAndUrl($proposal_data->contributor_name, $user_url)->toRenderable();

        // Generate Edit link
        $edit_url = Url::fromRoute('cfd_research_migration.proposal_edit_file_all', ['id' => $proposal_data->id]);
        $edit_link = Link::fromTextAndUrl(t('Edit'), $edit_url)->toRenderable();

        $proposal_rows[] = [
            'date' => date('d-m-Y', $proposal_data->creation_date),
            'student_name' => ['data' => $user_link],
            'title' => $proposal_data->project_title,
            'approval_date' => $approval_date,
            'completion_date' => $actual_completion_date,
            'status' => $approval_status,
            'action' => ['data' => $edit_link],
        ];
    }

    /* Check if there are any proposals */
    if (empty($proposal_rows)) {
        \Drupal::messenger()->addMessage(t('There are no proposals.'), 'status');
        return [];
    }

    // Define table headers
    $proposal_header = [
        'date' => t('Date of Submission'),
        'student_name' => t('Student Name'),
        'title' => t('Title of the Research Migration Project'),
        'approval_date' => t('Date of Approval'),
        'completion_date' => t('Date of Project Completion'),
        'status' => t('Status'),
        'action' => t('Action'),
    ];

    return [
        '#type' => 'table',
        '#header' => $proposal_header,
        '#rows' => $proposal_rows,
    ];
}


  // public function cfd_research_migration_abstract() {
  //   $user = \Drupal::currentUser();
  //   $return_html = "";
  //   $proposal_data = cfd_research_migration_get_proposal();
  //   if (!$proposal_data) {
  //     // drupal_goto('');
  //     return;
  //   } //!$proposal_data
  //   //$return_html .= l('Upload abstract', 'research-migration-project/abstract-code/upload') . '<br />';
  //   /* get experiment list */
  //   $query = \Drupal::database()->select('research_migration_submitted_abstracts');
  //   $query->fields('research_migration_submitted_abstracts');
  //   $query->condition('proposal_id', $proposal_data->id);
  //   $abstracts_q = $query->execute()->fetchObject();
  //   $query_pro = \Drupal::database()->select('research_migration_proposal');
  //   $query_pro->fields('research_migration_proposal');
  //   $query_pro->condition('id', $proposal_data->id);
  //   $abstracts_pro = $query_pro->execute()->fetchObject();
  //   $query_pdf = \Drupal::database()->select('research_migration_submitted_abstracts_file');
  //   $query_pdf->fields('research_migration_submitted_abstracts_file');
  //   $query_pdf->condition('proposal_id', $proposal_data->id);
  //   $query_pdf->condition('filetype', 'A');
  //   $abstracts_pdf = $query_pdf->execute()->fetchObject();
  //   if ($abstracts_pdf == TRUE) {
  //     if ($abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != "") {
  //       $abstract_filename = $abstracts_pdf->filename;
  //       //$abstract_filename = l($abstracts_pdf->filename, 'research-migration-project/download/project-file/' . $proposal_data->id);
  //     } //$abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != ""
  //     else {
  //       $abstract_filename = "File not uploaded";
  //     }
  //   } //$abstracts_pdf == TRUE
  //   else {
  //     $abstract_filename = "File not uploaded";
  //   }
  //   $query_process = \Drupal::database()->select('research_migration_submitted_abstracts_file');
  //   $query_process->fields('research_migration_submitted_abstracts_file');
  //   $query_process->condition('proposal_id', $proposal_data->id);
  //   $query_process->condition('filetype', 'S');
  //   $abstracts_query_process = $query_process->execute()->fetchObject();
  //   if ($abstracts_query_process == TRUE) {
  //     if ($abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != "") {
  //       $abstracts_query_process_filename = $abstracts_query_process->filename;
  //       //$abstracts_query_process_filename = l($abstracts_query_process->filename, 'research-migration-project/download/project-file/' . $proposal_data->id);
  //     } //$abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != ""
  //     else {
  //       $abstracts_query_process_filename = "File not uploaded";
  //     }
  //     if ($abstracts_q->is_submitted == '') {
  //       $url = l('Upload Case Directory', 'research-migration-project/abstract-code/upload');
  //     } //$abstracts_q->is_submitted == ''
  //     else {
  //       if ($abstracts_q->is_submitted == 1) {
  //         $url = "";
  //       } //$abstracts_q->is_submitted == 1
  //       else {
  //         if ($abstracts_q->is_submitted == 0) {
  //           $url = l('Edit', 'research-migration-project/abstract-code/upload');
  //         }
  //       }
  //     } //$abstracts_q->is_submitted == 0
  //   } //$abstracts_query_process == TRUE
  //   else {
  //     // $url = l('Upload Case Directory', 'research-migration-project/abstract-code/upload');
  //     $abstracts_query_process_filename = "File not uploaded";
  //   }
  //   $return_html .= '<strong>Contributor Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '<br /><br />';
  //   $return_html .= '<strong>Title of the Research Migration Project:</strong><br />' . $proposal_data->project_title . '<br /><br />';
  //   $return_html .= '<strong>Uploaded Synopsis Submission:</strong><br />' . $abstract_filename . '<br /><br />';
  //   $return_html .= '<strong>Uploaded Case Directory:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
  //   $return_html .= $url . '<br />';
  //   return $return_html;
  // }


public function cfd_research_migration_abstract() {
    $user = \Drupal::currentUser();
    $return_html = "";

    // Fetch proposal data
    $proposal_data = cfd_research_migration_get_proposal();
    if (!$proposal_data) {
        return;
    }

    // Fetch submitted abstracts
    $database = Database::getConnection();
    $abstracts_q = $database->select('research_migration_submitted_abstracts', 'rsa')
        ->fields('rsa')
        ->condition('proposal_id', $proposal_data->id)
        ->execute()
        ->fetchObject();

    $abstracts_pro = $database->select('research_migration_proposal', 'rmp')
        ->fields('rmp')
        ->condition('id', $proposal_data->id)
        ->execute()
        ->fetchObject();

    // Fetch synopsis submission file
    $abstracts_pdf = $database->select('research_migration_submitted_abstracts_file', 'rsaf')
        ->fields('rsaf')
        ->condition('proposal_id', $proposal_data->id)
        ->condition('filetype', 'A')
        ->execute()
        ->fetchObject();

    $abstract_filename = ($abstracts_pdf && !empty($abstracts_pdf->filename))
        ? $abstracts_pdf->filename
        : "File not uploaded";

    // Fetch case directory file
    $abstracts_query_process = $database->select('research_migration_submitted_abstracts_file', 'rsaf')
        ->fields('rsaf')
        ->condition('proposal_id', $proposal_data->id)
        ->condition('filetype', 'S')
        ->execute()
        ->fetchObject();

    $abstracts_query_process_filename = ($abstracts_query_process && !empty($abstracts_query_process->filename))
        ? $abstracts_query_process->filename
        : "File not uploaded";

    // Determine upload/edit link
    $url = "";
    if (!empty($abstracts_q->is_submitted)) {
        if ($abstracts_q->is_submitted == 0) {
            $upload_url = Url::fromRoute('cfd_research_migration.abstract_code_upload');
            $url = Link::fromTextAndUrl(t('Edit'), $upload_url)->toString();
        }
    } else {
        $upload_url = Url::fromRoute('cfd_research_migration.abstract_code_upload');
        $url = Link::fromTextAndUrl(t('Upload Case Directory'), $upload_url)->toString();
    }

    // Generate output
    $return_html .= '<strong>Contributor Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '<br /><br />';
    $return_html .= '<strong>Title of the Research Migration Project:</strong><br />' . $proposal_data->project_title . '<br /><br />';
    $return_html .= '<strong>Uploaded Synopsis Submission:</strong><br />' . $abstract_filename . '<br /><br />';
    $return_html .= '<strong>Uploaded Case Directory:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
    $return_html .= $url . '<br />';

    return [
        '#markup' => $return_html,
    ];
}

  public function cfd_research_migration_download_full_project() {
    $user = \Drupal::currentUser();
    $id = arg(3);
    $root_path = cfd_research_migration_path();
    //var_dump($root_path);die;
    $query = \Drupal::database()->select('research_migration_proposal');
    $query->fields('research_migration_proposal');
    $query->condition('id', $id);
    $research_migration_q = $query->execute();
    $research_migration_data = $research_migration_q->fetchObject();
    $research_migration_PATH = $research_migration_data->directory_name . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new ZipArchive();
    $zip->open($zip_filename, ZipArchive::CREATE);
    $query = \Drupal::database()->select('research_migration_proposal');
    $query->fields('research_migration_proposal');
    $query->condition('id', $id);
    $circuit_simulation_udc_q = $query->execute();
    $query = \Drupal::database()->select('research_migration_proposal');
    $query->fields('research_migration_proposal');
    $query->condition('id', $id);
    $query = \Drupal::database()->select('research_migration_submitted_abstracts_file');
    $query->fields('research_migration_submitted_abstracts_file');
    $query->condition('proposal_id', $id);
    $project_files = $query->execute();
    while ($cfd_project_files = $project_files->fetchObject()) {
      $zip->addFile($root_path . $research_migration_PATH . $cfd_project_files->filepath, $research_migration_PATH . str_replace(' ', '_', basename($cfd_project_files->filename)));
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      if ($user->uid) {
        /* download zip file */
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $research_migration_data->project_title) . '.zip"');
        header('Content-Length: ' . filesize($zip_filename));
        ob_end_flush();
        ob_clean();
        flush();
        readfile($zip_filename);
        unlink($zip_filename);
      } //$user->uid
      else {
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $research_migration_data->project_title) . '.zip"');
        header('Content-Length: ' . filesize($zip_filename));
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Pragma: no-cache');
        ob_end_flush();
        ob_clean();
        flush();
        readfile($zip_filename);
        unlink($zip_filename);
      }
    } //$zip_file_count > 0
    else {
      \Drupal::messenger()->addMessage("There are no research migration project in this proposal to download", 'error');
      drupal_goto('circuit-simulation-project/full-download/project');
    }
  }

  public function cfd_research_migration_completed_proposals_all() {
    $output = "";
    $query = \Drupal::database()->select('research_migration_proposal');
    $query->fields('research_migration_proposal');
    $query->condition('approval_status', 3);
    $query->orderBy('actual_completion_date', 'DESC');
    //$query->condition('is_completed', 1);
    $result = $query->execute();

    //var_dump($research_migration_abstract);die;
    if ($result->rowCount() == 0) {
      $output .= "Work has been completed for the following research migrations. We welcome your contributions." . "<hr>";

    } //$result->rowCount() == 0
    else {
      $output .= "Work has been completed for the following research migrations. We welcome your contributions." . "<hr>";
      $preference_rows = [];
      $i = $result->rowCount();
      while ($row = $result->fetchObject()) {
        $proposal_id = $row->id;
        $query1 = \Drupal::database()->select('research_migration_submitted_abstracts_file');
        $query1->fields('research_migration_submitted_abstracts_file');
        $query1->condition('file_approval_status', 1);
        $query1->condition('proposal_id', $proposal_id);
        $research_migration_files = $query1->execute();
        $research_migration_abstract = $research_migration_files->fetchObject();
        $solver_used = $row->solver_used;
        $project_title = l($row->project_title, "research-migration-project/research-migration-run/" . $row->id) . t("<br><strong>(Solver used: ") . $solver_used . t(")</strong>") ;
        $year = date("Y", $row->actual_completion_date);
        $preference_rows[] = [
          $i,
          $project_title,
          //$solver_used,
				$row->contributor_name,
          $row->university,
          $year,
        ];
        $i--;
      } //$row = $result->fetchObject()
      $preference_header = [
        'No',
        'Research Migration Project',
        //'Solver used',
			'Contributor Name',
        'University/ Institute',
        'Year of Completion',
      ];
      $output .= theme('table', [
        'header' => $preference_header,
        'rows' => $preference_rows,
      ]);
    }
    return $output;
  }

  public function cfd_research_migration_progress_all() {
    $page_content = "";
    $query = \Drupal::database()->select('research_migration_proposal');
    $query->fields('research_migration_proposal');
    $query->condition('approval_status', 1);
    $query->condition('is_completed', 0);
    $query->orderBy('approval_date', DESC);
    $result = $query->execute();
    if ($result->rowCount() == 0) {
      $page_content .= "Work is in progress for the following research migration under Research Migration Project<hr>";
    } //$result->rowCount() == 0
    else {
      $page_content .= "Work is in progress for the following research migration under Research Migration Project<hr>";
      $preference_rows = [];
      $i = $result->rowCount();
      while ($row = $result->fetchObject()) {
        $approval_date = date("Y", $row->approval_date);
        $preference_rows[] = [
          $i,
          $row->project_title,
          $row->contributor_name,
          $row->university,
          $approval_date,
        ];
        $i--;
      } //$row = $result->fetchObject()
      $preference_header = [
        'No',
        'Research Migration Project',
        'Contributor Name',
        'Institute/ University',
        'Year',
      ];
      $page_content .= theme('table', [
        'header' => $preference_header,
        'rows' => $preference_rows,
      ]);
    }
    return $page_content;
  }

  public function list_of_available_project_titles() {
    $output = "";
    //$static_url = "https://static.fossee.in/cfd/project-titles/";
    $preference_rows = [];
    $i = 1;
    $query = \Drupal::database()->query("SELECT * from rm_list_of_project_titles WHERE {rm_project_title_name} NOT IN( SELECT  project_title from research_migration_proposal WHERE approval_status = 0 OR approval_status = 1 OR approval_status = 3)");
    while ($result = $query->fetchObject()) {
      $preference_rows[] = [
        $i,
        //print_r(array_keys($case_studies_list))
				$result->rm_project_title_name,
        l('Click Here', $result->rm_project_link, [
          'attributes' => [
            'target' => '_blank'
            ]
          ]),
        //l(Download, 'research-migration-project/download/project-title-file/' .$result->id)
      ];
      $i++;
    }
    $preference_header = [
      'No',
      'List of available projects',
      'Link to the paper',
    ];
    $output .= theme('table', [
      'header' => $preference_header,
      'rows' => $preference_rows,
    ]);

    return $output;
  }

  public function download_research_migration_project_title_files() {
    $id = arg(3);
    $root_path = cfd_research_migration_project_titles_resource_file_path();
    $query = \Drupal::database()->select('rm_list_of_project_titles');
    $query->fields('rm_list_of_project_titles');
    $query->condition('id', $id);
    $result = $query->execute();
    $rm_project_files_list = $result->fetchObject();
    //$directory_name = $case_study_project_files_list->filepath;
    $abstract_file = $rm_project_files_list->filepath;
    ob_clean();
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Type: application/pdf");
    header('Content-disposition: attachment; filename="' . $abstract_file . '"');
    header("Content-Length: " . filesize($root_path . $abstract_file));
    header("Content-Transfer-Encoding: binary");
    header("Expires: 0");
    header("Pragma: no-cache");
    readfile($root_path . $abstract_file);
    ob_end_flush();
    ob_clean();
  }

  public function cfd_research_migration_project_files() {
    $proposal_id = arg(3);
    $root_path = cfd_research_migration_path();
    $query = \Drupal::database()->select('research_migration_submitted_abstracts_file');
    $query->fields('research_migration_submitted_abstracts_file');
    $query->condition('proposal_id', $proposal_id);
    $query->condition('filetype', 'A');
    $result = $query->execute();
    $cfd_research_migration_project_files = $result->fetchObject();
    $query1 = \Drupal::database()->select('research_migration_proposal');
    $query1->fields('research_migration_proposal');
    $query1->condition('id', $proposal_id);
    $result1 = $query1->execute();
    $research_migration = $result1->fetchObject();
    $directory_name = $research_migration->directory_name . '/';
    $abstract_file = $cfd_research_migration_project_files->filename;
    ob_clean();
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Type: application/pdf");
    header('Content-disposition: attachment; filename="' . $abstract_file . '"');
    header("Content-Length: " . filesize($root_path . $directory_name . $abstract_file));
    header("Content-Transfer-Encoding: binary");
    header("Expires: 0");
    header("Pragma: no-cache");
    readfile($root_path . $directory_name . $abstract_file);
    ob_end_flush();
    ob_clean();
  }

  public function _list_research_migration_certificates() {
    $user = \Drupal::currentUser();
    $query_id = \Drupal::database()->query("SELECT id FROM research_migration_proposal WHERE approval_status=3 AND uid= :uid", [
      ':uid' => $user->uid
      ]);
    $exist_id = $query_id->fetchObject();
    //var_dump($exist_id->id);die;
    if ($exist_id) {
      if ($exist_id->id) {
        if ($exist_id->id < 1) {
          \Drupal::messenger()->addMessage('<strong>You need to propose a <a href="https://cfd.fossee.in/research-migration-project/proposal">Research Migration Proposal</a></strong> or if you have already proposed then your Research Migration is under reviewing process', 'status');
          return '';
        } //$exist_id->id < 3
        else {
          $search_rows = [];
          global $output;
          $output = '';
          $query3 = \Drupal::database()->query("SELECT id,project_title,contributor_name FROM research_migration_proposal WHERE approval_status=3 AND uid= :uid", [
            ':uid' => $user->uid
            ]);
          while ($search_data3 = $query3->fetchObject()) {
            if ($search_data3->id) {
              $search_rows[] = [
                $search_data3->project_title,
                $search_data3->contributor_name,
                l('Download Certificate', 'research-migration-project/certificates/generate-pdf/' . $search_data3->id),
              ];
            } //$search_data3->id
          } //$search_data3 = $query3->fetchObject()
          if ($search_rows) {
            $search_header = [
              'Project Title',
              'Contributor Name',
              'Download Certificates',
            ];
            $output = theme('table', [
              'header' => $search_header,
              'rows' => $search_rows,
            ]);
            return $output;
          } //$search_rows
          else {
            echo ("Error");
            return '';
          }
        }
      }
    } //$exist_id->id
    else {
      \Drupal::messenger()->addMessage('<strong>You need to propose a <a href="https://cfd.fossee.in/research-migration-project/proposal">Research Migration Proposal</a></strong> or if you have already proposed then your Research Migration is under reviewing process', 'status');
      $page_content = "<span style='color:red;'> No certificate available </span>";
      return $page_content;
    }
  }

  public function verify_certificates($qr_code = 0) {
    $qr_code = arg(3);
    $page_content = "";
    if ($qr_code) {
      $page_content = verify_qrcode_fromdb($qr_code);
    } //$qr_code
    else {
      $verify_certificates_form = drupal_get_form("verify_certificates_form");
      $page_content = drupal_render($verify_certificates_form);
    }
    return $page_content;
  }

}
