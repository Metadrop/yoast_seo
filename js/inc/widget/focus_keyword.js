var YoastSeo = YoastSeo || {};

/**
 * @file
 * Drupal Yoast SEO.
 *
 * @ignore
 */

(function ($, Drupal) {

  'use strict';

  /**
   * This component takes care of handling the focus keyword field.
   *
   * @type {YoastSeo.FocusKeyword}
   */
  YoastSeo.FocusKeyword = Backbone.View.extend({

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      this.options = options || {};
      var language = options.language || null,
        self = this;

      // Autocomplete for focus keyword field.
      // We use the google autocomplete api.
      $(this.el).autocomplete({
        source: function(request, response) {
          $.getJSON("http://suggestqueries.google.com/complete/search?callback=?", {
            hl: language,
            q: request.term,
            client: "youtube"
          }).done(function(data) {
            var suggestions = [];
            $.each(data[1], function(key, val) {
              suggestions.push({"value":val[0]});
            });
            suggestions.length = 5;
            response(suggestions);
          });
        },

        // When an item is selected, as no change event is triggered, do it manually.
        close: function() {
          var formItemView = Drupal.BackboneForm._formItemViews[self.$el.attr('id')];
          formItemView._change();
        }
      });
    }

  }, {});

})(jQuery, Drupal);
