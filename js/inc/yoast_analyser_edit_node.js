var YoastSeo = YoastSeo || {};

/**
 * @file
 * Drupal Yoast SEO analyser model class for the node edit page.
 *
 * @ignore
 */

(function ($, Drupal) {

  "use strict";

  YoastSeo.AnalyserEditNode = YoastSeo.Analyser.extend({

    /**
     * Map the field of the node edit page with the attributes of the array
     * returned by the getData callback function.
     */
    fieldsMapping: {
      meta: 'meta_description',
      text: 'body',
      pageTitle: 'meta_title',
      title: 'title',
      url: 'path',
      snippetCite: 'path',
      snippetMeta: 'meta_description',
      snippetTitle: 'meta_title',
      keyword: 'focus_keyword'
    },

    /**
     * Tokens already resolved remotely.
     */
    tokensRemote: {},

    /**
     * Extract the form node edit page fields values.
     * Resolve tokens if there are and they can be solved, either locally or remotely.
     *
     * @param data
     * @returns {*}
     */
    extractFieldsValues: function(data) {
      // For all data required by the Yoast SEO snippet.
      // If their is a field extract the data from the fields if these fields
      // have been mapped.
      for (var fieldName in data) {
        var formItemView = Drupal.BackboneForm._formItemViews[this.options.fields[this.fieldsMapping[fieldName]]];

        if (typeof formItemView !== 'undefined') {
          var fieldValue = formItemView.value();

          // If the field hasn't been filled already.
          // Use the default value if provided.
          if (fieldValue == '') {
            if (typeof this.options.default_text[this.fieldsMapping[fieldName]] !== 'undefined'
              && this.options.default_text[this.fieldsMapping[fieldName]] != '') {
              data[fieldName] = this.tokenReplace(this.options.default_text[this.fieldsMapping[fieldName]]);
            }
          }
          // If the field has been filled.
          // Extract the value from the field and replace the tokens if any by their values.
          else {
            data[fieldName] = this.tokenReplace(formItemView.value());
          }
        }

        // If the data is empty and a place holder has been defined, use the placeholder as value.
        if ((typeof this.options.placeholder_text[fieldName] !== 'undefined'
          && this.options.placeholder_text[fieldName] != '') && data[fieldName] == '') {
          data[fieldName] = this.options.placeholder_text[fieldName];
        }
      }

      return data;
    },

    /**
     * {@inheritdoc}
     */
    getData: function () {
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
        baseUrl: this.options.base_root
      };

      // Extract form values regarding the required getData fields.
      data = this.extractFieldsValues(data);

      // The get data function can be called before the yoast analyser
      // has been instantiated (while instantiating this class by instance).
      if (this.yoast_analyser != null) {
        this.yoast_analyser.rawData = data;
      }

      return data;
    },

    /**
     * Replace tokens contained in a string by their values.
     * The token can be solved following two ways :
     * * Either on the page, if the token is relative to a page field ;
     * * Or remotely, by requesting the server ;
     *
     * @param value The string to process.
     * @returns {string}
     * @todo Can be moved in an util library.
     */
    tokenReplace: function (value) {
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
          if (typeof this.options.tokens[match[i]] != 'undefined') {
            var fieldName = this.options.tokens[match[i]],
              isRelativeField = this.options.fields[fieldName] != undefined;

            // If no field exist with the same token value, we consider it's a raw value.
            if (!isRelativeField) {
              tokenRawValue = true;
            }
            // Else, we know it's related to a field content.
            else {
              tokenRelativeField = this.options.tokens[match[i]];
            }
          }

          // If the token can be solved locally.
          if (tokenRelativeField != null) {
            // Replace the token with the relative field token value.
            var formItemView = Drupal.BackboneForm._formItemViews[this.options.fields[tokenRelativeField]];
            if (typeof formItemView !== 'undefined') {
              var tokenValue = this.tokenReplace(formItemView.value());
              value = value.replace(match[i], tokenValue);
            }
          }
          else if (tokenRawValue == true) {
            value = value.replace(match[i], this.options.tokens[match[i]]);
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
    },

    /**
     * refresh the snippet.
     */
    refreshSnippet: function () {
      this.yoast_analyser.reloadSnippetText();
    },

    /**
     * Refresh the anlysis
     */
    refreshAnalysis: function () {
      //if (typeof YoastSEO.app.snippetPreview === "undefined") {
      //  YoastSEO.app.init();
      //}
      this.yoast_analyser.refresh();
    },

    /**
     * Save Meta title and description in a cookies.
     * This is for reusing in the preview page.
     */
    saveCookie: function() {
      var data = this.getData(),
        dataToStore = {
        pageTitle: data.pageTitle,
        meta: data.meta,
        keyword: data.keyword,
        url: data.url
      };

      // Store meta title and meta description in a cookie.
      $.cookie.json = true;
      $.cookie(this.options.cookie_data_key, dataToStore, {json: true, path: '/' });
    }
  });

})(jQuery, Drupal);
