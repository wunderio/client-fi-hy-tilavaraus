(function ($) {
  if (!$) {
    return;
  }

  // Compatibility shims for legacy SelectWoo on jQuery 4.
  if (typeof $.isArray !== 'function') {
    $.isArray = Array.isArray;
  }

  if (typeof $.isFunction !== 'function') {
    $.isFunction = function (value) {
      return typeof value === 'function';
    };
  }

  if (typeof $.trim !== 'function') {
    $.trim = function (value) {
      if (value === null || value === undefined) {
        return '';
      }

      return String(value).trim();
    };
  }

  if (typeof $.camelCase !== 'function') {
    $.camelCase = function (value) {
      return String(value).replace(/^-ms-/, 'ms-').replace(/-([a-z])/g, function (_, letter) {
        return letter.toUpperCase();
      });
    };
  }
})(window.jQuery);

