<?php
// https://github.com/reversemagician/fenye.git

//简单分页封装类
class fenye{

	private $total=0;//数据总条数
	private $number=10;//每页显示的条数
	private $pagenumber=5;//显示的页码数 
	private $loopmodel='default';//循环体的计算方式
	private $loopextra=[];//循环体的计算方式的额外参数
	private $urlmodel=array(//自动检测路径时的路径方式
		'p'=>'p',//检测的翻页键值
		'p_url_type'=>'get',//自动过滤路径的方式 'get':?附加参数的路径方式 '\/':如index/p/5.html等方式可以是其他符合,由于使用到正则所以特殊符合要加'\' 'empty':如http://80s.la/movie/list/-----p25等无间隔=$style['p']='---p'
		'auto_reset_url'=>true,//自动过滤标记
		'auto_get_url'=>true,//自动获取标记
		);
	private $htmlmodel=[];//模板数据
	
	//以下可访问变量，大部分仅供访问值，修改无效
	public $countpaye=0;//共有多少页
	public $pageurl='';//当前路径
	public $p=0;//当前页码
	public $url='';//已过滤的路径信息，p值位置将被过滤成#num#。
				  //如果该值被外部设置,此时不会自动获取$p的值,因此此时需要手动设置$p的值
	public $loopstart=1;//循环起始值
	public $looplast=1;//循环起始结束
	public $data=[];//主体数据 *修改有效*

	
	/**
	 * 设定必要参数并初始化
	 * @param integer $total      数据总量
	 * @param integer $number     [每页显示的数据条数]
	 * @param integer $pagenumber [显示的页码数]
	 */
	public function __construct($total,$number=10,$pagenumber=5)
	{
		$this->number=$number;
		$this->pagenumber=$pagenumber;
		$this->total=$total;
		$this->dataInit();//数据初始化
	}

	/**
	 * 修改配置并重新初始化 
	 * @param [array]   $setting['urlmodel'] $urlmodel的对应参数
	 * @param [string]  $setting['loopmodel'] 循环体的计算方式
	 * @param [string]  $setting['pageurl'] 当前url(默认自动获取)
	 * @param [number]  $setting['p'] 当前页码 (默认自动获取)
	 * @param [string]  $setting['url'] 已过滤的路径信息 (默认自动获取)
	 * @return null 无返回 
	 */
	public function config($setting=[]){
		if(isset($setting['urlmodel'])){
			$this->setUrlModel($setting['urlmodel']);
		}
		if(isset($setting['loopmodel'])){
			$this->loopmodel=$setting['loopmodel'];
		}
		if(isset($setting['loopextra'])){
			$this->loopextra=$setting['loopextra'];
		}
		if(isset($setting['pageurl'])){
			$this->pageurl=$setting['pageurl'];
			$this->urlmodel['auto_get_url']=false;
		}
		if(isset($setting['p'])||isset($setting['url'])){
			if(isset($setting['p'])&&isset($setting['url'])){
				$this->p=$setting['p'];
				$this->url=$setting['url'];
				$this->urlmodel['auto_get_url']=false;
				$this->urlmodel['auto_reset_url']=false;
			}else{
				//这两个参数需要同时配置$setting['p']和$setting['url']
				echo '分页错误：分页类'.__CLASS__.'的'.__FUNCTION__ .'方法的部分配置无效，请检查！';
			}
		}

		$this->dataInit();//重新初始化
	}

	/**
	 * 获取结果
	 * @return array 分页结果
	 */
	public function result(){
		
		// html主体
		$html=$this->mainHtml();

		return [
			'p'=>$this->p,//当前页数
			'data'=>$this->data,//主体数据
			'limit'=>$this->getLimit(),//limit信息
			'html'=>$html['html'],
			'css'=>$html['css'],
			'script'=>$html['script'],
		];
		
	}
	
