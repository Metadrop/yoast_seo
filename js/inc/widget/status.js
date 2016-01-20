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
      this.options = options;
    },

    /**
     * Sets the SEO score in both the hidden input and the rating element.
     *
     * @param score
     */
    setScore: function (score) {
      this.score = score;
      var rate = YoastSeo.model.Status.scoreRating(score),
        yoast_settings = drupalSettings.yoast_seo;

      // Update score text in the score box.
      $('.score_value', this.$el).text(rate);

      // Update score in the score field.
      $('[data-drupal-selector="' + yoast_settings.fields.seo_status + '"]')
        .attr('value', score)
        .val(score);
    }

  }, {});

})(jQuery, Drupal);
