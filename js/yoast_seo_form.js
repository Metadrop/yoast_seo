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
     *
     * @param el
     *
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
     *
     * @param el_wrapper The HTMLElement to plug the FormItem view element on.
     * @param options Options to pass to the form item view constructor.
     *
     * @returns {Backbone.View}
     */
    getFormItemView: function (el_wrapper, options) {
      // Get the FormItem view class based on the HTMLElement wrapper.
      var FormItemViewClass = YoastSeoForm.getFormItemClass(el_wrapper),
      // The HTMLElement to bind the FieldItem view onto.
        el = null,
      // The form item view.
        formItem = null,
      // The options to pass to the form item class.
        options = options || {};

      // Based on the FormItem view tag, retrieve the HTMLElement to bind the View onto.
      el = $(FormItemViewClass.tag, el_wrapper);
      options.el = el;

      // Instantiate the form item view.
      formItem = new FormItemViewClass(options);

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
   *
   * @type {YoastSeoForm.views.FormItem}
   */
  YoastSeoForm.views.FormItem = Backbone.View.extend({
    /**
     * {@inheritdoc}
     */
    events: {
      'input': '_onInput',
      'focus': '_onFocus',
      'blur': '_onBlur'
    },

    /**
     * The FormItem view callbacks.
     */
    callbacks: {
      // When the value has changed.
      changed: null,
      // When the component has the focus.
      focused: null,
      // When the component has blured.
      blured: null
    },

    /**
     * The value before it has been changed.
     */
    _beforeChangeValue: null,

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      var options = options || {};

      // Callbacks have been given in options.
      if (typeof options.callbacks != 'undefined') {
        this.callbacks = options.callbacks;
      }
    },

    /**
     * Listen to the input event.
     *
     * @param evt
     */
    _onInput: function () {
      this._change();
    },

    /**
     * Listen to the focus event.
     *
     * @param evt
     */
    _onFocus: function () {
      this._focus();
    },

    /**
     * Listen to the blur event.
     *
     * @param evt
     */
    _onBlur: function () {
      this._blur();
    },

    /**
     * This function is internally called when the component value changed.
     *
     * @param evt
     * @param val
     */
    _change: function () {
      var value = this.value();
      if (typeof this.callbacks.changed == 'function'
        && value != this._beforeChangeValue) {
        this.callbacks.changed(value);
        this._beforeChangeValue = value;
      }
    },

    /**
     * This function is internally called when the component get the focus.
     *
     * @param evt
     */
    _focus: function () {
      if (typeof this.callbacks.focused == 'function') {
        this.callbacks.focused();
      }
    },

    /**
     * This function is internally called when the component has blured.
     *
     * @param evt
     */
    _blur: function () {
      if (typeof this.callbacks.blured == 'function') {
        this.callbacks.blured();
      }
    },

    /**
     * Get/Set the value of the form item view component.
     *
     * @param val (optional) set the value of the form item view.
     *
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
   *
   * @type {YoastSeoForm.views.Textfield}
   */
  YoastSeoForm.views.Textfield = YoastSeoForm.views.FormItem.extend({}, {
    tag: 'input'
  });

  /**
   * FormItem view that has for aim to control html element which are editable form item.
   *
   * @type {YoastSeoForm.views.ContentEditableHtmlElement}
   */
  YoastSeoForm.views.ContentEditableHtmlElement = YoastSeoForm.views.FormItem.extend({
    /**
     * {@inheritdoc}
     */
    events: {
      'focus': '_onFocus',
      'blur': '_onBlur',
      // Parent component use the input event, but this event is not supported on IE
      // for contenteditable elements.
      'keyup': '_onKeyup',
      'paste': '_onPaste'
    },

    /**
     * {@inheritdoc}
     */
    _onKeyup: function (evt) {
      this._change();
    },

    /**
     * This function is internally called when the component catch a paste event.
     *
     * @param evt
     */
    _onPaste: function (evt) {
      var self = this;
      setTimeout(function() {
        self._change();
      }, 0);
    },

    /**
     * {@inheritdoc}
     */
    value: function (val) {
      // No value is provided.
      // Get the value of the component.
      if (typeof val == 'undefined') {
        return this.$el.html();
      }
      // A value is provided.
      // Set the value of the component.
      else {
        this.$el.html(val);
      }
    }
  }, {
    // Can be any editable HTMLElement.
    tag: 'span'
  });

  /**
   * FormItem view that has for aim to control snippet element which are content editable form item.
   *
   * @type {YoastSeoForm.views.SnippetEditableHtmlElement}
   */
  YoastSeoForm.views.SnippetElement = YoastSeoForm.views.ContentEditableHtmlElement.extend({
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

  /**
   * FormItem view that has for aim to control textfield form item.
   *
   * @type {YoastSeoForm.views.Textarea}
   */
  YoastSeoForm.views.Textarea = YoastSeoForm.views.FormItem.extend({}, {
    tag: 'textarea'
  });

  /**
   * FormItem view that has for aim to control textarea ckeditor form item.
   *
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
    initialize: function (options) {
      var self = this;

      // @todo find a way to call super in Backbone.
      var options = options || {};

      // Callbacks have been given in options.
      if (typeof options.callbacks != 'undefined') {
        this.callbacks = options.callbacks;
      }

      // Listen to any change on the CKEDITOR component.
      Drupal.editors.ckeditor.onChange(this.el, function (val) {
        self.$el.val(val);
        self._change();
      });
    }
  }, {
    tag: 'textarea'
  });

  Drupal.YoastSeoForm = YoastSeoForm;

})(jQuery, Drupal);
