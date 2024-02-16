<?php

namespace Drupal\hacked;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Encapsulates a Hacked! project.
 *
 * This class should handle all the complexity for you, and so you should be
 * able to do:
 *
 * <code>
 * $project = HackedProject('context');
 * $project->computeDifferences();
 * </code>
 *
 * Which is quite nice I think.
 */
class HackedProject {

  use StringTranslationTrait;

  const HACKED_STATUS_UNCHECKED = 1;
  const HACKED_STATUS_PERMISSION_DENIED = 2;
  const HACKED_STATUS_HACKED = 3;
  const HACKED_STATUS_DELETED = 4;
  const HACKED_STATUS_UNHACKED = 5;
  const HACKED_DEFAULT_FILE_HASHER = 'hacked_ignore_line_endings';

  /**
   * Name.
   *
   * @var string
   */
  public $name = '';

  /**
   * Project info.
   *
   * @var array
   */
  public $projectInfo = [];

  /**
   * Remote file's downloader.
   *
   * @var \Drupal\hacked\HackedProjectWebDevDownloader|\Drupal\hacked\HackedProjectWebFilesDownloader
   */
  public $remoteFilesDownloader;

  /**
   * Remote files.
   *
   * @var \Drupal\hacked\HackedFileGroup
   */
  public $remoteFiles;

  /**
   * Local file.
   *
   * @var \Drupal\hacked\HackedFileGroup
   */
  public $localFiles;

  /**
   * Project type.
   *
   * @var string
   */
  public $projectType = '';

  /**
   * Existing version.
   *
   * @var string
   */
  public $existingVersion = '';

  /**
   * Result.
   *
   * @var array
   */
  public $result = [];

  /**
   * Project identified.
   *
   * @var bool
   */
  public $projectIdentified = FALSE;

  /**
   * Remote downloaded.
   *
   * @var bool
   */
  public $remoteDownloaded = FALSE;

  /**
   * Remote hashed.
   *
   * @var bool
   */
  public $remoteHashed = FALSE;

  /**
   * Local hashed.
   *
   * @var bool
   */
  public $localHashed = FALSE;

  /**
   * Constructor.
   */
  public function __construct($name) {
    // Identify the project.
    $this->name = $name;
    $this->identifyProject();

    // Choose an appropriate downloader.
    if ($this->isDevVersion()) {
      $this->remoteFilesDownloader = new HackedProjectWebDevDownloader($this);
    }
    else {
      $this->remoteFilesDownloader = new HackedProjectWebFilesDownloader($this);
    }
  }

  /**
   * Get the Human readable title of this project.
   */
  public function title() {
    $this->identifyProject();
    return $this->projectInfo['title'] ?? $this->name;
  }

  /**
   * Identify the project from the name we've been created with.
   *
   * We leverage the update (status) module to get the data we require about
   * projects. We just pull the information in, and make descisions about this
   * project being from CVS or not.
   */
  public function identifyProject() {
    // Only do this once, no matter how many times we're called.
    if (!empty($this->projectIdentified)) {
      return;
    }

    // Fetch the required data from the update (status) module.
    // @todo clean this up.
    $available = update_get_available(TRUE);
    $data = update_calculate_project_data($available);
    $releases = \Drupal::keyValueExpirable('update_available_releases')
      ->getAll();

    foreach ($data as $key => $project) {
      if ($key == $this->name) {
        $this->projectInfo = $project;
        if (!isset($this->projectInfo['releases']) || !is_array($this->projectInfo['releases'])) {
          $this->projectInfo['releases'] = [];
        }
        if (isset($releases[$key]['releases']) && is_array($releases[$key]['releases'])) {
          $this->projectInfo['releases'] += $releases[$key]['releases'];
        }

        // Add in the additional info that update module strips out.
        // This is a really naff way of doing this, but update (status) module
        // ripped out a lot of useful stuff in issue:
        // http://drupal.org/node/669554
        $this->projectIdentified = TRUE;
        $this->existingVersion = $this->projectInfo['existing_version'];
        $this->projectType = $this->projectInfo['project_type'];
        break;
      }
    }

    // Logging.
    if (!$this->projectIdentified) {
      $message = $this->t('Could not identify project: @name', ['@name' => $this->name]);
      \Drupal::logger('hacked')->warning($message->render());
    }
  }

