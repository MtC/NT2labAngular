<?php

require '../../config.php';
require '../../Slim/Slim.php';
require '../../Slim/Middleware.php';
require '../../Slim/Middleware/Tokenizer.php';
require '../../RedBean/rb.php';
require '../../Mptt/Mptt.php';
//require '../../Persona/Persona.php';

\Slim\Slim::registerAutoloader();
\Slim\Route::setDefaultConditions(array(
  'id' => '[0-9]{1,}',
));

R::setup("mysql:host={$config['host']};dbname={$config['dbname']}", $config['name'], $config['password']);
R::freeze(true);

$connection = new \PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['name'], $config['password']);

$app = new \Slim\Slim(['debug' => true]);
$app->add(new \Tokenizer());

class ResourceNotFoundException extends Exception {}

/**
 *  testroutes
 */
$app->get('/trial', function () use ($app, $connection) {
    $mptt = new \Mptt\Mptt($connection);
    $mptt->createTable('languages');
});

/**
 *  set returnCalls (correct and error messages)
 */
function returnCall($response) {
    $app = \Slim\Slim::getInstance();
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode($response);
}

function return400 ($e) {
    $app = \Slim\Slim::getInstance();
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
}

function return401 ($e) {
    $app = \Slim\Slim::getInstance();
    $app->response()->status(401);
    $app->response()->header('X-Status-Reason', $e->getMessage());
    $app->response()->header('WWW-Authenticate', 'FormBased');
}

/**
 *  logging in and out
 */
$app->post('/login', function () use ($app) {
    try {
        if (1 == 1) {
            returnCall(['response' => 'ok']);
        } else {
            throw new Exception($app->environment()['error']);
        }
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

$app->post('/logout', function () use ($app) {
    returnCall([], 'logout');
});

/**
 *  route: apps
 */
$app->get('/app', function () use ($app) {
    $_oDB = R::find('apps');
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode(R::exportAll($_oDB));
});

$app->get('/apps/:id', function ($id) use ($app) {    
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

$app->post('/apps', function () use ($app) {    
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

        returnCall($_oDB);
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

/**
 *  route: todo
 */
$app->get('/todo', function () use ($app) {
    try {
        if (isset($app->environment()['user'])) {
            $_oDB = R::getAll('SELECT id, done, name, priority, done_by, description FROM todo WHERE removed = 0 AND user_id = :user_id', [':user_id' => $app->environment()['user']['id']]);
            returnCall($_oDB);
        } else {
            throw new Exception('not logged in correctly');
        }
    } catch (Exception $e) {
        return401 ($e);
    }
    
});

$app->get('/todo/:id', function ($id) use ($app) {
    try {
        if (isset($app->environment()['user'])) {
            $_oDB = R::getAll('SELECT id, done, name, priority, done_by, description FROM todo WHERE id = :id AND user_id = :user_id', [':id' => $id, ':user_id' => $app->environment()['user']['id']]);
            returnCall($_oDB);
        } else {
            throw new Exception('not logged in correctly');
        }
    } catch (Exception $e) {
        return401 ($e);
    }
});

$app->post('/todo', function () use ($app) {    
    try {
        if (isset($app->environment()['user'])) {
            $request = $app->request();
            $body = $request->getBody();
            $input = json_decode($body); 
    
            $_oDB = \R::dispense('todo');
            $_oDB->name         = (string)$input->todo;
            $_oDB->user_id      = $app->environment()['user']->id;
            $_oDB->description  = isset($input->description) ? $input->description : '';
            $_oDB->added        = date('Y-m-d',time());
            $_oDB->priority     = isset($input->priority) ? $input->priority : 0;
            $_oDB->done_by      = $input->doneBy;
            $id = \R::store($_oDB);    
            
            returnCall(['message' => 'added todo']);
        } else {
            throw new Exception('not logged in correctly');
        }
    } catch (Exception $e) {
        return401 ($e);
    }
});

$app->put('/todo/:id', function ($id) use ($app) {
    try {
        if (isset($app->environment()['user'])) {
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
        } else {
            throw new Exception('not logged in correctly');
        }
    } catch (Exception $e) {
        return401 ($e);
    }
});

/**
 *  route: language and menu
 */
$app->get('/lang/:lang', function ($lang) use ($app) {
    try {
        $_language = R::find('languages', 'language = :language', [':language' => $lang]);
        if ($_language) {
            foreach ($_language as $object) {
                $return[$object->title] = $object->text;
            }
            returnCall($return);
        } else {
            throw new Exception ('language not loaded');
        }
    } catch (Exception $ex) {
        return400 ($e);
    }
});

$app->get('/menu', function () use ($app) {
    $return = [];

    $_languages = R::find('languages', 'type = :type', [':type' => 'url']);
    if ($_languages) {
        foreach ($_languages as $_object) {
            $return['urlToLanguage'][$_object->language][$_object->title] = $_object->text;
        }
        foreach ($_languages as $_object) {
            $return['languageToUrl'][$_object->language][$_object->text] = $_object->title;
        }
    }
    
    $_menu = R::find('menu');
    if ($_menu) {
        foreach ($_menu as $_object) {
            $return['urlToMenu'][$_object->title] = $_object->text;
        }
    }
    
    returnCall($return);
});

$app->run();