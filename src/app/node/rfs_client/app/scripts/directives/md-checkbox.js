
let angModule = require("../app.js");

angModule.angApp.directive("mdCheckbox", function () {
  return {
    template: "<div class='md-checkbox'><input id='{{id}}' type='checkbox' ng-model='model' ng-disabled='disabled' ng-true-value='{{t_value}}' ng-false-value='{{f_value}}' ng-change='abc()' ng-click='click()'> <label for='{{id}}' class='{{labelClass}}' > <div ng-transclude> </div> </label></div>",
    restrict: "EA",
    transclude: true,
    require: "?ngModel",
    scope: {
      disabled: "=ngDisabled",
      labelClass: "@",
      t_value: "@ngTrueValue",
      f_value: "@ngFalseValue",
      change: "&ngChange",
      click: "&ngClick"
    },
    link: function (scope, element, attrs, ngModelCtrl) {
      scope.id = new Date().getTime().toString(16) + Math.floor(Math.random() * 1000000).toString(16);

      if (typeof scope.t_value === "undefined") {
        scope.t_value = true;
      }
      if (typeof scope.f_value === "undefined") {
        scope.f_value = false;
      }

      scope.$watch("model", function (value) {
        ngModelCtrl.$setViewValue(value);
      });

      scope.$parent.$watch(attrs.ngModel, function (value) {
        scope.model = value;
      });

      if (!scope.labelClass) {
        scope.labelClass = "";
      }

    },

  };
});