	/**
	 * [设置html模板]
	 * @param  string $modelid     模板id
	 * @param  array  $model_v_set 动态修改模板的$model的参数
	 * @param  array  $css_v_set   动态修改模板的css参数 
	 * @param  string $call        调用方式，external或self
	 * @return array               模板数据
	 */
	public function setHtmlModel($modelid='default',$model_v_set=[],$css_v_set=[],$call='external'){
		//阻止分页类内部的重复调用导致外部调用该方法时模板被覆盖
		if($call=='self'){
			if(!empty($this->htmlmodel)){
				return $this->htmlmodel;
			}
		}

		//配置动态模板
		$model=$this->htmlModel($modelid,$model_v_set,$css_v_set);
		//默认以base模板作为默认配置
		$modeldefaultseting=$this->htmlModel('base');
		//合并配置
		$this->myArrayMerge($model,$modeldefaultseting);

		//过滤标签
		if(isset($model['css'])){
			if($model['css']!=''){
				$model['css']=$this->resetLabel('style',$model['css'],$model['style_label']);
			}
		}
		if(isset($model['script'])){
			if($model['script']!=''){
				$model['script']=$this->resetLabel('script',$model['script'],$model['script_label']);
			}
		}

		return $this->htmlmodel=$model;
	}

	

	/**
	 * 获取循环起始和结束值
	 * @param number $countpaye  总页数
	 * @param number $pagenumber 页码数
	 * @param number $p          当前页
	 * @return array 			 起始值和结束值
	 */
	private function getLoop($countpaye,$pagenumber,$p){
		$loopmodel=$this->loopmodel;
		switch ($loopmodel) {
			case 'pagenumber':
			//以倍数页码的方式
				$last=ceil($p/$pagenumber)*$pagenumber;
				$start=$last-$pagenumber+1;
				$start=$start>0?$start:1;//确认有效值
				$last=$countpaye<$last?$countpaye:$last;//确认有效值
				return [
					'start'=>$start,
					'last'=>$last,
				];
				break;

			case 'default1':
			// 在'default'的前提下增加了 ‘修正’
				$extra=[//额外参数
					'offset'=>isset($this->loopextra['offset'])?$this->loopextra['offset']:0,
					/*
					offset : 正向右,负向左。偏移量最好小于$pagenumber/2
					*/
				];
				
				$offset=$extra['offset'];
				$ge=floor($pagenumber/2);
				$start=$p-$ge;
				$start=$start-$offset;//偏移
				$maxstart=$countpaye-$pagenumber+1;//最大起点
				$start=$start>$maxstart?$maxstart:$start;
				$start=$start<1?1:$start;//最小起点为1
				//循环结束值
				$last=$start+$pagenumber-1;
				$last=$last>$countpaye?$countpaye:$last;//最大结束值$countpaye
				
				//修正
					if($start==1&&$last-$start+1>$p+$ge){
					//当起始值为1时判断修正$last
						$i=$last-$start+1-($p+$ge);//需要修正的项数
						$last=$last-$i;
					}
					if($last==$countpaye&&$start<$p-$ge){
					//当$last是最后一页时判断修正$start
						$i=$p-$ge-$start;//需要修正的项数
						$start=$start+$i;
					}
				return [
					'start'=>$start,
					'last'=>$last,
				];
				break;

			default:
			//默认当前页于页码居中
				$extra=[//额外参数
					'offset'=>isset($this->loopextra['offset'])?$this->loopextra['offset']:0,
					/*
					offset : 正向右,负向左。偏移量最好小于$pagenumber/2
					*/
				];
				$offset=$extra['offset'];
				$ge=floor($pagenumber/2);
				$start=$p-$ge;
				$start=$start-$offset;//偏移
				$maxstart=$countpaye-$pagenumber+1;//最大起点
				$start=$start>$maxstart?$maxstart:$start;
				$start=$start<1?1:$start;//最小起点为1
				//循环结束值
				$last=$start+$pagenumber-1;
				$last=$last>$countpaye?$countpaye:$last;//最大结束值$countpaye
				return [
					'start'=>$start,
					'last'=>$last,
				];
				break;
		}
	}

