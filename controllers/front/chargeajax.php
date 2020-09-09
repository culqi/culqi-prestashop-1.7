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
    if($result->id) Configuration::updateValue($result->id,'generatedCharge');
    die(Tools::jsonEncode($result));
  }

}
