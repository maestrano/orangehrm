<?php

class loginAction extends sfAction {

    /**
     * Login action. Forwards to OrangeHRM login page if not already logged in.
     * @param sfRequest $request A request object
     */
    public function execute($request) {
        
        // Hook:Maestrano
        $maestrano = MaestranoService::getInstance();
        if ($maestrano->isSsoEnabled()) {
          $this->redirect($maestrano->getSsoInitUrl());
        }
        
        $loginForm = new LoginForm();
        $this->message = $this->getUser()->getFlash('message');
        $this->form = $loginForm;
        
    }

}