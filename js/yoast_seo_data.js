YoastSeoData = function(args) {
  this.config = args.settings;
  this.analyzerArgs = args.analyzerArgs;
  this.fieldsMapping = args.fieldsMapping || {
      meta: 'meta_description',
      text: 'body',
      pageTitle: 'meta_title',
      title: 'title',
      url: 'url',
      snippetCite: 'url',
      snippetMeta: 'meta_description',
      snippetTitle: 'meta_title',
      keyword: 'focus_keyword'
  };

  this.data = this.getData();

  var self = this;
  // Update this.data everytime the field values are modified.
  jQuery(window).on('yoast_seo-form_item-changed', function() {
    self.data = null;
    self.data = self.getData();
    self.refreshSnippet();
    self.refreshAnalysis();
  });
};


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
YoastSeoData.prototype.getData = function () {

  // Return the content of this.data if exist.
  if (this.data != undefined && this.data != null) {
    return this.data;
  }

  var data = {
    keyword: '',
    meta: '',
    text: '',
    pageTitle: '',
    title: '',
    url: '',
    excerpt: '',
    snippetMeta: '',
    snippetCite: '',
    snippetTitle: '',
    baseUrl: this.config.base_root
  };


  // For all data required by the Yoast SEO snippet.
  // Retrieve the form item view relative to the data.
  for (var fieldName in data) {
    var formItemView = Drupal.YoastSeoForm._formItemViews[this.config.fields[this.fieldsMapping[fieldName]]];
    if (typeof formItemView !== 'undefined') {
      data[fieldName] = this.tokenReplace(formItemView.value());
    }
  }

  console.log(this.config);

  if (!this.config.seo_title_overwritten) {
    data.pageTitle = data.title;
    data.snippetTitle = data.title;

    //document.getElementById(this.config.fields.title).value = data.nodeTitle;
  }

  // Placeholder text in snippet if nothing was found.
  if (data.meta == '') {
    data.snippetMeta = this.config.placeholder_text.description;
  }
  if (data.pageTitle == '') {
    data.snippetTitle = this.config.placeholder_text.title;
  }
  if (data.snippetCite == '') {
    data.snippetCite = this.config.placeholder_text.url;
  }

  return data;
};


YoastSeoData.prototype.tokenReplace = function(value) {
  var tokenRegex = /(\[[^\]]*:[^\]]*\])/g,
    match = value.match(tokenRegex);

  // If the value contains tokens.
  if (match != null) {
    // Replace all the tokens by their relative value.
    for (var i in match) {
      // Check if the token is relative to a field available by javascript.
      var tokenRelativeField = _.findKey(this.config.tokens, function(val) {
        return val === match[i];
      });

      // If the token can be solved locally.
      if (typeof tokenRelativeField != 'undefined') {
        // Replace the token with the relative field token value.
        var formItemView = Drupal.YoastSeoForm._formItemViews[this.config.fields[this.fieldsMapping[tokenRelativeField]]];
        if (typeof formItemView !== 'undefined') {
          var tokenValue = this.tokenReplace(formItemView.value());
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

YoastSeoData.prototype.refreshSnippet = function () {
  YoastSEO.app.rawData = this.getData();
  YoastSEO.app.reloadSnippetText();
}

YoastSeoData.prototype.refreshAnalysis = function () {
  if (typeof YoastSEO.app.snippetPreview === "undefined") {
    YoastSEO.app.init();
  }
  YoastSEO.app.runAnalyzerCallback();
}

/**
 * Initializes the snippetPreview if it isn't there.
 * If it is already initialized, it get's new values from the inputs and rerenders snippet.
 */
YoastSeoData.prototype.getAnalyzerInput = function() {
  if (typeof YoastSEO.app.snippetPreview === "undefined") {
    YoastSEO.app.init();
  }
  else {
    this.updateRawData();
    YoastSEO.app.reloadSnippetText();
  }
  YoastSEO.app.runAnalyzerCallback();
};


/**
 * Grabs data from the refObj and returns populated analyzerData
 * @returns analyzerData
 */
YoastSeoData.prototype.updateRawData = function() {
  YoastSEO.app.rawData = this.getData();
};


/**
 * retuns a string that is used as a CSSclass, based on the numeric score
 * @param score
 * @returns output
 */
YoastSeoData.prototype.scoreRating = function( score ) {
  var scoreRate;
  switch ( score ) {
    case 0:
      scoreRate = "na";
      break;
    case 4:
    case 5:
      scoreRate = "poor";
      break;
    case 6:
    case 7:
      scoreRate = "ok";
      break;
    case 8:
    case 9:
    case 10:
      scoreRate = "good";
      break;
    default:
      scoreRate = "bad";
      break;
  }

  return scoreRate;
};

/**
 * Sets the SEO score in both the hidden input and the rating element.
 * @param score
 */
YoastSeoData.prototype.saveScores = function ( score ) {
  // Update score text in the score box.
  jQuery('.score_value', '#' + this.config.targets.overall_score_target_id).text(this.scoreRating(score));
  // Update score in the score field.
  jQuery('[data-drupal-selector="' + this.config.fields.seo_status + '"]')
    .attr('value', score)
    .val(score);
};


/**
 * Calls the eventbinders.
 * We don't need it.
 */
YoastSeoData.prototype.bindElementEvents = function() {

};

/**
 * Updates the snippet values.
 * We don't need it.
 *
 * @param {Object} ev
 */
YoastSeoData.prototype.updateSnippetValues = function( ev ) {
};