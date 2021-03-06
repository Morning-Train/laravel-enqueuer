<?php
namespace morningtrain\enqueuer;

use Illuminate\Support\Facades\Facade;

class enqueuer extends Facade 
{
	
	private static $cacheScripts = true;
	private static $cacheStyles = true;
	
    private static $alwaysGenerateStylesCache = false;
    private static $alwaysGenerateScriptsCache = false;
	
    private static $storageDisk = 'public';
	
	private static $scripts = array();
	private static $styles = array();
	
    protected static function getFacadeAccessor() 
	{ 
		return 'enqueuer'; 
	}
	
	public static function configure($options)
	{
		$editable = [
			'cacheScripts',
			'cacheStyles',
			'alwaysGenerateStylesCache',
			'alwaysGenerateScriptsCache',
			'storageDisk'
		];
		if(is_array($options))
		{
			foreach($editable as $val)
			{
				if(isset($options[$val]))
				{
					self::${$val} = $options[$val];
				}
			}
		}
	}
	
	

	private static function insertAfterKeys($key, $entity, &$array, $keys)
	{
		$count = -1;
		foreach ( array_reverse($array) as $index => $value )
		{
			$count++;
			if(in_array($index, $keys))
			{
				$temp = array();
				foreach($array as $i => $v)
				{
					if($i === $key)
					{
						continue;
					}
					$temp[$i] = $v;
					if($i === $index)
					{
						$temp[$key] = $entity;
					}
				}
				$array = $temp;
				return;
			}
		}
	}

	private static function isAfterKeys($key, $keys, $array)
	{
		foreach($array as $index => $value)
		{
			if($index === $key)
			{
				return false;
			}
			if(in_array($index, $keys))
			{
				unset($keys[array_search($index, $keys)]);
			}
			if(empty($keys))
			{
				return true;
			}
		}
		return true;
	}
	
	private static function resolveDependencies($list)
	{
		if(!empty($list))
		{
			$count = 0;
			$i = 0;
			$dependenciesAreResolved = false;
			while ($dependenciesAreResolved === false)
			{
				$foundWrongDependencies = false;
				$count++;
				foreach($list as $slug => $entity)
				{
					$i++;
					if(isset($entity['dependencies']) && !empty($entity['dependencies']))
					{
						$dependencies = $entity['dependencies'];
						if(!self::isAfterKeys($slug, $dependencies, $list))
						{
							$foundWrongDependencies = true;
							self::insertAfterKeys($slug, $entity, $list, $dependencies);
						}
					}
				}
				if(!$foundWrongDependencies)
				{
					break;
				}
				if($count > 100)
				{
					break;
				}
			}
		}
		return $list;
	}
	
	private static function resolveScriptDependencies()
	{
		$scripts = self::$scripts;
		foreach($scripts as $context => $list)
		{
			$list = self::resolveDependencies($list);
			$scripts[$context] = $list;
		}
		self::$scripts = $scripts;
	}
	
	private static function resolveStyleDependencies()
	{
		$styles = self::$styles;
		foreach($styles as $context => $list)
		{
			$list = self::resolveDependencies($list);
			$styles[$context] = $list;
		}
		self::$styles = $styles;
	}

	private static function add($what, $context, $identifier, $arguments)
	{
		if(!isset(self::${$what}[$context]))
		{
			self::${$what}[$context] = array();
		}
		self::${$what}[$context][$identifier] = $arguments;
	}
	
	private static function addScript($context, $identifier, $arguments)
	{
		if(isset($arguments['data']) && isset($arguments['data']['object']) && isset($arguments['data']['properties']) && !empty($arguments['data']['object']) && !empty($arguments['data']['properties']))
		{
			$content = 'if(typeof('.$arguments['data']['object'].') == "undefined"){ var '.$arguments['data']['object'].' = {}; } ';
			foreach($arguments['data']['properties'] as $key => $val)
			{
				$content .= $arguments['data']['object'].'["'.$key.'"] = '.json_encode($val).';'.PHP_EOL;
			}
			if(!isset($arguments['content']))
			{
				$arguments['content'] = '';
			}
			$arguments['content'] = $content . $arguments['content'];
		}
		self::add('scripts', $context, $identifier, $arguments);
	}
	
