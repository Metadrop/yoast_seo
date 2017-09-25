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

        console.log($form);

        window.orchestrator = new Orchestrator($form, settings.yoast_seo);
      });

      console.log(settings.yoast_seo);

      // Update the text fields behind the CKEditor when it changes.
      // TODO: Incorporate this with the update event binder.
      if (typeof CKEDITOR !== "undefined") {
        CKEDITOR.on('instanceReady', function (ev) {
          var editor = ev.editor;
          // Check if this the instance we want to track.
          // if (typeof YoastSEO.analyzerArgs.fields.text != 'undefined') {
          //   if (editor.name == YoastSEO.analyzerArgs.fields.text) {
              editor.on('change', function () {
                // Let CKEditor handle updating the linked text element.
                editor.updateElement();
                // Dispatch input event so Yoast SEO knows something changed!
                // DrupalSource.triggerEvent(editor.name);
              });
            // }
          // }
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

    console.log("Setting default data");
    // Default data.
    this.data = {
      meta: '',
      metaTitle: '',
      locale: 'en_US',
      keyword: jQuery('[data-drupal-selector=' + this.config.fields.focus_keyword + ']').val()
    };

    // We update what data we have available so that this.data is always
    // initialised.
    this.refreshData();

    this.app = new RealTimeSEO.App(this.config);
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

    console.log("Refreshing data");
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
        self.analyze();
      },
      error: function (jqXHR, status, error) {
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
    console.log('Setting data', data);

    // We merge the data so we can selectively overwrite things.
    this.data = Object.assign({}, this.data, data);

    this.updatePreview();
  };

  /**
   * This is used as a callback in the Real Time SEO library to provide the data
   * that is needed for analysis.
   *
   * @return analyzerData
   */
  Orchestrator.prototype.getData = function () {
    console.log('Data requested', this.data);

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
    console.log('Saving score ', score);

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
    console.log('Saving content score ', score);
  };

  /**
   * Updates the preview with the newest snippet.
   */
  Orchestrator.prototype.updatePreview = function () {
    var html =
      '<section class="snippet-editor__preview">' +
        '<div class="snippet_container snippet-editor__container" id="title_container">' +
          '<span class="title" id="snippet_title">' + this.data.metaTitle + '</span>' +
          '<span class="title" id="snippet_sitename"></span>' +
        '</div>' +
        '<div class="snippet_container snippet-editor__container" id="url_container">' +
          '<cite class="url urlBase" id="snippet_citeBase">yoast-seo7.dev/</cite>' +
          '<cite class="url" id="snippet_cite">example-post/</cite>' +
        '</div>' +
        '<div class="snippet_container snippet-editor__container" id="meta_container">' +
          '<span class="desc desc-default" id="snippet_meta">' + this.data.meta + '</span>' +
        '</div>' +
      '</section>';

    document.getElementById(this.config.targets.snippet).innerHTML = html;
  };

})(jQuery);

/**
 * Inputgenerator generates a form for use as input.
 * @param args
 * @param refObj
 * @constructor
 */
YoastSEO_DrupalSource = function (args) {
  this.config = args;
  this.refObj = {};
  this.analyzerData = {};
  this.tokensRemote = {};
};

/**
 * Sets field value and dispatches an event to fire content analysis magic
 * @param field
 */
YoastSEO_DrupalSource.prototype.triggerEvent = function (field) {
  if ('createEvent' in document) {
    var ev = document.createEvent('HTMLEvents');
    ev.initEvent('input', false, true);
    document.getElementById(field).dispatchEvent(ev);
  }
  else {
    document.getElementById(field).fireEvent('input');
  }
};

/**
 * Parses the input in snippet preview fields on input evt to data in the metatag and path fields
 * @param source
 * @param target
 */
YoastSEO_DrupalSource.prototype.parseSnippetData = function (source, target) {
  var listener = function (ev) {
    // textContent support for FF and if both innerText and textContent are
    // undefined we use an empty string.
    document.getElementById(target).value = (ev.target.value || '');
    this.triggerEvent(target);
  }.bind(this);
  document.getElementById(source).addEventListener('blur', listener);
};


/**
 * Grabs data from the refObj and returns populated analyzerData
 * @returns analyzerData
 */
YoastSEO_DrupalSource.prototype.getData = function () {
  // Default data in here.
  data = {
    keyword: this.getDataFromInput('keyword'),
    meta: this.getDataFromInput('meta'),
    snippetMeta: this.getDataFromInput('meta'),
    text: this.getDataFromInput('text'),
    pageTitle: this.getDataFromInput('title'),
    snippetTitle: this.getDataFromInput('title'),
    baseUrl: this.config.baseRoot,
    url: this.config.baseRoot + this.getDataFromInput('url'),
    snippetCite: this.getDataFromInput('url')
  };

  console.log('Data retrieved', data);

  return data;
};

