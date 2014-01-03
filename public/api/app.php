<?php

require '../../config.php';
require '../../Slim/Slim.php';
require '../../RedBean/rb.php';
//require '../../Persona/Persona.php';

\Slim\Slim::registerAutoloader();
\Slim\Route::setDefaultConditions(array(
  'id' => '[0-9]{1,}',
));

R::setup("mysql:host={$config['host']};dbname={$config['dbname']}", $config['name'], $config['password']);
R::freeze(true);

$app = new \Slim\Slim([
    'debug' => true
]);

class ResourceNotFoundException extends Exception {}

function getTitleFromUrl($url) {
    preg_match('/<title>(.+)<\/title>/', file_get_contents($url), $matches);

    return mb_convert_encoding($matches[1], 'UTF-8', 'UTF-8');
}

function getFaviconFromUrl($url) {
    $url = parse_url($url);
    $url = urlencode(sprintf('%s://%s', 
        isset($url['scheme']) ? $url['scheme'] : 'http', 
        isset($url['host']) ? $url['host'] : strtolower($url['path'])));
    
    return "http://g.etfv.co/$url";
}

function saveFavicon($url, $id) {
    file_put_contents("../favicons/$id.ico", file_get_contents(getFaviconFromUrl($url)));
}

function returnResult($action, $success = true, $id = 0) {
    echo json_encode([
        'action' => $action,
        'success' => $success,
        'id' => intval($id),
    ]);
}

//routegroups

function crypto_rand_secure($min, $max) {
        $range = $max - $min;
        if ($range < 0) return $min; // not so random...
        $log = log($range, 2);
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
}

function getToken($length, $isSimple = false){
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet.= "0123456789";
    $codeAlphabet.= $isSimple ? '' : '&%$#@_';
    for($i=0;$i<$length;$i++){
        $token .= $codeAlphabet[crypto_rand_secure(0,strlen($codeAlphabet))];
    }
    return $token;
}

$app->get('/trial', function () use ($app) {
    $app->response()->header('request', 'credentials');
    echo json_encode(['trial' => true]);
    //returnCall(['trial' => true]); 
});

$app->get('/poging/:name', function ($name) use ($app) {
    $_user = R::findOne('_users', 'name_canonical=?', [$name]);
    $_user->token = getToken(30);
    $_user->token_time = time();
    $_user->xsrf = $app->request()->headers('X-XSRF-TOKEN');
    $_id = R::store($_user);
    echo $_user->token;
});

function login () {
    $app = \Slim\Slim::getInstance();
    $request = $app->request();
    $body = $request->getBody();
    $input = json_decode($body);
    $_name = strtolower($input->login);

    $_user = R::findOne('_users', 'name_canonical=?', [$_name]);
    $_user->login_time = date("Y-m-d H:i:s", time());
    $_user->token = getToken(30);
    $_user->token_time = $_user->login_time;
    $_user->xsrf = getToken(50);
    $_id = R::store($_user);

    return ['token' => $_user->token, 'xsrf' => $_user->xsrf];
}

function update() {
    $app = \Slim\Slim::getInstance();
    $request = $app->request();
    $_token = $app->request()->headers('token');

    $_user = R::findOne('_users', 'token=?', [$_token]);
    if ($_user) {
        $_user->token = getToken(30);
        $_user->token_time = date("Y-m-d H:i:s", time());
        $_id = R::store($_user);
        $token = $_user->token;
    } else {
        $token = getToken(30);
    }
    return $token;
}

function logout () {
    $app = \Slim\Slim::getInstance();
    $request = $app->request();
    $_token = $app->request()->headers('token');
    $_xsrf = $app->request()->headers('X-XSRF-TOKEN');

    $_user = R::findOne('_users', 'xsrf=?', [$_xsrf]);
    $_user->login_time = '0000-00-00 00:00:00';
    $_user->token = '';
    $_user->token_time = $_user->login_time;
    $_user->xsrf = '';
    $_id = R::store($_user);

    return 'null';
}

