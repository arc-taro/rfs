
let angModule = require("../app.js");

angModule.angApp.directive("dateTimePicker", function () {
  return {
    require: "?ngModel",
    restrict: "AE",
    scope: {
      pick12HourFormat: "@",
      useCurrent: "@",
      location: "@",
      format: "@"
    },
    link: function (scope, elem, attrs, ngModel) {
      $(elem).datetimepicker({
        pick12HourFormat: scope.pick12HourFormat,
        //        useCurrent: scope.useCurrent,
        locale: "ja",
        format: scope.format,
        showTodayButton: true,
        widgetPositioning: {
          vertical: "bottom"
        }
        //        allowInputToggle:true
      });

      $(elem).attr("placeholder", scope.format);

      $(elem).on("input", function () {
        if (moment($(elem).val(), scope.format, true).isValid()) {
          $(elem).data("DateTimePicker").date(moment($(elem).val(), scope.format));
          ngModel.$setViewValue($(elem).data("DateTimePicker").date().format(scope.format));
        }
      });
      $(elem).off("keydown");
      $(elem).on("keydown", function (e) {
        if (e.keyCode === 8) {
          $(elem).data("DateTimePicker").clear();
          event.preventDefault();
          return false;
        } else if (e.keyCode >= 48 && e.keyCode <= 57) {
          // 数字入力
          $(elem).val($(elem).val() + (e.keyCode - 48));
          if (moment($(elem).val(), scope.format, true).isValid()) {
            $(elem).data("DateTimePicker").date(moment($(elem).val(), scope.format));
          }
          event.preventDefault();
          return false;
        } else if (e.keyCode >= 96 && e.keyCode <= 105) {
          // 数字入力
          $(elem).val($(elem).val() + (e.keyCode - 96));
          if (moment($(elem).val(), scope.format, true).isValid()) {
            $(elem).data("DateTimePicker").date(moment($(elem).val(), scope.format));
          }
          event.preventDefault();
          return false;
        } else if (e.keyCode == 109) {
          // マイナス
          $(elem).val($(elem).val() + "-");
          event.preventDefault();
          return false;
        } else if (e.keyCode == 186) {
          // コロン
          $(elem).val($(elem).val() + ":");
          event.preventDefault();
          return false;
        } else if (e.keyCode == 32) {
          // スペース
          $(elem).val($(elem).val() + " ");
          event.preventDefault();
          return false;
        }
        return true;
      });

      $(elem).on("focus", function () {
        if (moment($(elem).val(), scope.format, true).isValid()) {
          $(elem).data("DateTimePicker").date(moment($(elem).val(), scope.format));
        } else if (!ngModel.$viewValue) {
          $(elem).data("DateTimePicker").clear();
        }
        return false;
      });

      // 親から子にバインドして
      scope.$parent.$watch(attrs.ngModel, function (value) {
        if (value && moment(value, scope.format).isValid()) {
          $(elem).data("DateTimePicker").date(moment(value, scope.format));
        }
      });

      //Local event change
      elem.on("blur", function () {
        // 表示しているものをmoment形に変更
        $(elem).data("DateTimePicker").date(moment($(elem).val(), scope.format));
        // 親のng-modelに値を渡す。
        if ($(elem).data("DateTimePicker").date() && $(elem).data("DateTimePicker").date().isValid()) {
          ngModel.$setViewValue($(elem).data("DateTimePicker").date().format(scope.format));
        } else {
          ngModel.$setViewValue(null);
        }
      });

      elem.on("mousewheel", function (e) {
        if (!$(e.target).is(":focus")) {
          return;
        }

        let delta = e.originalEvent.deltaY ? -e.originalEvent.deltaY : e.originalEvent.wheelDelta ? e.originalEvent.wheelDelta : -e.originalEvent.detail;
        let step = 0;
        if (delta < 0) {
          //下にスクロールした場合の処理
          step = +1;
        } else if (delta > 0) {
          //上にスクロールした場合の処理
          step = -1;
        }
        if (e.preventDefault) {
          e.preventDefault();
        } else {
          e.returnValue = false;
        }

        let now = $(elem).data("DateTimePicker").date();
        if (scope.format.indexOf("mm") > 0) {
          // 分を変更
          now.add(step, "minutes");
        } else {
          // 日にちを変更
          now.add(step, "days");
        }
        $(elem).data("DateTimePicker").date(now);
      });
    },

  };
});