	private static function addStyle($context, $identifier, $arguments)
	{
		self::add('styles', $context, $identifier, $arguments);
	}
	
	private static function clearCache($where)
	{
		$files = \Storage::disk(self::$storageDisk)->allFiles('cache/'.$where);
		if(is_array($files) && count($files) > 0)
		{
			foreach($files as $file)
			{
				\Storage::disk(self::$storageDisk)->delete($file);
			}
		}
		$dirs = \Storage::disk(self::$storageDisk)->allDirectories('cache/'.$where);
		if(is_array($dirs) && count($dirs) > 0)
		{
			foreach($dirs as $dir)
			{
				\Storage::disk(self::$storageDisk)->deleteDirectory($dir);
			}
		}
	}
    
    private static function hasCache($where, $context)
	{
		$files = \Storage::disk(self::$storageDisk)->files('cache/'.$where.'/'.$context);
		if(is_array($files) && count($files) > 0)
		{
            return true;
		}        
        return false;
    }
    
    private static function getCache($where)
	{
		$files = \Storage::disk(self::$storageDisk)->files('cache/'.$where);
		if(is_array($files) && count($files) > 0)
		{
            return $files[0];
		}        
        return null;
    }
	
    private static function getStylesCache($context)
	{
        return self::getCache('styles/'.$context);
    }
	
    private static function getScriptsCache($context)
	{
        return self::getCache('scripts/'.$context);
    }
    
    public static function clearAllCache()
	{
        self::clearScriptsCache();
        self::clearStylesCache();
    }
    
	public static function clearScriptsCache()
	{
		self::clearCache('scripts');
	}
    
	public static function clearScriptsCacheForContext($context)
	{
		self::clearCache('scripts/'.$context);
	}
	
	public static function clearStylesCache()
	{
		self::clearCache('styles');
	}
    
	public static function clearStylesCacheForContext($context)
	{
		self::clearCache('styles/'.$context);
	}
	
	private static function cacheFile($name, $content)
	{
		$check = \Storage::disk(self::$storageDisk)->put($name, $content);
	}
	
	private static function generateBufferForStyles($files)
	{
		$buffer = "";
		
		foreach ($files as $file) 
		{
            $content = self::getContentForEntity($file);
            $buffer .= self::minifyJs($content);
		}
		
		return $buffer;
	}
    
    private static function minifyCss($buffer)
	{        
		// Remove comments
		$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);

		// Remove space after colons
		$buffer = str_replace(': ', ':', $buffer);