YoastSEO_DrupalSource.prototype.getDataFromInput = function (field) {
  var value;
  // If this is an array of id's
  if (this.config.fields[field] instanceof Array) {
    var output = [];
    for (var text_field in this.config.fields[field]) {
      if (
        typeof this.config.fields[field][text_field] !== 'undefined'
        && document.getElementById(this.config.fields[field][text_field])
        && document.getElementById(this.config.fields[field][text_field]).value != ''
      ) {
        output.push(document.getElementById(this.config.fields[field][text_field]).value);
      }
    }
    value = output.join('\n');
  } else {
    value = document.getElementById(this.config.fields[field]).value;
  }

  return this.tokenReplace(value);
};

/**
 * Grabs data from the refObj and returns populated analyzerData
 * @returns analyzerData
 */
YoastSEO_DrupalSource.prototype.updateRawData = function () {
  var data = {
    keyword: this.getDataFromInput('keyword'),
    meta: this.getDataFromInput('meta'),
    snippetMeta: this.getDataFromInput('meta'),
    text: this.getDataFromInput('text'),
    nodeTitle: this.getDataFromInput('nodeTitle'),
    pageTitle: this.getDataFromInput('title'),
    baseUrl: this.config.baseRoot,
    url: this.config.baseRoot + '/' + this.getDataFromInput('url'),
    snippetCite: this.getDataFromInput('url')
  };

  if (!this.config.SEOTitleOverwritten) {
    data.pageTitle = data.nodeTitle;
    data.snippetTitle = data.nodeTitle;

    document.getElementById(this.config.fields.title).value = data.nodeTitle;
  }

  // Placeholder text in snippet if nothing was found.
  if (data.meta == '') {
    data.snippetMeta = this.config.placeholderText.description;
  }
  if (data.pageTitle == '') {
    data.snippetTitle = this.config.placeholderText.title;
  }
  if (data.snippetCite == '') {
    data.snippetCite = this.config.placeholderText.url;
  }

  YoastSEO.app.rawData = data;
};

/**
 * Calls the eventbinders.
 */
YoastSEO_DrupalSource.prototype.bindElementEvents = function () {
  this.inputElementEventBinder();
};

/**
 * Binds the renewData function on the change of inputelements.
 */
YoastSEO_DrupalSource.prototype.inputElementEventBinder = function () {
  for (field in this.config.fields) {
    if (this.config.fields[field] instanceof Array) {
      for (var text_field in this.config.fields[field]) {
        if (typeof this.config.fields[field][text_field] != 'undefined' && document.getElementById(this.config.fields[field][text_field])) {
          document.getElementById(this.config.fields[field][text_field]).__refObj = this;
          document.getElementById(this.config.fields[field][text_field]).addEventListener('input', this.renewData.bind(this));
        }
      }
    }
    if (typeof this.config.fields[field] != 'undefined' && document.getElementById(this.config.fields[field])) {
      document.getElementById(this.config.fields[field]).__refObj = this;
      document.getElementById(this.config.fields[field]).addEventListener('input', this.renewData.bind(this));
    }
  }
};

/**
 * Calls getAnalyzerinput function on change event from element
 * @param event
 */
YoastSEO_DrupalSource.prototype.renewData = function (ev) {
  // @TODO: implement snippetPreview rebuild
  if (!this.config.SEOTitleOverwritten && (ev.target.id == this.config.fields.nodeTitle || ev.target.id == this.config.snippetFields.title)) {
    var $this = this;
    setTimeout(function () {
      $this.config.SEOTitleOverwritten = true;
      document.getElementById(YoastSEO.app.config.fields.title).value = ev.target.value;
      document.getElementById($this.config.snippetFields.title).value = ev.target.value;
      $this.triggerEvent(YoastSEO.app.config.snippetFields.title);
    }, 3000);
  }

  //If node is new we could use new typed title for js tokens  
  if (ev.target.id == this.config.fields.nodeTitle && Drupal.yoast_seo_node_new) {
    var metatagTitle =  document.getElementById(this.config.fields.title).value;
    //If node is new replace token title with value from input title
    //@todo: Review logic for better implement and remove hard 
    //[current-page:title]
    if(metatagTitle.indexOf('[current-page:title]') != -1){
      metatagTitle = metatagTitle.replace('[current-page:title]', ev.target.value);
    }
    //[node:title]
    if(metatagTitle.indexOf('[node:title]') != -1){
      metatagTitle = metatagTitle.replace('[node:title]', ev.target.value);
    }
    document.getElementById(this.config.snippetFields.title).value = this.tokenReplace(metatagTitle);
    this.triggerEvent(this.config.snippetFields.title);
  }

  if (ev.target.id == this.config.fields.title) {
    document.getElementById(this.config.snippetFields.title).value = this.tokenReplace(ev.target.value);
    this.triggerEvent(this.config.snippetFields.title);
  }

  if (ev.target.id == this.config.fields.meta) {
    document.getElementById(this.config.snippetFields.meta).value = this.tokenReplace(ev.target.value);
    this.triggerEvent(this.config.snippetFields.meta);
  }

  if (ev.target.id == this.config.fields.url) {
    document.getElementById(this.config.snippetFields.url).value = this.tokenReplace(ev.target.value);
    this.triggerEvent(this.config.snippetFields.url);
  }

  YoastSEO.app.refresh();
};

