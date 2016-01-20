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
     * Sets the SEO score in both the hidden input and the rating element.
     *
     * @param score
     */
    setScore: function (score) {
      this.score = score;
      var rate = YoastSeo.model.Status.scoreRating(score);

      // Update score text in the score box.
      $('.score_value', '#' + this.options.score_element_selector).text(rate);

      // Update score in the score field.
      $('[data-drupal-selector="' + this.options.seo_status + '"]')
        .attr('value', score)
        .val(score);
    }

  }, {});

})(jQuery, Drupal);
