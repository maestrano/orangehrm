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
        if (Maestrano::sso()->isSsoEnabled()) {
          $this->redirect(Maestrano::sso()->getLogoutUrl());
        }
        
        $this->redirect('auth/login');
    }

}