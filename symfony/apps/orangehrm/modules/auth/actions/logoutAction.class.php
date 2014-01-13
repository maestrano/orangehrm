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
        global $mno_settings;
        if ($mno_settings && $mno_settings->sso_enabled) {
          $this->redirect($mno_settings->sso_access_logout_url);
        }
        
        $this->redirect('auth/login');
    }

}