/**
 * Save the snippet values, but in reality we ignore this.
 *
 * @param {Object} ev
 */
YoastSEO_DrupalSource.prototype.saveSnippetData = function (ev) {
};

/**
 * retuns a string that is used as a CSSclass, based on the numeric score
 * @param score
 * @returns output
 */
YoastSEO_DrupalSource.prototype.scoreToRating = function (rating) {
  var scoreRate;

  if (rating <= 4) {
    scoreRate = 'bad';
  }

  if (rating > 4 && rating <= 7) {
    scoreRate = 'ok';
  }

  if (rating > 7) {
    scoreRate = 'good';
  }

  if (rating == 0) {
    scoreRate = 'na';
  }

  return Drupal.t('SEO: <strong>' + scoreRate + '</strong>');
};

/**
 * Sets the SEO score in the hidden element.
 * @param score
 */
YoastSEO_DrupalSource.prototype.saveScores = function (score) {
  console.log('Saving score ', score);

  var rating = 0;
  if (typeof score == 'number' && score > 0) {
    rating = ( score / 10 );
  }

  document.getElementById(this.config.targets.overall).getElementsByClassName('score_value')[0].innerHTML = this.scoreToRating(rating);
  document.querySelector('[data-drupal-selector="' + this.config.scoreElement + '"]').setAttribute('value', rating);
};

/**
 * Sets the SEO score in the hidden element.
 * @param score
 */
YoastSEO_DrupalSource.prototype.saveContentScore = function (score) {
  console.log('Saving content score ', score);
};


/**
 * Replace tokens.
 */
YoastSEO_DrupalSource.prototype.tokenReplace = function (value) {
  var self = this,
    tokenRegex = /(\[[^\]]*:[^\]]*\])/g,
    match = value.match(tokenRegex),
    tokensNotFound = [];

  // If the value contains tokens.
  if (match != null) {
    // Replace all the tokens by their relative value.
    for (var i in match) {
      var tokenRelativeField = null,
        tokenRawValue = false;

      // Check if the token is relative to a field present on the page.
      if (typeof this.config.tokens[match[i]] != 'undefined') {
        tokenRawValue = true;
        tokenRelativeField = this.config.tokens[match[i]];
      }

      if (tokenRawValue == true) {
        if (typeof this.config.fields[tokenRelativeField] != 'undefined') {
          // Use node title field value.
          if (tokenRelativeField == 'title') {
            tokenRelativeField = 'nodeTitle';
          }

          value = value.replace(match[i], document.getElementById(this.config.fields[tokenRelativeField]).value);
        } else {
          value = value.replace(match[i], this.config.tokens[match[i]]);
        }
      }
      // The token value has to be found remotely.
      else {
        // If the token value has already been resolved and stored locally.
        if (typeof this.tokensRemote[match[i]] != 'undefined') {
          value = value.replace(match[i], this.tokensRemote[match[i]]);
        }
        else {
          tokensNotFound.push(match[i]);
        }
      }
    }

    // If some tokens hasn't been resolved locally.
    // Try to solve them remotely.
    if (tokensNotFound.length) {
      jQuery.ajax({
        async: false,
        url: Drupal.url('yoast_seo/tokens'),
        type: 'POST',
        data: {'tokens[]': tokensNotFound},
        dataType: 'json'
      }).then(function (data) {
        // Store their value locally.
        // It will avoid an unnecessary call to the server.
        for (var token in data) {
          self.tokensRemote[token] = data[token];
          value = value.replace(token, self.tokensRemote[token]);
        }
      });
    }
  }

  return value;
};
