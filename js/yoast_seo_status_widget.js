'use strict';
var YoastSeo = YoastSeo || {};

/**
 * @file
 * Drupal Yoast SEO.
 *
 * @ignore
 */

(function ($, Drupal) {

  /**
   * This component takes care of displaying the Yoast SEO score computed for
   * a content.
   *
   * @type {YoastSeo.Status}
   */
  YoastSeo.Status = Backbone.View.extend({

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      var options = options || {};
      this.config = options.settings;

      if (typeof options.score_element_selector == 'undefined') {
        throw 'The options.score_element_selector should not be undefined.';
      }
      else if ($('#' + options.score_element_selector).length == 0) {
        throw 'No HTMLElement found with the given selector options.score_element_selector (' + options.score_element_selector + ').';
      }

      this.options = options;
    },

    /**
     * Returns a string that is used as a CSS class, based on the numeric score.
     *
     * @param score
     * @returns output
     */
    scoreRating: function (score) {
      var rules = this.options.score_status,
        def = rules['default'];
      delete rules['default'];

      var i = 0;

      for (i in rules) {
        if (score <= parseInt(i)) {
          return rules[i];
        }
      }

      return def;
    },

    /**
     * Sets the SEO score in both the hidden input and the rating element.
     *
     * @param score
     */
    setScore: function (score) {
      this.score = score;

      // Update score text in the score box.
      jQuery('.score_value', '#' + this.options.score_element_selector).text(this.scoreRating(score));

      // Update score in the score field.
      jQuery('[data-drupal-selector="' + this.options.seo_status + '"]')
        .attr('value', score)
        .val(score);
    }

  }, {});

})(jQuery, Drupal);
