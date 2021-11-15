# Magento 2 Nuvei Payments Module

---

# 3.1.7
```
	* Changed the place of a log.
	* Fixed a spell error.
	* Pass status 400 from v3.1.6 in Magento jsonOutput.
	* When we get CARD_TOKENIZATION stop the process.
```

# 3.1.6
```
	* If DMN can not create an Order send status 400 to the DMN sender.
```

### 3.1.5
```
	* Added additional check for an object in PreventAddToCart class.
```

### 3.1.4.x
```
	* Added additional checks for Nuvei Subscription attributes, when try to get their values.
```

### 3.1.3.x
```
	* Fix for the "Undefined index: selected_configurable_option" error.
	* Hide JS logs on the checkout in Production mode.
```

### 3.1.2.x
```
	* On the checkout page hide by default ApplePay. If the conditions are correct show it.
	* Removed unused variables.
	* Fix in the logic when check for a Product with a Payment Plan.
```

### 3.1.1.x
```
	* Small changes in createLog method.
	* Fix for the Nuvei Payment details table in the store.
	* Fix for showing Apple Pay, when is not active.
	* Fix for the Admin Warning message about missing nuvei-plugin-latest-version.txt file.
```

### 3.1.0.x
```
	* Added a cron job to check every week for a new plugin version in the GIT repo. If it is available, a system message will appear in the admin.
	* When we have CC in the APMs - preselect it. If CC is missing and there is only one APM - preselect it.
	* Fix - on Checkout, if the user do not select some of the available APMs, stop the process and show error message.
	* Fixed a JS bug when Collect the Plans.
	* Added Apple Pay support.
```

### 3.0.2.x
```
	* When the merchant Void an Order with Subscriptions, all subscriptions are also canceled.
	* Added marker for Nuvei Payment Plan in Sales > Order grid.
	* Removed Sales > Nuvei > Api Request page.
	* Added an option to allow Guest users to purchase products with plans.
	* In product preview page was added a table with Nuvei Subscriprion Details, based on selected product configuration.
	* In Store, My Account menu, was added menu to show only the Orders with approved Nuvei Subscription.
	* Do not allow an Order including a product with Payment plan with other products.
	* On Product edit/create page, populate Nuvei Plan fields by selected plan.
	* When merchant try to get its payment plans, if there are no plans - create a default one.
```

### 3.0.1.x
```
	* Generate invoices for the Partial Settles from CPanel.
	* Fix for the Void of Partial Settle.
	* Fix for the correct Refund based on the Invoice ID.
	* The Settle logic was moved form Payment class to After Save Invoice Observer. In Register Invoice Observer we set the Invoice status to Pending. Whem the DMN come, we change this status to Paid or Canceld.
```

### 3.0.0.x
```
	* Added icon on the checkout page.
	* Added uninstall script to remove plugin main data base.
	* Fix for the Partial Settle wrong amount in the Orders message.
	* Changed the statuses.
	* When install add new Order states.
	* When Billing Address is changed, pass it as parameter in the GetMerchantPaymentMethods ajax request.
	* Allowed multiple Refund DMNs.
	* Fix for the Payment Methods placeholders.
	* Fixed Neteller field type and placeholder.
	* Added logs in some Observers.
	* Preparation for fixing the Refund of Partial Settle in Model\Refund and Settle classes.
	* Removed userTokenId parameter from openOrder and updateOrder requests.
	* Removed unused "use" models calls.
	* Small fix for createLog() method.
	* Added \i18n\phrases.csv file. Use it for translations.
	* In the DMN class added new object - "nuvei" as additional information for the Order.
	* Replaced "Safecharge" with "Nuvei" in the code.
	* Formatting code.
	* Added sourceApplication in AbstractRequest class.
	* Added product Id, price and quantity in OpenOrder and UpdateOrder in merchantDetails->customField5.
	* The Order amount and currency were removed from the webSDK request.
	* After declined transaction do not get new Session token and do not reload the sdk fields.
	* Call UpdateOrder before each OpenOrder try. Also update the Order when click on Pay button.
	* When we receive DMN for Auth or Sale compare the Order amount, if they do not match, add note and set Order status to "Suspected Fraud".
	* When we receive DMN for Refund/Credit search for order only once, without sleep.
	* When we receive DMN for Auth or Settle  with more than an hour delay, search for order only once, without sleep.
	* Use custom unique clientUniqueId for APMs in Sandbox.
	* When call OpenOrder save the result session token in the Quote. Use it later in PaymentAPM class.
	* Added an option into admin to save the Debug log file in single file, or split files by days.
	* Implemented Magento 2 Loader Widget on the checkout.
	* When store is not on Test mode, pass scData.env = 'prod' to the webSDK.
	* On checkout, if we get "Unexpected error..." message after webSDK request - reload the page.
	* Additional check to prevent passing null in webSDK paymentOption.
	* Pass cardNumber object to webSDK, instead of sfcFirstField.
	* Pass Magento version in each request in merchantDetails['customField3'] array.
	* Do not allow DMN code to create order if the incoming status is not Approved.
	* Force quote to save the payment method when passed.
```

### 2.1.2.x
```
	* Fix for the late load of quote in the checkout JS.
```

## 2.1.1.x
```
	* Fix for overriding of Order with status Void or Refund.
	* Removed Void button from Invoices.
	* Fix for the second partial Refund.
	* Added plugin version into the logs. Do not log credentials on Prod. Expand arrays on Sandbox for better view.
	* Changes in checkout JS, for better work of webSDK.
	* Clean the "\" symbol from client details.
	* Try to force set the Payment provider (method) when it is selected.
	* Replaced OrderFactory with OrderRepositoryInterface.
	* Do not simulate Auth transaction, when the Order is of type Sale.
	* Mark Invoice of Order of type Sale with canVoidFlag true.
	* For Orders of type Sale force show Void button.
	* In Payment model added metchod canVoid().
	* Added third option for mandatory dorp-downs in the plugin settings.
	* Added Site Notify URL in the plugin settings.
	* In the DMN class check if the Order Paymend was made with SafeCharge module.

## 2.1.0.x
```
	* Use 3-fields webSDK instead one-field version, because of mobile optimization;

## 2.0.5.x
```
	* In OpenOrder request pass order amount into custom fields, to get it again in response and pass it to the front-end.
	* A revert - DMN class again extends Action class.
	* In DMN class added check for the Order total and DMN total parameter, for Auth transactions. If they are not same, we stop process.
	* In DMN class, add userPaymentOptionId to the payment for future use.
	* In DMN class, when check for Partial Settle we compare Order Base Grand Total with DMN parameter totalAmount.
	* In DMN class, check for available request quote parameter in placeOrder method.
	* In safecharge.js, removed some unused classes.
	* In safecharge.js, observe for add/remove coupons, and compare the total with the total passed to the OpenOrder request. If there is difference, we call new OpenOrder with correct total amount.
	* Added validation for the emails first, they are restricted to 79 symbols;

## 2.0.4.x
```
	* Removed admin option Save Order before success.
	* The DMN class no longer extends Action class.
	* In the DMN class added check for Declined Order - Auth or Sale type. In this case we stop process, and do not try to create Order.
	* In the DMN class added check for delay DMN, for example if the order was Settled and the DMN is for Auth transaction.
	* In the DMN class, when user does not reach Success Page, the time we wait before try to create an Order is changed to about 15 seconds.
	* In the DMN class, when the class try to create the Order we check if the Quote is active and if the used Payment Method is safecharge.
	* In the DMN class, for output messages, use JsonFactory, also set Response Code to 200.
	* On Succes Page we check if the Quote is active, we place Order only if it is. 
