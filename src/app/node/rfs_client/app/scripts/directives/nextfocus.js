'use strict';

/**
 * @ngdoc directive
 * @name rfsApp.directive:nextFocus
 * @description
 * # nextFocus
 */
let angModule = require('../app.js');
angModule.angApp.directive('nextFocus', function () {
  return {
    restrict: 'A',
    link: function ($scope, elem, attrs) {
      elem.bind('keydown', function (e) {
        var code = e.keyCode || e.which;
        if (code === 13) {
          event.preventDefault();
          // 次のinputを探す（disabledは除く)
          var fields = $(this).parents('form:eq(0),body').find('input, textarea, select').not(':disabled');
          var index = fields.index(this);
          if (index > -1 && (index + 1) < fields.length)
            fields.eq(index + 1).focus();
        }
      });
    }
  };
});
