/**
 * @file
 * Keeps removed equipment values out of the next room search.
 *
 * Drupal's Views AJAX URL is built from the current address, then the form
 * values are added on top. The cleanup only looks for <input> elements and
 * for the exact parameter name, so a multi-select named field_equipment[]
 * never matches field_equipment[0], field_equipment[1], and so on.
 * Deleted equipment therefore stays in the request, comes back as a chip,
 * and is included in the results.
 *
 * @see https://www.drupal.org/project/drupal/issues/3273334
 * @see https://www.drupal.org/project/drupal/issues/3625942
 */

(function ($, Drupal) {
  /**
   * Field name without a trailing [] or [0] suffix.
   *
   * @param {string} name
   *   Raw form control or query parameter name.
   *
   * @return {string}
   *   The base name.
   */
  function exposedFilterBaseName(name) {
    return String(name).replace(/\[.*$/, '');
  }

  /**
   * Decodes a query parameter name.
   *
   * @param {string} name
   *   Possibly percent-encoded parameter name.
   *
   * @return {string}
   *   Decoded name, or the original string when it is not valid encoding.
   */
  function decodeQueryName(name) {
    try {
      return decodeURIComponent(name.replace(/\+/g, ' '));
    }
    catch (e) {
      return name;
    }
  }

  /**
   * Base names of every named control in an exposed form.
   *
   * @param {jQuery} $form
   *   The exposed filter form.
   *
   * @return {Set<string>}
   *   Base names such as "field_equipment".
   */
  function exposedFilterBaseNames($form) {
    const names = new Set();
    if (!$form || !$form.length) {
      return names;
    }
    $form.find('[name]').each(function () {
      if (this.name) {
        names.add(exposedFilterBaseName(this.name));
      }
    });
    return names;
  }

  /**
   * Drops exposed-filter parameters so the form can submit the current values.
   *
   * @param {string} url
   *   Views AJAX URL, including any leftover query string.
   * @param {Set<string>} baseNames
   *   Base names of the exposed form controls.
   *
   * @return {string}
   *   The same URL without those parameters.
   */
  function cleanExposedFilterUrl(url, baseNames) {
    const question = url.indexOf('?');
    if (question === -1 || !baseNames.size) {
      return url;
    }

    const path = url.slice(0, question);
    const kept = url.slice(question + 1).split('&').filter((param) => {
      if (!param) {
        return false;
      }
      const baseName = exposedFilterBaseName(decodeQueryName(param.split('=')[0]));
      return !baseNames.has(baseName);
    });

    return kept.length ? `${path}?${kept.join('&')}` : path;
  }

  Drupal.uhSpace = Drupal.uhSpace || {};
  Drupal.uhSpace.exposedFilterBaseName = exposedFilterBaseName;
  Drupal.uhSpace.cleanExposedFilterUrl = cleanExposedFilterUrl;

  const OriginalAjaxView = Drupal.views && Drupal.views.ajaxView;
  if (typeof OriginalAjaxView !== 'function' || OriginalAjaxView.uhSpaceExposedFilters) {
    return;
  }

  /**
   * Views AJAX constructor that forgets stale multi-select filter values.
   *
   * @param {object} settings
   *   Settings for this view.
   */
  function AjaxView(settings) {
    OriginalAjaxView.call(this, settings);

    const baseNames = exposedFilterBaseNames(this.$exposed_form);
    if (!baseNames.size) {
      return;
    }

    // Only the exposed-form request should drop the address-bar filters.
    // Pager links already carry the active filters in their own URL, and
    // cleaning those URLs would load the next page with no equipment filter.
    (this.exposedFormAjax || []).forEach((instance) => {
      if (!instance || !instance.options || !instance.options.url) {
        return;
      }
      instance.options.url = cleanExposedFilterUrl(instance.options.url, baseNames);
    });
  }

  AjaxView.prototype = OriginalAjaxView.prototype;
  AjaxView.uhSpaceExposedFilters = true;
  Drupal.views.ajaxView = AjaxView;
})(jQuery, Drupal);
