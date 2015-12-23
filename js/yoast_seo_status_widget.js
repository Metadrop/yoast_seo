/**
 * @file
 * Drupal Yoast SEO status widget.
 *
 * This component takes care of displaying the Yoast SEO score computed for
 * a content.
 *
 * It requires the Yoast SEO text analysis library to be loaded.
 *
 * @see \Drupal\yoast_seo\Plugin\Field\FieldWidget\YoastSeoWidget
 *
 * @ignore
 */

YoastSeoStatusWidget = function (args) {
  this.config = args.settings;
}

/**
 * retuns a string that is used as a CSSclass, based on the numeric score
 * @param score
 * @returns output
 */
YoastSeoStatusWidget.prototype.scoreRating = function (score) {
  var scoreRate;

  switch (score) {
    case 0:
      scoreRate = Drupal.t('na');
      break;
    case 4:
    case 5:
      scoreRate = Drupal.t('poor');
      break;
    case 6:
    case 7:
      scoreRate = Drupal.t('ok');
      break;
    case 8:
    case 9:
    case 10:
      scoreRate = Drupal.t('good');
      break;
    default:
      scoreRate = Drupal.t('bad');
      break;
  }

  return scoreRate;
};

/**
 * Sets the SEO score in both the hidden input and the rating element.
 * @param score
 */
YoastSeoStatusWidget.prototype.saveScores = function (score) {
  // Update score text in the score box.
  jQuery('.score_value', '#' + this.config.targets.overall_score_target_id).text(this.scoreRating(score));
  // Update score in the score field.
  jQuery('[data-drupal-selector="' + this.config.fields.seo_status + '"]')
    .attr('value', score)
    .val(score);
};
