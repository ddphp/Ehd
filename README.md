# Ehd

## 亿惠达会员（Ehd\Member）

### 初始化

```php
$member = new \Ehd\Member();

// 查询字段：custid custname custsjh custsfz ynallnew
$condition = '288****55';  // 作为卡号
// or $condition = ['custid' => '288****55', ...]; 
$member->wsdl($wsdl)->where($condition)->find();
```

**where 方法可用查询字段**

1. custid 卡号
2. custname 姓名
3. custsjh 手机号
4. custsfz 身份证号
5. ynallnew 是否新增 Y/N

### 获取会员资料
```php
$member->getArchives();   // return array
$member->getArchives('custid');  // return string
$member->getArchives(['custid', 'name']);  // return array
```

### 调整会员积分

```php
$member->adjustScore(-1, '新版积分调整测试');  // return bool
```

### 变更会员资料

```php
$member->changeArchives($newArchives);  // return bool
```

**可修改会员资料项**

1. name 用户姓名
2. phone 电话号码
3. address 用户地址
4. personid 身份证号
5. mobilephone 手机（唯一性）
6. birthday 生日 yyyy-mm-dd 字符格式
7. sex 性别
8. edulevel 学历
9. email 电子邮箱
10. compname 单位名称
11. cmpaddr 单位地址
12. mantitle 职务
