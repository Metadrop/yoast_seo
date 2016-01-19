var YoastSeo = YoastSeo || {};

/**
 * @file
 * Drupal Yoast SEO analyser model class.
 *
 * @ignore
 */

(function ($, Drupal) {

  "use strict";

  YoastSeo.Analyser = Backbone.Model.extend({

    /**
     * The instance of the Yoast analyser.
     */
    yoast_analyser: null,

    /**
     * The model default options.
     */
    default_options: {
      analyser: {
        snippetPreview: null,
        elementTarget: [],
        typeDelay: 300,
        typeDelayStep: 100,
        maxTypeDelay: 1500,
        dynamicDelay: true,
        multiKeyword: false,
        snippetFields: {
          title: "snippet_title",
          url: "snippet_cite",
          meta: "snippet_meta"
        },
        targets: {
          output: null,
          overall: null,
          snippet: null
        },
        sampleText: {
          url: '',
          title: '',
          keyword: '',
          meta: '',
          text: ''
        }
      },
      //fields: {
      //  keyword: null,
      //  title: null,
      //  nodeTitle: null,
      //  meta: null,
      //  text: null,
      //  url: null
      //},
      //placeholderText: {
      //  title: 'Please click here and alter your page title.',
      //  description: 'Please click here and alter your page description.',
      //  url: 'Please click here and alter your page url.'
      //},
      SEOTitleOverwritten: null,
      scoreElement: null,
      baseRoot: '/'
    },

    // Analyser constructor.
    initialize: function (options) {
      this.options = $.extend(true, {}, this.default_options, options);

      // Declaring the callback functions required by the Yoast SEO analyser.
      this.options.analyser.callbacks = {
        getData: this.getData.bind(this),
        getAnalyzerInput: this.getAnalyzerInput.bind(this),
        bindElementEvents: this.bindElementEvents.bind(this),
        updateSnippetValues: this.updateSnippetValues.bind(this),
        saveScores: this.saveScores.bind(this)
      };

      // Make it global.
      this.yoast_analyser = new YoastSEO.App(this.options.analyser);
    },

    /**
     * Return an object fulfilling the Yoast SEO library getData callback requirements.
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
        baseUrl: ''
      };
      return data;
    },

    /**
     * @callback YoastSEO.App~getAnalyzerInput
     */
    getAnalyzerInput: function () {
      // If needed implement your logic in an inherited class.
    },

    /**
     * Calls the eventbinders.
     * We don't need it.
     *
     * @callback YoastSEO.App~bindElementEvents
     */
    bindElementEvents: function () {
      // If needed implement your logic in an inherited class.
    },

    /**
     * Updates the snippet values.
     * We don't need it.
     *
     * @callback YoastSEO.App~updateSnippetValues
     *
     * @param {Object} ev
     */
    updateSnippetValues: function () {
      // If needed implement your logic in an inherited class.
    },

    /**
     * Score has been calculated callback.
     *
     * @callback YoastSEO.App~saveScores
     */
    saveScores: function (score) {
      if (this.options.callback.saveScores != null) {
        this.options.callback.saveScores(score);
      }
    }

  });

})(jQuery, Drupal);
