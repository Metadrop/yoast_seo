/**
 * @file
 * Drupal Yoast SEO script to apply to the node edit pages.
 *
 * @ignore
 */
(function ($, Drupal) {

  Drupal.behaviors.yoast_seo = {
    attach: function (context, settings) {

      if (settings.yoast_seo == undefined) {
        throw 'YoastSEO settings are not defined';
      }

      // Settings for Yoast SEO.
      var yoast_settings = settings.yoast_seo;

      // Initialize the backbone form fields layer upon the drupal rendered fields.
      // It will help to manipulate and interact with them from javascript.
      $('.js-form-item', $('#' + yoast_settings.form_id)).each(function () {
        var formItem = Drupal.BackboneForm.getFormItemView(this, {
          callbacks: {
            changed: function(evt, val) {
              formItem.$el.trigger('yoast_seo-form_item-changed');
            }
          }
        });
      });

      // Instantiate the Yoast SEO status widget, to handle scores display.
      var status_widget_class = YoastSeo.Status;
      // If the premium module is activated, use the premium status widget.
      if (yoast_settings.premium.activated) {
        status_widget_class = YoastSeo.PremiumStatus;
      }
      var status_widget = new status_widget_class({
        score_element_selector: yoast_settings.targets.overall_score_target_id,
        score_status: yoast_settings.score_status,
        seo_status: yoast_settings.fields.seo_status
      });

      // Instantiate the Yoast SEO focus keyword widget, to handle the autocomplete capability.
      var focus_keyword_widget = new YoastSeo.FocusKeyword({
        el: $('#' + yoast_settings.fields.focus_keyword),
        language: yoast_settings.language
      });

      // Initialize the Yoast analyser.
      // Build the analyser options
      var analyzer_options = {
        analyser: {
          snippetPreview: yoast_settings.snippet_preview,
          elementTarget: [yoast_settings.targets.wrapper_target_id],
          targets: {
            output: yoast_settings.targets.output_target_id,
            overall: yoast_settings.targets.overall_score_target_id,
            snippet: yoast_settings.targets.snippet_target_id
          },
          sampleText: {
            keyword: yoast_settings.default_text.keyword,
            text: yoast_settings.default_text.body
          },
          SEOTitleOverwritten: yoast_settings.seo_title_overwritten
        },
        callback: {
          saveScores: function(score) {
            status_widget.setScore(score);
          }
        },
        default_text: yoast_settings.default_text,
        base_root: yoast_settings.base_root,
        fields: yoast_settings.fields,
        placeholder_text: {
          title: yoast_settings.placeholder_text.snippetTitle,
          description: yoast_settings.placeholder_text.snippetMeta,
          url: yoast_settings.placeholder_text.snippetCite
        },
        scoreElement: yoast_settings.fields.seo_status,
        tokens: yoast_settings.tokens,
        cookie_data_key: yoast_settings.cookie_data_key
      };
      var analyser = new YoastSeo.AnalyserEditNode(analyzer_options);

      // Update this.data everytime the field values are modified.
      jQuery(window).on('yoast_seo-form_item-changed', function () {
        var data = analyser.getData();
        analyser.refreshSnippet();
        analyser.refreshAnalysis();
        //self.saveCookie();
      });

      // Instantiate the FormItem View component plugged on the snippet preview title field.
      var snippetTitle = new YoastSeo.form.SnippetElement({
        el: $('#snippet_title'),
        callbacks: {
          // When the snippet preview title get the focus.
          // Replace the snippet preview title by a component which allows the edition of the title raw value (based on
          // the meta tag title field, including tokens).
          focused: function() {
            // Retrieve the Form Item view behind the meta tag title field.
            var formItem = Drupal.BackboneForm._formItemViews[settings.yoast_seo.fields['meta_title']],
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
      var snippetTitleRaw = new YoastSeo.form.SnippetElement({
        el: $('#snippet_title_raw'),
        callbacks: {
          // When snippet preview title raw value component change, update the meta tag title value.
          // By updating the meta tag title value, the analyser should perform a new analysis.
          changed: function() {
            var formItem = Drupal.BackboneForm._formItemViews[settings.yoast_seo.fields['meta_title']];
            formItem.value(snippetTitleRaw.value());
            formItem._change();
          },
          // When the component lose the focus, hide it and display the snippet preview title component instead.
          blured: function() {
            snippetTitle.$el.show();
            snippetTitleRaw.$el.hide();
          },
          // When the component get the focus.
          focused: function() {
            snippetTitleRaw.moveCursorEnd();
          }
        }
      });

      // Instantiate the FormItem View component plugged on the snippet preview summary field.
      var snippetSummary = new YoastSeo.form.SnippetElement({
        el: $('#snippet_meta'),
        callbacks: {
          // When the snippet preview summary get the focus.
          // Replace the snippet preview summary by a component which allows the edition of the summary raw value (based on
          // the meta tag description field, including tokens).
          focused: function() {
            // Retrieve the Form Item view behind the meta tag summary field.
            var formItem = Drupal.BackboneForm._formItemViews[settings.yoast_seo.fields['meta_description']],
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
      var snippetSummaryRaw = new YoastSeo.form.SnippetElement({
        el: $('#snippet_meta_raw'),
        callbacks: {
          // When snippet preview summary raw value component change, update the meta tag summary value.
          // By updating the meta tag summary value, the analyser should perform a new analysis.
          changed: function() {
            var formItem = Drupal.BackboneForm._formItemViews[settings.yoast_seo.fields['meta_description']];
            formItem.value(snippetSummaryRaw.value());
            formItem._change();
          },
          // When the component lose the focus, hide it and display the snippet preview summary component instead.
          blured: function() {
            snippetSummary.$el.show();
            snippetSummaryRaw.$el.hide();
          },
          // When the component get the focus.
          focused: function() {
            snippetSummaryRaw.moveCursorEnd();
          }
        }
      });

      // Instantiate the FormItem View component plugged on the snippet preview url field.
      var snippetUrl = new YoastSeo.form.SnippetElement({
        el: $('#snippet_cite'),
        callbacks: {
          // When the snippet preview cite get the focus.
          // Replace the snippet preview cite by a component which allows the edition of the cite raw value (based on
          // the advanced alias field, including tokens).
          focused: function() {
            // Retrieve the Form Item view behind the advanced alias field.
            var formItem = Drupal.BackboneForm._formItemViews[settings.yoast_seo.fields['path']],
              value = formItem.value();
            // If the value is empty, add a default / at the beginning of the field.
            if (value == '') {
              value = '/';
            }
            // Display the raw value component instead of the computed value.
            snippetUrl.$el.hide();
            snippetUrlRaw.$el.show();
            snippetUrlRaw.value(value);
            snippetUrlRaw.$el.focus();
          }
        }
      });

      // Add a field to manage the snippet preview summary raw value.
      $('<span contenteditable="true" class="url" id="snippet_cite_raw" style="display:none"></span>').appendTo('#url_container');

      // Instantiate the FormItem View component plugged on the snippet preview cite raw field.
      var snippetUrlRaw = new YoastSeo.form.SnippetElement({
        el: $('#snippet_cite_raw'),
        callbacks: {
          // When snippet preview url raw value component change, update the advanced alias field value.
          // By updating the advanced alias field value, the analyser should perform a new analysis.
          changed: function() {
            var formItem = Drupal.BackboneForm._formItemViews[settings.yoast_seo.fields['path']];
            formItem.value(snippetUrlRaw.value());
            formItem._change();
          },
          // When the component lose the focus, hide it and display the snippet preview url component instead.
          blured: function() {
            if (snippetUrlRaw.value() == '/') {
              var formItem = Drupal.BackboneForm._formItemViews[settings.yoast_seo.fields['path']];
              formItem.value('');
              formItem._change();
            }
            snippetUrl.$el.show();
            snippetUrlRaw.$el.hide();
          },
          // When the component get the focus.
          focused: function() {
            snippetUrlRaw.moveCursorEnd();
          }
        }
      });

    }
  };

})(jQuery, Drupal);
