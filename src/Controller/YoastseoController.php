<?php
/**
 * @file
 * Contains \Drupal\yoast_seo\Controller\YoastseoController.
 */

namespace Drupal\yoast_seo\Controller;

use Drupal\Core\Controller\ControllerBase;

class YoastseoController extends ControllerBase {
	public function content() {
		return array(
			'#type' => 'markup',
			'#markup' => $this->t('Hello, World!'),
		);
	}

	public function settings() {
		return array(
			'#type' => 'markup',
			'#markup' => $this->t('Hello, World!'),
		);
	}
}