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

function returnCall($response) {
    $app = \Slim\Slim::getInstance();
    if (null !== $app->request()->headers('token')) {
        $token = "{$app->request()->headers('token')}";
    } else if (isset($response['auth']) && $response['auth']) {
        //create token and place in database
        $token = 'authenticated';
    } else {
        $token = 'pipo';
        $app->response()->header('X-XSRF-TOKEN', '$xsrf');
    }   
    $app->response()->header('token', $token);
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode($response);
}

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
        'options'   => ['nl','en'],
        'en'        => [
            'lang' => 'en',
            'index.index' => 'NT2lab',
            'index.welkom' => 'Welcome',
            'menu.app.url' => 'apps',
            'menu.app.name' => 'apps-list',
            'menu.options.url' => 'options',
            'menu.options.name' => 'options',
            'options.language.title' => 'language choice',
            'options.language.url' => 'language-choice',
            'options.todo.title' => 'todo',
            'options.todo.url' => 'todo',
            'todo.list.todo' => 'toDo',
            'todo.list.added' => 'added',
            'todo.list.done_by' => 'done by',
            'todo.list.priority' => 'priority',
            'menu.user.url' => 'my-nt2lab',
            'menu.user.name'=> 'my NT2lab',
            'menu.login.url' => 'login',
            'menu.login.name' => 'login page',
            'options.lang.title' => 'Switch language',
            'options.lang.nl' => 'dutch',
            'options.lang.en' => 'english'],
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
            'todo.nothingToDo' => 'Heb je niets om handen? Geef jezelf taken:',
            'todo.list.added' => 'toegevoegd op',
            'options.lang.title' => 'Taal aanpassen',
            'options.lang.nl'=> 'Nederlands',
            'options.lang.en'=> 'Engels',
            'options.language.title' => 'taalkeuze',
            'options.language.url' => 'taalkeuze',
            'options.todo.title' => 'todo',
            'options.todo.url' => 'todo',
            'login.form.title' => 'Inlogpagina',
            'login.form.name' => 'inlognaam',
            'login.form.password' => 'wachtwoord',
            'login.form.submit' => 'inloggen',
            'login.form.or' => 'of',
            'login.persona.button' => 'inloggen met Persona',
            'login.persona.explanation' => '']
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
    
    $return['urlCheck'] = $menu;
    //$app->response()->header('Content-Type', 'application/json');
    //echo \json_encode($return);
    returnCall($return);
    
    /*
    try {
        
    } catch (Exception $ex) {

    }
    */
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
    $request = $app->request();
    $body = $request->getBody();
    $input = json_decode($body);
    if ($input->login == 'michel' && $input->password == 'mikmik') {
        //$app->response()->header('Access-Control-Expose-Headers', 'token');
        //$app->response()->header('token', 'zotteklap');
        //$app->response()->header('Content-Type', 'application/json');
        //echo \json_encode(['auth' => true]);
        returnCall(['auth' => true]);
    } else {
        $app->response()->status(400);
        //$app->response()->header('X-Status-Reason', 'oops');
    }
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
        $_oDB->name         = (string)$input->name;
        $_oDB->added        = date('Y-m-d',time());
        //$_oDB->priority     = (int)$input->priority;
        //$_oDB->done_by      = (string)$input->done_by;
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
    
    if($input->action == 'priority') {
        $_oDB = \R::load('todo',$id);
        $_oDB->priority   = $_oDB->priority == 0 ? 1 : 0;
        \R::store($_oDB);

        $_sResponse = ['priority' => $_oDB->priority];
        //$app->response()->header('Content-Type', 'application/json');
        //echo json_encode($_sResponse);
        returnCall($_sResponse);
    } 
    
    else if($input->action == 'done') {   
        $_oDB = \R::load('todo',$id);
        $_oDB->done   = $_oDB->done == 0 ? 1 : 0;
        \R::store($_oDB);

        $_sResponse = ['done' => $_oDB->done];
        //$app->response()->header('Content-Type', 'application/json');
        //echo json_encode($_sResponse);
        returnCall($_sResponse);
    }

    else if($input->action == 'removed') {   
        $_oDB = \R::load('todo',$id);
        $_oDB->removed   = 1;
        \R::store($_oDB);

        $_sResponse = ['removed' => 1];
        //$app->response()->header('Content-Type', 'application/json');
        //echo json_encode($_sResponse);
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