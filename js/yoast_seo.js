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
      var $context = $(context);

      /**
       * Yoast SEO data manipulation stone reference.
       * @type Object
       */
      var YoastSeoData = {
        /**
         * Map the drupal fields with the expected Yoast SEO data
         */
        fieldsMapping : {
          keyword: 'focus_keyword',
          meta: 'meta_description',
          text: 'body',
          pageTitle: 'meta_title',
          title: 'title',
          url: 'url'
        },

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

          //
          for (var fieldName in data) {
            var formItemView = Drupal.YoastSeoForm._formItemViews[settings.yoast_seo.fields[YoastSeoData.fieldsMapping[fieldName]]];
            if (typeof formItemView !== 'undefined') {
              data[fieldName] = formItemView.value();
            }
          }

          return data;
        }
      };


      var init = function () {
        if (drupalSettings.yoast_seo == undefined) {
          throw 'YoastSEO settings are not defined';
        }

        // Settings for Yoast SEO.
        var YsSettings = drupalSettings.yoast_seo;

        // Load Yoast SEO with its settings.
        $('body', context).once('yoast_seo', function () {
          YoastSEO.analyzerArgs = {
            source: YoastSEO_DrupalSource,
            analyzer: YsSettings.analyzer,
            snippetPreview: YsSettings.snippet_preview,
            elementTarget: [YsSettings.wrapper_target_id],
            typeDelay: 300,
            typeDelayStep: 100,
            maxTypeDelay: 1500,
            dynamicDelay: true,
            multiKeyword: false,
            targets: {
              output: YsSettings.targets.output_target_id,
              overall: 'yoast-seo-overall-score', // TODO : make it dynamic.
              snippet: YsSettings.targets.snippet_target_id
            },
            snippetFields: {
              title: "snippet_title",
              url: "snippet_cite",
              meta: "snippet_meta"
            },
            sampleText: {
              url: YsSettings.default_text.url,
              title: YsSettings.default_text.title,
              keyword: YsSettings.default_text.keyword,
              meta: YsSettings.default_text.meta,
              text: YsSettings.default_text.body
            },
            fields: {
              keyword: YsSettings.fields.focus_keyword,
              title: YsSettings.fields.meta_title,
              nodeTitle: YsSettings.fields.title,
              meta: YsSettings.fields.meta_description,
              text: YsSettings.fields.body,
              url: YsSettings.fields.url
            },
            placeholderText: {
              title: YsSettings.placeholderText.title,
              description: YsSettings.placeholderText.description,
              url: YsSettings.placeholderText.url
            },
            SEOTitleOverwritten: YsSettings.SEOTitleOverwritten,
            scoreElement: YsSettings.fields.seo_status,
            baseRoot: YsSettings.base_root
          };
        });

        // Construct the form
        $('.js-form-item', $('#node-page-form')).each(function () {
          Drupal.YoastSeoForm.getFormItemView(this);
        });

        $(window).on('yoast_seo-form_item-changed', function() {
          console.log('a change occurred on the data');
          console.log(YoastSeoData.getData());
        });

        console.log(YoastSeoData.getData());
      };

      init();
    }
  };

})(jQuery, Drupal);
