var YoastSeo = YoastSeo || {};
YoastSeo.form = YoastSeo.form || {};

/**
 * @file
 * Drupal Yoast SEO form utility.
 *
 * This library will help developers to interacts with drupal form
 * on client side.
 *
 * @ignore
 */

(function ($, Drupal) {

  'use strict';

  /**
   * FormItem view that has for aim to control snippet element which are content editable form item.
   *
   * @type {YoastSeo.form.SnippetElement}
   */
  YoastSeo.form.SnippetElement = Drupal.BackboneForm.views.ContentEditableHtmlElement.extend({
    /**
     * {@inheritdoc}
     */
    events: {
      'focus': '_onFocus',
      'blur': '_onBlur',
      'keyup': '_onKeyup',
      'keypress': '_onKeypress',
      'paste': '_onPaste'
    },

    /**
     * {@inheritdoc}
     */
    _onKeypress: function (evt) {
      // The user can't press enter on the snippet fields.
      if (evt.keyCode == 13) {
        evt.preventDefault();
        evt.stopImmediatePropagation();
        return;
      }
    }

  }, {
    // Can be any editable HTMLElement.
    tag: 'span'
  });

})(jQuery, Drupal);
