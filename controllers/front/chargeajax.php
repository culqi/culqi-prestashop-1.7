<?php

class CulqiChargeAjaxModuleFrontController extends ModuleFrontController
{

  public function initContent()
  {
    parent::initContent();
    $this->ajax = true;
  }

  public function displayAjax()
  {
    $result = $this->module->charge(Tools::getValue("token_id"), Tools::getValue("installments"));
    //die(Tools::jsonEncode($result));



      //header_remove();
      //header('Content-Type: application/json');
      echo json_encode($result);
      exit();


  }

}
