<?php

namespace Drupal\Tests\file_entity\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Site\Settings;
use Drupal\file\FileInterface;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\Core\Archiver\Tar;
use Drupal\file_entity\Entity\FileType;

/**
 * Tests creating and saving a file.
 *
 * @group file_entity
 */
class FileEntityCreationTest extends FileEntityTestBase {

  protected static $modules = ['views'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('create files',
      'edit own document files',
      'administer files',
      'administer site configuration',
      'view private files',
    ));
    $this->drupalLogin($web_user);
  }

  /**
   * Create a "document" file and verify its consistency in the database.
   *
   * Unset the private folder so it skips the scheme selecting page.
   */
  public function testSingleFileEntityCreation() {
    // Configure private file system path.
    // Unset private file system path so it skips the scheme selecting, because
    // there is only one file system path available (public://) by default.
    new Settings(['file_private_path' => NULL] + Settings::getAll());
    $this->rebuildContainer();

    $test_file = $this->getTestFile('text');
    // Create a file.
    $edit = array();
    $edit['files[upload]'] = \Drupal::service('file_system')->realpath($test_file->uri);
    $this->drupalGet('file/add');
    $this->submitForm($edit, 'Next');

    // Check that the document file has been uploaded.
    $this->assertSession()->responseContains(t('@type %name was uploaded.', array('@type' => 'Document', '%name' => 'text-0_0.txt')));

    // Check that the file exists in the database.
    $file = $this->getFileByFilename('text-0_0.txt');
    $this->assertInstanceOf(FileInterface::class, $file, t('File found in database.'));
  }

  /**
   * Upload a file with both private and public folder set.
   *
   * Should have one extra step selecting a scheme.
   * Selects private scheme and checks if the file is succesfully uploaded to
   * the private folder.
   */
  public function testFileEntityCreationMultipleSteps() {
    $test_file = $this->getTestFile('text');
    // Create a file.
    $edit = array();
    $edit['files[upload]'] = \Drupal::service('file_system')->realpath($test_file->uri);
    $this->drupalGet('file/add');
    $this->assertEmpty($this->xpath('//input[@id="edit-upload-remove-button"]'), 'Remove');
    $this->submitForm($edit, 'Next');

    // Check if your on form step 2, scheme selecting.
    // At this point it should not skip this form.
    $this->assertNotEmpty($this->xpath('//input[@name="scheme"]'), "Loaded select destination scheme page.");

    // Test if the public radio button is selected by default.
    $this->assertSession()->checkboxChecked('edit-scheme-public');

    // Submit form and set scheme to private.
    $edit = array();
    $edit['scheme'] = 'private';
    $this->submitForm($edit, 'Next');

    // Check that the document file has been uploaded.
    $this->assertSession()->responseContains(t('@type %name was uploaded.', array('@type' => 'Document', '%name' => 'text-0_0.txt')));

    // Check that the file exists in the database.
    $file = $this->getFileByFilename('text-0_0.txt');
    $this->assertInstanceOf(FileInterface::class, $file, t('File found in database.'));

    // Check if the file is stored in the private folder.
    $this->assertTrue(substr($file->getFileUri(), 0, 10) === 'private://', 'File uploaded in private folder.');
  }

  /**
   * Test the Title Text and Alt Text fields of to the predefined Image type.
   */
  public function testImageAltTitleFields() {
    // Disable private path to avoid irrelevant second form step.
    new Settings(['file_private_path' => NULL] + Settings::getAll());
    $this->rebuildContainer();

    // Create an image.
    $test_file = $this->getTestFile('image');
    $edit = array('files[upload]' => \Drupal::service('file_system')->realpath($test_file->uri));
    $this->drupalGet('file/add');
    $this->submitForm($edit, 'Next');

    $data = array(
      'field_image_title_text' => 'My image',
      'field_image_alt_text' => 'A test image',
    );

    // Set fields.
    $edit = array();
    foreach ($data as $field => $value) {
      $edit[$field . '[0][value]'] = $value;
    }
    $this->submitForm($edit, 'Save');
    $file = $this->getFileByFilename('image-test_0.png');
    $this->drupalGet('file/' . $file->id());
    $this->assertSession()->responseContains('alt="A test image"');
    $this->assertSession()->responseContains('title="My image"');

    // Make sure the field values are saved.
    $created_file = FileEntity::load(1)->getTranslation(LanguageInterface::LANGCODE_DEFAULT);
    foreach ($data as $field => $value) {
      $this->assertEquals($value, $created_file->get($field)->value);
    }
  }

  /**
   * Test archive upload.
   */
  public function testArchiveUpload() {
    // Create a archive type.
    $archive_type = FileType::create([
      'id' => 'archive',
      'label' => 'Archive',
      'status' => TRUE,
      'mimetypes' => [
        'application/gzip'
      ],
    ]);

    $archive_type->save();

    $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    // Create files for the archive.
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->saveData($this->randomString(), 'temporary://test_text.txt');
    $file_system->saveData($this->randomString(), 'temporary://test_png.png');
    $file_system->saveData($this->randomString(), 'temporary://test_jpg.jpg');

    $text_file_path = $file_system->getTempDirectory() . '/test_text.txt';
    $png_file_path = $file_system->getTempDirectory() . '/test_png.png';
    $jpg_file_path = $file_system->getTempDirectory() . '/test_jpg.jpg';

    $archive_path = $file_system->getTempDirectory() . '/archive.tar.gz';
    $archiver = new Tar($archive_path);
    $archiver->add($text_file_path);
    $archiver->add($png_file_path);
    $archiver->add($jpg_file_path);

    $edit = [
      'files[upload]' => $archive_path,
      'pattern' => '.*jpg|.*gif',
      'remove_archive' => TRUE,
    ];
    $this->drupalGet('admin/content/files/archive');
    $this->submitForm($edit, 'Submit');

    $this->assertSession()->pageTextContains('Extracted archive.tar.gz and added 1 new files.');

    $this->assertTrue($file = !empty($file_storage->loadByProperties(['filename' => 'test_jpg.jpg'])), "File that matches the pattern can be found in the database.");
    $this->assertTrue($file ? $this->getFileByFilename('test_jpg.jpg')->isPermanent() : FALSE, "File that matches the pattern is permanent.");
    $this->assertFalse(!empty($file_storage->loadByProperties(['filename' => 'test_png.png'])), "File that doesn't match the pattern is not in the database.");
    $this->assertFalse(!empty($file_storage->loadByProperties(['filename' => 'test_text.txt'])), "File that doesn't match the pattern is not in the database.");
    $this->assertFalse(!empty($file_storage->loadByProperties(['filename' => 'archive.tar.gz'])), "Archive is removed since we checked the remove_archive checkbox.");

    $all_files = $file_system->scanDirectory('public://', '/.*/');
    $this->assertTrue(array_key_exists('public://archive.tar/' . $jpg_file_path, $all_files), "File that matches the pattern is in the public directory.");
    $this->assertFalse(array_key_exists('public://archive.tar/' . $png_file_path, $all_files), "File that doesn't match the pattern is removed from the public directory.");
    $this->assertFalse(array_key_exists('public://archive.tar/' . $text_file_path, $all_files), "File that doesn't match the pattern is removed from the public directory.");
    $this->assertFalse(array_key_exists('public://archive.tar.gz', $all_files), "Archive is removed from the public directory since we checked the remove_archive checkbox.");

    $archive_path = $file_system->getTempDirectory() . '/archive2.tar.gz';
    $archiver = new Tar($archive_path);
    $archiver->add($text_file_path);

    $edit = [
      'files[upload]' => $archive_path,
      'remove_archive' => FALSE,
    ];
    $this->drupalGet('admin/content/files/archive');
    $this->submitForm($edit, 'Submit');

    $this->assertTrue($file = !empty($file_storage->loadByProperties(['filename' => 'archive2.tar.gz'])), "Archive is in the database since value for remove_checkbox is FALSE.");
    $this->assertTrue($file ? $this->getFileByFilename('archive2.tar.gz')->isPermanent() : FALSE, "Archive is permanent since value for remove_checkbox is FALSE.");

    $all_files = $file_system->scanDirectory('public://', '/.*/');
    $this->assertTrue(array_key_exists('public://archive2.tar.gz', $all_files), "Archive is in the public directory since value for remove_checkbox is FALSE.");
  }

}
