<?php

namespace Drupal\hacked;

use Drupal\Core\File\FileSystemInterface;

/**
 * Downloads a development snapshot of a project using Git.
 */
class HackedProjectWebDevDownloader extends HackedProjectWebDownloader {

  /**
   * Get download information for the dev release.
   *
   * @return array
   *   'module'    => The name of the project.
   *   'giturl'    => The upstream URL of the project.
   *   'branch'    => The development branch the snapshot was taken from.
   *   'timestamp' => When the project was originally downloaded.
   */
  public function downloadLink() {
    $info = [];
    $project = &$this->project->projectInfo;

    // Get the branch we're on.
    $release = strtok($project['existing_version'], '-') . '-' . $project['existing_major'] . '.x-dev';
    $branch = $project['releases'][$release]['tag'];

    // Assign some information depending on the project type.
    if ($project['project_type'] == 'core') {
      // Special handling for core.
      $info['module'] = 'drupal';
    }
    else {
      // Contrib.
      $info['module'] = $project['name'];
    }

    // Assign all of the other information.
    $info['giturl'] = 'https://git.drupal.org/project/' . $project['name'] . '.git';
    $info['branch'] = $branch;
    $info['timestamp'] = $project['datestamp'];

    // Return it.
    return $info;
  }

  /**
   * Download the version of the project to compare.
   *
   * @return bool
   *   TRUE if the download was successful; FALSE otherwise.
   */
  public function download() {
    // Get detailed information about the download.
    $destination = $this->getDestination();
    $info = $this->downloadLink();
    $giturl = $info['giturl'] ?? '';
    $branch = $info['branch'] ?? '';
    $timestamp = $info['timestamp'] ?? '';

    // Return the path if the project version has already been downloaded to
    // the cache.
    if (file_exists($destination)) {
      return $destination;
    }

    // Clone the project repository and check out the appropriate branch.
    try {
      $this->gitClone($giturl, $destination, $branch);
    }
    catch (\Exception $e) {
      \Drupal::logger('hacked')->error($e->getMessage());
      return FALSE;
    }

    // Attempt to check out the last commit before timestamp on the local
    // version.
    try {
      $this->gitCheckout($destination, $branch, $timestamp);
    }
    catch (\Exception $e) {
      \Drupal::logger('hacked')->error($e->getMessage());
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Clones a git repository in a temporary directory.
   *
   * @param string $giturl
   *   URL of the project.
   * @param string $path
   *   The location in which to place the checkout.
   * @param string $branch
   *   The branch to check out.
   */
  public function gitClone(string $giturl, string $path, string $branch) {

    // Get the Git command and location information.
    $git_cmd = $this->gitGetCommand();
    $dirname = dirname($path);
    $basename = basename($path);

    // Prepare the download location.
    \Drupal::service('file_system')->prepareDirectory($dirname, FileSystemInterface::CREATE_DIRECTORY);
    \Drupal::service('file_system')->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);

    // Perform the clone operation, and leave us in the appropriate branch.
    exec("cd $dirname; $git_cmd clone --branch $branch $giturl $basename", $output_lines, $return_value);

    // Throw an exception if it didn't work.
    if ($return_value != 0) {
      throw new \Exception('Could not clone project %project into path %path at branch %branch.', [
        '%project' => $this->project->title(),
        '%path' => $path,
        '%branch' => $branch,
      ]);
    }
  }

  /**
   * Checks out the last Git commit prior to the project's timestamp.
   *
   * @param string $path
   *   The path of the Git repository where the checkout should happen.
   * @param string $branch
   *   The desired branch within the repository containing the commit.
   * @param string $timestamp
   *   The UNIX timestamp for the downloaded project.
   */
  public function gitCheckout(string $path, string $branch, string $timestamp) {

    // Fetch the Git command.
    $git_cmd = $this->gitGetCommand();

    // Point the working tree at the commit just before the timestamp.
    // This takes us to the point in time when the local project was downloaded.
    exec("cd $path; $git_cmd checkout $($git_cmd rev-list -n 1 --before='$timestamp' $branch)", $output_lines, $return_value);

    // Throw an exception if it didn't work.
    if ($return_value != 0) {
      throw new \Exception('Could not check out latest commit before %timestamp in Git repository %repository.', [
        '%timestamp' => $timestamp,
        '%repository' => $path,
      ]);
    }
  }

  /**
   * Helper function to return the command to run git on the command line.
   *
   * @return string
   *   The command with which to run Git.
   */
  public function gitGetCommand() {
    $command = \Drupal::config('hacked.settings')->get('git_cmd');
    return is_null($command) ? 'git' : $command;
  }

  /**
   * Returns the final destination of the unpacked project.
   *
   * @return string
   *   The unpacked project's path.
   */
  public function getFinalDestination() {
    // Simply return the original destination as Git's cloning already provides
    // an unpacked project.
    return $this->getDestination();
  }

}
