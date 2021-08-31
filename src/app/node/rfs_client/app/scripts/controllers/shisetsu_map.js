"use strict";

/**
 * @ngdoc function
 * @name rfsApp.controller:AboutCtrl
 * @description
 * # AboutCtrl
 * Controller of the rfsApp
 */

/**
 * この画面では、検索された施設の
 * 全ての健全性が確認できる画面を表示します
 * 検索されているため、すでに施設を特定するIDと
 * 最新の点検管理番号、履歴番号を渡してもらうことで
 * ここでの機能は指定された情報を表示することに特化します
 */
class ShisetsuMapCtrl {
  constructor($scope, $uibModalInstance, $http, data) {
    this.$uibModalInstance = $uibModalInstance;
    this.$scope = $scope;
    this.$http = $http;

    this.map = null;
    this.projection3857 = new OpenLayers.Projection("EPSG:3857");
    this.projection4326 = new OpenLayers.Projection("EPSG:4326");
    this.vectorLayer = null;
    this.feature = null;
    this.data = angular.copy(data);

    setTimeout(() => {
      this.initMap();
    }, 1000);
  }

  close() {
    this.$uibModalInstance.close(null);
  }

  save() {
    this.$uibModalInstance.close(this.data);
  }

  initMap() {
    if (this.map) {
      return;
    }

    this.map = new OpenLayers.Map({
      div: "modal_map",
      projection: this.projection3857,
      displayProjection: this.projection4326
    });

    this.map.addLayer(
      new OpenLayers.Layer.XYZ(
        "標準地図",
        "https://cyberjapandata.gsi.go.jp/xyz/std/${z}/${x}/${y}.png",
        {
          attribution:
            "<div style='text-align:right'><a href='https://www.gsi.go.jp/kikakuchousei/kikakuchousei40182.html' target='_blank'>国土地理院</a></div>",
          numZoomLevels: 19
        }
      )
    );

    this.map.isValidZoomLevel = function(zoomLevel) {
      return (
        zoomLevel != null &&
        zoomLevel >= 7 && // set min level here, could read from property
        zoomLevel < this.getNumZoomLevels()
      );
    };

    // VectorLayerの初期化
    this.vectorLayer = new OpenLayers.Layer.Vector("vector layer");
    this.vectorLayer.setVisibility(true);
    this.map.addLayers([this.vectorLayer]);

    //        var lon = this.data.syucchoujoLon;
    //        var lat = this.data.syucchoujoLat;

    // 施設の座標が設定されている場合
    if (
      this.data.lon &&
      this.data.lat &&
      this.data.lon != 0 && this.data.lat != 0
    ) {
      this.setPoint(this.data.lon, this.data.lat);
      //            lon = this.data.lon;
      //            lat = this.data.lat;

      this.map.setCenter(
        new OpenLayers.LonLat(this.data.lon, this.data.lat).transform(
          this.projection4326,
          this.projection3857
        ),
        16
      );
    } else {
      var left;
      var bottom;
      var right;
      var top;

      // 路線情報がある場合は路線情報を、路線情報が無い場合は出張所の範囲を表示する
      left = this.data.syucchoujo.lt_lon;
      bottom = this.data.syucchoujo.lt_lat;
      right = this.data.syucchoujo.rb_lon;
      top = this.data.syucchoujo.rb_lat;

      if (this.data.rrow) {
        if (
          this.data.rrow.ext1x &&
          this.data.rrow.ext2y &&
          this.data.rrow.ext2x &&
          this.data.rrow.ext1y
        ) {
          left = this.data.rrow.ext1x;
          bottom = this.data.rrow.ext2y;
          right = this.data.rrow.ext2x;
          top = this.data.rrow.ext1y;
        }
      }

      this.map.zoomToExtent(
        new OpenLayers.Bounds(left, bottom, right, top).transform(
          this.projection4326,
          this.projection3857
        )
      );
    }

    // クリックイベントのコントローラ定義
    OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
      defaultHandlerOptions: {
        single: true,
        double: false,
        pixelTolerance: 0,
        stopSingle: false,
        stopDouble: false
      },

      initialize: function(options) {
        this.handlerOptions = OpenLayers.Util.extend(
          {},
          this.defaultHandlerOptions
        );
        OpenLayers.Control.prototype.initialize.apply(this, arguments);
        this.handler = new OpenLayers.Handler.Click(
          this,
          {
            click: this.trigger
          },
          this.handlerOptions
        );
      },

      trigger: e => {
        var lonlat = this.map.getLonLatFromPixel(e.xy);
        lonlat.transform(this.projection3857, this.projection4326);
        this.setPoint(lonlat.lon, lonlat.lat);
        //          alert("You clicked near " + lonlat.lat + " N, " +
        //                + lonlat.lon + " E");
      }
    });

    this.map.addControl(new OpenLayers.Control.MousePosition());
    if (this.data.editable) {
      this.control = new OpenLayers.Control.Click();
      this.map.addControl(this.control);
      this.control.activate();
    }
    // スケールラインのコントローラ定義
    this.scaleLineControl = new OpenLayers.Control.ScaleLine({
      maxWidth: 150,
      bottomOutUnits: "",
      bottomInUnits: "",
      geodesic: true
    });
    this.map.addControls([this.scaleLineControl]);
    this.scaleLineControl.activate();
  }

  setPoint(lon, lat) {
    // 丸め
    if (lon) {
      lon = Math.round(lon * 1000000000) / 1000000000;
    }
    if (lat) {
      lat = Math.round(lat * 1000000000) / 1000000000;
    }

    this.data.lon = lon;
    this.data.lat = lat;
    if (!this.feature) {
      this.feature = new OpenLayers.Feature.Vector(
        new OpenLayers.Geometry.Point(this.data.lon, this.data.lat).transform(
          this.projection4326,
          this.projection3857
        )
      );
      this.feature.style = {
        fillColor: "#669933",
        fillOpacity: 1,
        strokeColor: "#aaee77",
        strokeWidth: 3,
        pointRadius: 8,
        graphicWidth: 32,
        graphicHeight: 32
      };

      //      if (this.data.shisetsu_kbn_row) {
      if (this.data.shisetsu_kbn == 1) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/dh_1.gif`;
      } else if (this.data.shisetsu_kbn == 2) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/dj_1.gif`;
      } else if (this.data.shisetsu_kbn == 3) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/ss_1.gif`;
      } else if (this.data.shisetsu_kbn == 4) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/bs_1.gif`;
      } else if (this.data.shisetsu_kbn == 5) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/yh_1.gif`;
      } else if (this.data.shisetsu_kbn == 6) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/ka_1.gif`;
      } else if (this.data.shisetsu_kbn == 7) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/kb_1.gif`;
      } else if (this.data.shisetsu_kbn == 8) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/kc_1.gif`;
      } else if (this.data.shisetsu_kbn == 9) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/kd_1.gif`;
      } else if (this.data.shisetsu_kbn == 10) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/ki_1.gif`;
      } else if (this.data.shisetsu_kbn == 11) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/jh_1.gif`;
      } else if (this.data.shisetsu_kbn == 12) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/sd_1.gif`;
      } else if (this.data.shisetsu_kbn == 13) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/dt_1.gif`;
      } else if (this.data.shisetsu_kbn == 14) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/tt_1.gif`;
      } else if (this.data.shisetsu_kbn == 15) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/ck_1.gif`;
      } else if (this.data.shisetsu_kbn == 16) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/sk_1.gif`;
      } else if (this.data.shisetsu_kbn == 17) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/bh_1.gif`;
      } else if (this.data.shisetsu_kbn == 18) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/dy_1.gif`;
      } else if (this.data.shisetsu_kbn == 19) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/dn_1.gif`;
      } else if (this.data.shisetsu_kbn == 20) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/ts_1.gif`;
      } else if (this.data.shisetsu_kbn == 21) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/rh_1.gif`;
      } else if (this.data.shisetsu_kbn == 24) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/br_1.gif`;
      } else if (this.data.shisetsu_kbn == 25) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/ok_1.gif`;
      } else if (this.data.shisetsu_kbn == 26) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/kf_1.gif`;
      } else if (this.data.shisetsu_kbn == 27) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/hd_1.gif`;
      } else if (this.data.shisetsu_kbn == 28) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/gk_1.gif`;
      } else if (this.data.shisetsu_kbn == 29) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/dm_1.gif`;
      }
      //      }
      this.feature.data = this.data;
      this.vectorLayer.addFeatures([this.feature]);
    } else {
      this.feature.move(
        new OpenLayers.LonLat(lon, lat).transform(
          this.projection4326,
          this.projection3857
        )
      );
    }
  }
}

let angModule = require("../app.js");
angModule.angApp.controller("ShisetsuMapCtrl", [
  "$scope",
  "$uibModalInstance",
  "$http",
  "data",
  function($scope, $uibModalInstance, $http, data) {
    return new ShisetsuMapCtrl($scope, $uibModalInstance, $http, data);
  }
]);
