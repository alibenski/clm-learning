<?php

namespace Drupal\hacked\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\hacked\HackedProject;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for hacked routes.
 */
class HackedController extends ControllerBase {

  /**
   * The list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * The hacked cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $hackedCache;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The list of available modules.
   * @param \Drupal\Core\Cache\CacheBackendInterface $hacked_cache
   *   Cache backend instance to use.
   */
  public function __construct(ModuleExtensionList $extension_list_module, CacheBackendInterface $hacked_cache) {
    $this->extensionListModule = $extension_list_module;
    $this->hackedCache = $hacked_cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.module'),
      $container->get('cache.default'),
    );
  }

  /**
   * Hacked project.
   *
   * @param \Drupal\hacked\HackedProject $project
   *   Hacked project.
   *
   * @return array
   *   Project array.
   */
  public function hackedProject(HackedProject $project) {
    return [
      '#theme' => 'hacked_detailed_report',
      '#project' => $project->computeDetails(),
    ];
  }

  /**
   * Menu title callback for the hacked details page.
   */
  public function hackedProjectTitle(HackedProject $project) {
    return $this->t('Hacked status for @project', ['@project' => $project->title()]);
  }

  /**
   * Page callback to build up a full report.
   */
  public function hackedStatus() {
    // We're going to be borrowing heavily from the update module.
    $build = ['#theme' => 'update_report'];
    if ($available = update_get_available(TRUE)) {
      $build = ['#theme' => 'hacked_report'];
      $this->moduleHandler()->loadInclude('update', 'compare.inc');
      $data = update_calculate_project_data($available);
      $build['#data'] = $this->getProjectData($data);
      if (!is_array($build['#data'])) {
        return $build['#data'];
      }
    }
    return $build;
  }

  /**
   * Page callback to rebuild the hacked report.
   */
  public function hackedStatusManually() {
    // We're going to be borrowing heavily from the update module.
    if ($available = update_get_available(TRUE)) {
      $this->moduleHandler()->loadInclude('update', 'compare.inc');
      $data = update_calculate_project_data($available);
      return $this->getProjectData($data, TRUE, 'admin/reports/hacked');
    }
    return $this->redirect('hacked.report');
  }

  /**
   * Compute the report data for hacked.
   *
   * @param array $projects
   *   Projects.
   * @param bool $force
   *   Force.
   * @param \Drupal\Core\Url|string $redirect
   *   Redirect.
   *
   * @return mixed
   *   Get project data.
   */
  protected function getProjectData(array $projects, bool $force = FALSE, $redirect = NULL) {
    // Try to get the report form cache if we can.
    $cache = $this->hackedCache->get('hacked:full-report');
    if (!empty($cache->data) && !$force) {
      return $cache->data;
    }

    // Enter a batch to build the report.
    $operations = [];
    foreach ($projects as $project) {
      $operations[] = [
        'hacked_build_report_batch',
        [$project['name']],
      ];
    }

    $batch = [
      'operations' => $operations,
      'finished' => 'hacked_build_report_batch_finished',
      'file' => $this->extensionListModule->getPath('hacked') . '/hacked.report.inc',
      'title' => $this->t('Building report'),
    ];

    batch_set($batch);
    // End page execution and run the batch.
    return batch_process($redirect);
  }

}