	/**
	 * [生成html主体]
	 * @param  array  [$data] 主体数据
	 * @return [type]       html的内容、css、JavaScript
	 */
	private function mainHtml()
	{
		//确认初始化模板
		$model=$this->setHtmlModel('default',[],[],'self');
		//生产按钮主体
		$html=$this->btn($this->data);
		return [
			'html'=>$html,
			'css'=>$this->htmlmodel['css'],
			'script'=>$this->htmlmodel['script'],
		];
	}

	//html btn主体
	private function btn($data){
		$btn=[];

		//4个基础按钮
		$basebtn=[
			$data['first'],
			$data['last'],
			$data['prev'],
			$data['next'],
		];
		foreach ($basebtn as $v) {
			$btn[$v['name'].'_btn']=$this->makingABtn($v['name'],$v);
		}

		//间隙按钮
		$btn['block']=$this->htmlmodel['html_set']['block'];

		//循环体按钮
		$loopblock=$this->htmlmodel['html_set']['loop_block'];//循环间隙
		foreach ($data['loop'] as $k => $v) {
			//循环按钮
			$btn['loop_btn'][]=$this->makingABtn('loop',$v);
			
			//循环间隔
			if($k<=count($data['loop'])-2){
				$btn['loop_btn'][]=$loopblock;
			}
		}
		$btn['loop_btn']=implode('',$btn['loop_btn']);

		//排序并匹配系统变量
		$order=$this->htmlmodel['html_view']['order'];
		$order_true=$this->htmlmodel['html_view']['order_true'];
		$end=[];
		foreach ($order as $k => $v) {
			$content='';//显示的内容
			if(in_array($v,$order_true)&&$v!=''){//判断order是否合法
				if(isset($btn[$v])){
					$content=$btn[$v];
				}
			}else{
				$content=$v;
			}
			//检查是否存在显示逻辑
			if(isset($this->htmlmodel['html_view']['order_show_rule'][$k])){
				if($this->htmlmodel['html_view']['order_show_rule'][$k]){
				//显示
					$end[]=$this->replaceSystemVal($content);
				}
			}else{
			//直接显示
				$end[]=$this->replaceSystemVal($content);
			}
		}
					
		//拼接首尾
		$html=$this->htmlmodel['html_set']['outer_begin'].implode('',$end).$this->htmlmodel['html_set']['outer_end'];
		return $html;
	}

	//生产一个btn 
	private function makingABtn($name,$val){
		$text='#text#';
		$href='#href#';
		$self_p='#self_p#';

		// btn最终配置
		$finalset=$this->getBtnFinalSeting($name);

		//匹配默认变量 #text#
		$remark=$name=='loop'?$val['name']:$this->htmlmodel['html_set'][$name.'_text'];//合适的#text#
		$btn= str_replace($text,$remark,$finalset[$val['status']]);
		
		//匹配默认变量 #href#
		$btn=str_replace($href,$val['href'],$btn);

		//匹配默认变量 #self_p#
		$btn=str_replace($self_p,$val['self_p'],$btn);

		return $btn;
	}

	//获取按钮的最终配置
	private function getBtnFinalSeting($name){
		$btnset=$this->getArrayValue('html_set.'.$name.'_btn',$this->htmlmodel);
		$globalbtnset=$this->getArrayValue('html_set.btn',$this->htmlmodel);
		//合并
		return array_merge((is_array($globalbtnset)?$globalbtnset:[]),(is_array($btnset)?$btnset:[]));
	}

	//html标签过滤 删除或增加
	private function resetLabel($label='',$content='',$has_label=true){
		if ($has_label) {
			return strpos($content, '<'.$label.'>')!==false?$content:'<'.$label.'>'.$content.'</'.$label.'>';
		}else{
			return strpos($content, '<'.$label.'>')!==false?preg_replace('/<[\/]?'.$label.'>/','',$content):$content;
		}
	}

