# CloudFlare Website IP Checker

因为发现Cloudflare开始屏蔽一些网站在中国大陆的访问  
见 ![https://github.com/posir/CloudFlareWebsiteChecker/issues/1](https://github.com/posir/CloudFlareWebsiteChecker/issues/1)   详细说明   
所以编写了这个工具，用于批量检查网站域名是否被分配到.1结尾的IP地址  
这个是单文件运行 不依赖 CloudFlare SDK

## 介绍
这个是脚本的用途， 是批量检查Cloudflare账户下 所有开启了CDN加速服务的网站 被分配的IP池, 是否屏蔽了中国大陆区域的访问  
*该检测结果 关系到你的网站是否可在中国大陆访问*

## 使用

```shell

# 下载
wget -c -4 -k https://raw.githubusercontent.com/posir/CloudFlareWebsiteChecker/refs/heads/main/CF_BanCheck.php

# 使用
php CF_BanCheck.php <EMAIL> <KEY>

# 示例
php CF_BanCheck.php cf@cloudflare.com dacweawcejiwoiodawdoiweiaodioawiodwe

```

## 预览
![check](https://github.com/user-attachments/assets/b415a0ae-9c03-4ec2-8fd0-fcac37d51de5)



## 问题

API KEY 获取方式  
打开这个页面 https://dash.cloudflare.com/profile/api-tokens  
找到  *全局API Key / Global API Key* 点击 查看  
会要求输入密码  然后会显示出来 这个就是了,要保存好 不要暴露给别人使用

