// AG Gridでチェックボックスを表示するための関数
// 下記記事に掲載の関数を修正して使用
// https://blog.ag-grid.com/binding-boolean-values-to-checkboxes-in-ag-grid/
function CheckboxRenderer() { }

CheckboxRenderer.prototype.init = function (params) {
    this.params = params;

    // editableかどうかをカラムの定義（colDef）から取得して判定
    let editable = true;
    if (params.colDef.editable !== null && params.colDef.editable !== undefined) {
        // paramsの型はparamsを引数に取る関数か、もしくはboolean型が入ってくるので、型を判定してそれぞれ処理
        const type = Object.prototype.toString.call(params.colDef.editable).slice(8, -1).toLowerCase();
        if(type == "function") {
            // 関数の場合は引数にparamsを入れて実行結果をeditableに代入
            editable = params.colDef.editable(params);
        } else if (type == "boolean") {
            // booleanの場合はそのまま代入
            editable = params.colDef.editable;
        }
    }

    this.eGui = document.createElement('input');
    this.eGui.type = 'checkbox';
    this.eGui.checked = params.value;
    // editableがfalseの場合はdisabledにする
    this.eGui.disabled = !editable;

    this.checkedHandler = this.checkedHandler.bind(this);
    this.eGui.addEventListener('click', this.checkedHandler);
};

CheckboxRenderer.prototype.checkedHandler = function (e) {
    let checked = e.target.checked;
    let colId = this.params.column.colId;
    this.params.node.setDataValue(colId, checked);
};

CheckboxRenderer.prototype.getGui = function (params) {
    return this.eGui;
};

CheckboxRenderer.prototype.destroy = function (params) {
    this.eGui.removeEventListener('click', this.checkedHandler);
};

export default CheckboxRenderer;