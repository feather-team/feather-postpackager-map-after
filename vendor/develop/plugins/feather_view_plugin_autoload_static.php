<?php
/*
自动加载动态资源插件
*/
class Feather_View_Plugin_Autoload_Static extends Feather_View_Plugin_Abstract{
	private $map = array();
	private $commonMap;
	private $mapLoaded = array();
	private $domain;
	private $caching;
	private $cache;
	private $view;

	private static $RESOURCES_TYPE = array('headJs', 'bottomJs', 'css');

	protected function initialize(){
		if($domain = $this->getOption('domain')){
			$this->domain = $domain;
		}else{
			$this->domain = '';
		}

		$this->caching = $this->getOption('caching');
	}

	private function initMap($path){
		//if path can be find in map
		if(isset($this->map[$path])){
			return true;
		}

		$resources = $this->getOption('resources');

		if(empty($resources) && !empty($this->view->template_dir)){
			$resources = array();

			foreach((array)$this->view->template_dir as $dir){
				$resources = array_merge($resources, glob($dir . '/../map/**.php'));
			}
		}

		if(!empty($resources)){
			$foundCommon = !empty($this->commonMap);
			$foundPath = false;

			//合并map表
			foreach($resources as $resourcepath){
				if(isset($this->mapLoaded[$resourcepath])){
					continue;
				}

				$resource = require($resourcepath);
				$map = $resource['map'];

				if(isset($resource['commonMap'])){
					if(!$foundCommon){
						$this->commonMap = $resource['commonMap'];
						$foundCommon = true;
					}
					
					$this->map = array_merge($this->map, $map);

					if(!$foundPath && isset($map[$path])){
						$foundPath = true;
					}

					$this->mapLoaded[$resourcepath] = 1;
				}else{
					if(!$foundPath && isset($map[$path])){
						$this->map = array_merge($this->map, $map);
						$foundPath = true;

						$this->mapLoaded[$resourcepath] = 1;
					}
				}

				if($foundPath && $foundCommon){
					break;
				}
			}
		}
	}

	//获取页面所有的静态资源
	private function getSelfMap($path){
		$selfMap = isset($this->map[$path]) ? $this->map[$path] : array();

		if(isset($selfMap['components'])){
			$componentsMap = array();

			foreach($selfMap['components'] as $components){
				$componentsMap = array_merge_recursive($componentsMap, $this->getSelfMap($components));
			}

			return array_merge_recursive($componentsMap, $selfMap);
		}

		return $selfMap;
	}

	private function getSelfResources($path){
		$maps = $this->map;
		$selfMap = $this->getSelfMap($path);

		if(!isset($selfMap['isPagelet'])){
			$selfMap = array_merge_recursive($this->commonMap, $selfMap);
		}

		$tmpCss = array();
		$finalResources = array();
		$finalRequires = array();

		foreach(self::$RESOURCES_TYPE as $type){
			if(isset($selfMap[$type])){
				$ms = $selfMap[$type];
				$tmp = $this->analyseResources($ms, 'deps', true);

				if($type != 'css'){
					$final = array();

					foreach($tmp as $v){
						if(strrchr($v, '.') == '.css'){
							array_push($tmpCss, $v);
						}else{
							array_push($final, $v);
						}
					}

					$finalResources[$type] = $final;
					$finalRequires = array_merge($finalRequires, $this->analyseResources($ms, 'requires', false, true, false));
				}else{
					$finalResources[$type] = array_merge($tmp, $tmpCss);
				}
			}else{
				$finalResources[$type] = array();
			}
		}

		if(isset($selfMap['requires'])){
			$requires = $selfMap['requires'];

			$tmpResources = $this->analyseResources($requires, 'deps', false);

			foreach($tmpResources as $resource){
				if(strrchr($resource, '.') == '.css'){
					array_push($finalResources['css'], $resource);
				}else{
					array_push($finalResources['bottomJs'], $resource);
				}
			}

			$tmpRequires = $this->analyseResources($requires, 'requires', true, true, false);
			$finalRequires = array_merge($finalRequires, $tmpRequires);
		}

		//get real url
		foreach($finalResources as &$resources){
			$resources = array_unique($resources);
		}

		unset($resources);
		//end

		// //get require info
		$finalMap = array();
		$finalDeps = array();

		foreach($finalRequires as $key => $value){
			if(!isset($finalMap[$value])){
				$finalMap[$value] = array();
			}

			$finalMap[$value][] = $key;

			if(isset($maps[$key])){
				$info = $maps[$key];

				if(isset($info['requires']) && isset($info['isMod'])){
					$finalDeps[$key] = $info['requires'];
				}
			}
		}

		foreach($finalMap as $k => &$v){
			$v = array_values(array_unique($v));
		}

		unset($v);
		
		$finalResources['requires'] = array(
			'map' => $finalMap,
			'deps' => $finalDeps
		);

		return $finalResources;
	}

