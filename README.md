#**Instalation Guide**

As you should know in magento there is two mager ways to install modules,
via composer or manualy. 

### NOTE !!! Module dependencies
>After browser policies updates we depend on 
  [Veritworks/Cookiefix](https://github.com/Veriteworks/CookieFix). 
> Get the version compatible for your magento and install it.  

##**Composer Cycle** 
1. Composer require some/module
2. bin/magento module:enable <Some_Module>
3. bin/magento setup:upgrade && bin/magento setup:di:compile && bin/magento c:f 

##**Manual Installation**

Put this module to app/code/TotalProcessing/Opp

For example from source home directory 
>   $ git clone [--branch tag_name] <CrrrentModuleRepository> app/code/TotalProcessing/Opp
   
Put the proper version of Veritworks/Cookiefix to app/code/Veritworks/Cookiefix, depend on magento version
>   $ git clone [--branch tag_name] https://github.com/Veriteworks/CookieFix.git src/app/code/Veriteworks/CookieFix

Check the readme of Veritworks/Cookiefix for configuration, it depends on magento version.

Execute the following steps ...
 
1. Check modules existence from magento cli
>$ bin/magento module:status

The result should be like this example: 

> .....
> 
> List of disabled modules:
> 
> TotalProcessing_Opp
> 
> Veriteworks_CookieFix

2. Enable modules
>$  bin/magento module:enable TotalProcessing_Opp Veriteworks_CookieFix

3. Upgrade magento 
>$  bin/magento setup:upgrade && bin/magento setup:di:compile
  
4. Cache flush 
>$  bin/magento c:f


5. Continue with configuration TotalProcessing Module as admin from 

Stores -> Configuration -> Sales -> Payment Methods -> Total Processing Limited 

Use sandbox environment for tests.

Check Total processing documentation to find different test cards.
