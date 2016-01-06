/**
 * @file
 */
YoastSeoData = function (args) {
  this.config = args.settings;
  this.analyzerArgs = args.analyzerArgs;
  this.fieldsMapping = args.fieldsMapping || {
    meta: 'meta_description',
    text: 'body',
    pageTitle: 'meta_title',
    title: 'title',
    url: 'path',
    snippetCite: 'path',
    snippetMeta: 'meta_description',
    snippetTitle: 'meta_title',
    keyword: 'focus_keyword'
  };

  this.tokensRemote = {};
  this.data = this.getData();

  var self = this;
  // Update this.data everytime the field values are modified.
  jQuery(window).on('yoast_seo-form_item-changed', function () {
    self.data = null;
    self.data = self.getData();
    self.refreshSnippet();
    self.refreshAnalysis();
  });
};

/**
 * This should return an object with the given properties.
 *
 * @callback YoastSEO.App~getData
 *
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
  // If their is a field
  // Extract the data from the fields if these fields have been mapped.
  for (var fieldName in data) {
    var formItemView = Drupal.YoastSeoForm._formItemViews[this.config.fields[this.fieldsMapping[fieldName]]];
    if (typeof formItemView !== 'undefined') {
      var fieldValue = formItemView.value();

      // If the field hasn't been filled already.
      // Use the default value if provided.
      if (fieldValue == '') {
        if (typeof this.config.default_text[this.fieldsMapping[fieldName]] !== 'undefined'
            && this.config.default_text[this.fieldsMapping[fieldName]] != '') {
          data[fieldName] = this.tokenReplace(this.config.default_text[this.fieldsMapping[fieldName]]);
        }
      }
      // If the field has been filled.
      // Extract the value from the field and replace the tokens if any by their values.
      else {
        data[fieldName] = this.tokenReplace(formItemView.value());
      }
    }

    // If the data is empty and a place holder has been defined, use the placeholder as value.
    if ((typeof this.config.placeholder_text[fieldName] !== 'undefined'
      && this.config.placeholder_text[fieldName] != '') && data[fieldName] == '') {
      data[fieldName] = this.config.placeholder_text[fieldName];
    }
  }

  return data;
};

YoastSeoData.prototype.tokenReplace = function (value) {
  var self = this,
    tokenRegex = /(\[[^\]]*:[^\]]*\])/g,
    match = value.match(tokenRegex),
    tokensNotFound = [];

  // If the value contains tokens.
  if (match != null) {
    // Replace all the tokens by their relative value.
    for (var i in match) {
      var tokenRelativeField = null;
      var tokenRawValue = false;

      // Check if the token is relative to a field present on the page.
      if (typeof this.config.tokens[match[i]] != 'undefined') {
        var fieldName = this.config.tokens[match[i]];
        var isRelativeField = this.config.fields[fieldName] != undefined;
        // If no field exist with the same token value, we consider it's a raw value.
        if (! isRelativeField) {
          tokenRawValue = true;
        }
        // Else, we know it's related to a field content.
        else {
          tokenRelativeField = this.config.tokens[match[i]];
        }
      }

      // If the token can be solved locally.
      if (tokenRelativeField != null) {
        // Replace the token with the relative field token value.
        var formItemView = Drupal.YoastSeoForm._formItemViews[this.config.fields[tokenRelativeField]];
        if (typeof formItemView !== 'undefined') {
          var tokenValue = this.tokenReplace(formItemView.value());
          value = value.replace(match[i], tokenValue);
        }
      }
      else if (tokenRawValue == true) {
        value = value.replace(match[i],  this.config.tokens[match[i]]);
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

YoastSeoData.prototype.refreshSnippet = function () {
  YoastSEO.app.rawData = this.getData();
  YoastSEO.app.reloadSnippetText();
};

YoastSeoData.prototype.refreshAnalysis = function () {
  if (typeof YoastSEO.app.snippetPreview === "undefined") {
    YoastSEO.app.init();
  }
  YoastSEO.app.runAnalyzerCallback();
};

/**
 * Initializes the snippetPreview if it isn't there.
 * If it is already initialized, it get's new values from the inputs and rerenders snippet.
 */
YoastSeoData.prototype.getAnalyzerInput = function () {
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
 * Grabs data from the refObj and returns populated analyzerData.
 *
 * @returns analyzerData
 */
YoastSeoData.prototype.updateRawData = function () {
  YoastSEO.app.rawData = this.getData();
};


/**
 * Calls the eventbinders.
 * We don't need it.
 */
YoastSeoData.prototype.bindElementEvents = function () {

};

/**
 * Updates the snippet values.
 * We don't need it.
 *
 * @param {Object} ev
 */
YoastSeoData.prototype.updateSnippetValues = function (ev) {
};

/**
 * Load focus keyword autocomplete.
 *
 * @param focusKeywordId
 */
YoastSeoData.prototype.loadFocusKeywordAutocomplete = function (focusKeywordId, language) {
  // Autocomplete for focus keyword field.
  // We use the google autocomplete api.
  var $ = jQuery;
  $('#' + focusKeywordId).autocomplete({
    source: function(request, response) {
      $.getJSON("http://suggestqueries.google.com/complete/search?callback=?",
        {
          "hl":language,
          "jsonp":"suggestCallBack",
          "q":request.term,
          "client":"youtube"
        }
      );
      suggestCallBack = function (data) {
        var suggestions = [];
        $.each(data[1], function(key, val) {
          suggestions.push({"value":val[0]});
        });
        suggestions.length = 5;
        response(suggestions);
      };
    }
  });
}