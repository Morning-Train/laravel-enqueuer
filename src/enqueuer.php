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
	
	private static function add($what, $context, $identifier, $content, $direct = false)
	{
		if(!isset(self::${$what}[$context]))
		{
			self::${$what}[$context] = array();
		}
		self::${$what}[$context][$identifier] = array('content' => $content, 'direct' => $direct);
	}
	
	private static function addScript($context, $identifier, $content, $direct = false)
	{
		self::add('scripts', $context, $identifier, $content, $direct);
	}
	
	private static function addStyle($context, $identifier, $content, $direct = false)
	{
		self::add('styles', $context, $identifier, $content, $direct);
	}
	
	private static function clearCache($where)
	{
		$files = \Storage::disk('public')->files('cache/'.$where);
		if(is_array($files) && count($files) > 0)
		{
			foreach($files as $file)
			{
				\Storage::disk('public')->delete($file);
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
	
    private static function hasStylesCache($context)
	{
        return self::hasCache('styles/'.$context);
    }
	
    private static function hasScriptsCache($context)
	{
        return self::hasCache('scripts/'.$context);
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
        self::clearScriptsCache('public');
        self::clearStylesCache('public');
        self::clearScriptsCache('admin');
        self::clearStylesCache('admin');
    }
    
	private static function clearScriptsCache($context)
	{
		self::clearCache('scripts/'.$context);
	}
	
	private static function clearStylesCache($context)
	{
		self::clearCache('styles/'.$context);
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
			if(!$file['direct'])
			{
				$buffer .= file_get_contents($file['content']);
			}
			else 
			{
				$buffer .= $file['content'];
			}
		}

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
	
	private static function generateBufferForScripts($files)
	{
		$buffer = "";
		foreach ($files as $file) 
		{
            $content = '';
			if(!$file['direct'])
			{
				$content = file_get_contents($file['content']);
			} 
			else 
			{
				$content = $file['content'];
			} 
            $buffer .= self::minifyJs($content);
            $buffer .= ';'.PHP_EOL;
		}
        

		return $buffer;
	}
	
	private static function getScripts($context)
	{
		$output = '';
		if(isset(self::$scripts[$context]))
		{
			$scripts = self::$scripts[$context];
            $hasCache = self::hasScriptsCache($context);
			if(self::$cacheScripts || (self::$alwaysGenerateScriptsCache && $hasCache))
			{
                if(self::$alwaysGenerateScriptsCache || (!self::$alwaysGenerateScriptsCache && !$hasCache))
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
						if($script['direct'])
						{
							$output .= '<script>'.$script['content'].'</script>';	
						} 
						else 
						{
							$output .= '<script src="'.$script['content'].'"></script>';	
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
            $hasCache = self::hasStylesCache($context);
			if(self::$cacheStyles || (self::$alwaysGenerateStylesCache && $hasCache))
			{
                if(self::$alwaysGenerateStylesCache || (!self::$alwaysGenerateStylesCache && !$hasCache))
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
			} else 
			{
				if(!empty($styles))
				{
					foreach($styles as $style)
					{
						if($style['direct'])
						{
							$output .= '<style>'.$style['content'].'</style>';
						}
						else
						{
							$output .= '<link rel="stylesheet" href="'.$style['content'].'">';	
						}
					}
				}
			}
		}
		echo $output;
	}
	
	public static function __callStatic($name, $arguments)
    {
	
		if(count($arguments) == 2)
		{
			$arguments[] = false;
		}

		preg_match("/(?<=add).*?(?=Script)/", $name, $addScriptMatches);
		if(!empty($addScriptMatches))
		{
			return self::addScript(strtolower($addScriptMatches[0]), $arguments[0], $arguments[1], $arguments[2]);
		}
		
		preg_match("/(?<=add).*?(?=Style)/", $name, $addStyleMatches);
		if(!empty($addStyleMatches))
		{
			return self::addStyle(strtolower($addStyleMatches[0]), $arguments[0], $arguments[1], $arguments[2]);
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