<?php

class AdminCulqiSessionCheckController extends ModuleAdminController
{
    public function init()
    {
        if (Tools::getValue('ajax') && Tools::getValue('action') === 'checkSession') {
            header('Content-Type: application/json');
            die(json_encode([
                'session_valid' => $this->context->employee->isLoggedBack(),
                'login_url' => $this->context->link->getAdminLink('AdminLogin')
            ]));
        }
        die('Access denied');
    }
}
