<?php

use Illuminate\Support\Facades\Facade;


class enqueuer extends Facade {

    protected static function getFacadeAccessor() { return 'enqueuer'; }

	
	private static $cache = true;
	private static $cacheScripts = true;
	private static $cacheStyles = true;
    private static $alwaysGenerateStylesCache = false;
    private static $alwaysGenerateScriptsCache = false;
	private static $scripts = array();
	private static $styles = array();
	
	private static function add($what, $context, $identifier, $content, $direct = false){
		if(!isset(self::${$what}[$context])){
			self::${$what}[$context] = array();
		}
		self::${$what}[$context][$identifier] = array('content' => $content, 'direct' => $direct);
	}
	
	private static function addScript($context, $identifier, $content, $direct = false){
		self::add('scripts', $context, $identifier, $content, $direct);
	}
	
	private static function addStyle($context, $identifier, $content, $direct = false){
		self::add('styles', $context, $identifier, $content, $direct);
	}	
	
	public static function addAdminScript($identifier, $content, $direct = false){
		self::addScript('admin', $identifier, $content, $direct);
	}
	
	public static function addPublicScript($identifier, $content, $direct = false){
		self::addScript('public', $identifier, $content, $direct);
	}
	
	public static function addAdminStyle($identifier, $content, $direct = false){
		self::addStyle('admin', $identifier, $content, $direct);
	}
	
	public static function addPublicStyle($identifier, $content, $direct = false){
		self::addStyle('public', $identifier, $content, $direct);
	}
	
	private static function clearCache($where){
		$files = \Storage::disk('public')->files('cache/'.$where);
		if(is_array($files) && count($files) > 0){
			foreach($files as $file){
				\Storage::disk('public')->delete($file);
			}
		}
	}
    
    private static function hasCache($where){
		$files = \Storage::disk('public')->files('cache/'.$where);
		if(is_array($files) && count($files) > 0){
            return true;
		}        
        return false;
    }
	
    private static function hasStylesCache($context){
        return self::hasCache($context.'-styles');
    }
	
    private static function hasScriptsCache($context){
        return self::hasCache($context.'-scripts');
    }
    
    private static function getCache($where){
		$files = \Storage::disk('public')->files('cache/'.$where);
		if(is_array($files) && count($files) > 0){
            return $files[0];
		}        
        return null;
    }
	
    private static function getStylesCache($context){
        return self::getCache($context.'-styles');
    }
	
    private static function getScriptsCache($context){
        return self::getCache($context.'-scripts');
    }
    
    public static function clearAllCache(){
        self::clearScriptsCache('public');
        self::clearStylesCache('public');
        self::clearScriptsCache('admin');
        self::clearStylesCache('admin');
    }
    
	private static function clearScriptsCache($context){
		self::clearCache($context.'-scripts');
	}
	
	private static function clearStylesCache($context){
		self::clearCache($context.'-styles');
	}
	
	private static function cacheFile($name, $content){
		\Storage::disk('public')->put($name, $content);
	}
	
	private static function generateBufferForStyles($files){
		$buffer = "";
		
		foreach ($files as $file) {
			
			if(!$file['direct']){
				$buffer .= file_get_contents($file['content']);
			} else {
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
    
    private static function minifyJs($buffer){
        
        // Remove comments
		$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);

        //Minify
        //$buffer = Minifier::minify($buffer);
        
        return $buffer;
    }
	
	private static function generateBufferForScripts($files){
		$buffer = "";
		foreach ($files as $file) {
            $content = '';
			if(!$file['direct']){
				$content = file_get_contents($file['content']);
			} else {
				$content = $file['content'];
			} 
            $buffer .= self::minifyJs($content);
            $buffer .= ';'.PHP_EOL;
		}
        

		return $buffer;
	}
	
	private static function getScripts($context){
		$output = '';
		if(isset(self::$scripts[$context])){
			$scripts = self::$scripts[$context];
            $hasCache = self::hasScriptsCache($context);
			if(self::$cacheScripts || (self::$alwaysGenerateScriptsCache && $hasCache)){
                if(self::$alwaysGenerateScriptsCache || (!self::$alwaysGenerateScriptsCache && !$hasCache)){
    				$buffer = self::generateBufferForScripts($scripts);
    				self::clearScriptsCache($context);
    				$cachedScriptName = uniqid().'.js';
    				$cachedScriptName = 'cache/'.$context.'-scripts/'.$cachedScriptName;
    				self::cacheFile($cachedScriptName, $buffer);
                } else {
                    $cachedScriptName = self::getScriptsCache($context);
                } 
				$output .= '<script src="'.url($cachedScriptName).'"></script>';	
			} else {
				if(!empty($scripts)){
					foreach($scripts as $script){
						if($script['direct']){
							$output .= '<script>'.$script['content'].'</script>';	
						} else {
							$output .= '<script src="'.$script['content'].'"></script>';	
						}
					}
				}
			}
			
		}
		// echo '<pre>';var_dump(self::$scripts);echo '</pre>';
		echo $output;
	}
	
	private static function getStyles($context){
		$output = '';
		
		// echo '<pre>';
		// var_dump(self::$styles);
		// echo '</pre>';
		
		if(isset(self::$styles[$context])){
			$styles = self::$styles[$context];
            $hasCache = self::hasStylesCache($context);
			if(self::$cacheStyles || (self::$alwaysGenerateStylesCache && $hasCache)){
                if(self::$alwaysGenerateStylesCache || (!self::$alwaysGenerateStylesCache && !$hasCache)){
    				$buffer = self::generateBufferForStyles($styles);
    				self::clearStylesCache($context);
    				$cachedStylesheetName = uniqid().'.css';
    				$cachedStylesheetName = 'cache/'.$context.'-styles/'.$cachedStylesheetName;
    				self::cacheFile($cachedStylesheetName, $buffer);
                } else {
                    $cachedStylesheetName = self::getStylesCache($context);
                }                	
   				$output .= '<link rel="stylesheet" href="'.url($cachedStylesheetName).'">';
			} else {
				if(!empty($styles)){
					foreach($styles as $style){
						if($style['direct']){
							$output .= '<style>'.$style['content'].'</style>';
						} else {
							$output .= '<link rel="stylesheet" href="'.$style['content'].'">';	
						}
					}
				}
			}
			
		}
		// echo '<pre>';var_dump(self::$styles);echo '</pre>';
		echo $output;
	}
	
	
	public static function getAdminScripts(){
		self::getScripts('admin');
	}
	
	public static function getPublicScripts(){
		self::getScripts('public');
	}
	
	public static function getAdminStyles(){
		self::getStyles('admin');
	}
	
	public static function getPublicStyles(){
		self::getStyles('public');
	}
	
}