	//匹配系统变量名并全部替换成系统值
	private function replaceSystemVal($str){
		$preg='/#([^#]+)#/';//匹配规则 #系统变量名# 或同#styel.first#

		preg_match_all($preg,$str,$pv);
		if(!empty($pv[0])){
			foreach ($pv[1] as $k => $v) {
				//常规字符串变量
				if(isset($this->$v)){
					$str=str_replace($pv[0][$k],$this->$v,$str);
				}

				//数组内变量 如url.\w+
				$preg_style='/([\w]+)\.([\w]+)/';
				preg_match_all($preg_style,$v,$pv_style);
				if(!empty($pv_style[0])){
					$yi=$pv_style[1][0];
					if(isset($this->$yi[$pv_style[2][0]])){
						$str=str_replace($pv[0][$k],$this->$yi[$pv_style[2][0]],$str);
					}
				}
			}
		}

		return $str;
	}

	//配置$this->urlmodel参数
	private function setUrlModel($arr=array()){
		foreach($arr as $k => $v){
			if(isset($this->urlmodel[$k])){
				$this->urlmodel[$k]=$v;
			}
		}
	}

	/**
	 * 初始化数据
	 * 【执行路径方法、计算基础值、生成数据主体】
	 * @return 
	 */
	private function dataInit(){
		//解析路径
		$this->resetUrl();
		
		//总页数
		$countpaye=ceil($this->total/$this->number)==0?1:ceil($this->total/$this->number);
		$this->countpaye=$countpaye;

		//过滤非法p值
		$this->p=$this->p>$countpaye?$countpaye:($this->p<=0?1:$this->p);
		
		//循环起始值、结束值
		$loopdata=$this->GetLoop($countpaye,$this->pagenumber,$this->p);
		$this->loopstart=$loopdata['start'];
		$this->looplast=$loopdata['last'];
		
		$data=[];
		$data['first']=[
			'name'=>'first',
			'self_p'=>1,//本身的p值
			'page_p'=>$this->p,//当前页p值
			'href'=>str_replace('#num#',1,$this->url),//url
			'status'=>($this->p==1?'disable':'normal')//'normal','selection'=>'','disable'
		];
		$data['last']=[
			'name'=>'last',
			'self_p'=>$countpaye,
			'page_p'=>$this->p,
			'href'=>str_replace('#num#',$countpaye,$this->url),
			'status'=>($this->p==$countpaye?'disable':'normal')
		];
		$data['prev']=[
			'name'=>'prev',
			'self_p'=>$this->p-1<=0?1:$this->p-1,
			'page_p'=>$this->p,
			'href'=>str_replace('#num#',$this->p-1<=0?1:$this->p-1,$this->url),
			'status'=>($this->p-1<=0?'disable':'normal')
		];
		$data['next']=[
			'name'=>'next',
			'self_p'=>$this->p>=$countpaye?$countpaye:$this->p+1,
			'page_p'=>$this->p,
			'href'=>str_replace('#num#',$this->p>=$countpaye?$countpaye:$this->p+1,$this->url),
			'status'=>($this->p>=$countpaye?'disable':'normal')
		];
		$data['count_p']=$countpaye;
		$data['count_t']=$this->total;
		for ($x=$this->loopstart; $x<=$this->looplast;$x++) {
			$data['loop'][]=[
				'name'=>$x,//按钮上面的显示
				'self_p'=>$x,
				'page_p'=>$this->p,
				'href'=>str_replace('#num#',$x,$this->url),
				'status'=>($this->p==$x?'selection':'normal')
			];
		}

		$this->data=$data;
	}

