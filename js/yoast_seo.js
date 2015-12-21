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
    attach: function (context, settings) {
      var $context = $(context),
        formItems = {};

      var fields = {
        keyword: 'edit-field-yoast-seo-0-yoast-seo-focus-keyword', // or 'edit-field-meta-tags-0-basic-keywords',
        meta: 'edit-field-meta-tags-0-basic-description',
        text: 'edit-body-0-value',
        pageTitle: 'edit-field-meta-tags-0-basic-title',
        title: 'edit-field-meta-tags-0-basic-title'
      };

      YoastSeoData = {
        /**
         * This should return an object with the given properties
         *
         * @callback YoastSEO.App~getData
         * @returns {Object} data
         * @returns {String} data.keyword The keyword that should be used
         * @returns {String} data.meta
         * @returns {String} data.text The text to analyze
         * @returns {String} data.pageTitle The text in the HTML title tag
         * @returns {String} data.title The title to analyze
         * @returns {String} data.url The URL for the given page
         * @returns {String} data.excerpt Excerpt for the pages
         */
        getData: function () {
          var data = {
            keyword: '',
            meta: '',
            text: '',
            pageTitle: '',
            title: '',
            url: '',
            excerpt: ''
          };

          for (var fieldName in data) {
            var formItemView = Drupal.YoastSeoForm._formItemViews[fields[fieldName]];
            if (typeof formItemView !== 'undefined') {
              data[fieldName] = formItemView.value();
            }
          }

          return data;
        }
      };

      // Construct the form
      $('.js-form-item', $('#node-page-form')).each(function () {
        Drupal.YoastSeoForm.getFormItemView(this);
      });

      $(window).on('yoast_seo-form_item-changed', function() {
        console.log(YoastSeoData.getData());
      });

      console.log(YoastSeoData.getData());
    }
  };

})(jQuery, Drupal);
