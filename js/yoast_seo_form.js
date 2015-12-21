/**
 * @file
 * Drupal Yoast SEO form utility.
 *
 * This library will help developers to interacts with drupal form
 * on client side
 *
 * @ignore
 */

(function ($, Drupal) {

  'use strict';

  /**
   * @namespace
   */
  var YoastSeoForm = {

    /**
     * Form item views store.
     */
    _formItemViews: {},

    /**
     * Based on a form item HTMLElement wrapper, get the FormItem view class
     * to use to control the form item HTMLElement field.
     * @param el
     * @returns {function}
     */
    getFormItemClass: function (el_wrapper) {
      var field_item_class = YoastSeoForm.views.Textfield;
      var field_types_map = {
        'js-form-type-textfield': 'Textfield',
        'js-form-type-textarea': 'Textarea'
      };

      // If the element carries a CKEDITOR.
      var $textarea = $('textarea', $(el_wrapper));
      if ($textarea.length && CKEDITOR.dom.element.get($textarea[0]).getEditor()) {
        field_item_class = YoastSeoForm.views.Ckeditor;
      }
      else {
        // Else define the FormItem class regarding the element wrapper classes.
        for (var field_type in field_types_map) {
          if ($(el_wrapper).hasClass(field_type)) {
            field_item_class = YoastSeoForm.views[field_types_map[field_type]];
          }
        }
      }

      return field_item_class;
    },

    /**
     * Factory to instantiate or retrieve a Form Item view based on a HTMLElement
     * wrapper.
     * @param el_wrapper
     * @returns {Backbone.View}
     */
    getFormItemView: function (el_wrapper) {
      // Get the FormItem view class based on the HTMLElement wrapper.
      var FormItemViewClass = YoastSeoForm.getFormItemClass(el_wrapper),
      // The HTMLElement to bind the FieldItem view onto.
        el = null,
      // The form item view.
        formItem = null;

      // Based on the FormItem view tag, retrieve the HTMLElement to bind the View onto.
      el = $(FormItemViewClass.tag, el_wrapper);

      // Instantiate the form item view.
      formItem = new FormItemViewClass({
        el: el
      });

      // If the element has an idea, store it.
      var id = el.attr('id');
      if (id) {
        YoastSeoForm._formItemViews[id] = formItem;
      }

      // Instantiate the FormItem view.
      return formItem;
    }
  };

  /**
   * @namespace
   */
  YoastSeoForm.views = {};

  /**
   * Abstract class (kind of) which as for aim to control Drupal Form Item field.
   * @type {YoastSeoForm.views.FormItem}
   */
  YoastSeoForm.views.FormItem = Backbone.View.extend({
    /**
     * {@inheritdoc}
     */
    events: {
      'input': 'onInput'
    },

    /**
     * {@inheritdoc}
     */
    initialize: function () {
      // Initialize your component.
    },

    /**
     * Listen to the input event.
     * @param evt
     */
    onInput: function (evt) {
      this.change(evt, $(this.el).val());
    },

    /**
     *
     * @param val
     */
    change: function (evt, val) {
      this.$el.trigger('yoast_seo-form_item-changed')
    },

    /**
     * Get/Set the value of the form item view component.
     * @param val (optional) set the value of the form item view.
     * @return The value of the component if getter or void if setter.
     */
    value: function (val) {
      // No value is provided.
      // Get the value of the component.
      if (typeof val == 'undefined') {
        return this.$el.val();
      }
      // A value is provided.
      // Set the value of the component.
      else {
        this.$el.val(val);
      }
    }
  }, {
    /**
     * The tag of the HTMLElement that carries the form item field.
     */
    tag: 'input'
  });

  /**
   * FormItem view that has for aim to control textfield form item.
   * @type {YoastSeoForm.views.Textfield}
   */
  YoastSeoForm.views.Textfield = YoastSeoForm.views.FormItem.extend({}, {
    tag: 'input'
  });

  /**
   * FormItem view that has for aim to control textfield form item.
   * @type {YoastSeoForm.views.Textarea}
   */
  YoastSeoForm.views.Textarea = YoastSeoForm.views.FormItem.extend({}, {
    tag: 'textarea'
  });

  /**
   * FormItem view that has for aim to control textarea ckeditor form item.
   * @type {YoastSeoForm.views.Ckeditor}
   */
  YoastSeoForm.views.Ckeditor = YoastSeoForm.views.Textarea.extend({
    /**
     * {@inheritdoc}
     */
    events: {},

    /**
     * {@inheritdoc}
     */
    initialize: function () {
      var self = this;
      // Listen to any change on the CKEDITOR component.
      Drupal.editors.ckeditor.onChange(this.el, function (val) {
        self.change(val);
      });
    }
  }, {
    tag: 'textarea'
  });

  Drupal.behaviors.yoastSeoForm = {
    attach: function (context) {
      var $context = $(context);
    }
  };

  Drupal.YoastSeoForm = YoastSeoForm;

})(jQuery, Drupal);
