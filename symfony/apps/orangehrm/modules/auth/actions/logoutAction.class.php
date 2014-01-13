<?php

class logoutAction extends sfAction {

    /**
     * Logout action
     * @param $request 
     */
    public function execute($request) {
        $authService = new AuthenticationService();
        $authService->clearCredentials();
        
        // Hook:Maestrano
        $maestrano = MaestranoService::getInstance();
        if ($maestrano->isSsoEnabled()) {
          $this->redirect($maestrano->getSsoLogoutUrl());
        }
        
        $this->redirect('auth/login');
    }

}