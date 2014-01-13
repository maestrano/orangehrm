<?php

class loginAction extends sfAction {

    /**
     * Login action. Forwards to OrangeHRM login page if not already logged in.
     * @param sfRequest $request A request object
     */
    public function execute($request) {
        
        // Hook:Maestrano
        global $mno_settings;
        if ($mno_settings && $mno_settings->sso_enabled) {
          $this->redirect($mno_settings->sso_init_url);
        }
        
        $loginForm = new LoginForm();
        $this->message = $this->getUser()->getFlash('message');
        $this->form = $loginForm;
        
    }

}

