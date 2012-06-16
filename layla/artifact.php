<?php
/**
 * Artifact - A View abstraction taken from Layla.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file licence.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@getlayla.com so I can send you a copy immediately.
 *
 * @package    Layla Components
 * @version    1.0
 * @author     Koen Schmeets <koen@getlayla.com>
 * @license    MIT License
 * @link       http://getlayla.com
 */

namespace Layla;

use Layla\Artifact\Renderers\Tabs;
use Layla\Artifact\Renderers\Table;
use Layla\Artifact\Renderers\Bootstrap;
use Layla\Artifact\Catcher;

use Closure;
use Exception;

use Laravel\Str;
use Laravel\Config;
use Laravel\Bundle;

/**
 * This class can turn an array or a callback defining a view into HTML and has
 * Driver support. (Bootstrap, Table and Tabs drivers are included)
 */
class Artifact {

	/**
	 * The currently active artifact renderers.
	 *
	 * @var array
	 */
	public static $drivers = array();

	/**
	 * All the artifacts that have been registered
	 * 
	 * @var array
	 */
	public static $artifacts = array();

	/**
	 * The third-party artifact renderer registrar.
	 *
	 * @var array
	 */
	public static $registrar = array();

	/**
	 * Get a artifact renderer driver instance.
	 *
	 * @param  string  $driver
	 * 
	 * @return Driver
	 */
	public static function driver($driver = null)
	{
		if (is_null($driver)) $driver = Config::get('artifact.renderer.driver');

		if ( ! isset(static::$drivers[$driver]))
		{
			static::$drivers[$driver] = static::factory($driver);
		}

		return static::$drivers[$driver];
	}

	/**
	 * Create a new artifact renderer driver instance.
	 *
	 * @param  string  $driver
	 * 
	 * @return Driver
	 */
	protected static function factory($driver)
	{
		if (isset(static::$registrar[$driver]))
		{
			$resolver = static::$registrar[$driver];

			return $resolver();
		}

		switch ($driver)
		{
			case 'bootstrap':
				return new Bootstrap;
			case 'table':
				return new Table;
			case 'tabs':
				return new Tabs;

			default:
				throw new Exception("Artifact renderer driver \"{$driver}\" is not supported.");
		}
	}

	/**
	 * Register a third-party artifact renderer driver.
	 *
	 * @param  string   $driver
	 * @param  Closure  $resolver
	 * 
	 * @return void
	 */
	public static function extend($driver, Closure $resolver)
	{
		static::$registrar[$driver] = $resolver;
	}

	/**
	 * Retrieve a page or form
	 * 
	 * @param 	string 	$type 		whether we are loading a page or a form
	 * @param 	string 	$name 		the page identifier
	 * @param 	array 	$arguments 	extra arguments
	 * 
	 * @return 	string 	the HTML
	 */
	protected static function load($type, $name, $arguments = array())
	{
		if( ! array_key_exists($name, static::$artifacts[$type]))
		{
			throw new Exception("The {$type} you are trying to retrieve does not exist.");
		}

		if(static::$artifacts[$type][$name] instanceof Closure)
		{
			return static::render(static::$artifacts[$type][$name]);
		}
		
		list($file, $class_name, $method) = static::parse($name, $type);

		require_once $file;

		return static::render(function($catcher) use ($class_name, $method, $arguments)
		{
			array_unshift($arguments, $catcher);
			$class = new $class_name;
			return call_user_func_array(array($class, $method), $arguments);
		});
	}

	/**
	 * Parse the path to the form or page
	 * 
	 * @param string $name
	 * @param string $type
	 * 
	 * @return array($file,$class_name,$method)
	 */
	protected static function parse($name, $type)
	{
		list($path, $method) = explode('@', static::$artifacts[$type][$name]);
		list($bundle, $path) = Bundle::parse($path);

		$file = Bundle::path($bundle).Str::plural($type).DS.str_replace('.', DS, $path).EXT;
		$class_name = static::format($bundle, $path, $type);

		return array($file, $class_name, $method);
	}

	/**
	 * Format a bundle and controller identifier into the Form's or Page's class name.
	 *
	 * @param  string  $bundle
	 * @param  string  $controller
	 * 
	 * @return string
	 */
	protected static function format($bundle, $path, $type)
	{
		return Bundle::class_prefix($bundle).Str::classify($path).'_'.ucfirst($type);
	}

	/**
	 * Register the route to a form or page
	 * 
	 * @param 	string 	$type 		whether we are registering a page or a form
	 * @param 	string 	$name 		the identifier
	 * @param 	string 	$action 	path to the class and method
	 * 
	 * @return 	void
	 */
	public static function register($type, $name, $action = null)
	{
		static::$artifacts[$type][$name] = $action;
	}

	/**
	 * The method for rendering the fields
	 * 
	 * @param Closure 	$callback 	The Closure containing the calls
	 * 
	 * @return string 	the generated HTML
	 */
	public static function render($callback)
	{
		return static::driver()->render($callback);
	}

	/**
	 * The method for loading a registered artifact
	 * 
	 * @param	string	$method The method being called, identifying the artifact's type
	 * @param	array	$parameters The parameters on the method
	 */
	public static function __callStatic($method, $parameters)
	{
		$name = array_shift($parameters);

		return static::load($method, $name, $parameters);
	}

}