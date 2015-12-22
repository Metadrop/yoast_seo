/**
 * @file
 * Drupal Yoast SEO.
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

          // For all data required by the Yoast SEO snippet.
          // Retrieve the form item view relative to the data.
          for (var fieldName in data) {
            var formItemView = Drupal.YoastSeoForm._formItemViews[settings.yoast_seo.fields[YoastSeoData.fieldsMapping[fieldName]]];
            if (typeof formItemView !== 'undefined') {
              data[fieldName] = YoastSeoData.tokenReplace(formItemView.value());
            }
          }

          return data;
        },

        /**
         * Replace the tokens found in a given string by their relative values.
         *
         * @param {String} value
         * @return {String}
         */
        tokenReplace: function(value) {
          var tokenRegex = /(\[[^\]]*:[^\]]*\])/g,
            match = value.match(tokenRegex);

          // If the value contains tokens.
          if (match != null) {
            // Replace all the tokens by their relative value.
            for (var i in match) {
              // Check if the token is relative to a field available by javascript.
              var tokenRelativeField = _.findKey(settings.yoast_seo.tokens, function(val) {
                return val === match[i];
              });

              // If the token can be solved locally.
              if (typeof tokenRelativeField != 'undefined') {
                // Replace the token with the relative field token value.
                var formItemView = Drupal.YoastSeoForm._formItemViews[settings.yoast_seo.fields[YoastSeoData.fieldsMapping[tokenRelativeField]]];
                if (typeof formItemView !== 'undefined') {
                  var tokenValue = YoastSeoData.tokenReplace(formItemView.value());
                  value = value.replace(match[i], tokenValue);
                }
              }
              else {
                console.log('remote token found ' + match[i] + ' / @todo implement the server call.');
              }
            }
          }

          return value;
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
              overall: YsSettings.targets.overall_score_target_id,
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