function returnCall($response, $loginout = false) {
    $app = \Slim\Slim::getInstance();  
    if (!$loginout) {
        if (null !== $app->request()->headers('token')) {
            $token = update();
        } else if (isset($response['auth']) && $response['auth']) {
            //hier moet extra check komen
            $token = update();
        } else {
            $token = getToken(30);      
        }
    } else if ($loginout == 'login') {
        $_response = login();
        $token = $_response['token'];
        $app->response()->header('X-XSRF-TOKEN', $_response['xsrf']);
        
    } else {
        $token = logout();
        $app->response()->header('X-XSRF-TOKEN', 'null');
    }
    $app->response()->header('token', $token);
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode($response);
}

$app->get('/token/:token', function ($token) {
    $salt = bin2hex(openssl_random_pseudo_bytes(22));
    $hash = crypt($token, '$2a$12$'.$salt);
    echo json_encode(['token' => $hash]);
});

$app->get('/app', function () use ($app) {
    $_oDB = R::find('apps');
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode(R::exportAll($_oDB));
});

$app->get('/todo', function () use ($app) {
    //$_oDB = R::find('todo', 'removed = 0');
    $_oDB = R::getAll('SELECT * FROM todo WHERE removed = 0');
    returnCall($_oDB);
});

$app->get('/todo/:id', function ($id) use ($app) {
    try {
        $_oDB = R::findOne('todo', 'id=?', [$id]);
        returnCall(R::exportAll($_oDB));
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

$app->get('/app/:id', function ($id) use ($app) {    
    try {
        $_oDB = R::findOne('apps', 'id=?', array($id));
        if ($_oDB) {
            $app->response()->header('Content-Type', 'application/json');
            echo json_encode(R::exportAll($_oDB));
        } else {
            throw new ResourceNotFoundException();
        }
    } catch (ResourceNotFoundException $e) {
        $app->response()->status(404);
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

$app->get('/lang/:lang', function ($lang) use ($app) {
    $langs = [
        'options'   => ['en','nl'],
        'en'        => [
            'lang' => 'en',
            'index.index' => 'NT2lab',
            'index.welkom' => 'Welcome',
            'menu.app.url' => 'apps',
            'menu.app.name' => 'apps-list',
            'menu.options.url' => 'options',
            'menu.options.name' => 'options',
            'options.main.title' => 'Options',
            'options.language.title' => 'language choice',
            'options.language.url' => 'language-choice',
            'options.todo.title' => 'todo',
            'options.todo.url' => 'todo',
            'todo.list.title' => 'ToDo',
            'todo.list.add' => 'add',
            'todo.list.nothingToDo' => 'Give yourself something to do!',
            'todo.list.todo' => 'toDo',
            'todo.list.added' => 'added',
            'todo.list.done_by' => 'done by',
            'todo.list.priority' => 'priority',
            'todo.form.url' => 'add-todo',
            'todo.form.title' => 'Add item',
            'todo.form.todo' => 'toDo',
            'todo.form.description' => 'description',
            'todo.form.doneBy' => 'done by',
            'todo.form.priority' => 'has priority',
            'todo.form.submit' => 'submit',
            'todo.form.redirect' => 'done',
            'todo.form.reset' => 'reset',
            'menu.user.url' => 'my-nt2lab',
            'menu.user.name'=> 'my NT2lab',
            'menu.login.url' => 'login',
            'menu.login.name' => 'login page',
            'menu.logout.url' => 'logout',
            'menu.logout.name' => 'logout',
            'options.lang.title' => 'Switch language',
            'options.lang.nl' => 'dutch',
            'options.lang.en' => 'english',
            'login.form.title' => 'Login page',
            'login.form.name' => 'name',
            'login.form.password' => 'password',
            'login.form.submit' => 'submit',
            'error.bad-credentials' => 'wrong credentials',
            'error.bad-request' => 'unknown request'],
        'nl'        => [
            'lang' => 'nl',
            'index.index' => 'Home',
            'index.welkom'=> 'Welkom',
            'menu.app.url'=> 'apps',
            'menu.app.name' => 'apps-lijst',
            'menu.options.url'=> 'opties',
            'menu.options.name' => 'opties',
            'menu.user.url'=> 'mijn-nt2lab',
            'menu.user.name'=> 'mijn NT2lab',
            'menu.login.url'=> 'inlog',
            'menu.login.name' => 'inlogpagina',
            'options.lang.title' => 'Taal aanpassen',
            'options.lang.nl'=> 'Nederlands',
            'options.lang.en'=> 'Engels',
            'options.language.title' => 'taalkeuze',
            'options.language.url' => 'taalkeuze',
            'options.main,title' => 'Opties',
            'options.todo.title' => 'taken',
            'options.todo.url' => 'taken',
            'options.main.title' => 'Opties',
            'todo.list.title' => 'Taken',
            'todo.list.add' => 'meer',
            'todo.list.todo' => 'Taken',
            'todo.list.done_by' => 'klaar op',
            'todo.list.priority' => 'belangrijk',
            'todo.list.nothingToDo' => 'Heb je niets om handen? Geef jezelf taken!',
            'todo.list.added' => 'toegevoegd op',
            'todo.form.url' => 'taak-toevoegen',
            'todo.form.title' => 'Taak toevoegen',
            'todo.form.todo' => 'taak',
            'todo.form.doneBy' => 'klaar op',
            'todo.form.description' => 'beschrijving',
            'todo.form.priority' => 'belangrijk',
            'todo.form.submit' => 'toevoegen',
            'todo.form.reset' => 'legen',
            'todo.form.redirect' => 'terug',
            'login.form.title' => 'Inlogpagina',
            'login.form.name' => 'inlognaam',
            'login.form.password' => 'wachtwoord',
            'login.form.submit' => 'inloggen',
            'login.persona.button' => 'inloggen met Persona',
            'login.persona.explanation' => '',
            'menu.logout.name' => 'uitloggen',
            'error.bad-credentials' => 'inlognaam of wachtwoord incorrect',
            'error.bad-request' => 'onbekend verzoek']
    ];
    
    if (in_array($lang, $langs['options'])) {
        $return = $langs[$lang];
    } else {
        $return = $langs['nl'];
        $lang = 'nl';
        $return['error'] = true;
    }
    
    $menu = new stdClass();
    $menu->init = true;
    $menu->{$langs[$lang]['menu.app.url']} = 'apps';
    $menu->{$langs[$lang]['menu.options.url']} = 'options';
    $menu->{$langs[$lang]['menu.user.url']} = 'user';
    $menu->{$langs[$lang]['menu.login.url']} = 'login';
    $menu->{$langs[$lang]['options.todo.url']} = 'todo';
    $menu->{$langs[$lang]['options.language.url']} = 'language';
    $menu->{$langs[$lang]['todo.form.url']} = 'todo-add';
    
    $url = [];
    $url['lang'] = $lang;
    foreach ($langs['options'] as $l) {
        $url['backToLanguage'][$l] = $l.'/'.$langs[$l]['menu.options.url'].'/'.$langs[$l]['options.language.url'];
    }
    $url['menu.app.url'] = $langs[$lang]['menu.app.url'];
    $url['menu.options.url'] = $langs[$lang]['menu.options.url'];
    $url['menu.user.url'] = $langs[$lang]['menu.user.url'];
    $url['menu.login.url'] = $langs[$lang]['menu.login.url'];
    $url['options.todo.url'] = $langs[$lang]['options.todo.url'];
    $url['options.language.url'] = $langs[$lang]['options.language.url'];
    $url['todo.form.url'] = $langs[$lang]['todo.form.url'];
    
    
    $return['urlCheck'] = $menu;
    $return['url'] = $url;
    //$app->response()->header('Content-Type', 'application/json');
    //echo \json_encode($return);
    returnCall($return);
    
    /*
    try {
        
    } catch (Exception $ex) {

    }
    */
});

$app->get('/menu', function () use ($app) {
    $return = [
        'urlToLanguage' => [
            'en' => [
                'menu.app.url'          => 'apps',
                'menu.options.url'      => 'options',
                'menu.user.url'         => 'my-nt2lab',
                'menu.login.url'        => 'login',
                'options.todo.url'      => 'todo',
                'options.language.url'  => 'language-choice',
                'todo.form.url'         => 'add-todo',
                'todo.form-change.url'  => 'todo-change'
            ],
            'nl' => [
                'menu.app.url'          => 'apps',
                'menu.options.url'      => 'opties',
                'menu.user.url'         => 'mijn-nt2lab',
                'menu.login.url'        => 'inlog',
                'options.todo.url'      => 'taken',
                'options.language.url'  => 'taalkeuze',
                'todo.form.url'         => 'taak-toevoegen',
                'todo.form-change.url'  => 'taak-aanpassen'
            ]
         ],
         'urlToMenu'    => [
            'menu.app.url'          => 'apps',
            'menu.options.url'      => 'options',
            'menu.user.url'         => 'user',
            'menu.login.url'        => 'login',
            'options.todo.url'      => 'todo',
            'options.language.url'  => 'language',
            'todo.form.url'         => 'todo-add',
            'todo.form-change.url'  => 'todo-change'
         ]
    ];
    foreach($return['urlToLanguage'] as $key => $value) {
        foreach($return['urlToLanguage'][$key] as $url => $name) {
            $return['languageToUrl'][$key][$name] = $url;
        }
    }
    returnCall($return);
});

$app->post('/app', function () use ($app) {    
    try {
        // get and decode JSON request body
        $request = $app->request();
        $body = $request->getBody();
        $input = json_decode($body); 

        // store article record
        $_oDB = \R::dispense('apps');
        $_oDB->name         = (string)$input->name;
        $_oDB->date_added   = (string)$input->date_added;
        $_oDB->date_updated = (string)$input->date_updated;
        $_oDB->dependency   = (int)$input->dependency;
        $_oDB->category_id  = (string)$input->category_id;
        $id = \R::store($_oDB);    

        // return JSON-encoded response body
        //$app->response()->header('Content-Type', 'application/json');
        //echo json_encode(R::exportAll($_oDB));
        returnCall($_oDB);
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

$app->post('/login', function () use ($app) {
    $_request = $app->request();
    $_body = $_request->getBody();
    $_input = json_decode($_body);
    
    $_name = strtolower($_input->login);
    
    try {
        $_oDB = R::findOne('_users', 'name_canonical=?', [$_name]);
        if ($_oDB) {
            $_hash = $_oDB->password;
            if (crypt($_input->password, $_hash) == $_hash) {
                returnCall(['user' => $_oDB->name, 'role' => $_oDB->role], 'login');
            } else {
                throw new Exception('unknown user or password');
            }
        } else {
            throw new Exception('unknown user or password');
        }
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

$app->post('/logout', function () use ($app) {
    returnCall([], 'logout');
});
/*
$app->post('/persona/:action', function ($action) use ($app) {
    $request = $app->request();
    $body = $request->getBody();
    $input = json_decode($body);
    $response = ['testing' => 'nope'];
    switch($action) {
        case 'verify':
            if (isset($input->assertion)) {
                $persona = new Persona();
                $result = $persona->verifyAssertion($input->assertion);

                if ($result->status === 'okay') {
                    $response = ['success' => true];
                } else {
                    $response = ['success' => false];
                }
            }
            break;
        default;
    }
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode($response);
});
*/
$app->post('/todo', function () use ($app) {    
    try {
        // get and decode JSON request body
        $request = $app->request();
        $body = $request->getBody();
        $input = json_decode($body); 

        // store article record
        $_oDB = \R::dispense('todo');
        $_oDB->name         = (string)$input->todo;
        $_oDB->description  = isset($input->description) ? $input->description : '';
        $_oDB->added        = date('Y-m-d',time());
        $_oDB->priority     = isset($input->priority) ? $input->priority : 0;
        $_oDB->done_by      = $input->doneBy;
        $id = \R::store($_oDB);    
        
        $_oDB = R::getAll('SELECT * FROM todo WHERE id = ?', [$id]);
        returnCall($_oDB);
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

$app->put('/todo/:id', function ($id) use ($app) {
    $request = $app->request();
    $body = $request->getBody();
    $input = json_decode($body);
    
    if (isset($input->action)) {
        if($input->action == 'priority') {
            $_oDB = \R::load('todo',$id);
            $_oDB->priority   = $_oDB->priority == 0 ? 1 : 0;
            \R::store($_oDB);    
            $_sResponse = ['priority' => $_oDB->priority];
            returnCall($_sResponse);
        } else if($input->action == 'done') {   
            $_oDB = \R::load('todo',$id);
            $_oDB->done   = $_oDB->done == 0 ? 1 : 0;
            \R::store($_oDB);  
            $_sResponse = ['done' => $_oDB->done];
            returnCall($_sResponse);
        } else if($input->action == 'removed') {   
            $_oDB = \R::load('todo',$id);
            $_oDB->removed   = 1;
            \R::store($_oDB);
            $_sResponse = ['removed' => 1];
            returnCall($_sResponse);
        }
    } else {
        $_oDB = \R::load('todo',$id);
        $_oDB->name         = $input->todo;
        $_oDB->description  = $input->description;
        $_oDB->priority     = $input->priority;
        $_oDB->done_by      = $input->doneBy;
        \R::store($_oDB); 
        $_sResponse = ['changed' => 1];
        returnCall($_sResponse);   
    }
});

$app->delete('/todo/:id', function ($id) use ($app) {
    $_oDB = \R::load('todo',$id);
    $_oDB->removed   = 1;
    \R::store($_oDB);

    $_sResponse = ['removed' => 1];
    //$app->response()->header('Content-Type', 'application/json');
    //echo json_encode($_sResponse);
    returnCall($_sResponse);
});
/*
$app->get('/app/:id', function ($id) use ($db, $app) {
    $sth = $db->prepare('SELECT * FROM apps WHERE id = ? LIMIT 1;');
    $sth->execute([intval($id)]);
    echo json_encode($sth->fetchAll(PDO::FETCH_CLASS)[0]);
});

$app->post('/bookmark', function () use ($db, $app) {
    $title = $app->request()->post('title');
    $sth = $db->prepare('INSERT INTO bookmark (url, title) VALUES (?, ?);');
    $sth->execute([
        $url = $app->request()->post('url'),
        empty($title) ? getTitleFromUrl($url) : $title,
    ]);
    saveFavicon($url, $id = $db->lastInsertId());

    returnResult('add', $sth->rowCount() == 1, $id);
});

$app->put('/bookmark/:id', function ($id) use ($db, $app) {
    $sth = $db->prepare('UPDATE bookmark SET title = ?, url = ? WHERE id = ?;');
    $sth->execute([
        $app->request()->post('title'),
        $url = $app->request()->post('url'),
        intval($id),
    ]);
    saveFavicon($url, $id);

    returnResult('add', $sth->rowCount() == 1, $id);
});

$app->delete('/bookmark/:id', function ($id) use ($db) {
    $sth = $db->prepare('DELETE FROM bookmark WHERE id = ?;');
    $sth->execute([intval($id)]);

    unlink("../icons/$id.ico");

    returnResult('delete', $sth->rowCount() == 1, $id);
});

$app->get('/install', function () use ($db) {
    $db->exec('	CREATE TABLE IF NOT EXISTS bookmark (
					id INTEGER AUTO_INCREMENT, 
					title TEXT, 
					url VARCHAR(255) UNIQUE);');

    returnResult('install');
});
*/
$app->run();