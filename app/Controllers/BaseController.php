<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation.
     *
     * @var list<string>
     */
    protected $helpers = ['form', 'url'];

    public function __construct()
    {
        $this->request ??= service('request');
        $this->response ??= service('response');
    }

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        $this->helpers = array_unique(array_merge($this->helpers, ['form', 'url']));

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Prevent client/browser caching of authenticated dynamic views
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');

        // Ensure SQLite3 compatibility for CONCAT() when running on serverless runtime
        $db = \Config\Database::connect();
        if ($db->DBDriver === 'SQLite3') {
            $db->initialize();
            if ($db->connID instanceof \SQLite3) {
                $db->connID->createFunction('CONCAT', static fn (...$args): string => implode('', array_map('strval', $args)));
            }
        }

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }
}