  /**
   * Determines if the project is a dev-version or has an explicit release.
   *
   * @return bool
   *   TRUE if the project is a dev release; FALSE otherwise.
   */
  public function isDevVersion() {
    // Grab the version string.
    $version = $this->existingVersion;

    // Assume we have a dev version if the string ends with "-dev".
    return (strlen($version) < 4 || substr_compare($version, '-dev', -4, 4) !== 0) ? FALSE : TRUE;
  }

  /**
   * Downloads the remote project to be hashed later.
   */
  public function downloadRemoteProject() {
    // Only do this once, no matter how many times we're called.
    if (!empty($this->remoteDownloaded)) {
      return;
    }

    $this->identifyProject();
    $this->remoteDownloaded = (bool) $this->remoteFilesDownloader->download();

    // Logging.
    if (!$this->remoteDownloaded) {
      $message = $this->t('Could not download project: @title', ['@title' => $this->title()]);
      \Drupal::logger('hacked')->error($message->render());
    }
  }

  /**
   * Hashes the remote project downloaded earlier.
   */
  public function hashRemoteProject() {
    // Only do this once, no matter how many times we're called.
    if (!empty($this->remoteHashed)) {
      return;
    }

    // Ensure that the remote project has actually been downloaded.
    $this->downloadRemoteProject();

    // Set up the remote file group.
    $base_path = $this->remoteFilesDownloader->getFinalDestination();
    $this->remoteFiles = HackedFileGroup::fromDirectory($base_path);
    $this->remoteFiles->computeHashes();

    $this->remoteHashed = !empty($this->remoteFiles->files);

    // Logging.
    if (!$this->remoteHashed) {
      $message = $this->t('Could not hash remote project: @title', ['@title' => $this->title()]);
      \Drupal::logger('hacked')->error($message->render());
    }
  }

  /**
   * Locate the base directory of the local project.
   */
  public function locateLocalProject() {
    // We need a remote project to do this :( .
    $this->hashRemoteProject();

    // Do we have at least some modules to check for:
    if (!is_array($this->projectInfo['includes']) || !count($this->projectInfo['includes'])) {
      return FALSE;
    }

    // If this project is drupal it, we need to handle it specially.
    if ($this->projectType !== 'core') {
      $includes = array_keys($this->projectInfo['includes']);
      $include = array_shift($includes);
      $include_type = $this->projectInfo['project_type'];
    }
    else {
      // Just use the system module to find where we've installed drupal.
      $include = 'system';
      $include_type = 'module';
    }

    $path = \Drupal::service('extension.path.resolver')->getPath($include_type, $include);

    // Now we need to find the path of the info file in the downloaded package:
    $temp = '';
    foreach ($this->remoteFiles->files as $file) {
      if (preg_match('@(^|.*/)' . $include . '.info.yml$@', $file)) {
        $temp = $file;
        break;
      }
    }

    // How many '/' were in that path:
    $slash_count = substr_count($temp, '/');
    $back_track = str_repeat('/..', $slash_count);

    return realpath($path . $back_track);
  }

  /**
   * Hash the local version of the project.
   */
  public function hashLocalProject() {
    // Only do this once, no matter how many times we're called.
    if (!empty($this->localHashed)) {
      return;
    }

    $location = $this->locateLocalProject();

    $this->localFiles = HackedFileGroup::fromList($location, $this->remoteFiles->files);
    $this->localFiles->computeHashes();

    $this->localHashed = !empty($this->localFiles->files);

    // Logging.
    if (!$this->localHashed) {
      $message = $this->t('Could not hash local project: @title', ['@title' => $this->title()]);
      \Drupal::logger('hacked')->error($message->render());
    }
  }

