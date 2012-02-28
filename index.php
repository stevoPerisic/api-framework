<?php
/**
 * API framework front controller.
 * 
 * @package api-framework
 * @author  Martin Bean <martin@martinbean.co.uk>
 */

/**
 * Model autoloader.
 * 
 * @param string $model_name
 */
function autoload_model($model_name) {
    autoload_class($model_name, 'models');
}

/**
 * Controller autoloader.
 * 
 * @param string $controller_name
 */
function autoload_controller($controller_name) {
    autoload_class($controller_name, 'controllers');
}

/**
 * Generic class autoloader.
 * 
 * @param string $class_name
 */
function autoload_class($class_name, $sub_directory = '') {
    $directory = 'classes'.DIRECTORY_SEPARATOR;
    $filename  = strtolower(preg_replace('/([a-z]+)([A-Z]+)/', '$1_$2', $class_name));
    if (strlen($sub_directory) > 0) {
        $directory.= $sub_directory.DIRECTORY_SEPARATOR;
    }
    if (file_exists($directory . $filename . '.php')) {
        require_once $directory . $filename . '.php';
    }
}

/**
 * Register autoloader functions.
 */
spl_autoload_register('autoload_model');
spl_autoload_register('autoload_controller');
spl_autoload_register('autoload_class');

/**
 * Parse the incoming request.
 */
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

/**
 * Route the request.
 */
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

/**
 * Send the response to the client.
 */
$response_obj = Response::create($response_str, 'json');
echo $response_obj->render();