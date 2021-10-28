<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require "BaseController.php";

/**

KenzenSumAjux
概要：健全性の集計用
 **/
class KenzenSumAjax extends BaseController {

  public function __construct() {
    parent::__construct();
    // メソッド共通の処理をする
  }

  public function init() {

    log_message('debug', __METHOD__);
    $this->load->model('SchCommon');

    $result["mst"]["dogen_syucchoujo_dat"] = $this->SchCommon->getDogenSyucchoujo2();
    $result["mst"]["shisetsu_kbn"] = $this->SchCommon->getMstSimple("rfs_m_shisetsu_kbn natural join rfs_m_shisetsu_huzokubutsu");
    //$result["mst"]["buzai"] = $this->SchCommon->getMstSimple("rfs_m_buzai", "true", "buzai_cd");
    $result["mst"]["buzai"] = $this->SchCommon->getBuzaiMst();
    //$result["mst"]["shisetsu_judge"] = $this->SchCommon->getMstSimple("rfs_m_shisetsu_judge", "true", "shisetsu_judge");
    $result["mst"]["shisetsu_judge"] = $this->SchCommon->getShisetsuJudgeMst();
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  public function sum() {

    log_message('debug', __METHOD__);

    $params = $this->post;

    $this->load->model('KenzenSumModel');

    $result['data']["shisetsu_list"] = $this->KenzenSumModel->getShisetsuList($params);
    $result['data']["judge_list"] = $this->KenzenSumModel->getShisetsuJudge($params);

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);

  }
}