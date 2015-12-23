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
      if (settings.yoast_seo == undefined) {
        throw 'YoastSEO settings are not defined';
      }

      // Settings for Yoast SEO.
      var YsSettings = settings.yoast_seo;

      // Load Yoast SEO with its settings.
      YoastSEO.analyzerArgs = {
        //source: YoastSeoData,
        analyzer: YsSettings.analyzer,
        snippetPreview: YsSettings.snippet_preview,
        elementTarget: [YsSettings.targets.wrapper_target_id],
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
          title: YsSettings.placeholder_text.title,
          description: YsSettings.placeholder_text.description,
          url: YsSettings.placeholder_text.url
        },
        SEOTitleOverwritten: YsSettings.seo_title_overwritten,
        scoreElement: YsSettings.fields.seo_status,
        baseRoot: YsSettings.base_root
      };


      // Construct the form
      $('.js-form-item', $('#' + YsSettings.form_id)).each(function () {
        Drupal.YoastSeoForm.getFormItemView(this);
      });

      // Init Yoast SEO.
      var yoastSeoData = new YoastSeoData({
        settings: settings.yoast_seo,
        analyzerArgs: YoastSEO.analyzerArgs
      });

      // Instantiate YoastSeoStatusWidget, to handle scores display.
      var yoastSeoStatusWidget = new YoastSeoStatusWidget({
        settings: settings.yoast_seo
      });

      // Declaring the callback functions, for now we bind DrupalSource.
      YoastSEO.analyzerArgs.callbacks = {
        getData: yoastSeoData.getData.bind(yoastSeoData),
        getAnalyzerInput: yoastSeoData.getAnalyzerInput.bind(yoastSeoData),
        bindElementEvents: yoastSeoData.bindElementEvents.bind(yoastSeoData),
        updateSnippetValues: yoastSeoData.updateSnippetValues.bind(yoastSeoData),
        saveScores: yoastSeoStatusWidget.saveScores.bind(yoastSeoStatusWidget)
      };

      // Make it global.
      window.YoastSEO.app = new YoastSEO.App(YoastSEO.analyzerArgs);
    }
  };

})(jQuery, Drupal);
