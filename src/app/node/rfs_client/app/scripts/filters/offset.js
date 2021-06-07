'use strict';

/**
 * @ngdoc filter
 * @name rfsApp.filter:mylimitTo
 * @function
 * @description
 * # mylimitTo
 * Filter in the rfsApp.
 */
let angModule = require('../app.js');
angModule.angApp.filter('offset', function () {
  return function (input, start) {
    if (input) {
      start = parseInt(start);
      return input.slice(start);
    }
  };
});
