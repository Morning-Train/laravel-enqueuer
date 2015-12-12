<?php
namespace morningtrain;

use Illuminate\Support\Facades\Facade;

class enqueuer extends Facade {
	
	private static $cache = true;
	
	private static $cacheScripts = true;
	private static $cacheStyles = true;
	
    private static $alwaysGenerateStylesCache = false;
    private static $alwaysGenerateScriptsCache = false;
	
	private static $scripts = array();
	private static $styles = array();
	
    protected static function getFacadeAccessor() 
	{ 
		return 'enqueuer'; 
	}
	
	
	private static function uksortScriptsCallback($a, $b)
	{
		
	}
	
	private static function resolveDependencies()
	{
		$scripts = self::$scripts;
		foreach($scripts as $context => $scriptsList)
		{
			uksort($scriptsList, function($a, $b) use ($scriptsList){
				if(isset($scriptsList[$b]['dependencies']) && is_array($scriptsList[$b]['dependencies']))
				{
					if(in_array($a, $scriptsList[$b]['dependencies']))
					{
						return -1;
					}
				}
				if(isset($scriptsList[$a]['dependencies']) && is_array($scriptsList[$a]['dependencies']))
				{
					if(in_array($b, $scriptsList[$a]['dependencies']))
					{
						return 1;
					}
				}
				return 0;
			});
			$scripts[$context] = $scriptsList;
		}
		self::$scripts = $scripts;
		
		$styles = self::$styles;
		foreach($styles as $context => $stylesList)
		{
			uksort($stylesList, function($a, $b) use ($stylesList){
				if(isset($stylesList[$b]['dependencies']) && is_array($stylesList[$b]['dependencies']))
				{
					if(in_array($a, $stylesList[$b]['dependencies']))
					{
						return -1;
					}
				}
				if(isset($stylesList[$a]['dependencies']) && is_array($stylesList[$a]['dependencies']))
				{
					if(in_array($b, $stylesList[$a]['dependencies']))
					{
						return 1;
					}
				}
				return 0;
			});
			$styles[$context] = $stylesList;
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
		self::resolveDependencies();
	}
	
	private static function addScript($context, $identifier, $arguments)
	{
		self::add('scripts', $context, $identifier, $arguments);
	}
	
	private static function addStyle($context, $identifier, $arguments)
	{
		self::add('styles', $context, $identifier, $arguments);
	}
	
	private static function clearCache($where)
	{
		$files = \Storage::disk('public')->allFiles('cache/'.$where);
		if(is_array($files) && count($files) > 0)
		{
			foreach($files as $file)
			{
				\Storage::disk('public')->delete($file);
			}
		}
		$dirs = \Storage::disk('public')->allDirectories('cache/'.$where);
		if(is_array($dirs) && count($dirs) > 0)
		{
			foreach($dirs as $dir)
			{
				\Storage::disk('public')->deleteDirectory($dir);
			}
		}
	}
    
    private static function hasCache($where)
	{
		$files = \Storage::disk('public')->files('cache/'.$where);
		if(is_array($files) && count($files) > 0)
		{
            return true;
		}        
        return false;
    }
    
    private static function getCache($where)
	{
		$files = \Storage::disk('public')->files('cache/'.$where);
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
    
	private static function clearScriptsCache()
	{
		self::clearCache('scripts');
	}
	
	private static function clearStylesCache()
	{
		self::clearCache('styles');
	}
	
	private static function cacheFile($name, $content)
	{
		\Storage::disk('public')->put($name, $content);
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
			$content = $entity['content'];
		}
		else if(isset($entity['location']))
		{
			$content = file_get_contents($entity['location']);
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
			return self::$cacheStyles || (self::$alwaysGenerateStylesCache && $hasCache);
		}
		if($type == 'scripts')
		{
			return self::$cacheScripts || (self::$alwaysGenerateScriptsCache && $hasCache);
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
		$output = '';
		if(isset(self::$scripts[$context]))
		{
			$scripts = self::$scripts[$context];
			if(self::useCache('scripts'))
			{
                if(self::shouldCacheBeGenerated('scripts', $context))
				{
    				$buffer = self::generateBufferForScripts($scripts);
    				self::clearScriptsCache($context);
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
						if(isset($script['content']))
						{
							$output .= '<style>'.$style['content'].'</style>';
						}
						if(isset($script['location']))
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
	
		preg_match("/(?<=add).*?(?=Script)/", $name, $addScriptMatches);
		if(!empty($addScriptMatches))
		{
			return self::addScript(strtolower($addScriptMatches[0]), $arguments[0], $arguments[1]);
		}
		
		preg_match("/(?<=add).*?(?=Style)/", $name, $addStyleMatches);
		if(!empty($addStyleMatches))
		{
			return self::addStyle(strtolower($addStyleMatches[0]), $arguments[0], $arguments[1]);
		}
		
		preg_match("/(?<=get).*?(?=Scripts)/", $name, $getScriptsMatches);
		if(!empty($getScriptsMatches))
		{
			return self::getScripts(strtolower($getScriptsMatches[0]));
		}
		
		preg_match("/(?<=get).*?(?=Styles)/", $name, $getStylesMatches);
		if(!empty($getStylesMatches))
		{
			return self::getStyles(strtolower($getStylesMatches[0]));
		}
		
		return null;
    }

}