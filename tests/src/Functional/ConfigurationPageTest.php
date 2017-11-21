<?php

namespace Drupal\Tests\yoast_seo\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the Real-Time SEO configuration page.
 *
 * @group yoast_seo_ui
 */
class ConfigurationPageTest extends BrowserTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    // CKEditor module is required to avoid loading errors during node creation.
    'ckeditor',
    'yoast_seo',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create an article content type that we will use for testing.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests that a user requires the 'administer yoast seo' permission.
   *
   * The permission is required to access the configuration page.
   */
  public function testAdministerPermissionRequirement() {
    $unauthorized = $this->drupalCreateUser();
    $authorized = $this->drupalCreateUser(['administer yoast seo']);

    // Test that a user without the permission is denied.
    $this->drupalLogin($unauthorized);

    $this->drupalGet('/admin/config/yoast_seo');
    $this->assertSession()->statusCodeEquals(403);

    // Test that a user with the permission can see the page.
    $this->drupalLogin($authorized);

    $this->drupalGet('/admin/config/yoast_seo');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that analysis is not enabled for any entities on first install.
   */
  public function testInstallAnalysisState() {
    $account = $this->drupalCreateUser(['administer yoast seo']);

    $this->drupalLogin($account);

    $this->drupalGet('/admin/config/yoast_seo');

    // Check that the checkbox indicates disabled.
    $checked = $this->assertSession()->fieldExists('Article')->getAttribute('checked');
    $this->assertFalse($checked, "Expected Real-Time SEO module to be disabled for 'Article'");
  }

  /**
   * Tests that analysis can be enabled for the node article bundle.
   */
  public function testEnableForNodeBundle() {
    $account = $this->drupalCreateUser([
      'administer yoast seo',
      'create article content',
      // TODO: The administer url aliases shouldn't be necessary anymore.
      'administer url aliases',
    ]);

    $this->drupalLogin($account);

    $this->drupalGet('/admin/config/yoast_seo');

    // Select the article bundle to enable.
    $this->assertSession()->fieldExists('Article')->check();
    $this->getSession()->getPage()->pressButton('Save');

    // Check that the enabled status is reflected in the interface.
    $checked = $this->assertSession()->fieldExists('Article')->getAttribute('checked');
    $this->assertTrue($checked, "Expected Real-Time SEO module to be enabled for 'Article'");

    // Check that the SEO analyzer shows up on the article add page.
    $this->drupalGet('node/add/article');
    $this->assertSession()->pageTextContains('Real-time SEO for drupal');
  }

}
