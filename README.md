# Installation Instructions

At the moment module could be installed only in `app/code`. Module will be
available for composer-based installations soon.

## Important Module Dependencies related to third-party cookies handling

In Feb 2021 Google Chrome and the other mainstream browsers have adopted a new
a third-party cookies restriction policy and `SameSite` value is `Lax` by default.
That prevents all iframe-based implementations to get or manage the session cookie,
that way payments are not getting processed correctly. 
This module also depends on it and needs an additional extension installed
until Adobe release v2.4.4 in which is supposed to have a way to
control cookie policy OOTB.

[MDN SameSite cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite) is an explanation 
of the work flow of the SameSite cookie property, which is defining requirements to the environment. TotalProcessing/Opp
uses cookies with attributes `SameSite=none; secure=true;`. This feature is available only in secure contexts (HTTPS) 
and cookies will be rejected because it has the SameSite attribute set to None or an invalid value,
without the secure attribute. Secure property value is set based on the type of the connection 
(HTTPS/HTTP similarly the value is true/false). As result of the policies TotalProcessing/Opp module should work only on
HTTPS connections. 

[Veriteworks/CookieFix](https://github.com/Veriteworks/CookieFix) is an experimental
extension providing the necessary default cookie policy adjustments.
At the time of the release of this document there are two tags available for the
different Magento versions - `2.4.2` is supported by all Magento `2.2.x` and `2.3.x` versions
until `2.3.6-p1`. Magento versions `2.3.6-p1` and above, including `2.4.x` need 
`Veriteworks_CookieFix` tag `3.0.1` which is adding an option in admin to specify
default `SameSite` policy. You need to install the version compatible with your Magento
instance together with this extension.  

## Composer Installation

Coming soon! At the time of writing of this document there is still no public repository to obtain the module from.

## Manual Installation

1. Obtain the module code archive and extract it somewhere (or check it out from a remote
repository when such is already available).


2. In Magento root create a new sub-folder `app/code/TotalProcessing/Opp`


3. Copy extracted archive contents into the newly-created folder. Make sure `composer.json`,
   `registration.php` and the folders like `Block`, `Controller`, `Model`, etc. are
   placed in `app/code/TotalProcessing/Opp` and not in a sub-level folder.


4. Check out the suitable version of `Veriteworks/CookieFix` (see details above how to find which one is for your platform version) into `app/code/Veriteworks/CookieFix`:

    **For Magento versions below 2.3.6-p1**
    ```bash
    $ git clone --branch 2.4.2 https://github.com/Veriteworks/CookieFix.git app/code/Veriteworks/CookieFix
    ```
    
    **For Magento versions 2.3.6-p1 and above**
    ```bash
    $ git clone --branch 3.0.1 https://github.com/Veriteworks/CookieFix.git app/code/Veriteworks/CookieFix
    ```

    [Veriteworks/CookieFix](https://github.com/Veriteworks/CookieFix) README contains further information regarding configuration.


5. Check modules' status with magento CLI
   ```bash
   $ bin/magento module:status
   ```
   
   The output should be similar to the following: 

   ```
   .....
   List of disabled modules:
   
   TotalProcessing_Opp
   Veriteworks_CookieFix
   ```

6. Enable both `TotalProcessing_Opp` and `Veriteworks_CookieFix` modules by executing the following commands:
   ```bash
   $ bin/magento module:enable TotalProcessing_Opp
   $ bin/magento module:enable Veriteworks_CookieFix
   ```

7. Execute Magento deployment commands:
   ```
   $ bin/magento setup:upgrade
   $ bin/magento setup:di:compile
   $ bin/magento setup:static-content:deploy
   $ bin/magento cache:flush
   ```

## Post-installation steps
After installing both modules continue with payment method configuration in admin panel.
Configuration options are available at **Stores > Configuration > Sales > Payment Methods > Total Processing Limited**.
Veriteworks CookieFix module `SameSite` policy is applied by default with tag `2.4.2`.
A new admin option appears with tag `3.0.1` as **Stores > Configuration > General > Web > Default Cookie Settings**.

You can find various test credit card numbers in [TotalProcessing documentation portal](https://docs.oppwa.com/reference/parameters#test-accounts).
