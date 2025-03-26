<?php
 
 namespace Drupal\cfd_research_migration\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\user\Entity\User;


class CfdResearchMigrationGlobalFunction{


  function _df_list_of_states() {
      $states = [
          0 => '-Select-',
      ];
  
      // Get database connection
      $connection = Database::getConnection();
      $query = $connection->select('list_states_of_india', 'lsoi')
          ->fields('lsoi', ['state']);
      
      // Fetch the results as an associative array [state => state]
      $results = $query->execute()->fetchAllKeyed();
  
      return $states + $results;
  }
  
}
 