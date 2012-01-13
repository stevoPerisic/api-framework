<?php
/**
 * API framework front controller.
 * 
 * @package api-framework
 * @author  Martin Bean <martin@martinbean.co.uk>
 */

/**
 * Autoloader function.
 */
function __autoload($class_name) {
    $filename = preg_replace('/([a-z]+)([A-Z]+)/', '$1_$2', $class_name);
    if (file_exists('classes/models/' . $filename . '.php')) {
        require_once 'classes/models/' . $filename . '.php';
    }
    else if (file_exists('classes/controllers/' . $filename . '.php')) {
        require_once 'classes/controllers/' . $filename . '.php';
    }
    else if (file_exists('classes/' . $filename . '.php')) {
        require_once 'classes/' . $filename . '.php';
    }
    else {
        // class does not exist; throw exception
    }
}

// parse the incoming request
$request = new Request();
if (isset($_SERVER['PATH_INFO'])) {
    $request->url_elements = explode('/', trim($_SERVER['PATH_INFO'], '/'));
}
$request->method = strtoupper($_SERVER['REQUEST_METHOD']);
switch ($request->method) {
    case 'GET':
        $request->parameters = $_GET;
    break;
    case 'POST':
        $request->parameters = $_POST;
    break;
    case 'PUT':
        parse_str(file_get_contents('php://input'), $request->parameters);
    break;
}

// route the request
if (!empty($request->url_elements)) {
    $controller_name = ucfirst($request->url_elements[0]) . 'Controller';
    if (class_exists($controller_name)) {
        $controller = new $controller_name;
        $action_name = strtolower($request->method);
        $response_str = call_user_func_array(array($controller, $action_name), array($request));
    }
    else {
        header('HTTP/1.1 404 Not Found');
        $response_str = 'Unknown request: ' . $request->url_elements[0];
    }
}
else {
    $response_str = 'Unknown request';
}

// send the response to the client
$response_obj = Response::create($response_str, 'json');
echo $response_obj->render();