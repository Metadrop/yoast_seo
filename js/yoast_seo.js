/**
 * JavaScript file that handles initializing and firing the RealTime SEO
 * analysis library.
 *
 * Support goalgorilla/YoastSEO.js v2.0.0.
 */
(function ($) {
  Drupal.yoast_seo = Drupal.yoast_seo || {};
  Drupal.yoast_seo_node_new = false;

  Drupal.behaviors.yoast_seo = {
    attach: function (context, settings) {
      if (typeof settings.yoast_seo === 'undefined') {
        throw "No settings specified for the YoastSEO analysis library."
      }

      // TODO: This should be set on the server side to make it work for all entity types.
      if(settings.path && settings.path.currentPath.indexOf('node/add') !== -1){
        settings.yoast_seo.is_new = true;
      }

      $('body', context).once('realtime-seo').each(function () {
        var orchestrator = new Orchestrator(settings.yoast_seo);
      });

      // Making sure we only initiate Yoast SEO once.
      // $('body', context).once('yoast_seo').each(function () {

        // Make it global.
        // window.RealTimeSEO.app = new App(analyzerArgs);

        // Parse the input from snippet preview fields to their corresponding metatag and path fields
        // DrupalSource.parseSnippetData(YoastSEO.analyzerArgs.snippetFields.title, YoastSEO.analyzerArgs.fields.title);
        // DrupalSource.parseSnippetData(YoastSEO.analyzerArgs.snippetFields.url, YoastSEO.analyzerArgs.fields.url);
        // DrupalSource.parseSnippetData(YoastSEO.analyzerArgs.snippetFields.meta, YoastSEO.analyzerArgs.fields.meta);

        // No enter on contenteditable fields.
        // $("#snippet_title, #snippet_cite, #snippet_meta").keypress(function (e) {
        //   if (e.keyCode === 13) {
        //     e.preventDefault();
        //   }
        // });

        if (typeof CKEDITOR !== "undefined") {
          CKEDITOR.on('instanceReady', function (ev) {
            var editor = ev.editor;
            // Check if this the instance we want to track.
                editor.on('change', function () {
                  // Let CKEditor handle updating the linked text element.
                  editor.updateElement();
                  // Dispatch input event so Yoast SEO knows something changed!
                  // DrupalSource.triggerEvent(editor.name);
                });
          });
        }

      // });
    }
  };

  function verifyRequirements(config) {
    if (typeof config.targets !== "object") {
      throw "`targets` is a required Orchestrator argument, `targets` is not an object.";
    }

    if (typeof RealTimeSEO === 'undefined') {
      $('#' + config.targets.output).html('<p><strong>' + Drupal.t('It looks like something went wrong when we tried to load the Real-Time SEO content analysis library. Please check it the module is installed correctly.') + '</strong></p>');
      throw "RealTimeSEO is not defined. Is the library attached?";
    }

  }

  /**
   * Couples Drupal with the RealTimeSEO implementation.
   *
   * @constructor
   */
  var Orchestrator = function (config) {

    verifyRequirements(config);

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
    if (typeof this.app !== "undefined" ) {
      return;
    }

    this.app = new RealTimeSEO.App(this.config);
  };

  /**
   * This is the most important part of our part of the equation.
   *
   * We talk to Drupal to provide all the data that the YoastSEO.js library
   * needs to do the analysis.
   *
   * @returns analyzerData
   */
  Orchestrator.prototype.getData = function () {
    // Default data in here.
    data = {
      keyword: this.getDataFromInput("keyword"),
      meta: this.getDataFromInput("meta"),
      snippetMeta: this.getDataFromInput("meta"),
      text: this.getDataFromInput("text"),
      pageTitle: this.getDataFromInput("title"),
      snippetTitle: this.getDataFromInput("title"),
      baseUrl: this.config.baseRoot,
      url: this.config.baseRoot + this.getDataFromInput("url"),
      snippetCite: this.getDataFromInput("url")
    };

    console.log("Data retrieved", data);

    return data;
  };

  // Temporary function to keep things working.
  Orchestrator.prototype.getDataFromInput = function (f) {
    return 'static';
  }


  /**
   * Sets the SEO score in the hidden element.
   * @param score
   */
  Orchestrator.prototype.saveScores = function (score) {
    console.log("Saving score ", score);

    var rating = 0;
    if (typeof score === "number" && score > 0) {
      rating = ( score / 10 );
    }

    document.getElementById(this.config.targets.overall).getElementsByClassName("score_value")[0].innerHTML = this.scoreRating(rating);
    document.querySelector('[data-drupal-selector="' + this.config.scoreElement + '"]').setAttribute('value', rating);
  };

  /**
   * Sets the content score in the hidden element.
   * @param score
   */
  Orchestrator.prototype.saveContentScore = function (score) {
    console.log("Saving content score ", score);
  };


})(jQuery);

