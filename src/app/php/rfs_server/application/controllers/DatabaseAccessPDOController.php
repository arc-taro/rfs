<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("BaseController.php");

/**
* コントローラー名：DatabaseAccessPDOController
* 汎用DBAコントローラ
* シンプルなクエリ発行用API
*/
class DatabaseAccessPDOController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // メソッド共通の処理をする
        $this->load->model('DatabaseAccessPDOModel');
    }

    public function select()
    {
        $parmas = $this->post;

        $db = $parmas['db'];
        $table = $parmas['table'];
        $where = (is_array($parmas['where']) && count($parmas['where']) > 0)
            ? $parmas['where']
            : [];
        $column = (is_array($parmas['column']) && count($parmas['column']) > 0)
            ? $parmas['column']
            : [];
        $order  = (is_array($parmas['order']) && count($parmas['order']) > 0)
            ? $parmas['order']
            : [];
        $limit  = (isset($parmas['limit']))
            ? $parmas['limit']
            : null;
        $offset = (isset($parmas['offset']))
            ? $parmas['offset']
            : null;

        $result = $this->DatabaseAccessPDOModel->select($db, $table, $where, $column, $order, $limit, $offset);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }
}
