<?php
namespace Ehd;

use Ehd\Exceptions\MemberException;
use LaLit\Array2XML;

class Member
{
    private $soapClient;
    private $condition;
    private $archives;
    private $score;

    public function wsdl($wsdl)
    {
        $this->soapClient = SoapClient::get($wsdl);

        return $this;
    }

    public function where($condition)
    {
        $this->condition = $this->generateSearchCondition($condition);

        return $this;
    }

    /**
     * 获取会员资料
     * @return string|array
     * @throws MemberException
     */
    public function getArchives()
    {
        if (!$this->archives) {
            throw new MemberException('会员资料查询失败');
        }

        $item = array_merge($this->archives, $this->score);
        unset($item['customerid']);

        switch (func_num_args()) {
            case 0:
                $fields = [];
                break;
            case 1:
                $fields = func_get_args()[0];
                break;
            default:
                $fields = func_get_args();
                break;
        }

        $ret = [];
        switch (true) {
            case empty($fields):
                $ret = $item;
                break;
            case is_string($fields):
                if (in_array($fields, array_keys($item))) {
                    $ret = $item[$fields];
                } else {
                    $this->throwNotFieldException();
                }
                break;
            case is_array($fields):
                foreach ($fields as $field) {
                    if (isset($item[$field])) {
                        $ret[$field] = $item[$field];
                    } else {
                        $this->throwNotFieldException();
                    }
                }
                break;
            default:
                throw new MemberException('参数类型限定错误', 105);
                break;
        }

        return $ret;
    }

    public function adjustScore($newjf, $comm = '')
    {
        $request = <<<request
<?xml version="1.0" encoding="UTF-8"?>
<request>
 <dnums>1</dnums>
 <data>
  <item>
   <customerid>{$this->score['cardid']}</customerid>
   <oldjf>{$this->score['totjf']}</oldjf>
   <newjf>{$newjf}</newjf>
   <comm>{$comm}</comm>
  </item>
 </data>
</request>
request;

        $chg = $this->soapClient->chghyjf([
            'pm_xml' => $request
        ]);

        $chg = \LaLit\XML2Array::createArray($chg->chghyjfResult)['response'];

        if ($chg['resultCode'] === '') {
            $this->findScore();
            return true;
        } else {
            return false;
        }
    }

    public function changeArchives($archives)
    {
        $only = [
            'name', 'phone', 'address', 'personid', 'mobilephone', 'birthday',
            'sex', 'edulevel', 'email', 'compname', 'cmpaddr', 'mantitle'
        ];

        $oldArchives = array_only($this->archives, $only);

        $archives = array_only($archives, $only);

        $archives['customerid'] = $this->archives['customerid'];

        $newArchives = array_merge($oldArchives, $archives);

        $request['dnums'] = '1';
        $request['data']['item'] = $newArchives;

        $xml = simplexml_import_dom(Array2XML::createXML('request', $request))->asXML();
        $chg = $this->soapClient->chghyda(['pm_xml' => $xml]);
        $i = 0;
        while (!$chg) {
            $i++;
            if ($i > 3) {
                break;
            }
            $chg = $this->soapClient->chghyda(['pm_xml' => $xml]);
        }
        $chg = \LaLit\XML2Array::createArray($chg->chghydaResult)['response'];

        if ($chg['resultCode'] === '') {
            $this->findArchives();
            return true;
        } else {
            return false;
        }
    }

    public function find()
    {
        $this->findArchives();
        $this->findScore();

        return $this;
    }

    public function add($name)
    {

    }

    private function findScore()
    {
        $retJf = $this->soapClient->readhyjf([
            'pm_qryhyjfxml' => $this->generateSearchCondition($this->archives['customerid'])
        ]);

        $this->score = \LaLit\XML2Array::createArray($retJf->readhyjfResult)['response']['resultMessage']['item'];
    }

    private function findArchives()
    {
        $reXml = $this->soapClient->readhyda([
            'pm_qryhydaxml' => $this->condition
        ]);

        $reXml = \LaLit\XML2Array::createArray($reXml->readhydaResult)['response'];



        switch ($reXml['resultCode']) {
            case '1':  // 存在唯一会员记录
                $this->archives = $reXml['resultMessage']['item'];
                break;
            case '-1':
                throw new MemberException($reXml['resultMessage'], 101);
                break;
            case '':
                throw new MemberException('会员不存在', 102);
                break;
            default:
                throw new MemberException('存在不唯一会员记录', 103);
        }
    }

    /**
     * 创建会员卡查询条件 XML 字符串
     * @param string|array $condition
     * @return string
     */
    private function generateSearchCondition($condition = [])
    {
        $fields = ['custid', 'custname', 'custsjh', 'custsfz', 'ynallnew'];
        $request = [];
        $t_condition = [];

        if (is_string($condition)) {
            $t_condition['custid'] = $condition;
        } else {
            $condition = array_only($condition, $fields);
            if (empty($condition)) {
                throw new MemberException('查询字段不存在', 106);
            }
            if (
                empty($condition['custid']) &&
                empty($condition['custsjh']) &&
                empty($condition['custsfz']) &&
                empty($condition['custname'])
            ) {
                throw new MemberException('手机号/身份证号/卡号/姓名同时为空', 107);
            }
            $t_condition = $condition;
        }

        foreach ($fields as $field) {
            if (isset($t_condition[$field])) {
                $request[$field] = $t_condition[$field];
            } else {
                $request[$field] = '';
            }
        }

        return simplexml_import_dom(Array2XML::createXML('request', $request))->asXML();
    }

    private function throwNotFieldException()
    {
        throw new MemberException('存在未定义的会员资料项', 104);
    }
}