	//获得当前页和过滤url路径
	private function resetUrl(){
		$this->getPageUrl();//获取当前路径

		if(!$this->urlmodel['auto_reset_url']){return false;}//保证手动设置的url优先
		
		if ($this->urlmodel['p_url_type']=='get') {
		//get
			$this->p=!isset($_GET[$this->urlmodel['p']])?1:$_GET[$this->urlmodel['p']];
			$this->p=is_numeric($this->p)?$this->p:1;
			//过滤的url信息
			$this->url=str_replace('&'.$this->urlmodel['p'].'='.$this->p,'', $this->pageurl);
			$this->url=str_replace('?'.$this->urlmodel['p'].'='.$this->p.'&','?', $this->url);
			$this->url=str_replace('?'.$this->urlmodel['p'].'='.$this->p,'', $this->url);
			$fuhao=strpos($this->url,'?')?'&':'?';
			$this->url=$this->url.$fuhao.$this->urlmodel['p'].'=#num#';
		}

		if($this->urlmodel['p_url_type']=='empty'){
		//empty
			// 判断p值
			$preg='/'.$this->urlmodel['p'].'[0-9]+/';
			preg_match_all($preg,$this->pageurl,$getp);
			if(count($getp[0])>1){
				//你可以更改$this->urlmodel["p"]的值来解决匹配到多个的问题
				echo '分页错误：分页类'.__CLASS__.'的'.__FUNCTION__ .'方法的错误，请检查！（匹配到多个$urlmodel["p"],请正确配置$urlmodel["p"]参数）';
			}else{

				if(count($getp[0])==0){
					$this->p=1;
					$this->url=$this->pageurl.$this->urlmodel['p'].'#num#';
				}
				if(count($getp[0])==1){
					preg_match_all("/\d+$/",$getp[0][0],$pv);
					$this->p=$pv[0][0];
					$this->url=preg_replace($preg,$this->urlmodel['p'].'#num#',$this->pageurl);
				}
			}			
		}
		
		if($this->urlmodel['p_url_type']!='empty'&&$this->urlmodel['p_url_type']!='get'){
		//特殊符合做间隔
			$preg='/'.$this->urlmodel['p'].$this->urlmodel['p_url_type'].'[0-9]+/';
			//重新设置url
			$this->url=preg_replace($preg,$this->urlmodel['p'].str_replace('\\','', $this->urlmodel['p_url_type']).'#num#',$this->pageurl);
			preg_match_all('/'.$this->urlmodel['p'].$this->urlmodel['p_url_type'].'([0-9]+)/',$this->pageurl,$pageurlnum);
			if(isset($pageurlnum[1][0])){
				$this->p=$pageurlnum[1][0];
			}else{
				//待开发,不知道实际情况
				echo '分页错误：分页类'.__CLASS__.'的'.__FUNCTION__ .'方法的错误，请检查！（特殊符合做间隔方式空白部分，待开发）';
			}
		}
			
	}

	//获取当前页面url
	private function getPageUrl(){

		if(!$this->urlmodel['auto_get_url']){return false;}//保证手动设置的url优先
		
		$pageurl='http';
		if(isset($_SERVER['HTTPS'])){
			if($_SERVER['HTTPS']=="on"){
				$pageurl.='s';
			}
		}
		$pageurl.='://';
		if($_SERVER['SERVER_PORT']!='80'){
			$pageurl.=$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		}else{
			$pageurl.=$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		}
		$this->pageurl=$pageurl;
	}

	// 获取limit
	private function getLimit(){
		return [
			' limit '.($this->p-1)*$this->number.','.$this->number.' ',
			array(($this->p-1)*$this->number,$this->number)
		];
	}

	//返回多维数组的某个值,$where="waiceng.ceng2.ceng3"
	private function getArrayValue($where='',$arr=[]){
		if($where==''||empty($arr)){return '';}
		$layer=explode('.',$where);
		$vstr='$arr';
		foreach ($layer as $v) {
			$vstr.='["'.$v.'"]';
		}
		$evalstr='$result=isset('.$vstr.')?'.$vstr.':"undefined";';//undefined该值不存在
		eval($evalstr);
		return $result;
	}

