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
      }

       init();

        // Construct the form
        $('.js-form-item', $('#node-page-edit-form')).each(function () {
          //YoastSeoForm.getFormItemView(this);
        });

      }
    };

})(jQuery, Drupal);
