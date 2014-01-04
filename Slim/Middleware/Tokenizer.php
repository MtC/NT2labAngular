<?php

class Tokenizer extends \Slim\Middleware {
    
    public function call() {
        $app = $this->app;

        $this->env      = $app->environment();
        $this->request  = $app->request();
        $this->response = $app->response();
        
        if ($this->env['REQUEST_METHOD'] == 'POST' && $this->env['PATH_INFO'] == '/login') {
            $this->login();
        } else if ($this->env['REQUEST_METHOD'] == 'POST' && $this->env['PATH_INFO'] == '/logout') {
            $this->logout();
        } else {
            $this->headers();
        }       

        $this->next->call();
    }
    
    protected function createToken ($length) {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
    
    protected function headers () {
        $_token = $this->verifyToken();
        if (null !== $this->request->headers('X-Xsrf-Token')) {
            $_xsrf = \R::findOne('users', 'xsrf=?', [$this->request->headers('X-Xsrf-Token')]);
            if ($_token && $_xsrf) {
                $this->response->header('X-Xsrf-Token', $_xsrf->xsrf);
                $this->env['user']  = ['id' => $_xsrf->id, 'authorization' => $this->getAuthorization($_xsrf->role)];
            } else if ($_xsrf && !$_token) {
                $_xsrf->xsrf = null;
                \R::Store($_xsrf);
            }
        }
        if ($_token) {
            $this->response->header('X-Token', $_token->added.'#'.$_token->token);
        } else {
            $this->insertToken();
        }
    }
    
    protected function verifyToken () {
        if (null === $this->request->headers('X-Token')) return false;
        list($_added, $_token) = explode('#', $this->request->headers('X-Token'));
        $_oDB        = \R::findOne('tokens', 'token=? AND added=? AND address=?', [$_token, $_added, $this->env['REMOTE_ADDR']]);
        return $_oDB ? $_oDB : false;
    }
    
    protected function insertToken () {
        $_token          = \R::dispense('tokens');
        $_token->token   = $this->createToken(16);
        $_token->added   = date('Y-m-d H:i:s', time());
        $_token->address = $this->env['REMOTE_ADDR'];
        $id              = \R::store($_token);
        $this->response->header('X-Token', $_token->added.'#'.$_token->token);
    }
    
    protected function login () {
        $_token = $this->verifyToken();
        if ($_token) {
            $_body = $this->request->getBody();
            $_input= json_decode($_body);
            $_name = strtolower($_input->login);
            $_user = \R::findOne('users', 'name_canonical=?', [$_name]);
            if (crypt($_input->password, $_user->password) == $_user->password) {
                $_user->xsrf = $this->createToken(32);
                $_user->login_time = date('Y-m-d H:i:s', time());
                \R::store($_user);
                $this->response->header('X-Xsrf-Token', $_user->xsrf);
                $this->response->header('X-Token', $_token->added.'#'.$_token->token);
                $this->response->header('X-User', $_user->name);
                $this->response->header('X-Role', $_user->role);
                $this->env['user']  = ['id' => $_user->id, 'authorization' => $this->getAuthorization($_user->role)];
                $this->env['error'] = 'alles is goed';
            } else {
                $this->response->header('X-Token', $this->insertToken());
                $this->env['error'] = 'logingegevens kloppen niet';
            }
        } else {
            $this->response->header('X-Token', $this->insertToken());
        }
    }
    
    protected function logout () {
        if (null !== $this->request->headers('X-Xsrf-Token')) {
            $_oDBUser       = R::findOne('users', 'xsrf=?', [$this->request->headers('X-Xsrf-Token')]);
            if ($_oDBUser) {
                $_oDBUser->xsrf = null;
                \R::store($_oDBUser);
            }
            $trash = $this->verifyToken();
            if ($trash) \R::Trash($trash);
            $this->insertToken();
        }
    }
    
    protected function getAuthorization ($role) {
        $_roles = ['USER' => 1, 'TEACHER' => 2, 'ADMIN' => 3];
        return $_roles[$role];
    }
}