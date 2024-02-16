<?php

namespace Drupal\hacked\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\diff\DiffEntityComparison;
use Drupal\hacked\HackedProject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller routines for hacked routes.
 */
class HackedDiffController extends ControllerBase {

  /**
   * Wrapper object for writing/reading configuration from diff.plugins.yml.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The diff entity comparison service.
   *
   * @var \Drupal\diff\DiffEntityComparison
   */
  protected $entityComparison;

  /**
   * Value for current url request.
   *
   * @var string
   *   RequestStack value
   */
  protected $requestStack;

  /**
   * Constructs a HackedDiffController object.
   *
   * @param \Drupal\diff\DiffEntityComparison $entity_comparison
   *   DiffEntityComparison service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   */
  public function __construct(DiffEntityComparison $entity_comparison, RequestStack $request_stack) {
    $this->config = $this->config('diff.settings');
    $this->entityComparison = $entity_comparison;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('diff.entity_comparison'),
      $container->get('request_stack'),
    );
  }

  /**
   * Shows a diff report for a specific file in a project.
   *
   * @param \Drupal\hacked\HackedProject $project
   *   The HackedProject instance.
   *
   * @return array
   *   Markup.
   */
  public function hackedProjectDiff(HackedProject $project) {
    if (!$this->moduleHandler()->moduleExists('diff')) {
      return [
        '#markup' => $this->t('The diff module is required to use this feature.'),
      ];
    }

    $file = $this->requestStack->getCurrentRequest()->get('file');
    $project->identifyProject();

    if ($project->fileIsDiffable($file)) {
      $original_file = $project->fileGetLocation($file, 'remote');
      $installed_file = $project->fileGetLocation($file, 'local');

      /** @var \Drupal\hacked\HackedFileHasher $hasher */
      $hasher = hacked_get_file_hasher();

      $build = [
        '#theme' => 'table',
        '#header' => [$this->t('Original'), '', $this->t('Current'), ''],
        '#rows' => $this->entityComparison->getRows($hasher->fetchLines($original_file), $hasher->fetchLines($installed_file), TRUE),
      ];

      // Add the CSS for the diff.
      $build['#attached']['library'][] = 'diff/diff.general';
      $theme = $this->config->get('general_settings.theme');
      if ($theme) {
        if ($theme === 'default') {
          $build['#attached']['library'][] = 'diff/diff.default';
        }
        elseif ($theme === 'github') {
          $build['#attached']['library'][] = 'diff/diff.github';
        }
      }
      // If the setting could not be loaded or is missing use the default theme.
      elseif ($theme == NULL) {
        $build['#attached']['library'][] = 'diff/diff.github';
      }
      return $build;
    }
    return [
      '#markup' => $this->t('Cannot hash binary file or file not found: %file', ['%file' => $file]),
    ];
  }

  /**
   * Menu title callback for the hacked site report page.
   */
  public function hackedProjectDiffTitle(HackedProject $project) {
    $file = $this->requestStack->getCurrentRequest()->get('file');
    return $this->t('Hacked status for @file in project @project', [
      '@project' => $project->title(),
      '@file'    => $file,
    ]);
  }

}
