<?php

$user = rex::getUser();

if(!rex::isBackend() || $user)
{
  rex_extension::register('ADDONS_INCLUDED', function($params) use($user)
  {
    $loggedIn = rex_backend_login::hasSession();
    if($loggedIn && !$user)
    {
      $login = new rex_backend_login;
      if($login->checkLogin())
      {
        $user = $login->getUser();
        rex::setProperty('user', $user);
      }
    }
    if($user && $user->isAdmin())
    {
      rex_developer_manager::sync();
    }
  });
}
