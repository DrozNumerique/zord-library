<?php

class Account extends Module {
    
    public function activate() {
        $code = isset($this->params['code']) ? $this->params['code'] : null;
        if ($code) {
            $user = (new UserEntity())->retrieve([
                'where' => ['activate' => $code]
            ]);
            if ($user) {
                return $this->connection($user->login, $code, null, $this->locale->activate);
            }
        }
        return $this->error(404);
    }
    
    public function connection($login = '', $activate = null, $lasthref = null, $message = null) {
        return $this->page('connection', [
            'login' => $login,
            'activate' => $activate,
            'lasthref' => $this->either($lasthref, 'lasthref'),
            'message' => $message
        ]);
    }
    
    public function connect() {
        $login    = isset($this->params['login'])    ? trim($this->params['login'])    : '';
        $password = isset($this->params['password']) ? trim($this->params['password']) : null;
        $confirm  = isset($this->params['confirm'])  ? trim($this->params['confirm'])  : null;
        $email    = isset($this->params['email'])    ? $this->params['email']          : null;
        $activate = isset($this->params['activate']) ? $this->params['activate']       : null;
        $lasthref = isset($this->params['lasthref']) ? $this->params['lasthref']       : null;
        $message = null;
        if (!empty($email)) {
            $code = User::crypt($email.microtime());
            $account = (new UserEntity())->update(
                ['where' => ['email' => $email]],
                ['activate' => $code]
            );
            if ($account) {
                $result = $this->sendActivation($email, $account->name, $code);
                if (isset($result['error'])) {
                    $message = $this->locale->mail_error.'<br/>('.$result['error'].')';
                } else {
                    $message = $this->locale->mail_sent;
                }
            } else {
                $message = $this->locale->unknown_user;
            }
        } else if (!empty($login) && !empty($password)) {
            if (!empty($activate)) {
                if ($password == $confirm) {
                    (new UserEntity())->update(
                        ['where' => ['activate' => $activate]],
                        [
                            'password' => $password,
                            'activate' => ''
                        ]
                    );
                } else {
                    $message = $this->locale->wrong_confirm;
                    $password = null;
                }
            }
            if ($password) {
                $user = User::authenticate($login, $password);
                if ($user) {
                    $this->controler->setUser($user);
                    $target = $lasthref ? $this->controler->getTarget($lasthref, true) : $this->controler->getDefaultTarget();
                    return $this->forward($target);
                } else {
                    $message = $this->locale->auth_failed;
                }
            }
        } 
        return $this->connection($login, $activate, $lasthref, $message);
    }
    
    public function disconnect() {
        $this->user->disconnect();
        return $this->redirect($this->baseURL, true);
    }
}
