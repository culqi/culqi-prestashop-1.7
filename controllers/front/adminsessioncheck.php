<?php

class CulqiAdminsessioncheckModuleFrontController extends ModuleFrontController {
    public function initContent() {
        var_dump('Access denied');
        die('Access denied');
        header('Content-Type: application/json');
        die(json_encode([
            'session_valid' => $this->context->employee->isLoggedBack(),
            'login_url' => $this->context->link->getAdminLink('AdminLogin')
        ]));
    }
}
