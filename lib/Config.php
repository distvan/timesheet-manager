<?php
namespace DotLogics;

use LSS\XML2Array;
use \DOMDocument;

/**
 * Config Handling class
 *
 * @author Istvan Dobrentei
 * @copyright DotLogics Hungary Kft.
 * @url https://www.dotlogics.hu
 */
final class Config
{
    const CONFIG_FILE = 'config.xml';
    private static $_config;

    static private function init()
    {
        self::$_config = array();
        $doc = new DOMDocument();
        $doc->load(realpath(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . Config::CONFIG_FILE);
        self::$_config = XML2Array::createArray($doc->saveXML());
    }

    static public function getConfig()
    {
        self::init();
        return self::$_config['config'];
    }

    static public function getPortalConfig()
    {
        self::init();
        return [
            'settings' => [
                'determineRouteBeforeAppMiddleware' => self::$_config['config']['app']['displayErrorDetails'],
                'displayErrorDetails' => self::$_config['config']['app']['displayErrorDetails'],
                'view' => [
                    'template_path' => __DIR__ . DIRECTORY_SEPARATOR . self::$_config['config']['view']['templatePath'],
                    'twig' => [
                        'cache' => __DIR__ . DIRECTORY_SEPARATOR . self::$_config['config']['view']['twig']['cache'],
                        'debug' => self::$_config['config']['view']['twig']['debug'],
                        'auto_reload' => self::$_config['config']['view']['twig']['autoReload'],
                    ],
                ],
                'logger' => [
                    'name' => self::$_config['config']['logger']['name'],
                    'path' => __DIR__ . DIRECTORY_SEPARATOR . self::$_config['config']['logger']['path'],
                ],
            ],
        ];
    }
}
?>