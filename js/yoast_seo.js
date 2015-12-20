/**
 * @file
 * Drupal Yoast SEO form utility.
 *
 * This library will help developers to interacts with drupal form
 * on client side
 *
 * @ignore
 */

(function ($, Drupal) {

  Drupal.behaviors.yoastSeo = {
    attach: function (context) {
      var $context = $(context),
        formItems = {};

      // Construct the form
      $('.js-form-item', $('#node-page-edit-form')).each(function () {
        YoastSeoForm.getFormItemView(this);
      });

    }
  };

})(jQuery, Drupal);
