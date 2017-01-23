<?php
namespace Ehd\Member;

use cszchen\citizenid\Parser;
use Ehd\Exceptions\RegistException;
use Ehd\SoapClient;
use LaLit\Array2XML;
use LaLit\XML2Array;

class Regist
{
    private $soapClient;

    private $prefix;
    private $figure = 5;

    private $name;
    private $phone;
    private $personid;
    private $birthday;
    private $gender;
    private $cardid;

    public function wsdl($wsdl)
    {
        $this->soapClient = SoapClient::get($wsdl);
        return $this;
    }

    public function prefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function figure($figure)
    {
        $this->figure = $figure;
        return $this;
    }

    public function data($name, $phone, $personid)
    {
        $this->name = $name;

        if ($this->isExistField('custsjh', $phone)) {
            throw new RegistException('手机号已存在', 1);
        } else {
            $this->phone = $phone;
        }

        if ($this->isExistField('custsfz', $personid)) {
            throw new RegistException('身份证号已存在', 2);
        } else {
            $this->personid = $personid;
        }

        return $this;
    }

    public function save($ycJf = 0)
    {
        $res = $this->soapClient->addhyda($this->generateData($ycJf));

        $res = XML2Array::createArray($res->addhydaResult);

        $resultCode = $res['response']['resultCode'];
        if ($resultCode === '-1') {
            throw new RegistException($res['response']['resultMessage'], 3);
        }

        return $this->cardid;
    }

    public function isExistField($fieldName, $fieldValue)
    {
        $request = array_combine(['custid', 'custname', 'custsjh', 'custsfz', 'ynallnew'], ['', '', '', '', '']);
        $request[$fieldName] = $fieldValue;

        $xml = Array2XML::createXML('request', $request)->saveXML();

        $res = $this->soapClient->readhyda([
            'pm_qryhydaxml' => $xml
        ]);

        $res = XML2Array::createArray($res->readhydaResult);

        return $res['response']['resultCode'] === '1';
    }

    private function parsePersonid()
    {
        $personidModule = app(Parser::class);

        $personidModule->setId($this->personid);

        $this->birthday = $personidModule->getBirthday();
        $gender = $personidModule->getGender();
        if ($gender === 0) {
            $this->gender = 2;
        } elseif ($gender === 1) {
            $this->gender = 1;
        } else {
            $this->gender = 3;
        }
    }

    private function generateData($ycJf = 0)
    {
        $cardid = $this->randCardid();
        while ($this->isExistField('custid', $cardid)) {
            $cardid = $this->randCardid();
        }
        $this->cardid = $cardid;

        $this->parsePersonid();

        $item = [
            'customerid' => $this->cardid,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => '',
            'personid' => $this->personid,
            'mobilephone' => $this->phone,
            'birthday' => $this->birthday,
            'sex' => $this->gender,
            'edulevel' => '',
            'email' => '',
            'compname' => '',
            'cmpaddr' => '',
            'mantitle' => '',
            'yc_jf' => $ycJf
        ];

        $request['dnums'] = 1;
        $request['data']['item'] = $item;

        return ['pm_xml' => Array2XML::createXML('request', $request)->saveXML()];
    }

    private function randCardid()
    {
        return $this->prefix . str_pad(rand(0, pow(10, $this->figure - strlen($this->prefix)) -1), $this->figure, '0', STR_PAD_LEFT);
    }
}