  /**
   * Compute the differences between our version and the canonical version.
   */
  public function computeDifferences() {
    // Make sure we've hashed both remote and local files.
    $this->hashRemoteProject();
    $this->hashLocalProject();

    $results = [
      'same'          => [],
      'different'     => [],
      'missing'       => [],
      'access_denied' => [],
    ];

    // Now compare the two file groups.
    foreach ($this->remoteFiles->files as $file) {
      if ($this->remoteFiles->filesHashes[$file] == $this->localFiles->filesHashes[$file]) {
        $results['same'][] = $file;
      }
      elseif (!$this->localFiles->fileExists($file)) {
        $results['missing'][] = $file;
      }
      elseif (!$this->localFiles->isReadable($file)) {
        $results['access_denied'][] = $file;
      }
      else {
        $results['different'][] = $file;
      }
    }

    $this->result = $results;
  }

  /**
   * Return a nice report, a simple overview of the status of this project.
   */
  public function computeReport() {
    // Ensure we know the differences.
    $this->computeDifferences();

    // Do some counting.
    $report = [
      'project_name' => $this->name,
      'status'       => $this::HACKED_STATUS_UNCHECKED,
      'counts'       => [
        'same'          => count($this->result['same']),
        'different'     => count($this->result['different']),
        'missing'       => count($this->result['missing']),
        'access_denied' => count($this->result['access_denied']),
      ],
      'title'        => $this->title(),
    ];

    // Add more details into the report result (if we can).
    $details = [
      'link',
      'name',
      'existing_version',
      'install_type',
      'datestamp',
      'project_type',
      'includes',
    ];
    foreach ($details as $item) {
      if (isset($this->projectInfo[$item])) {
        $report[$item] = $this->projectInfo[$item];
      }
    }

    if ($report['counts']['access_denied'] > 0) {
      $report['status'] = $this::HACKED_STATUS_PERMISSION_DENIED;
    }
    elseif ($report['counts']['missing'] > 0) {
      $report['status'] = $this::HACKED_STATUS_HACKED;
    }
    elseif ($report['counts']['different'] > 0) {
      $report['status'] = $this::HACKED_STATUS_HACKED;
    }
    elseif ($report['counts']['same'] > 0) {
      $report['status'] = $this::HACKED_STATUS_UNHACKED;
    }

    return $report;
  }

  /**
   * Return a nice detailed report.
   */
  public function computeDetails() {
    // Ensure we know the differences.
    $report = $this->computeReport();

    $report['files'] = [];

    // Add extra details about every file.
    $states = [
      'access_denied' => $this::HACKED_STATUS_PERMISSION_DENIED,
      'missing'       => $this::HACKED_STATUS_DELETED,
      'different'     => $this::HACKED_STATUS_HACKED,
      'same'          => $this::HACKED_STATUS_UNHACKED,
    ];

    foreach ($states as $state => $status) {
      foreach ($this->result[$state] as $file) {
        $report['files'][$file] = $status;
        $report['diffable'][$file] = $this->fileIsDiffable($file);
      }
    }

    return $report;
  }

  /**
   * File is diffable.
   */
  public function fileIsDiffable($file) {
    $this->hashRemoteProject();
    $this->hashLocalProject();
    return $this->remoteFiles->isNotBinary($file) && $this->localFiles->isNotBinary($file);
  }

  /**
   * File get location.
   */
  public function fileGetLocation($file, $storage = 'local') {
    switch ($storage) {
      case 'remote':
        $this->downloadRemoteProject();
        return $this->remoteFiles->fileGetLocation($file);

      case 'local':
        $this->hashLocalProject();
        return $this->localFiles->fileGetLocation($file);
    }
    return FALSE;
  }

}
