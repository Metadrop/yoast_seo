<?php
/**
 * @file
 * Contains \Drupal\metatag\Tests\MetatagFieldTest.
 *
 * Scenarios :
 * * The Yoast SEO section is only available on content type which have a field_meta_tags field.
 */

namespace Drupal\yoast_seo\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Ensures that the Yoast Seo works correctly.
 *
 * @group YoastSeo
 */
class YoastSeoTest extends WebTestBase {

  /**
   * Profile to use.
   */
  protected $profile = 'testing';

  /**
   * Admin user
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field_ui',
    'metatag',
    'yoast_seo',
    'entity_test',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'access administration pages',
    'view test entity',
    'administer entity_test fields',
    'administer entity_test content',
    'administer meta tags',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
  }

  /**
   * Yoast Seo works with entity that have a Meta Tag field.
   * Add a Meta Tag field to the custom entity test.
   */
  protected function customizeEntityTestContentType() {
    // Add a new metatag field.
    $this->drupalGet('entity_test/structure/entity_test/fields/add-field');
    $edit = [
      'label' => 'Metatag',
      'field_name' => 'meta_tags',
      'new_storage_type' => 'metatag',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, [], t('Save field settings'));
  }

//  /**
//   * The Yoast SEO section is only available on content type which have
//   * a field_meta_tags field.
//   *
//   * Given    I am logged in as admin
//   * When     I am adding a content on a content type which doesn't have a Meta Tag field
//   * Then     Then I should not see the Yoast SEO section active
//   * When     I am adding a content on a content type which have a Meta Tag field
//   */
//  public function testRequireMetaTagField() {
//    // Given I am logged in as admin
//    $this->drupalLogin($this->adminUser);
//    // When I am adding an Entity Test content
//    $this->drupalGet('entity_test/add');
//    // Then I should not see the Yoast SEO section active
//    $this->assertNoText('Yoast SEO for Drupal');
//    // When I am adding a content on a content type which have a Meta Tag field
//    $this->customizeEntityTestContentType();
//  }

  /**
   *
   */
  public function testMetatagFieldsValues() {
    // Login as admin User
    $this->drupalLogin($this->adminUser);
    // Extend the custom entity test with Meta Tag
    $this->customizeEntityTestContentType();
//    $metatag_attachments = metatag_get_tags_from_route();

    $this->drupalGet('entity_test/add');
    $edit = [
      'name[0][value]' => 'Barfoo',
      'user_id[0][target_id]' => 'foo (' . $this->adminUser->id() . ')',
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $entities = entity_load_multiple_by_properties('entity_test', [
      'name' => 'Barfoo',
    ]);
    $entity = reset($entities);
//    $this->drupalGet('entity_test/' . $entity->id());
//    $elements = $this->cssSelect('meta[name=keywords]');
//    $this->assertTrue(count($elements) === 1, 'Found keywords metatag from defaults');
//    $this->assertEqual((string) $elements[0]['content'], 'Purple monkey dishwasher', 'Default keywords applied');
//    return $entity;
    $entity->id();

    $manager = \Drupal::service('metatag.manager');
    $test = $manager->attachmentsFromEntity($entity->id());

  }
}
