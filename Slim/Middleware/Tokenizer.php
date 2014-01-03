<?php

class Tokenizer extends \Slim\Middleware {
    
    public function call() {
        $app = $this->app;

        $this->env      = $app->environment();
        $this->request  = $app->request()->headers();
        $this->response = $app->response();
        print_r($this->env);
        $this->headers();

        $this->next->call();
    }
    
    protected function createToken ($length) {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
    
    protected function headers () {
        if (isset($this->request['X-XSRF-TOKEN'])) {
            $_error       = false;
            $_tokenValid  = $this->verifyToken();
            $_maxTime     = time() - (60*60);                   //60 minutes
            if (strtotime($_tokenValid->added) < $_maxTime) {
                \R::Trash($_tokenValid);
                $_tokenValid = false;
            } else {
                
            }
            $_oDBUser     = R::findOne('users', 'xsrf=?', [$this->request['X-XSRF-TOKEN']]);
            if ($_oDBUser && $_tokenValid) {
                $this->response->header('token', $_tokenValid->added.'#'.$_tokenValid->token);
                $this->response->header('X-XSRF-TOKEN', $_oDBUser->xsrf);
                $this->env['user']  = $_oDBUser;
                $this->env['roles'] = ['user' => 1, 'teacher' => 2, 'admin' => 3];
            } else {
                $_oDBUser->xsrf = null;
                \R::store($_oDBUser);
                $this->insertToken();
            }
        } else if (isset($this->request['token'])) {
            $trash = $this->verifyToken();
            if ($trash) \R::Trash($trash);
            $this->insertToken();
        } else {
            $this->insertToken();
        }
    }
    
    protected function verifyToken () {
        list($_added, $_token) = explode('#', $this->request['token']); 
        $_oDB        = \R::findOne('tokens', 'token=? AND added=? AND addres=?', [$_token, $_added, $this->env['REMOTE_ADDR']]);
        return $_oDB ? $_oDB : false;
    }
    
    protected function insertToken () {
        $_oDB        = \R::dispense('tokens');
        $_oDB->token = $this->createToken(16);
        $_oDB->added = date('Y-m-d H:i:s');
        $_oDB->address  = $this->env['REMOTE_ADDR'];
        $id          = \R::store($_oDB);
        $this->response->header('token', $_oDB->added.'#'.$_oDB->token);
    }
}