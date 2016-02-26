var YoastSeo = YoastSeo || {};
YoastSeo.model = YoastSeo.model || {};

/**
 * @file
 * Drupal Yoast SEO analyser model class.
 *
 * @ignore
 */

(function ($, Drupal) {

  "use strict";

  YoastSeo.model.Status = Backbone.Model.extend({}, {

    /**
     * Returns a string that is used as a CSS class, based on the numeric score.
     *
     * @param score
     * @returns output
     */
    scoreRating: function (score) {
      var rules = YoastSeo.model.Status.score_status,
        def = rules['default'];
      delete rules['default'];

      for (var i in rules) {
        if (score <= parseInt(i)) {
          return rules[i];
        }
      }

      return def;
    }

  });

})(jQuery, Drupal);