		// Remove whitespace
		$buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);

        return $buffer;
    }
    
    private static function minifyJs($buffer)
	{
        // Remove comments
		$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
		
        return $buffer;
    }
	
	private static function getContentForEntity($entity)
	{
		$content = '';
		if(isset($entity['content']))
		{
			$content .= $entity['content'].PHP_EOL;
		}
		if(isset($entity['location']))
		{
			$content .= file_get_contents($entity['location']).PHP_EOL;
		}
		return $content;
	}
	
	private static function generateBufferForScripts($files)
	{
		$buffer = "";
		foreach ($files as $file) 
		{
            $content = self::getContentForEntity($file);
            $buffer .= self::minifyJs($content);
            $buffer .= ';'.PHP_EOL;
		}
		return $buffer;
	}
	
	private static function useCache($type)
	{
		if($type == 'styles')
		{
			return self::$cacheStyles || (self::$alwaysGenerateStylesCache);
		}
		if($type == 'scripts')
		{
			return self::$cacheScripts || (self::$alwaysGenerateScriptsCache);
		}
		return false;
	}
	
	private static function shouldCacheBeGenerated($type, $context)
	{
		$hasCache = self::hasCache($type, $context);
		if($type == 'styles')
		{
			return self::$alwaysGenerateStylesCache || (!self::$alwaysGenerateStylesCache && !$hasCache);
		}
		if($type == 'scripts')
		{
			return self::$alwaysGenerateScriptsCache || (!self::$alwaysGenerateScriptsCache && !$hasCache);
		}
		return false;
	}
	
	private static function getScripts($context)
	{
		self::resolveScriptDependencies();
		$output = '';
		if(isset(self::$scripts[$context]))
		{
			$scripts = self::$scripts[$context];
			if(self::useCache('scripts'))
			{
                if(self::shouldCacheBeGenerated('scripts', $context))
				{
    				$buffer = self::generateBufferForScripts($scripts);
    				self::clearScriptsCacheForContext($context);
    				$cachedScriptName = uniqid().'.js';
    				$cachedScriptName = 'cache/scripts/'.$context.'/'.$cachedScriptName;
    				self::cacheFile($cachedScriptName, $buffer);
                } 
				else
				{
                    $cachedScriptName = self::getScriptsCache($context);
                } 
				$output .= '<script src="'.url($cachedScriptName).'"></script>';	
			} 
			else 
			{
				if(!empty($scripts))
				{
					foreach($scripts as $script)
					{
						if(isset($script['content']))
						{
							$output .= '<script>'.$script['content'].'</script>';	
						}
						if(isset($script['location']))
						{
							$output .= '<script src="'.$script['location'].'"></script>';
						}
					}
				}
			}
			
		}
		echo $output;
	}
	
	private static function getStyles($context)
	{
		self::resolveStyleDependencies();
		$output = '';
		if(isset(self::$styles[$context]))
		{
			$styles = self::$styles[$context];
			if(self::useCache('styles'))
			{
                if(self::shouldCacheBeGenerated('styles', $context))
				{
    				$buffer = self::generateBufferForStyles($styles);
    				self::clearStylesCache($context);
    				$cachedStylesheetName = uniqid().'.css';
    				$cachedStylesheetName = 'cache/styles/'.$context.'/'.$cachedStylesheetName;
    				self::cacheFile($cachedStylesheetName, $buffer);
                } 
				else 
				{
                    $cachedStylesheetName = self::getStylesCache($context);
                }                	
   				$output .= '<link rel="stylesheet" href="'.url($cachedStylesheetName).'">';
			} 
			else 
			{
				if(!empty($styles))
				{
					foreach($styles as $style)
					{
						if(isset($style['content']))
						{
							$output .= '<style>'.$style['content'].'</style>';
						}
						if(isset($style['location']))
						{
							$output .= '<link rel="stylesheet" href="'.$style['location'].'">';
						}
					}
				}
			}
		}
		echo $output;
	}
	
	public static function __callStatic($name, $arguments)
    {
	
		preg_match("/(?<=add).*?(?=Script)/", $name, $matches);
		if(!empty($matches))
		{
			return self::addScript(strtolower($matches[0]), $arguments[0], $arguments[1]);
		}
		
		preg_match("/(?<=add).*?(?=Style)/", $name, $matches);
		if(!empty($matches))
		{
			return self::addStyle(strtolower($matches[0]), $arguments[0], $arguments[1]);
		}
		
		preg_match("/(?<=get).*?(?=Scripts)/", $name, $matches);
		if(!empty($matches))
		{
			return self::getScripts(strtolower($matches[0]));
		}
		
		preg_match("/(?<=get).*?(?=Styles)/", $name, $matches);
		if(!empty($matches))
		{
			return self::getStyles(strtolower($matches[0]));
		}
		
		preg_match("/(?<=clear).*?(?=ScriptsCache)/", $name, $matches);
		if(!empty($matches))
		{
			return self::clearScriptsCacheForContext(strtolower($matches[0]));
		}
		
		preg_match("/(?<=clear).*?(?=StylesCache)/", $name, $matches);
		if(!empty($matches))
		{
			return self::clearStylesCacheForContext(strtolower($matches[0]));
		}
		
		throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Unknown method: '.$name);
    }

}