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
          url: '',//YsSettings.default_text.url,
          title: '',//YsSettings.default_text.title,
          keyword: YsSettings.default_text.keyword,
          meta: '',//YsSettings.default_text.meta,
          text: YsSettings.default_text.body
        },
        fields: {
          keyword: YsSettings.fields.focus_keyword,
          title: YsSettings.fields.meta_title,
          nodeTitle: YsSettings.fields.title,
          meta: YsSettings.fields.meta_description,
          text: YsSettings.fields.body,
          url: YsSettings.fields.path
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

      // Construct the form.
      $('.js-form-item', $('#' + YsSettings.form_id)).each(function () {
        var formItem = Drupal.YoastSeoForm.getFormItemView(this, {
          callbacks: {
            changed: function(evt, val) {
              formItem.$el.trigger('yoast_seo-form_item-changed');
            }
          }
        });
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

      // Load autocomplete on focus keyword.
      yoastSeoData.loadFocusKeywordAutocomplete(YsSettings.fields.focus_keyword, YsSettings.language);

      // Instantiate the FormItem View component plugged on the snippet preview title field.
      var snippetTitle = new Drupal.YoastSeoForm.views.SnippetElement({
        el: $('#snippet_title'),
        callbacks: {
          // When the snippet preview title get the focus.
          // Replace the snippet preview title by a component which allows the edition of the title raw value (based on
          // the meta tag title field, including tokens).
          focused: function() {
            // Retrieve the Form Item view behind the meta tag title field.
            var formItem = Drupal.YoastSeoForm._formItemViews[settings.yoast_seo.fields['meta_title']],
              rawValue = formItem.value();

            // If the raw value is empty, check if a default value has been provided.
            if (rawValue == '' && settings.yoast_seo.default_text['meta_title'] != '') {
              rawValue = settings.yoast_seo.default_text['meta_title'];
            }

            // Display the raw value component instead of the computed value.
            snippetTitle.$el.hide();
            snippetTitleRaw.$el.show();
            snippetTitleRaw.value(rawValue);
            snippetTitleRaw.$el.focus();
          }
        }
      });

      // Add a field to manage the snippet preview title raw value.
      $('<span contenteditable="true" class="title" id="snippet_title_raw" style="display:none"></span>').appendTo('#title_container');

      // Instantiate the FormItem View component plugged on the snippet preview title raw field.
      var snippetTitleRaw = new Drupal.YoastSeoForm.views.SnippetElement({
        el: $('#snippet_title_raw'),
        callbacks: {
          // When snippet preview title raw value component change, update the meta tag title value.
          // By updating the meta tag title value, the analyser should perform a new analysis.
          changed: function() {
            var formItem = Drupal.YoastSeoForm._formItemViews[settings.yoast_seo.fields['meta_title']];
            formItem.value(snippetTitleRaw.value());
            formItem._change();
          },
          // When the component lose the focus, hide it and display the snippet preview title component instead.
          blured: function() {
            snippetTitle.$el.show();
            snippetTitleRaw.$el.hide();
          }
        }
      });

      // Instantiate the FormItem View component plugged on the snippet preview summary field.
      var snippetSummary = new Drupal.YoastSeoForm.views.SnippetElement({
        el: $('#snippet_meta'),
        callbacks: {
          // When the snippet preview summary get the focus.
          // Replace the snippet preview summary by a component which allows the edition of the summary raw value (based on
          // the meta tag description field, including tokens).
          focused: function() {
            // Retrieve the Form Item view behind the meta tag summary field.
            var formItem = Drupal.YoastSeoForm._formItemViews[settings.yoast_seo.fields['meta_description']],
              rawValue = formItem.value();

            // If the raw value is empty, check if a default value has been provided.
            if (rawValue == '' && settings.yoast_seo.default_text['meta_description'] != '') {
              rawValue = settings.yoast_seo.default_text['meta_description'];
            }

            // Display the raw value component instead of the computed value.
            snippetSummary.$el.hide();
            snippetSummaryRaw.$el.show();
            snippetSummaryRaw.value(rawValue);
            snippetSummaryRaw.$el.focus();
          }
        }
      });

      // Add a field to manage the snippet preview summary raw value.
      $('<span contenteditable="true" class="desc" id="snippet_meta_raw" style="display:none"></span>').appendTo('#meta_container');

      // Instantiate the FormItem View component plugged on the snippet preview summary raw field.
      var snippetSummaryRaw = new Drupal.YoastSeoForm.views.SnippetElement({
        el: $('#snippet_meta_raw'),
        callbacks: {
          // When snippet preview summary raw value component change, update the meta tag summary value.
          // By updating the meta tag summary value, the analyser should perform a new analysis.
          changed: function() {
            var formItem = Drupal.YoastSeoForm._formItemViews[settings.yoast_seo.fields['meta_description']];
            formItem.value(snippetSummaryRaw.value());
            formItem._change();
          },
          // When the component lose the focus, hide it and display the snippet preview summary component instead.
          blured: function() {
            snippetSummary.$el.show();
            snippetSummaryRaw.$el.hide();
          }
        }
      });

      // Instantiate the FormItem View component plugged on the snippet preview url field.
      var snippetUrl = new Drupal.YoastSeoForm.views.SnippetElement({
        el: $('#snippet_cite'),
        callbacks: {
          // When the snippet preview cite get the focus.
          // Replace the snippet preview cite by a component which allows the edition of the cite raw value (based on
          // the advanced alias field, including tokens).
          focused: function() {
            // Retrieve the Form Item view behind the advanced alias field.
            var formItem = Drupal.YoastSeoForm._formItemViews[settings.yoast_seo.fields['path']];
            // Display the raw value component instead of the computed value.
            snippetUrl.$el.hide();
            snippetUrlRaw.$el.show();
            snippetUrlRaw.value(formItem.value());
            snippetUrlRaw.$el.focus();
          }
        }
      });

      // Add a field to manage the snippet preview summary raw value.
      $('<span contenteditable="true" class="url" id="snippet_cite_raw" style="display:none"></span>').appendTo('#url_container');

      // Instantiate the FormItem View component plugged on the snippet preview cite raw field.
      var snippetUrlRaw = new Drupal.YoastSeoForm.views.SnippetElement({
        el: $('#snippet_cite_raw'),
        callbacks: {
          // When snippet preview url raw value component change, update the advanced alias field value.
          // By updating the advanced alias field value, the analyser should perform a new analysis.
          changed: function() {
            var formItem = Drupal.YoastSeoForm._formItemViews[settings.yoast_seo.fields['path']];
            formItem.value(snippetUrlRaw.value());
            formItem._change();
          },
          // When the component lose the focus, hide it and display the snippet preview url component instead.
          blured: function() {
            snippetUrl.$el.show();
            snippetUrlRaw.$el.hide();
          }
        }
      });

    }
  };

})(jQuery, Drupal);