	//多维数组合并 以$a为主,冲突会保留$a的数据
	private function myArrayMerge(&$a,$b){
		foreach($a as $key=>&$val){
			if(is_array($val) && array_key_exists($key, $b) && is_array($b[$key])){
				$this->myArrayMerge($val,$b[$key]);
				$val = $val + $b[$key];
			}else if(is_array($val) || (array_key_exists($key, $b) && is_array($b[$key]))){
				$val = is_array($val)?$val:$b[$key];
			}
		}
		$a = $a + $b;
	}

	/**
	 * 分页模板
	 * @param  modelid $modelid    模板id
	 * @param  modelid $model_v_set动态地配置$model的参数(格式与$model一致)
	 * @param  modelid $css_v_set  对应模板分页的css样式配置参数,示例参考默认模板
	 * @return model               模板数组
	 */
	private function htmlModel($modelid='',$model_v_set=[],$css_v_set=[]){

		switch ($modelid) {
			case 'base':
			//基础模板。用于参数说明和作为默认参数源。*不要修改该模板
				$css_v=[];//参数与下面的$model['css']中对应；css动态风格，用法参考默认模板
				$css_v=empty($css_v_set)?$css_v:array_merge($css_v,$css_v_set);
				$model=[
					'html_set'=>[//html基本配置
						'outer_begin'=>'',//分页外层头部
						'outer_end'=>'',//分页外层尾部
						'loop_block'=>'',//循环间隙，主体循环时自动加
						'block'=>'',//普通间隙 需要在排序手动添加
						'btn'=>[//所有按钮的公共默认样式
							'normal'=>'',//正常状态 所有按钮有效
							'selection'=>'',//选中状态 循环体按钮有效
							'disable'=>'',//不可选状态 基础按钮有效
							/*['#self_p#','#href#','#text#']有效的匹配值*/
							/*#self_p# 匹配当前按钮对应页码数*/
							/*#href# 匹配当前按钮对应路径*/
							/*#text# 默认的按钮文字*/
							/*可以使用 #countpaye#的方式来匹配到系统值 */
							/*'normal'=>'<div>#p#/#countpaye#<div>',//可以利用 #变量系统名# 的的方式匹配到该系统值，如变量$this->p可以写成#p#来匹配到值，或者二维数组值#style.last#*/
						],
						'loop_btn'=>[],//循环体按钮
						'prev_btn'=>[],//上页按钮
						'next_btn'=>[],//下页按钮
						'first_btn'=>[],//首页按钮
						'last_btn'=>[],//尾页按钮
						'first_text'=>'首页',//若该值为#self_p#代表匹配自身页码
						'last_text'=>'尾页',//若该值为#self_p#代表匹配自身页码
						'prev_text'=>'上一页',//若该值为#self_p#代表匹配自身页码
						'next_text'=>'下一页',//若该值为#self_p#代表匹配自身页码
					],
					'html_view'=>[//视图（显示配置）
						'order'=>[ //显示排序
							'first_btn',//首页
							'prev_btn',//上一页
							'loop_btn',//循环体
							'next_btn',//下页
							'last_btn',//尾页
							//'<div>共#p#页</div>',/*这个是非法顺序值，所有的非法顺序值会被认为是间隙对应添加到分页的对应位置中*/
						],
						'order_true'=>[//规定合法的显示顺序名
							'first_btn',
							'prev_btn',
							'loop_btn',
							'next_btn',
							'last_btn',
							'block',
						],
						'order_show_rule'=>[//额外显示逻辑
							0=>true,//对应html_view.order的第1项 true代表显示
						],
					],
					'css'=>'',//对应该模板的css样式
					'script'=>'',//对应该模板的js内容
					'style_label'=>true,//css样式是否要带有style标签
					'script_label'=>true,//script是否要带有标签
				];
				if(empty($model_v_set)){
					return $model;
				}else{
					//动态$model
					$this->myArrayMerge($model_v_set,$model);
					return $model_v_set;
				}
				break;
			case 'select':
				$css_v=[];
				$css_v=empty($css_v_set)?$css_v:array_merge($css_v,$css_v_set);
				$model=[
					'html_set'=>[
						'outer_begin'=>'<select onchange="fenye_select(this.value)">',
						'outer_end'=>'</select>',
						'btn'=>[
							'normal'=>'<option value="#href#">#text#</option>',
							'selection'=>'<option value="#href#" selected = "selected">#text#</option>',
						]
					],
					'html_view'=>[
						'order'=>[ 
							'first_btn',
							'loop_btn',
							'last_btn',
							'',
							'',//视情况补上一些空白排序以防基础模板中的排序漏出
						],
						'order_show_rule'=>[
							0=>$this->loopstart!=1,
							2=>$this->looplast!=$this->countpaye,
						]
					],
					'script'=>'<script>fenye_select=function (url){window.location.href=url;}</script>',
				];

				if(empty($model_v_set)){
					return $model;
				}else{
					$this->myArrayMerge($model_v_set,$model);
					return $model_v_set;
				}
				break;
			case 'bilibili':
				$css_v=[];
				$css_v=empty($css_v_set)?$css_v:array_merge($css_v,$css_v_set);
				$model=[
					'html_set'=>[
						'outer_begin'=>'<ul class="fenye_bili">',
						'outer_end'=>'</ul>',
						'btn'=>[
							'normal'=>'<a href="#href#"><li class="fenye_bili_item">#text#</li></a>',
							'selection'=>'<a href="#href#"><li class="fenye_bili_item fenye_bili_item_action">#text#</li></a>',
							'disable'=>'',
						],
						'prev_btn'=>[
							'normal'=>'<a href="#href#"><li class="fenye_bili_prve">上一页</li></a>',
						],
						'next_btn'=>[
							'normal'=>'<a href="#href#"><li class="fenye_bili_prve">下一页</li></a>',
						],
						'first_text'=>'#self_p#',
						'last_text'=>'#self_p#',

					],
					'html_view'=>[
						'order'=>[ 
							'prev_btn',
							'first_btn',
							'<li class="fenye_bili_ge"></li>',
							'loop_btn',
							'<li class="fenye_bili_ge"></li>',
							'last_btn',
							'next_btn',
							'<span class="fenye_bili_total">共 #countpaye# 页，跳至<input id="fenye_bili_search" onkeydown="enterRedirect(this)" placeholder="回车" url="#url#" type="text">页</span>'
						],
						'order_show_rule'=>[//显示逻辑
							1=>$this->loopstart>=2,//对应html_view.order.1
							2=>$this->loopstart>=2+1,//对应html_view.order.2
							4=>$this->looplast<$this->countpaye-1,//对应html_view.order.4
							5=>$this->looplast<$this->countpaye,//对应html_view.order.5
						]
					],
					'script'=>'function enterRedirect(e) {if (event.keyCode == 13) {var str=e.getAttribute("url");var url =str.replace("#num#",e.value);window.location.href=url;}}',
					'css'=>'.fenye_bili{text-align: center;width: auto;display: inline-block;margin:0; padding:0;}.fenye_bili li{list-style:none;display:inline-block;line-height:38px;padding:0 14px;border:1px solid #d7dde4;border-radius:4px;background-color:#fff;font-size:14px;transition:all .2s ease-in-out;font-family:Arial;color:#666}.fenye_bili .fenye_bili_prve{margin-right:10px}.fenye_bili .fenye_bili_ge{cursor:pointer;font-family:Arial;transition:all .2s ease-in-out;border:none;padding:0 3px}.fenye_bili .fenye_bili_ge:after{content:"\2022\2022\2022";display:block;letter-spacing:1px;color:#ccc;text-align:center}.fenye_bili .fenye_bili_item{margin-right:8px;-ms-user-select:none;user-select:none;cursor:pointer;padding:0 15px}.fenye_bili .fenye_bili_item_action{background-color:#00a1d6;border-color:#00a1d6;color:#fff}.fenye_bili_total{display:inline-block;height:32px;line-height:32px;margin-left:20px;color:#99a2aa;font-size:12px}#fenye_bili_search{border-radius:4px;margin:0 8px;width:50px;line-height:28px;height:28px;padding:0 10px;transition:all .3s ease;vertical-align:top;border:1px solid #ccd0d7;text-align:center}'
				];

				if(empty($model_v_set)){
					return $model;
				}else{
					$this->myArrayMerge($model_v_set,$model);
					return $model_v_set;
				}
				break;
			default:
			//默认模板
				$css_v=[//css风格
					'size'=>1,//整体大小
					'float'=>'left',//整体浮动
					'color'=>'#009688',//#1E9FFF #5FB878 #009688 #4476A7 #FF5722
					'class_pre'=>'',//类名前缀
				];
				$css_v=empty($css_v_set)?$css_v:array_merge($css_v,$css_v_set);
				$model=[
					'html_set'=>[
						'outer_begin'=>'<div class="config_fanye_">',
						'outer_end'=>'<div style="clear:both"></div></div>',
						'btn'=>[
							'normal'=>'<a href="#href#" class="'.$css_v['class_pre'].'config_fanye_a">#text#</a>',
							'selection'=>'<a href="#href#" class="'.$css_v['class_pre'].'config_fanye_a '.$css_v['class_pre'].'config_fanye_a_">#text#</a>',
							'disable'=>'<a href="javascript:;" class="'.$css_v['class_pre'].'config_fanye_a config_fanye_d">#text#</a>',
						],
					],
					'html_view'=>[
						'order'=>[
							'first_btn',
							'prev_btn',
							'loop_btn',
							'next_btn',
							'last_btn',
							'<div class="'.$css_v['class_pre'].'config_fanye_count">共#countpaye#页</div><div class="'.$css_v['class_pre'].'config_fanye_count">共#total#条</div>',
						],
					],
					'css'=>'.'.$css_v['class_pre'].'config_fanye_{  float: '.$css_v['float'].';}.'.$css_v['class_pre'].'config_fanye_a{padding: 0 '.($css_v['size']*15).'px;color: #333;background-color: #fff;float: left;height: '.($css_v['size']*28).'px;line-height: '.($css_v['size']*28).'px;margin-left:-'.($css_v['size']*1).'px;font-size: '.($css_v['size']*12).'px;vertical-align: middle;border: '.($css_v['size']*1).'px solid #e2e2e2;text-decoration:none;font-family: "微软雅黑";}.'.$css_v['class_pre'].'config_fanye_a:hover{color:'.$css_v['color'].';}.'.$css_v['class_pre'].'config_fanye_a:first-child{border-radius:'.($css_v['size']*2).'px 0 0 '.($css_v['size']*2).'px;}.'.$css_v['class_pre'].'config_fanye_a:last-child{border-radius: 0 '.($css_v['size']*2).'px '.($css_v['size']*2).'px 0;}.'.$css_v['class_pre'].'config_fanye_a_{background: '.$css_v['color'].';border: '.($css_v['size']*1).'px solid '.$css_v['color'].';color: #fff;border-radius:'.($css_v['size']*2).'px ;}.'.$css_v['class_pre'].'config_fanye_a_:hover{color:#fff;}.'.$css_v['class_pre'].'config_fanye_d{color: #d2d2d2 !important;cursor: not-allowed !important;}.'.$css_v['class_pre'].'config_fanye_count{line-height: '.($css_v['size']*28).'px;float: left;color: #666;font-size: '.($css_v['size']*14).'px;margin-left: '.($css_v['size']*8).'px;}.hand{cursor:pointer;}',
				];

				if(empty($model_v_set)){
					return $model;
				}else{
					//动态$model
					$this->myArrayMerge($model_v_set,$model);
					return $model_v_set;
				}
				break;
		}
	}

}


?>
