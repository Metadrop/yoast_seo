/**
 * JavaScript file that handles initializing and firing the RealTime SEO
 * analysis library.
 *
 * Support goalgorilla/YoastSEO.js v2.0.0.
 */
(function ($) {
  'use strict';

  Drupal.yoast_seo = Drupal.yoast_seo || {};
  Drupal.yoast_seo_node_new = false;

  Drupal.behaviors.yoast_seo = {
    attach: function (context, settings) {
      if (typeof settings.yoast_seo === 'undefined') {
        throw 'No settings specified for the YoastSEO analysis library.';
      }

      // TODO: This should be set on the server side to make it work for all entity types.
      if (settings.path && settings.path.currentPath.indexOf('node/add') !== -1) {
        settings.yoast_seo.is_new = true;
      }

      $('body', context).once('realtime-seo').each(function () {
        // TODO: This fails if there are multiple forms.
        var $form = $('form').first();

        window.orchestrator = new Orchestrator($form, settings.yoast_seo);
      });

      // Update the text fields behind the CKEditor when it changes.
      // TODO: Incorporate this with the update event binder.
      if (typeof CKEDITOR !== 'undefined') {
        CKEDITOR.on('instanceReady', function (ev) {
          var editor = ev.editor;
          editor.on('change', function () {
            // Let CKEditor handle updating the linked text element.
            editor.updateElement();
          });
        });
      }
    }
  };

  function verifyRequirements(config) {
    // Make a string.endsWidth method available if its not supported.
    if (!String.prototype.endsWith) {
      String.prototype.endsWith = function (searchStr, Position) {
        // This works much better than >= because
        // it compensates for NaN:
        if (!(Position < this.length))
          Position = this.length;
        else
          Position |= 0; // round position
        return this.substr(Position - searchStr.length,
          searchStr.length) === searchStr;
      };
    }

    if (typeof config.targets !== 'object') {
      throw '`targets` is a required Orchestrator argument, `targets` is not an object.';
    }
    else {
      // Turn {name}_target_id into {name}: target_id.
      for (var key in config.targets) {
        if (key.endsWith('target_id')) {
          var target = key.substr(0, key.length - '_target_id'.length);
          config.targets[target] = config.targets[key];
          delete config.targets[key];
        }
      }
    }


    if (typeof RealTimeSEO === 'undefined') {
      $('#' + config.targets.output).html('<p><strong>' + Drupal.t('It looks like something went wrong when we tried to load the Real-Time SEO content analysis library. Please check it the module is installed correctly.') + '</strong></p>');
      throw 'RealTimeSEO is not defined. Is the library attached?';
    }

  }

  /**
   * Couples Drupal with the RealTimeSEO implementation.
   *
   * @param $form
   *   A jQuery selector reference to the form that we are analysing.
   * @param config
   *   The configuration for this orchestrator.
   *
   * @constructor
   */
  var Orchestrator = function ($form, config) {

    verifyRequirements(config);

    this.$form = $form;
    this.config = config;

    this.configureCallbacks();

    this.initializeApp();
  };

  /**
   * Set up the callbacks required by our analyzer library.
   */
  Orchestrator.prototype.configureCallbacks = function () {
    var defaultCallbacks = {
      getData: this.getData.bind(this),
      saveScores: this.saveScores.bind(this),
      saveContentScore: this.saveContentScore.bind(this)
    };

    // If any callbacks were set in config already, they will take precedence.
    this.config.callbacks = Object.assign(defaultCallbacks, this.config.callbacks);
  };

  /**
   * Creates and launches our analyzer library.
   */
  Orchestrator.prototype.initializeApp = function () {
    // Ensure this has no effect if called twice.
    if (typeof this.app !== 'undefined') {
      return;
    }

    var self = this;

    // Set up our event listener for normal form elements
    this.$form.change(this.handleChange.bind(this));

    // Set up our event listener for CKEditor instances if any.
    // We do this in a setTimeout to allow CKEDITOR to load.
    setTimeout(function () {
      if (typeof CKEDITOR !== 'undefined') {
        for (var i in CKEDITOR.instances) {
          CKEDITOR.instances[i].on('blur', self.handleBlur.bind(self));
        }
      }
    }, 200);

    // Default data.
    this.data = {
      meta: '',
      metaTitle: '',
      locale: 'en_US',
      keyword: jQuery('[data-drupal-selector=' + this.config.fields.focus_keyword + ']').val()
    };

    this.app = new RealTimeSEO.App(this.config);

    // We update what data we have available so that this.data is always
    // initialised. We also run the initializer to review existing entities.
    this.refreshData(!this.config.is_new);
  };

  /**
   * Handles a change on the form. If our keyword was changed we just rerun
   * the analysis. In all other cases we schedule a reload of our data.
   */
  Orchestrator.prototype.handleChange = function (event) {
    var $target = $(event.target);

    if ($target.attr('data-drupal-selector') === this.config.fields.focus_keyword) {
      // Update the keyword and re-analyze.
      this.setData({ keyword: $target.val() });
      this.analyze();
      return;
    }

    this.scheduleUpdate();
  };

  /**
   * Handles the blur of a CKEditor field. We just rerun the analysis.
   */
  Orchestrator.prototype.handleBlur = function (event) {
    this.scheduleUpdate();
  };

  /**
   * Schedules an update in a short moment. Will undo any previously scheduled
   * updates to avoid excessive HTTP requests.
   */
  Orchestrator.prototype.scheduleUpdate = function () {
    if (this.update_timeout) {
      clearTimeout(this.update_timeout);
      this.update_timeout = false;
    }

    var self = this;
    this.update_timeout = setTimeout(function () {
      self.update_timeout = false;
      self.refreshData(true);
    }, 500);
  };

  /**
   * Tells the library to retrieve its data and runs the analyzer.
   */
  Orchestrator.prototype.analyze = function () {
    this.app.getData();
    this.app.runAnalyzer();
  };


  /**
   * Sends a request to our Drupal endpoint to refresh our local data.
   *
   * This is the most important part of our part of the equation.
   *
   * We talk to Drupal to provide all the data that the YoastSEO.js library
   * needs to do the analysis.
   */
  Orchestrator.prototype.refreshData = function (analyze) {
    if (typeof analyze === 'undefined') {
      analyze = false;
    }

    var self = this;

    this.$form.ajaxSubmit({
      // TODO: This endpoint probably shouldn't be static.
      url: '/yoast_seo/preview',
      data: {
        yoast_seo_preview: {
          path: drupalSettings.path.currentPath,
          action: this.$form.attr('action'),
          method: this.$form.attr('method')
        }
      },
      success: function (data) {
        self.setData(data);
        if (analyze) {
          self.analyze();
        }
      },
      error: function (jqXHR, status, error) {
        // TODO: Implement error handling for this endpoint.
        console.log('Failed to refresh data', error);
      }
    });
  };

  /**
   * Provides a method to set the data that we provide to the Real Time SEO
   * library for analysis.
   *
   * Can be used as a callback to our Drupal analysis endpoint.
   *
   * @param data
   */
  Orchestrator.prototype.setData = function (data) {
    // We merge the data so we can selectively overwrite things.
    this.data = Object.assign({}, this.data, data);

    this.updatePreview();

    // Some things are composed of others.
    this.data.titleWidth = document.getElementById('snippet_title').offsetWidth;
    this.data.permalink = this.config.base_root + this.data.url;
  };

  /**
   * This is used as a callback in the Real Time SEO library to provide the data
   * that is needed for analysis.
   *
   * @return analyzerData
   */
  Orchestrator.prototype.getData = function () {
    return this.data;
  };

  // Temporary function to keep things working.
  Orchestrator.prototype.getDataFromInput = function (f) {
    return 'static';
  };

  /**
   * Sets the SEO score in the hidden element.
   * @param score
   */
  Orchestrator.prototype.saveScores = function (score) {
    var rating = 0;
    if (typeof score === 'number' && score > 0) {
      rating = ( score / 10 );
    }

    document.getElementById(this.config.targets.overall_score).getElementsByClassName('score_value')[0].innerHTML = this.scoreToRating(rating);
    document.querySelector('[data-drupal-selector="' + this.config.fields.seo_status + '"]').setAttribute('value', rating);
  };

  /**
   * retuns a string that is used as a CSS class, based on the numeric score
   *
   * @param score
   * @returns rating
   */
  Orchestrator.prototype.scoreToRating = function (score) {
    var rating;

    if (score === 0) {
      rating = 'na';
    }
    else if (score <= 4) {
      rating = 'bad';
    }
    else if (score > 4 && score <= 7) {
      rating = 'ok';
    }
    else if (score > 7) {
      rating = 'good';
    }

    return Drupal.t('SEO: <strong>' + rating + '</strong>');
  };

  /**
   * Sets the content score in the hidden element.
   * @param score
   */
  Orchestrator.prototype.saveContentScore = function (score) {
    // TODO: Implement this method, we're not currently using this score.
  };

  /**
   * Updates the preview with the newest snippet.
   */
  Orchestrator.prototype.updatePreview = function () {
    var emphasized_title = this.data.metaTitle;

    if (this.data.keyword) {
      var keyword_pos = this.data.metaTitle.toLowerCase().indexOf(this.data.keyword.toLowerCase());
      var keyword_length = this.data.keyword.length;
      emphasized_title = this.data.metaTitle.substr(0, keyword_pos) + '<strong>' + this.data.metaTitle.substr(keyword_pos, keyword_length) + '</strong>' + this.data.metaTitle.substr(keyword_pos + keyword_length);
    }

    var html =
      '<section class="snippet-editor__preview">' +
        '<div class="snippet_container snippet-editor__container" id="title_container">' +
          '<span class="title" id="snippet_title">' + emphasized_title + '</span>' +
          '<span class="title" id="snippet_sitename"></span>' +
        '</div>' +
        '<div class="snippet_container snippet-editor__container" id="url_container">' +
          '<cite class="url urlBase" id="snippet_citeBase">' + this.config.base_root + '</cite>' +
          '<cite class="url" id="snippet_cite">' + this.data.url + '</cite>' +
        '</div>' +
        '<div class="snippet_container snippet-editor__container" id="meta_container">' +
          '<span class="desc desc-default" id="snippet_meta">' + this.data.meta + '</span>' +
        '</div>' +
      '</section>';

    document.getElementById(this.config.targets.snippet).innerHTML = html;
  };

})(jQuery);
