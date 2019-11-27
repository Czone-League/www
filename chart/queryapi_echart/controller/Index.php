<?php
namespace app\queryapi_echart\controller;
use think\Controller;
use think\Cache;

class Index extends Controller
{
    public function index()
    {
		return view('index/index');
    }

    public function charts()
    {
		return view('index/charts');
    }

    public function charts_()
    {
		return view('index/charts_');
    }

    public function qnews()
    {
		return view('index/qnews');
    }

	public function lists()
    {
		return view('index/lists');
    }


//*****************************************************************************

	//��ȡ��Ѷ���
	public function getnews()
	{
		$types=input('get.types');
		$url = "http://alirm-com.konpn.com/query/qnews?pidx=1&ps=10&types=".$types;
		echo($this->http_request($url,config('AppCode')));
    }

	//��ȡk���������
	public function kms()
	{
		$symbol=input('get.symbol');
		$period=input('get.period');
		$lasttick=(int)input('get.lasttick');

		if($period=="T")
			$period="1M";

		if($lasttick>0)
			echo($this->GetLstKMaps($symbol, $period, $lasttick));
		else
			echo($this->GetKMaps($symbol, $period));
    }

	//��ȡʵʱ�����������
	public function rms()
	{
		$symbols=input('get.symbols');
		$url = "http://alirm-com.konpn.com/query/comrms?symbols=".$symbols;
		echo($this->http_request($url,config('AppCode')));
	}

	//*****************************************************************************
	/// <summary>
	///	K�����ݺ���
	/// ��ȡ���n��������
	/// ���������Ĵ���
	/// </summary>
	public function GetLstKMaps($symbol, $period, $fromtick)
	{
		if($period=="T")
			$period="1M";

		$url = "http://alirm-com.konpn.com/query/comlstkm?period=".$period."&rout=*&symbol=".$symbol."&fromtick=".strval($fromtick);
		return $this->http_request($url,config('AppCode'));
	}


	/// <summary>
	/// K�����ݺ���
	/// ��ȡĳƷ��ĳ���ڵ�һ����k��
	/// 1M���1500��D���2000���������300
	/// 3���ӻ���,����޸�������3��
	/// </summary>
	/// <param name="symbol">Ʒ��</param>
	/// <param name="period">����, 1M,5M,15M,30M,1H,D</param>
	public function GetKMaps($symbol, $period)
	{
		if($period=="T")
			$period="1M";
		 //$cache=Cache::getInstance('File');

		$ckey=$this->getCacheKey($symbol,$period);

		$cachekms = cache($ckey);
		if(empty($cachekms)==true)
		{
			$cachekms = array();
			for($i=1;$i<=2;$i++)//ÿҳn��ȡ1ҳ����
			{
				//ÿ��n��
				$url = "http://alirm-com.konpn.com/query/comkm?psize=300&period=".$period."&rout=*&pidx=".strval($i)."&symbol=".$symbol;
				$obj=json_decode($this->http_request($url,config('AppCode')));
				
				if(empty($obj)==true || $obj->Code<0){}
				else
				{
					$cachekms=array_merge($cachekms,$obj->Obj);
				}
			}

			if(empty($cachekms)==true || count($cachekms)<=0){}
			else
			{
				if($period=="D" || $period=="W" || $period=="M"){cache($ckey,$cachekms,10*60);}
				else if($period=="1M"){cache($ckey,$cachekms,60);}
				else if($period=="5M"){cache($ckey,$cachekms,2*60);}
				else {cache($ckey,$cachekms,5*60);}
			}

			$retl["Code"]=1;
			$retl["Obj"]=$cachekms;

			return json_encode($retl);
		}

		$retl["Code"]=0;
		$retl["Obj"]=$cachekms;

		return json_encode($retl);
	}

	function getCacheKey($symbol,$period)
	{
		$datekey = date("mdHi");
		if ($period == "D" || $period == "W" || $period == "M") $datekey = date("md");
		else if ($period == "1M") $datekey = date("mdHi");
		else if ($period == "5M") $datekey = date("mdH").floor(date("i")/5);
		else if ($period == "15M") $datekey = date("mdH").floor(date("i")/ 15);
		else if ($period == "30M") $datekey = date("mdH").floor(date("i")/ 30);
		else if ($period == "1H") $datekey = date("mdH");
		else if ($period == "4H") $datekey = date("md").floor(date("H")/4);

		return "queryechart".$symbol.$period.$datekey;
	}

	//*****************************************************************************

//http����
    function http_request($url, $appcode)
    {
		$headers = array();
		array_push($headers, "Authorization:APPCODE " . $appcode);
		array_push($headers, "Accept:application/json");
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		if (1 == strpos("$".$url, "https://"))
		{
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}

		$result = curl_exec ( $curl );
        curl_close($curl);
        return $result;
    }

   function http_request_clear($url)
    {
		$headers = array();
		array_push($headers, "Accept:application/json");
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		if (1 == strpos("$".$url, "https://"))
		{
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}

		$result = curl_exec ( $curl );
        curl_close($curl);
        return $result;
    }
}