function submit_form() {
  var $form = jQuery('.node-form');
  // We only send non-empty form elements. Any missing elements will be caught
  // by validation but this ensures we don't get errors for required children of
  // optional elements (such as alt attribute for images).
  var postData = $form.find(':input[value!=\'\']:not(.js-hide.form-submit)').serialize();
  $form.ajaxSubmit({
    url: '/yoast_seo/preview',
      data : {
        yoast_seo_preview: {
          path: drupalSettings.path.currentPath,
          action: $form.attr('action'),
          method: $form.attr('method')
        },
        // target: {
        // },
        // form_data: postData
      },
    success:function(data, status, xhr)
    {
      if(status == "success")
      {
        console.log(data);
        // Do something on page
      }
      else
      {
        console.log("Failed")
        // Do something on page
      }
    }
  });
  // jQuery.ajax({
  //   url: '/yoast_seo/preview',
  //   beforeSend: function (request)
  //   {
  //     request.setRequestHeader('Content-Type', 'text/html;   charset=utf-8');
  //   },
  //   type: "POST",
  //   data : {
  //     path: drupalSettings.path.currentPath,
  //     target: {
  //       action: $form.attr('action'),
  //       method: $form.attr('method')
  //     },
  //     form_data: postData
  //   },
  //   success:function(data, status, xhr)
  //   {
  //     if(status == "success")
  //     {
  //       console.log(data);
  //       // Do something on page
  //     }
  //     else
  //     {
  //       console.log("Failed")
  //       // Do something on page
  //     }
  //   }
  // });

  console.log(postData);
  console.log("Yes");
}

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
  if ("createEvent" in document) {
    var ev = document.createEvent("HTMLEvents");
    ev.initEvent("input", false, true);
    document.getElementById(field).dispatchEvent(ev);
  }
  else {
    document.getElementById(field).fireEvent("input");
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
    document.getElementById(target).value = (ev.target.value || "");
    this.triggerEvent(target);
  }.bind(this);
  document.getElementById(source).addEventListener("blur", listener);
};


/**
 * Grabs data from the refObj and returns populated analyzerData
 * @returns analyzerData
 */
YoastSEO_DrupalSource.prototype.getData = function () {
  // Default data in here.
  data = {
    keyword: this.getDataFromInput("keyword"),
    meta: this.getDataFromInput("meta"),
    snippetMeta: this.getDataFromInput("meta"),
    text: this.getDataFromInput("text"),
    pageTitle: this.getDataFromInput("title"),
    snippetTitle: this.getDataFromInput("title"),
    baseUrl: this.config.baseRoot,
    url: this.config.baseRoot + this.getDataFromInput("url"),
    snippetCite: this.getDataFromInput("url")
  };

  console.log("Data retrieved", data);

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
    value = output.join("\n");
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
    keyword: this.getDataFromInput("keyword"),
    meta: this.getDataFromInput("meta"),
    snippetMeta: this.getDataFromInput("meta"),
    text: this.getDataFromInput("text"),
    nodeTitle: this.getDataFromInput("nodeTitle"),
    pageTitle: this.getDataFromInput("title"),
    baseUrl: this.config.baseRoot,
    url: this.config.baseRoot + '/' + this.getDataFromInput("url"),
    snippetCite: this.getDataFromInput("url")
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
          document.getElementById(this.config.fields[field][text_field]).addEventListener("input", this.renewData.bind(this));
        }
      }
    }
    if (typeof this.config.fields[field] != 'undefined' && document.getElementById(this.config.fields[field])) {
      document.getElementById(this.config.fields[field]).__refObj = this;
      document.getElementById(this.config.fields[field]).addEventListener("input", this.renewData.bind(this));
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
YoastSEO_DrupalSource.prototype.scoreRating = function (rating) {
  var scoreRate;

  if (rating <= 4) {
    scoreRate = "bad";
  }

  if (rating > 4 && rating <= 7) {
    scoreRate = "ok";
  }

  if (rating > 7) {
    scoreRate = "good";
  }

  if (rating == 0) {
    scoreRate = "na";
  }

  return Drupal.t("SEO: <strong>" + scoreRate + "</strong>");
};

/**
 * Sets the SEO score in the hidden element.
 * @param score
 */
YoastSEO_DrupalSource.prototype.saveScores = function (score) {
  console.log("Saving score ", score);

  var rating = 0;
  if (typeof score == "number" && score > 0) {
    rating = ( score / 10 );
  }

  document.getElementById(this.config.targets.overall).getElementsByClassName("score_value")[0].innerHTML = this.scoreRating(rating);
  document.querySelector('[data-drupal-selector="' + this.config.scoreElement + '"]').setAttribute('value', rating);
};

/**
 * Sets the SEO score in the hidden element.
 * @param score
 */
YoastSEO_DrupalSource.prototype.saveContentScore = function (score) {
  console.log("Saving content score ", score);
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
