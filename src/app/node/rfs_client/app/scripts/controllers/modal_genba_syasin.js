'use strict';

/**
 * @ngdoc function
 * @name controller:ModalGenbaSyasinCtrl
 * @description
 */

class ModalGenbaSyasinCtrl {
  constructor($uibModalInstance, $http, data) {
    this.$uibModalInstance = $uibModalInstance;
    this.$http = $http;

    // 呼び出し元からのデータのコピー
    this.data = angular.copy(data);
    // 現場写真の写真リスト（
    this.photo_list = [];
    // 現場写真の写真リスト(オリジナル)
    this.org_photo_list = JSON.parse(JSON.stringify(this.photo_list));

    console.debug(data);

    $http({
      method: 'POST',
      url: 'api/index.php/PictureAjax/get_gs_title_list',
      data: {
        dogen_cd: this.data.dogen_cd,
        syucchoujo_cd: this.data.syucchoujo_cd
      }
    }).then((msg) => {
      this.title_list = msg.data.data;
      this.album_title = msg.data.data[0].title_cd;
      if (this.init_select) {
        this.album_title = this.init_select;
      }
      this.title_change();
    });
  }

  close() {
    this.$uibModalInstance.close(null);
  }


  reset() {
    this.photo_list = JSON.parse(JSON.stringify(this.org_photo_list));
  }

  title_change() {
    this.$http({
      method: 'POST',
      url: 'api/index.php/PictureAjax/get_gs_picture_list',
      data: {
        title_cd: this.album_title,
        dogen_cd: this.data.dogen_cd,
        syucchoujo_cd: this.data.syucchoujo_cd
      }
    }).then((msg) => {
      this.photo_list = msg.data.data;
      this.org_photo_list = JSON.parse(JSON.stringify(this.photo_list));
    });
  }

  select(photo) {
    photo.sel = !photo.sel;
  }

  save() {
    var url_list = [];
    var genba_photo_list = [];
    for (var i = 0; i < this.photo_list.length; i++) {
      var photo = this.photo_list[i];
      if (photo.sel) {
        url_list.push(photo.src);
        genba_photo_list.push(photo);
      }
    }

    this.$http({
      method: 'POST',
      url: 'api/index.php/UploadController/uploadFromUrlList',
      data: {
        url_list: url_list
      }
    }).then((data) => {
      this.data.photo_list = data.data;
      for (var i = 0; i < genba_photo_list.length; i++) {
        this.data.photo_list[i].exif_dt = genba_photo_list[i].exif_dt;
        this.data.photo_list[i].lon = genba_photo_list[i].lon;
        this.data.photo_list[i].lat = genba_photo_list[i].lat;
        this.data.photo_list[i].description = genba_photo_list[i].description;
      }
    }).finally(() => {
      this.$uibModalInstance.close(this.data);
    });
  }
}

let angModule = require('../app.js');
angModule.angApp.controller('ModalGenbaSyasinCtrl', ['$uibModalInstance', '$http', 'data', function ($uibModalInstance, $http, data) {
  return new ModalGenbaSyasinCtrl($uibModalInstance, $http, data);
}]);
