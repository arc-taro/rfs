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
angModule.angApp.filter('wareki', function () {
  return function (input, format) {
    // 日付がない場合はハイフン返却
    if (input == null || input == '') {
      return "-";
    }

    // 年を取得
    var y = new Date(input).getFullYear();
    var m = new Date(input).getMonth() + 1;
    var d = new Date(input).getDate();

    var s;
    if (y > 1988) {
      s = "H" + (y - 1988);
    } else if (y > 1925) {
      s = "S" + (y - 1925);
    } else if (y > 1911) {
      s = "T" + (y - 1911);
    } else if (y > 1867) {
      s = "M" + (y - 1867);
    }
    //return moment(input).format(format.replace("WWWW", s));
    return s;
  };
});
