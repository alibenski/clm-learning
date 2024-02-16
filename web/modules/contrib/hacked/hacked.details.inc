<?php

/**
 * @file
 * The Hacked! module, shows which project have been changed since download.
 *
 * We download the original project file, and hash all the files contained
 * within, then we hash our local copies and compare.
 * This module should never be used on a production server.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\hacked\HackedProject;

/**
 * Theme project status report.
 *
 * @ingroup themeable
 */
function template_preprocess_hacked_detailed_report(&$variables) {
  $project = $variables['project'];

  $variables['files'] = [
    '#type'       => 'table',
    '#attributes' => ['class' => ['update']],
    // Attach the library to a variable that gets printed always.
    '#attached'   => [
      'library' => [
        'update/drupal.update.admin',
      ],
    ],
  ];

  foreach ($project['files'] as $file => $status) {
    if (!isset($status)) {
      continue;
    }

    $url = NULL;
    if (\Drupal::currentUser()->hasPermission('view diffs of changed files') && \Drupal::moduleHandler()->moduleExists('diff') && ($status != HackedProject::HACKED_STATUS_UNHACKED) && !empty($project['diffable'][$file])) {
      $url = Url::fromRoute('hacked.project_diff', [
        'project' => $project['project_name'],
        'file' => $file,
      ]);
    }

    $file_status = [
      '#theme' => 'hacked_file_status',
      '#file'  => [
        'name'   => $file,
        'status' => $status,
        'url' => $url,
      ],
    ];

    // Add the project status row and details.
    $variables['files'][$file]['status'] = $file_status;

    switch ($status) {
      case HackedProject::HACKED_STATUS_UNHACKED:
        $variables['files'][$file]['#attributes'] = ['class' => ['color-success']];
        break;

      case HackedProject::HACKED_STATUS_DELETED:
        $variables['files'][$file]['#attributes'] = ['class' => ['color-error']];
        break;

      case HackedProject::HACKED_STATUS_HACKED:
      case HackedProject::HACKED_STATUS_PERMISSION_DENIED:
      case HackedProject::HACKED_STATUS_UNCHECKED:
      default:
        $variables['files'][$file]['#attributes'] = ['class' => ['color-warning']];
        break;
    }
  }
}

/**
 * Theme hacked file status.
 */
function template_preprocess_hacked_file_status(&$variables) {
  switch ($variables['file']['status']) {
    case HackedProject::HACKED_STATUS_UNHACKED:
      $uri = 'core/misc/icons/73b355/check.svg';
      $text = t('Unchanged');
      break;

    case HackedProject::HACKED_STATUS_DELETED:
      $uri = 'core/misc/icons/e32700/error.svg';
      $text = t('Deleted');
      break;

    case HackedProject::HACKED_STATUS_HACKED:
      $uri = 'core/misc/icons/e29700/warning.svg';
      $text = t('Changed!');
      break;

    case HackedProject::HACKED_STATUS_PERMISSION_DENIED:
      $uri = 'core/misc/icons/e29700/warning.svg';
      $text = t('Permission Denied');
      break;

    case HackedProject::HACKED_STATUS_UNCHECKED:
    default:
      $uri = 'core/misc/icons/e29700/warning.svg';
      $text = t('Unchecked');
      break;
  }

  $variables['status']['attributes'] = new Attribute();
  $variables['status']['label'] = $text;
  $variables['status']['icon'] = [
    '#theme'  => 'image',
    '#width'  => 18,
    '#height' => 18,
    '#uri'    => $uri,
    '#alt'    => $text,
    '#title'  => $text,
  ];
}