	private function analyseResources($resources, $type = 'deps', $collectSelf = false, $returnHash = false, $withDomain = true, &$hash = array(), &$foundHash = array()){
		$urls = array();
		$maps = $this->map;

		foreach($resources as $v){
			//如果存在
			if(isset($maps[$v])){
				$info = $maps[$v];

				//如果未查找过
				if(!isset($foundHash[$v])){
					//如果pack
					if(isset($info['pkg'])){
						$pkgName = $info['pkg'];

						//如果pkg未查找过
						if(!isset($foundHash[$pkgName])){
							$pkg = $maps[$pkgName];
							$url = $foundHash[$pkgName] = $withDomain ? $this->domain . $pkg['url'] : $pkg['url'];

							//如果pkg有deps，并且不是mod，说明多个非mod文件合并，需要同时加载他们中所有的文件依赖，防止页面报错
							if(isset($pkg[$type]) && !isset($info['isMod'])){
								$urls = array_merge($urls, $this->analyseResources($pkg[$type], $type, true, false, $withDomain, $hash, $foundHash));
							}
						}else{
							$url = $foundHash[$pkgName];
						}

						//如果自己有deps，并且是mod，则可以不通过pkg加载依赖，只需要加载自己的依赖就可以了，mod为延迟加载。
						if(isset($info[$type]) && isset($info['isMod'])){
							$urls = array_merge($urls, $this->analyseResources($info[$type], $type, true, false, $withDomain, $hash, $foundHash));
						}
					}else{
						$url = $foundHash[$v] = $withDomain ? $this->domain . $info['url'] : $info['url'];

						//如果自己有deps，没打包，直接加载依赖
						if(isset($info[$type])){
							$urls = array_merge($urls, $this->analyseResources($info[$type], $type, true, false, $withDomain, $hash, $foundHash));
						}
					}
				}else{
					$url = $foundHash[$v];
				}
			}else{
				$url = $v;
			}

			if($collectSelf){
				$urls[] = $url;
				$hash[$v] = $url;
			}
		}

		return !$returnHash ? array_unique($urls) : $hash;
	}

	private function getRequireMD($deps){
		$hash = $this->getUrl($deps, true, false);
		$mapResult = array();
		$depsResult = array();
		$maps = $this->map;

		foreach($hash as $key => $value){
			if(!isset($mapResult[$value])){
				$mapResult[$value] = array();
			}

			$mapResult[$value][] = $key;

			if(isset($maps[$key])){
				$info = $maps[$key];

				if(isset($info['deps']) && isset($info['isMod'])){
					$depsResult[$key] = $info['deps'];
				}
			}
		}

		foreach($mapResult as $k => &$v){
			$v = array_values(array_unique($v));
		}
		
		return array('map' => $mapResult, 'deps' => $depsResult);
	}

	private function getCache(){
		if(!$this->cache){
			$cache = $this->getOption('cache');

			if(is_object($cache) && is_a($cache, 'Feather_View_Plugin_Cache_Abstract')){
				$this->cache = $cache;
			}else{
				$this->cache = new $cache;
			}
		}

		return $this->cache;
	}

	//执行主程
	public function exec($path, $content = '', Feather_View $view){
		$this->view = $view;
		$view->set('FEATHER_STATIC_DOMAIN', $this->domain);

		$path = '/' . ltrim($path, '/');
		$cache = $this->caching ? $this->getCache()->read($path) : null;

		if(!$cache){
			$this->initMap($path);

			$resources = $this->getSelfResources($path);

			//拿到当前文件所有的map信息
			$headJsInline = array();

			if(!empty($resources['requires'])){
				$config = $resources['requires'];
				$config['domain'] = $this->domain;
				$headJsInline[] = 'require.mergeConfig(' . self::jsonEncode($config) . ')';
			}
		
			$cache = array(
				'FEATHER_USE_HEAD_SCRIPTS' => array(
					'inline' => $headJsInline,
					'outline' => $resources['headJs']
				),
		        'FEATHER_USE_BOTTOM_SCRIPTS' => array(
		        	'outline' => $resources['bottomJs']
		        ),
				'FEATHER_USE_STYLES' => array(
					'outline' => $resources['css']
				)
			);


			//如果需要设置缓存
			$this->caching && $this->getCache()->write($path, $cache);
		}

		//设置模版值
		$view->set($cache);

		return $content;
	}

	private static function jsonEncode($v){
    	return str_replace('\\', '', json_encode($v));
	}
}
