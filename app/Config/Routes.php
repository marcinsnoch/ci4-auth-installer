<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'HomeController::index', ['filter' => 'auth']);
$routes->get('/terms-and-conditions', 'TermsController::index');
$routes->get('/user-profile', 'ProfileController::index', ['filter' => 'auth']);
$routes->put('/user-profile', 'ProfileController::update', ['filter' => 'auth']);
$routes->get('/products/new', 'ProductController::new', ['filter' => 'auth']);
$routes->get('/products/table-data', 'ProductController::tableData', ['filter' => 'auth']);
$routes->post('/products', 'ProductController::create', ['filter' => 'auth']);
$routes->get('/products', 'ProductController::index', ['filter' => 'auth']);
$routes->get('/products/(:segment)', 'ProductController::show/$1', ['filter' => 'auth']);
$routes->get('/products/(:segment)/edit', 'ProductController::edit/$1', ['filter' => 'auth']);
$routes->put('/products/(:segment)', 'ProductController::update/$1', ['filter' => 'auth']);
$routes->get('/products/(:segment)/remove', 'ProductController::delete/$1', ['filter' => 'auth']);

/**
 * Auth routing
 */
$routes->get('/logout', 'AuthController::logout');
$routes->match(['get', 'post'], 'login', 'AuthController::login', ['filter' => 'noauth']);
$routes->match(['get', 'post'], 'register', 'AuthController::register', ['filter' => 'noauth']);
$routes->match(['get', 'post'], 'forgot-password', 'AuthController::forgotPassword', ['filter' => 'noauth']);
$routes->match(['get', 'post'], 'reset-password', 'AuthController::resetPassword', ['filter' => 'noauth']);
$routes->get('activation', 'AuthController::activation', ['filter' => 'noauth']);
