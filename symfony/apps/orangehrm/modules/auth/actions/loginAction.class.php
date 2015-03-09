<?php

class loginAction extends sfAction {
  /**
   * Login action. Forwards to OrangeHRM login page if not already logged in.
   * @param sfRequest $request A request object
   */
  public function execute($request) {
    // Hook:Maestrano
    if(Maestrano::sso()->isSsoEnabled()) {
      // Handle action when user is logged in
      if ($_SESSION["loggedIn"]) {
        $mnoSession = new Maestrano_Sso_Session($_SESSION);
        // Check session validity and trigger SSO if not
        if (!$mnoSession->isValid()) {
          header('Location: ' . Maestrano::sso()->getInitPath());
        }
      } else {
        header('Location: ' . Maestrano::sso()->getInitPath());
      }
    } else {
      $loginForm = new LoginForm();
      $this->message = $this->getUser()->getFlash('message');
      $this->form = $loginForm;
    }
  }
}