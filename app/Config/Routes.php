<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->addRedirect('home', '/');
$routes->get('/', 'HomeController::index', ['filter' => 'auth']);
$routes->get('/terms-and-conditions', 'TermsController::index');
$routes->get('/logout', 'AuthController::logout');
$routes->match(['get', 'post'], 'login', 'AuthController::login', ['filter' => 'noauth']);
$routes->match(['get', 'post'], 'register', 'AuthController::register', ['filter' => 'noauth']);
$routes->match(['get', 'post'], 'forgot-password', 'AuthController::forgotPassword', ['filter' => 'noauth']);
$routes->match(['get', 'post'], 'reset-password', 'AuthController::resetPassword', ['filter' => 'noauth']);
$routes->get('activation', 'AuthController::activation', ['filter' => 'noauth']);
