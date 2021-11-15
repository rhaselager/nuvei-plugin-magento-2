# Magento 2 Nuvei Payments Module

---

## Install manually under app/code
Download & place the contents of this repository under {YOUR-MAGENTO2-ROOT-DIR}/app/code/Nuvei/Payments  
Then, run the following commands under your Magento 2 root dir:
```
php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:flush
```

---

https://www.nuvei.com/

© 2007 - 2021 Nuvei
All rights reserved.

![Nuvei Logo](https://nuvei.com/wp-content/uploads/2020/07/Nuvei-logo-footer.png)
