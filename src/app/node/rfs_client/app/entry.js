"use strict";

// エントリーポイント
// javascript library
let angular = require("angular");
require("jquery-ui/ui/widgets/sortable");
require("jquery-ui/ui/widgets/droppable");
require("jquery-ui/ui/widgets/draggable");
require("bootstrap");
require("angular-cookies");
require("angularjs-dropdown-multiselect");
require("angular-route");
require("angular-animate");
require("angular-resource");
require("angular-touch");
require("angular-sanitize");
require("angular-ui-bootstrap");
require("ngstorage");
require("eonasdan-bootstrap-datetimepicker");
require("./lib/jquery-confirm2/dist/jquery-confirm.min.js");
require("./lib/ng-flow/ng-flow-standalone.js");
require("./lib/fusty-flow.js/src/fusty-flow.js");
require("./lib/fusty-flow.js/src/fusty-flow-factory.js");
require("./lib/allmighty-autocomplete/script/autocomplete.js");
require("./lib/allmighty-autocomplete_cstm/script/autocompletecstm.js");
//require("./lib/grid/src/Grid.js");
require("./lib/angular-i18n/angular-locale_ja-jp.js");
require("exif-js");
require("./lib/jquery-ui/jquery-ui.min.js");
require("angular-dragdrop");

//<script src="bower_components/videojs/dist/video-js/video.js"></script>
//  <script src="bower_components/flat-ui/dist/js/flat-ui.js"></script>
//          <script src="bower_components/jquery-confirm2/dist/jquery-confirm.min.js"></script>
//            <script src="bower_components/lodash/dist/lodash.compat.js"></script>



// angular module
require("./scripts/controllers/main.js");
require("./scripts/controllers/about.js");
require("./scripts/controllers/tenken.js");
require("./scripts/controllers/top.js");
require("./scripts/controllers/shisetsu_edit.js");
require("./scripts/controllers/tenken_rireki.js");
require("./scripts/controllers/genba_syashin.js");
require("./scripts/controllers/shisetsu_map.js");
require("./scripts/controllers/bssk_children.js");
require("./scripts/controllers/sys_top.js");
require("./scripts/controllers/raf_top.js");
require("./scripts/controllers/fam_main.js");
require("./scripts/controllers/fam_edit.js");
require("./scripts/controllers/raf_target.js");
require("./scripts/controllers/gdh_main.js");
require("./scripts/controllers/kenzen_sum.js");
require("./scripts/controllers/modal_gdh_photo.js");
require("./scripts/controllers/modal_genba_syasin.js");
require("./scripts/filters/offset.js");
require("./scripts/filters/wareki.js");
require("./scripts/directives/nextfocus.js");
require("./scripts/directives/md-checkbox.js");
require("./scripts/directives/date-time-picker.js");
require("./scripts/controllers/snow_removal.js");
require("./scripts/controllers/csv_list.js");
require("./scripts/controllers/tenken_keikaku.js");
